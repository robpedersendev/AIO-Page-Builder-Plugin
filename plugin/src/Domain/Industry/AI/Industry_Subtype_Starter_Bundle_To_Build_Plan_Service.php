<?php
/**
 * Subtype-aware starter bundle to Build Plan conversion (Prompt 462).
 * Delegates to Industry_Starter_Bundle_To_Build_Plan_Service with subtype context and optional parent fallback.
 * Plans remain reviewable and approval-gated; no auto-execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Converts a subtype-scoped or industry starter bundle into a draft Build Plan.
 * Exposes subtype source in plan rationale; falls back to parent industry bundle when subtype bundle is invalid or missing.
 */
final class Industry_Subtype_Starter_Bundle_To_Build_Plan_Service {

	/** @var Industry_Starter_Bundle_Registry */
	private $bundle_registry;

	/** @var Industry_Starter_Bundle_To_Build_Plan_Service */
	private $bundle_to_plan_service;

	public function __construct(
		Industry_Starter_Bundle_Registry $bundle_registry,
		Industry_Starter_Bundle_To_Build_Plan_Service $bundle_to_plan_service
	) {
		$this->bundle_registry       = $bundle_registry;
		$this->bundle_to_plan_service = $bundle_to_plan_service;
	}

	/**
	 * Converts the given starter bundle (subtype or parent) into a draft Build Plan. Uses subtype context when bundle is subtype-scoped; falls back to parent industry bundle when bundle key is invalid or inactive.
	 *
	 * @param string               $bundle_key Bundle key (e.g. realtor_buyer_agent_starter or realtor_starter).
	 * @param array<string, mixed>  $context    Optional: industry_key, industry_subtype_key, profile_context_ref for fallback and rationale.
	 * @return Plan_Generation_Result Success with plan_id and payload, or failure with errors.
	 */
	public function convert_to_draft( string $bundle_key, array $context = array() ): Plan_Generation_Result {
		$bundle_key = trim( $bundle_key );
		if ( $bundle_key === '' ) {
			return Plan_Generation_Result::failure( array( __( 'Bundle key is required.', 'aio-page-builder' ) ) );
		}

		$bundle = $this->bundle_registry->get( $bundle_key );
		$industry_key = isset( $context['industry_key'] ) && is_string( $context['industry_key'] ) ? trim( $context['industry_key'] ) : '';
		$subtype_key_from_bundle = '';

		if ( $bundle !== null ) {
			$status = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
				? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
				: '';
			if ( $status === Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
				$subtype_key_from_bundle = isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] )
					? trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] )
					: '';
				if ( $industry_key === '' && isset( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) && is_string( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] ) ) {
					$industry_key = trim( $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] );
				}
				$gen_context = array_merge( $context, array( 'source_starter_bundle_key' => $bundle_key ) );
				if ( $subtype_key_from_bundle !== '' ) {
					$gen_context['industry_subtype_key'] = $subtype_key_from_bundle;
				}
				return $this->bundle_to_plan_service->convert_to_draft( $bundle_key, $gen_context );
			}
		}

		// * Parent-only fallback: try first active bundle for industry when requested bundle not found or inactive.
		if ( $industry_key !== '' ) {
			$parent_bundles = $this->bundle_registry->get_for_industry( $industry_key, '' );
			foreach ( $parent_bundles as $parent ) {
				$status = isset( $parent[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] ) && is_string( $parent[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
					? trim( $parent[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] )
					: '';
				if ( $status !== Industry_Starter_Bundle_Registry::STATUS_ACTIVE ) {
					continue;
				}
				$parent_key = isset( $parent[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] ) && is_string( $parent[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					? trim( $parent[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] )
					: '';
				if ( $parent_key !== '' ) {
					$fallback_context = array_merge( $context, array(
						'source_starter_bundle_key' => $parent_key,
						'industry_key'              => $industry_key,
					) );
					return $this->bundle_to_plan_service->convert_to_draft( $parent_key, $fallback_context );
				}
			}
		}

		return Plan_Generation_Result::failure( array( __( 'Starter bundle not found or not active.', 'aio-page-builder' ) ) );
	}
}
