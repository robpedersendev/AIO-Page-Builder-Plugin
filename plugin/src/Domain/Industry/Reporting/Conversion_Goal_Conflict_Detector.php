<?php
/**
 * Read-only detector that flags when conversion goals are weakly matched, contradictory, or risky (Prompt 503).
 * Surfaces warnings and suggested review directions; no auto-mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Detects goal vs bundle, override, and caution layer conflicts per conversion-goal-conflict-precedence-contract.
 */
final class Conversion_Goal_Conflict_Detector {

	/** Conflict type: weak fit between goal and current bundle. */
	public const CONFLICT_WEAK_BUNDLE_FIT = 'weak_goal_bundle_fit';

	/** Conflict type: goal contradicts override state. */
	public const CONFLICT_GOAL_OVERRIDE = 'goal_override_contradiction';

	/** Conflict type: goal posture vs caution layer. */
	public const CONFLICT_GOAL_CAUTION = 'goal_caution_tension';

	/** Severity: info. */
	public const SEVERITY_INFO = 'info';

	/** Severity: caution. */
	public const SEVERITY_CAUTION = 'caution';

	/** Severity: warning. */
	public const SEVERITY_WARNING = 'warning';

	/**
	 * Runs conflict detection for the given profile and optional override/bundle state. Read-only; no mutation.
	 *
	 * @param array<string, mixed> $profile    Normalized industry profile (primary_industry_key, industry_subtype_key, selected_starter_bundle_key, conversion_goal_key).
	 * @param array<string, mixed> $options    Optional: overrides_snapshot (array), caution_flags (list), bundle_goal_aware (bool for current bundle).
	 * @return list<array{conflict_type: string, severity: string, related_refs: array, explanation: string, suggested_review_action: string}>
	 */
	public function detect( array $profile, array $options = array() ): array {
		$goal = isset( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY ] )
			: '';
		if ( $goal === '' ) {
			return array();
		}

		$conflicts = array();
		$bundle_key = isset( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY ] )
			: '';

		$bundle_goal_aware = isset( $options['bundle_goal_aware'] ) && $options['bundle_goal_aware'] === true;
		if ( $bundle_key !== '' && ! $bundle_goal_aware ) {
			$conflicts[] = array(
				'conflict_type'            => self::CONFLICT_WEAK_BUNDLE_FIT,
				'severity'                => self::SEVERITY_INFO,
				'related_refs'            => array( 'bundle_key' => $bundle_key, 'goal_key' => $goal ),
				'explanation'             => __( 'Selected bundle may not have a goal overlay for this conversion goal; plan will use base bundle conversion.', 'aio-page-builder' ),
				'suggested_review_action' => __( 'Review whether bundle choice aligns with conversion goal, or add a goal overlay for this bundle.', 'aio-page-builder' ),
			);
		}

		$overrides_snapshot = isset( $options['overrides_snapshot'] ) && is_array( $options['overrides_snapshot'] ) ? $options['overrides_snapshot'] : array();
		if ( $overrides_snapshot !== array() ) {
			$conflicts[] = array(
				'conflict_type'            => self::CONFLICT_GOAL_OVERRIDE,
				'severity'                => self::SEVERITY_CAUTION,
				'related_refs'            => array( 'goal_key' => $goal ),
				'explanation'             => __( 'Overrides are present; goal-based recommendations do not replace overrides.', 'aio-page-builder' ),
				'suggested_review_action' => __( 'Confirm overrides still match intent; goal refines within override constraints.', 'aio-page-builder' ),
			);
		}

		$caution_flags = isset( $options['caution_flags'] ) && is_array( $options['caution_flags'] ) ? $options['caution_flags'] : array();
		if ( $caution_flags !== array() ) {
			$conflicts[] = array(
				'conflict_type'            => self::CONFLICT_GOAL_CAUTION,
				'severity'                => self::SEVERITY_INFO,
				'related_refs'            => array( 'goal_key' => $goal ),
				'explanation'             => __( 'Caution rules apply; goal does not suppress compliance or risk advisories.', 'aio-page-builder' ),
				'suggested_review_action' => __( 'Review both goal posture and caution messages before approval.', 'aio-page-builder' ),
			);
		}

		return $conflicts;
	}
}
