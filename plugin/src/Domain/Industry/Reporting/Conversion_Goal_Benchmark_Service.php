<?php
/**
 * Internal benchmark harness for conversion-goal support (Prompt 500).
 * Compares no-goal vs goal-aware outcomes for sections, page templates, bundles, and Build Plans.
 * Internal-only; no live-state mutation; bounded output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Runs no-goal vs goal-aware comparison and returns readable differentiation summaries.
 */
final class Conversion_Goal_Benchmark_Service {

	/** Launch goal set (conversion-goal-profile-contract). */
	public const LAUNCH_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** Max goals to run in one benchmark (bounded). */
	private const MAX_GOALS_PER_RUN = 10;

	/** Max comparison items to include in summary (e.g. top N section/template keys). */
	private const MAX_COMPARISON_ITEMS = 10;

	/** @var Industry_Subtype_Comparison_Service|null */
	private ?Industry_Subtype_Comparison_Service $comparison_service;

	public function __construct( ?Industry_Subtype_Comparison_Service $comparison_service = null ) {
		$this->comparison_service = $comparison_service;
	}

	/**
	 * Runs benchmark comparing no-goal vs goal-aware outputs for the given base profile and goal set.
	 * Does not mutate live state. Returns bounded summary.
	 *
	 * @param array<string, mixed> $profile_base Normalized industry profile (primary_industry_key, industry_subtype_key, selected_starter_bundle_key). conversion_goal_key should be empty for no-goal baseline.
	 * @param list<string>         $goal_keys    Goal keys to test (e.g. LAUNCH_GOAL_KEYS). Capped at MAX_GOALS_PER_RUN.
	 * @return array{
	 *   no_goal_baseline: array{section_keys?: list<string>, template_keys?: list<string>},
	 *   by_goal: array<string, array{section_keys?: list<string>, template_keys?: list<string>, differentiation_summary: string}>,
	 *   readable_summary: list<string>,
	 *   warnings: list<string>
	 * }
	 */
	public function run_benchmark( array $profile_base, array $goal_keys = array() ): array {
		$goal_keys = array_slice( array_intersect( array_values( $goal_keys ), self::LAUNCH_GOAL_KEYS ), 0, self::MAX_GOALS_PER_RUN );
		$warnings = array();

		$baseline = $this->get_baseline_comparison( $profile_base );
		$by_goal = array();
		$readable_summary = array();

		foreach ( $goal_keys as $goal_key ) {
			$profile_with_goal = array_merge( $profile_base, array( Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => $goal_key ) );
			$with_goal = $this->get_baseline_comparison( $profile_with_goal );
			$diff_summary = $this->differentiation_summary( $baseline, $with_goal, $goal_key );
			$by_goal[ $goal_key ] = array(
				'section_keys'            => $with_goal['section_keys'] ?? array(),
				'template_keys'            => $with_goal['template_keys'] ?? array(),
				'differentiation_summary'  => $diff_summary,
			);
			$readable_summary[] = sprintf( 'Goal "%s": %s', $goal_key, $diff_summary );
		}

		$readable_summary = array_merge(
			array( 'Baseline (no goal): ' . ( count( $baseline['section_keys'] ?? array() ) ) . ' section keys, ' . ( count( $baseline['template_keys'] ?? array() ) ) . ' template keys.' ),
			$readable_summary
		);

		return array(
			'no_goal_baseline'   => $baseline,
			'by_goal'            => $by_goal,
			'readable_summary'   => $readable_summary,
			'warnings'           => $warnings,
		);
	}

	/**
	 * Returns baseline comparison data (section_keys, template_keys) for the given profile. Uses comparison service when available.
	 *
	 * @param array<string, mixed> $profile
	 * @return array{section_keys: list<string>, template_keys: list<string>}
	 */
	private function get_baseline_comparison( array $profile ): array {
		$primary = isset( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$subtype = isset( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] ) && is_string( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			? trim( $profile[ Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY ] )
			: '';

		$section_keys = array();
		$template_keys = array();
		if ( $this->comparison_service !== null && $primary !== '' ) {
			$comparison = $this->comparison_service->get_comparison( $primary, $subtype );
			if ( is_array( $comparison ) ) {
				$section_keys = $subtype !== ''
					? ( isset( $comparison['subtype_top_section_keys'] ) && is_array( $comparison['subtype_top_section_keys'] ) ? $comparison['subtype_top_section_keys'] : array() )
					: ( isset( $comparison['parent_top_section_keys'] ) && is_array( $comparison['parent_top_section_keys'] ) ? $comparison['parent_top_section_keys'] : array() );
				$template_keys = $subtype !== ''
					? ( isset( $comparison['subtype_top_template_keys'] ) && is_array( $comparison['subtype_top_template_keys'] ) ? $comparison['subtype_top_template_keys'] : array() )
					: ( isset( $comparison['parent_top_template_keys'] ) && is_array( $comparison['parent_top_template_keys'] ) ? $comparison['parent_top_template_keys'] : array() );
				$section_keys = array_slice( array_values( $section_keys ), 0, self::MAX_COMPARISON_ITEMS );
				$template_keys = array_slice( array_values( $template_keys ), 0, self::MAX_COMPARISON_ITEMS );
			}
		}

		return array( 'section_keys' => $section_keys, 'template_keys' => $template_keys );
	}

	/**
	 * Produces a short differentiation summary between baseline and goal-aware result.
	 */
	private function differentiation_summary( array $baseline, array $with_goal, string $goal_key ): string {
		$base_sections = $baseline['section_keys'] ?? array();
		$base_templates = $baseline['template_keys'] ?? array();
		$goal_sections = $with_goal['section_keys'] ?? array();
		$goal_templates = $with_goal['template_keys'] ?? array();

		$section_diff = count( array_diff( $goal_sections, $base_sections ) ) + count( array_diff( $base_sections, $goal_sections ) );
		$template_diff = count( array_diff( $goal_templates, $base_templates ) ) + count( array_diff( $base_templates, $goal_templates ) );

		if ( $section_diff === 0 && $template_diff === 0 ) {
			return 'No difference from baseline (goal overlay may not be applied for this industry/subtype).';
		}
		$parts = array();
		if ( $section_diff > 0 ) {
			$parts[] = sprintf( '%d section key(s) differ', $section_diff );
		}
		if ( $template_diff > 0 ) {
			$parts[] = sprintf( '%d template key(s) differ', $template_diff );
		}
		return implode( '; ', $parts ) . '.';
	}
}
