<?php
/**
 * Extends bundle-to-Build Plan conversion so conversion-goal overlays can shape draft plans (Prompt 498).
 * Delegates to Industry_Starter_Bundle_To_Build_Plan_Service; applies optional goal overlay when available.
 * Preserves review, explanation, and approval safeguards; no auto-execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

/**
 * Goal-aware starter bundle to draft Build Plan conversion. Fallback to non-goal conversion when goal missing or invalid.
 */
final class Conversion_Goal_Starter_Bundle_To_Build_Plan_Service {

	/** Context key: conversion goal key (optional). When set and valid, goal overlay may refine the draft. */
	public const CONTEXT_CONVERSION_GOAL_KEY = 'conversion_goal_key';

	/** Context key: industry profile array (optional). When set, conversion_goal_key may be read from profile. */
	public const CONTEXT_INDUSTRY_PROFILE = 'industry_profile';

	/** @var Industry_Starter_Bundle_To_Build_Plan_Service */
	private Industry_Starter_Bundle_To_Build_Plan_Service $bundle_to_plan;

	/** @var Industry_Starter_Bundle_Registry */
	private Industry_Starter_Bundle_Registry $bundle_registry;

	public function __construct(
		Industry_Starter_Bundle_To_Build_Plan_Service $bundle_to_plan,
		Industry_Starter_Bundle_Registry $bundle_registry
	) {
		$this->bundle_to_plan   = $bundle_to_plan;
		$this->bundle_registry = $bundle_registry;
	}

	/**
	 * Converts the given starter bundle into a draft Build Plan. When conversion_goal_key is present and valid,
	 * goal overlay may refine page families, CTA posture, section emphasis, funnel shape. Fallback: non-goal conversion.
	 *
	 * @param string               $bundle_key Bundle key (e.g. realtor_starter).
	 * @param array<string, mixed>  $context    Optional: conversion_goal_key, industry_profile, profile_context_ref, industry_key.
	 * @return Plan_Generation_Result Success with plan_id and payload (and goal_overlay_source in rationale when applied), or failure.
	 */
	public function convert_to_draft( string $bundle_key, array $context = array() ): Plan_Generation_Result {
		$bundle_key = trim( $bundle_key );
		if ( $bundle_key === '' ) {
			return Plan_Generation_Result::failure( array( __( 'Bundle key is required.', 'aio-page-builder' ) ) );
		}

		$goal_key = $this->resolve_conversion_goal_key( $context );
		$gen_context = array_merge( $context, array( 'source_starter_bundle_key' => $bundle_key ) );
		if ( $goal_key !== '' ) {
			$gen_context['conversion_goal_key'] = $goal_key;
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

		// * Delegate to base conversion. When goal overlay registry exists, inject refinement here; for now goal is passed in context for rationale only.
		return $this->bundle_to_plan->convert_to_draft( $bundle_key, $gen_context );
	}

	/**
	 * Resolves conversion_goal_key from context or industry profile. Returns empty when invalid or missing.
	 */
	private function resolve_conversion_goal_key( array $context ): string {
		$goal = isset( $context[ self::CONTEXT_CONVERSION_GOAL_KEY ] ) && is_string( $context[ self::CONTEXT_CONVERSION_GOAL_KEY ] )
			? trim( $context[ self::CONTEXT_CONVERSION_GOAL_KEY ] )
			: '';
		if ( $goal === '' && isset( $context[ self::CONTEXT_INDUSTRY_PROFILE ] ) && is_array( $context[ self::CONTEXT_INDUSTRY_PROFILE ] ) ) {
			$profile = $context[ self::CONTEXT_INDUSTRY_PROFILE ];
			$goal   = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				? trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
				: '';
		}
		if ( $goal !== '' && ( strlen( $goal ) > 64 || ! preg_match( '#^[a-z0-9_-]+$#', $goal ) ) ) {
			return '';
		}
		return $goal;
	}
}
