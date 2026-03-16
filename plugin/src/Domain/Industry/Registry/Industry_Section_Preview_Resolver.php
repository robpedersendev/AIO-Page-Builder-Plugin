<?php
/**
 * Resolves industry-aware preview context for a section detail screen (Prompt 384, industry-admin-screen-contract).
 * Read-only: recommendation fit, composed helper, warnings, substitute suggestions. Safe fallback when no industry.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\Sections\Industry_Section_Preview_View_Model;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Helper_Doc_Composer;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;

/**
 * Builds industry preview view model for a single section. Uses profile, section recommendation resolver, helper composer, optional substitute engine.
 */
final class Industry_Section_Preview_Resolver {

	/** @var Industry_Profile_Repository|null */
	private ?Industry_Profile_Repository $profile_repository;

	/** @var Industry_Pack_Registry|null */
	private $pack_registry;

	/** @var Industry_Section_Recommendation_Resolver */
	private Industry_Section_Recommendation_Resolver $recommendation_resolver;

	/** @var Industry_Helper_Doc_Composer */
	private Industry_Helper_Doc_Composer $helper_composer;

	/** @var Industry_Substitute_Suggestion_Engine|null */
	private $substitute_engine;

	public function __construct(
		?Industry_Profile_Repository $profile_repository,
		?Industry_Pack_Registry $pack_registry,
		Industry_Section_Recommendation_Resolver $recommendation_resolver,
		Industry_Helper_Doc_Composer $helper_composer,
		?Industry_Substitute_Suggestion_Engine $substitute_engine = null
	) {
		$this->profile_repository     = $profile_repository;
		$this->pack_registry          = $pack_registry;
		$this->recommendation_resolver = $recommendation_resolver;
		$this->helper_composer        = $helper_composer;
		$this->substitute_engine      = $substitute_engine;
	}

	/**
	 * Resolves industry-aware preview view model for the given section. Safe when no profile or invalid key.
	 *
	 * @param string               $section_key        Section template internal_key.
	 * @param array<string, mixed>  $section_definition  Single section definition (internal_key, section_purpose_family, etc.).
	 * @param array<int, array<string, mixed>> $all_sections Optional. When provided with substitute_engine, substitute suggestions are filled.
	 * @return Industry_Section_Preview_View_Model
	 */
	public function resolve( string $section_key, array $section_definition, array $all_sections = array() ): Industry_Section_Preview_View_Model {
		$section_key = \sanitize_key( $section_key );
		if ( $this->profile_repository === null ) {
			return $this->empty_view_model();
		}
		$profile = $this->profile_repository->get_profile();
		$primary = isset( $profile['primary_industry_key'] ) && \is_string( $profile['primary_industry_key'] )
			? \trim( $profile['primary_industry_key'] )
			: '';

		if ( $primary === '' ) {
			return $this->empty_view_model();
		}

		$primary_pack = null;
		if ( $this->pack_registry !== null ) {
			$primary_pack = $this->pack_registry->get( $primary );
		}

		$sections_for_resolver = ! empty( $all_sections ) ? $all_sections : array( $section_definition );
		$result                = $this->recommendation_resolver->resolve( $profile, $primary_pack, $sections_for_resolver, array() );
		$item                  = $this->get_item_by_key( $result, $section_key );

		$fit               = $item['fit_classification'] ?? Industry_Section_Recommendation_Resolver::FIT_NEUTRAL;
		$warning_flags     = isset( $item['warning_flags'] ) && \is_array( $item['warning_flags'] ) ? array_values( array_filter( array_map( 'strval', $item['warning_flags'] ) ) ) : array();
		$explanation_reasons = isset( $item['explanation_reasons'] ) && \is_array( $item['explanation_reasons'] ) ? array_values( array_filter( array_map( 'strval', $item['explanation_reasons'] ) ) ) : array();

		$composed_result  = $this->helper_composer->compose( $section_key, $primary );
		$composed_doc     = $composed_result->get_composed_doc();
		$composed_for_view = array(
			'tone_notes'         => $composed_doc['tone_notes'] ?? '',
			'cta_usage_notes'    => $composed_doc['cta_usage_notes'] ?? '',
			'compliance_cautions' => $composed_doc['compliance_cautions'] ?? '',
			'media_notes'        => $composed_doc['media_notes'] ?? '',
			'seo_notes'          => $composed_doc['seo_notes'] ?? '',
			'overlay_applied'    => $composed_result->is_overlay_applied(),
		);

		$substitute_suggestions = array();
		if ( $this->substitute_engine !== null && ! empty( $all_sections ) ) {
			$substitute_suggestions = $this->substitute_engine->suggest_section_substitutes(
				$section_key,
				$fit,
				$result,
				$all_sections,
				5
			);
		}

		$compliance_warnings = $composed_result->get_compliance_warnings();

		return new Industry_Section_Preview_View_Model(
			true,
			$primary,
			$fit,
			$composed_for_view,
			$substitute_suggestions,
			$warning_flags,
			$explanation_reasons,
			$compliance_warnings
		);
	}

	/**
	 * @param Industry_Section_Recommendation_Result $result
	 * @param string $section_key
	 * @return array{section_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, warning_flags: array}
	 */
	private function get_item_by_key( Industry_Section_Recommendation_Result $result, string $section_key ): array {
		foreach ( $result->get_items() as $item ) {
			$key = $item['section_key'] ?? '';
			if ( $key === $section_key ) {
				return $item;
			}
		}
		return array(
			'section_key'         => $section_key,
			'score'               => 0,
			'fit_classification'  => Industry_Section_Recommendation_Resolver::FIT_NEUTRAL,
			'explanation_reasons' => array(),
			'industry_source_refs' => array(),
			'warning_flags'       => array(),
		);
	}

	private function empty_view_model(): Industry_Section_Preview_View_Model {
		return new Industry_Section_Preview_View_Model(
			false,
			'',
			Industry_Section_Recommendation_Resolver::FIT_NEUTRAL,
			array(),
			array(),
			array(),
			array(),
			array()
		);
	}
}
