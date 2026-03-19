<?php
/**
 * Build Plan review view model for subtype-specific context (Prompt 463).
 * Exposes subtype bundle origin, subtype key, and rationale for plan-level and item-level display.
 * Safe fallback when subtype metadata is absent.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;

/**
 * View model for subtype influence, subtype bundle origin, and subtype caution notes in Build Plan review.
 */
final class Subtype_Build_Plan_Explanation_View_Model {

	/** View-model key: whether plan has subtype context. */
	public const KEY_HAS_SUBTYPE_CONTEXT = 'has_subtype_context';

	/** View-model key: source starter bundle key. */
	public const KEY_SOURCE_STARTER_BUNDLE_KEY = 'source_starter_bundle_key';

	/** View-model key: source industry subtype key. */
	public const KEY_SOURCE_INDUSTRY_SUBTYPE_KEY = 'source_industry_subtype_key';

	/** View-model key: single-line rationale for display. */
	public const KEY_SUBTYPE_BUNDLE_RATIONALE_LINE = 'subtype_bundle_rationale_line';

	/** View-model key: optional subtype caution notes (bounded list). */
	public const KEY_SUBTYPE_CAUTION_NOTES = 'subtype_caution_notes';

	/**
	 * Builds view model from plan definition. Safe when keys are missing.
	 *
	 * @param array<string, mixed> $plan_definition Plan root (may contain source_starter_bundle_key, source_industry_subtype_key, warnings).
	 * @return array{
	 *   has_subtype_context: bool,
	 *   source_starter_bundle_key: string|null,
	 *   source_industry_subtype_key: string|null,
	 *   subtype_bundle_rationale_line: string,
	 *   subtype_caution_notes: array<int, string>
	 * }
	 */
	public static function from_plan_definition( array $plan_definition ): array {
		$bundle_key  = isset( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] ) && is_string( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] )
			? trim( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_STARTER_BUNDLE ] )
			: '';
		$subtype_key = isset( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_INDUSTRY_SUBTYPE ] ) && is_string( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_INDUSTRY_SUBTYPE ] )
			? trim( $plan_definition[ Build_Plan_Schema::KEY_SOURCE_INDUSTRY_SUBTYPE ] )
			: '';

		$has_subtype_context = $subtype_key !== '' || $bundle_key !== '';

		$rationale_line = '';
		if ( $bundle_key !== '' && $subtype_key !== '' ) {
			$rationale_line = sprintf(
				/* translators: 1: bundle key, 2: subtype key */
				__( 'This plan was generated from subtype bundle %1$s (subtype: %2$s).', 'aio-page-builder' ),
				$bundle_key,
				$subtype_key
			);
		} elseif ( $bundle_key !== '' ) {
			$rationale_line = sprintf(
				/* translators: %s: bundle key */
				__( 'This plan was generated from starter bundle %s.', 'aio-page-builder' ),
				$bundle_key
			);
		}

		$subtype_caution_notes = array();
		$warnings              = isset( $plan_definition[ Build_Plan_Schema::KEY_WARNINGS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_WARNINGS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_WARNINGS ]
			: array();
		foreach ( array_slice( $warnings, 0, 5 ) as $w ) {
			$msg = is_array( $w ) ? (string) ( $w['message'] ?? $w['summary'] ?? '' ) : (string) $w;
			if ( $msg !== '' && strlen( $msg ) <= 512 ) {
				$subtype_caution_notes[] = $msg;
			}
		}

		return array(
			self::KEY_HAS_SUBTYPE_CONTEXT           => $has_subtype_context,
			self::KEY_SOURCE_STARTER_BUNDLE_KEY     => $bundle_key !== '' ? $bundle_key : null,
			self::KEY_SOURCE_INDUSTRY_SUBTYPE_KEY   => $subtype_key !== '' ? $subtype_key : null,
			self::KEY_SUBTYPE_BUNDLE_RATIONALE_LINE => $rationale_line,
			self::KEY_SUBTYPE_CAUTION_NOTES         => $subtype_caution_notes,
		);
	}

	/**
	 * Merges subtype view model into an industry explanation view model for item-level display.
	 *
	 * @param array<string, mixed> $industry_view_model From Industry_Build_Plan_Explanation_View_Model::from_item_payload().
	 * @param array<string, mixed> $subtype_context    From self::from_plan_definition().
	 * @return array<string, mixed> Merged view model with subtype_* keys when has_subtype_context.
	 */
	public static function merge_into_industry_view_model( array $industry_view_model, array $subtype_context ): array {
		if ( empty( $subtype_context[ self::KEY_HAS_SUBTYPE_CONTEXT ] ) ) {
			return $industry_view_model;
		}
		$industry_view_model['subtype_context']     = $subtype_context;
		$industry_view_model['has_subtype_context'] = true;
		return $industry_view_model;
	}
}
