<?php
/**
 * Builds conversion-goal prompt-pack overlay from input artifact (Prompt 533).
 * Returns goal-aware planning guidance for prompt-pack assembly; primary-goal precedence, optional secondary.
 * Safe when goal context missing or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\AI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\InputArtifacts\Input_Artifact_Schema;

/**
 * Produces conversion-goal overlay fragment for prompt-pack assembly. No throw; returns minimal overlay when no goal.
 */
final class Conversion_Goal_Prompt_Pack_Overlay_Service {

	public const OVERLAY_SCHEMA_VERSION = '1';

	/** Overlay key: primary conversion goal (for explanation/audit). */
	public const OVERLAY_PRIMARY_GOAL_KEY = 'primary_goal_key';
	/** Overlay key: optional secondary goal (for explanation/audit). */
	public const OVERLAY_SECONDARY_GOAL_KEY = 'secondary_goal_key';
	/** Overlay key: guidance text appended to system prompt. */
	public const OVERLAY_CONVERSION_GOAL_GUIDANCE_TEXT = 'conversion_goal_guidance_text';

	/** Launch goal set: stable keys for planning-hint map. */
	private const GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** Short planning hint per goal (no registry dependency). */
	private const GOAL_PLANNING_HINTS = array(
		'calls'         => 'Prioritize phone-call conversions: CTAs and proof points should encourage calling; include visible phone/click-to-call.',
		'bookings'      => 'Prioritize booking conversions: CTAs and proof points should support appointment or booking flow; reduce friction to book.',
		'estimates'     => 'Prioritize estimate-request conversions: CTAs and proof points should encourage requesting an estimate or quote.',
		'consultations' => 'Prioritize consultation conversions: CTAs and proof points should support booking or requesting a consultation.',
		'valuations'    => 'Prioritize valuation conversions: CTAs and proof points should encourage requesting a valuation or appraisal.',
		'lead_capture'  => 'Prioritize lead-capture conversions: CTAs and proof points should support form submission or contact capture; nurture before hard sell.',
	);

	/**
	 * Builds goal overlay from input artifact. Safe: returns minimal overlay when industry_context has no valid goal.
	 *
	 * @param array<string, mixed> $input_artifact Built input artifact (may contain industry_context with primary_goal_key, secondary_goal_key).
	 * @return array<string, mixed> Overlay with schema_version; optional primary_goal_key, secondary_goal_key, conversion_goal_guidance_text.
	 */
	public function get_overlay_for_artifact( array $input_artifact ): array {
		$base             = array( 'schema_version' => self::OVERLAY_SCHEMA_VERSION );
		$industry_context = isset( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] ) && is_array( $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ] )
			? $input_artifact[ Input_Artifact_Schema::ROOT_INDUSTRY_CONTEXT ]
			: null;
		if ( $industry_context === null ) {
			return $base;
		}
		$primary = isset( $industry_context['primary_goal_key'] ) && is_string( $industry_context['primary_goal_key'] )
			? trim( $industry_context['primary_goal_key'] )
			: '';
		if ( $primary === '' || ! $this->is_launch_goal( $primary ) ) {
			return $base;
		}
		$base[ self::OVERLAY_PRIMARY_GOAL_KEY ] = $primary;
		$secondary                              = isset( $industry_context['secondary_goal_key'] ) && is_string( $industry_context['secondary_goal_key'] )
			? trim( $industry_context['secondary_goal_key'] )
			: '';
		if ( $secondary !== '' && $this->is_launch_goal( $secondary ) && $secondary !== $primary ) {
			$base[ self::OVERLAY_SECONDARY_GOAL_KEY ] = $secondary;
		}
		$guidance = $this->build_guidance_text( $primary, $secondary );
		if ( $guidance !== '' ) {
			$base[ self::OVERLAY_CONVERSION_GOAL_GUIDANCE_TEXT ] = $guidance;
		}
		return $base;
	}

	private function is_launch_goal( string $key ): bool {
		return in_array( $key, self::GOAL_KEYS, true );
	}

	private function build_guidance_text( string $primary, string $secondary ): string {
		$hint = self::GOAL_PLANNING_HINTS[ $primary ] ?? '';
		if ( $hint === '' ) {
			return '';
		}
		$parts = array( $hint );
		if ( $secondary !== '' ) {
			$sec_hint = self::GOAL_PLANNING_HINTS[ $secondary ] ?? '';
			if ( $sec_hint !== '' ) {
				$parts[] = 'Secondary objective (lower priority): ' . $sec_hint;
			}
		}
		return implode( "\n\n", $parts );
	}
}
