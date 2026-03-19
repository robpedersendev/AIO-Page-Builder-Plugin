<?php
/**
 * Build Plan review view model for conversion-goal influence (Prompt 499).
 * Exposes goal overlay source and rationale so reviewers see how conversion goal shaped the draft plan.
 * Safe fallback when goal metadata is absent.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\BuildPlan;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;

/**
 * View model for conversion-goal influence on Build Plan (CTA posture, funnel intent, bundle-derived changes).
 */
final class Conversion_Goal_Build_Plan_Explanation_View_Model {

	/** View-model key: whether plan has conversion-goal context. */
	public const KEY_HAS_GOAL_CONTEXT = 'has_goal_context';

	/** View-model key: conversion goal key. */
	public const KEY_CONVERSION_GOAL_KEY = 'conversion_goal_key';

	/** View-model key: whether a goal overlay was applied (vs goal passed but no overlay). */
	public const KEY_GOAL_OVERLAY_APPLIED = 'goal_overlay_applied';

	/** View-model key: single-line rationale for display. */
	public const KEY_GOAL_RATIONALE_LINE = 'goal_rationale_line';

	/** View-model key: optional goal influence notes (bounded list). */
	public const KEY_GOAL_INFLUENCE_NOTES = 'goal_influence_notes';

	/** Human-readable labels for launch goal keys. */
	private const GOAL_LABELS = array(
		'calls'         => 'Calls',
		'bookings'      => 'Bookings',
		'estimates'     => 'Estimates / quotes',
		'consultations' => 'Consultations',
		'valuations'    => 'Valuations',
		'lead_capture'  => 'Lead capture',
	);

	/**
	 * Builds view model from plan definition. Safe when goal_overlay_source is missing.
	 *
	 * @param array<string, mixed> $plan_definition Plan root (may contain goal_overlay_source).
	 * @return array{
	 *   has_goal_context: bool,
	 *   conversion_goal_key: string|null,
	 *   goal_overlay_applied: bool,
	 *   goal_rationale_line: string,
	 *   goal_influence_notes: array<int, string>
	 * }
	 */
	public static function from_plan_definition( array $plan_definition ): array {
		$goal_source = isset( $plan_definition[ Build_Plan_Schema::KEY_GOAL_OVERLAY_SOURCE ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_GOAL_OVERLAY_SOURCE ] )
			? $plan_definition[ Build_Plan_Schema::KEY_GOAL_OVERLAY_SOURCE ]
			: array();
		$goal_key    = isset( $goal_source['conversion_goal_key'] ) && is_string( $goal_source['conversion_goal_key'] )
			? trim( $goal_source['conversion_goal_key'] )
			: '';
		$applied     = isset( $goal_source['applied'] ) && $goal_source['applied'] === true;

		$has_goal_context = $goal_key !== '';

		$goal_label     = $goal_key !== '' ? ( self::GOAL_LABELS[ $goal_key ] ?? self::humanize_goal_key( $goal_key ) ) : '';
		$rationale_line = '';
		if ( $goal_key !== '' ) {
			if ( $applied ) {
				$rationale_line = sprintf(
					/* translators: %s: goal label (e.g. Calls, Bookings) */
					__( 'This plan was refined for conversion goal: %s.', 'aio-page-builder' ),
					$goal_label
				);
			} else {
				$rationale_line = sprintf(
					/* translators: %s: goal label */
					__( 'Conversion goal set to %s (no overlay applied for this bundle).', 'aio-page-builder' ),
					$goal_label
				);
			}
		}

		$goal_influence_notes = array();
		if ( $has_goal_context ) {
			$goal_influence_notes[] = sprintf(
				/* translators: %s: goal label */
				__( 'Plan recommendations may emphasize CTA and funnel posture for: %s.', 'aio-page-builder' ),
				$goal_label
			);
		}

		return array(
			self::KEY_HAS_GOAL_CONTEXT     => $has_goal_context,
			self::KEY_CONVERSION_GOAL_KEY  => $goal_key !== '' ? $goal_key : null,
			self::KEY_GOAL_OVERLAY_APPLIED => $applied,
			self::KEY_GOAL_RATIONALE_LINE  => $rationale_line,
			self::KEY_GOAL_INFLUENCE_NOTES => $goal_influence_notes,
		);
	}

	/**
	 * Merges goal view model into an existing plan-level view model for display.
	 *
	 * @param array<string, mixed> $plan_view_model Plan-level view model (e.g. from Subtype_Build_Plan_Explanation_View_Model).
	 * @param array<string, mixed> $goal_context    From self::from_plan_definition().
	 * @return array<string, mixed> Merged view model with goal_* keys when has_goal_context.
	 */
	public static function merge_into_plan_view_model( array $plan_view_model, array $goal_context ): array {
		if ( empty( $goal_context[ self::KEY_HAS_GOAL_CONTEXT ] ) ) {
			return $plan_view_model;
		}
		$plan_view_model['goal_context']     = $goal_context;
		$plan_view_model['has_goal_context'] = true;
		return $plan_view_model;
	}

	private static function humanize_goal_key( string $key ): string {
		return str_replace( array( '_', '-' ), ' ', ucfirst( $key ) );
	}
}
