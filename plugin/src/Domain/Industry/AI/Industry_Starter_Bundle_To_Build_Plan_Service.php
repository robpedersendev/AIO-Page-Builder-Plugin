<?php
/**
 * Converts a selected starter bundle into a draft Build Plan proposal (Prompt 409).
 * Preserves approval gating; does not auto-execute. Safe fallback when bundle is incomplete or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Reads a starter bundle and produces a draft Build Plan via Build_Plan_Generator.
 * Plan remains pending review; bundle is advisory input only.
 */
final class Industry_Starter_Bundle_To_Build_Plan_Service {

	private const AI_RUN_REF_PREFIX = 'starter-bundle';

	/** @var Industry_Starter_Bundle_Registry */
	private Industry_Starter_Bundle_Registry $bundle_registry;

	/** @var Build_Plan_Generator */
	private Build_Plan_Generator $plan_generator;

	public function __construct( Industry_Starter_Bundle_Registry $bundle_registry, Build_Plan_Generator $plan_generator ) {
		$this->bundle_registry = $bundle_registry;
		$this->plan_generator  = $plan_generator;
	}

	/**
	 * Converts the given starter bundle into a draft Build Plan. Plan is persisted and subject to normal review.
	 *
	 * @param string               $bundle_key Bundle key (e.g. realtor_starter).
	 * @param array<string, mixed> $context    Optional: profile_context_ref, industry_key for scoring.
	 * @return Plan_Generation_Result Success with plan_id and payload, or failure with errors.
	 */
	public function convert_to_draft( string $bundle_key, array $context = array() ): Plan_Generation_Result {
		$bundle_key = trim( $bundle_key );
		if ( $bundle_key === '' ) {
			return Plan_Generation_Result::failure( array( __( 'Bundle key is required.', 'aio-page-builder' ) ) );
		}

		$bundle = $this->bundle_registry->get( $bundle_key );
		if ( $bundle === null ) {
			return Plan_Generation_Result::failure( array( __( 'Starter bundle not found.', 'aio-page-builder' ) ) );
		}

		$status = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
			? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
			: '';
		if ( $status !== Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
			return Plan_Generation_Result::failure( array( __( 'Starter bundle is not active.', 'aio-page-builder' ) ) );
		}

		$normalized = $this->build_normalized_output_from_bundle( $bundle );

		$gen_context = array_merge( $context, array( 'source_starter_bundle_key' => $bundle_key ) );
		$ai_run_ref  = self::AI_RUN_REF_PREFIX;
		$output_ref  = self::AI_RUN_REF_PREFIX . ':' . $bundle_key;

		return $this->plan_generator->generate( $normalized, $ai_run_ref, $output_ref, $gen_context );
	}

	/**
	 * Builds a minimal normalized output (Build_Plan_Draft_Schema shape) from a bundle.
	 *
	 * @param array<string, mixed> $bundle Valid active bundle from registry.
	 * @return array<string, mixed> Normalized output (may have empty new_pages_to_create).
	 */
	private function build_normalized_output_from_bundle( array $bundle ): array {
		$label   = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] )
			? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] )
			: '';
		$summary = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] )
			? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] )
			: '';

		$template_refs = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) && is_array( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] )
			? $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ]
			: array();
		$section_refs  = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] ) && is_array( $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] )
			? $bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ]
			: array();

		$new_pages = array();
		foreach ( array_values( $template_refs ) as $i => $template_key ) {
			if ( ! is_string( $template_key ) || trim( $template_key ) === '' ) {
				continue;
			}
			$template_key = trim( $template_key );
			$title        = $this->humanize_template_key_to_title( $template_key, $i + 1 );
			$slug         = sanitize_title( $title );
			if ( $slug === '' ) {
				$slug = 'page-' . ( $i + 1 );
			}
			// * Plain comma-separated section refs (not JSON); avoids embedding raw "[{" fragments in plan payloads.
			$section_guidance = implode( ', ', array_slice( array_map( 'strval', $section_refs ), 0, 3 ) );
			$new_pages[]      = array(
				'proposed_page_title' => $title,
				'proposed_slug'       => $slug,
				'purpose'             => __( 'From starter bundle', 'aio-page-builder' ),
				'template_key'        => $template_key,
				'menu_eligible'       => true,
				'section_guidance'    => $section_guidance,
				'confidence'          => 'medium',
			);
		}

		$plan_summary_text = $label !== '' ? sprintf( /* translators: %s: bundle label */ __( 'Draft from starter bundle: %s', 'aio-page-builder' ), $label ) : __( 'Draft from starter bundle', 'aio-page-builder' );
		$warnings          = array();
		if ( $label !== '' ) {
			$warnings[] = sprintf( /* translators: %s: bundle label */ __( 'Generated from starter bundle: %s', 'aio-page-builder' ), $label );
		}

		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => Build_Plan_Draft_Schema::SCHEMA_REF,
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY      => array(
				Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT       => $plan_summary_text,
				Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE     => 'mixed',
				Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE => 'medium',
			),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array(
				'summary' => $summary !== '' ? $summary : $plan_summary_text,
			),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array(
				'navigation_summary' => '',
			),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE => $new_pages,
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => $warnings,
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array(
				'overall'       => 'medium',
				'planning_mode' => 'mixed',
			),
		);
	}

	private function humanize_template_key_to_title( string $template_key, int $index ): string {
		$clean = preg_replace( '#^pt_#', '', $template_key );
		$clean = str_replace( array( '_01', '_02', '-' ), array( '', '', ' ' ), $clean ?? '' );
		$words = explode( '_', $clean ?? '' );
		$title = implode( ' ', array_map( 'ucfirst', array_map( 'strtolower', $words ) ) );
		if ( strlen( $title ) > 0 && strlen( $title ) <= 100 ) {
			return $title;
		}
		return sprintf( /* translators: %d: page number */ __( 'Page %d', 'aio-page-builder' ), $index );
	}
}
