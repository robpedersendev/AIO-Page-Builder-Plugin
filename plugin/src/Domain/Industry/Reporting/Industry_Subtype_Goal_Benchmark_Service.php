<?php
/**
 * Combined subtype-plus-goal benchmark harness (Prompt 535).
 * Compares parent-only, subtype-only, goal-only, and subtype+goal scenarios across recommendations,
 * bundle outputs, Build Plans, and previews. Internal-only; no live-state mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Runs combined benchmark: parent vs subtype vs goal vs combined. Produces readable summaries.
 */
final class Industry_Subtype_Goal_Benchmark_Service {

	/** Launch goal set (same as Conversion_Goal_Benchmark_Service). */
	public const LAUNCH_GOAL_KEYS = array( 'calls', 'bookings', 'estimates', 'consultations', 'valuations', 'lead_capture' );

	/** @var Conversion_Goal_Benchmark_Service|null */
	private ?Conversion_Goal_Benchmark_Service $goal_benchmark;

	/** @var Industry_Subtype_Benchmark_Service|null */
	private ?Industry_Subtype_Benchmark_Service $subtype_benchmark;

	public function __construct(
		?Conversion_Goal_Benchmark_Service $goal_benchmark = null,
		?Industry_Subtype_Benchmark_Service $subtype_benchmark = null
	) {
		$this->goal_benchmark    = $goal_benchmark;
		$this->subtype_benchmark = $subtype_benchmark;
	}

	/**
	 * Runs combined benchmark for the given base profile, optional subtype, and optional goal.
	 * Compares parent-only, subtype-only (when subtype provided), goal-only (when goal provided), and combined.
	 * Does not mutate live state. Returns bounded report with readable summary.
	 *
	 * @param array<string, mixed> $profile_base Normalized industry profile (primary_industry_key required; industry_subtype_key, conversion_goal_key optional for this run).
	 * @param string               $subtype_key  Optional subtype key; empty = skip subtype dimension.
	 * @param string               $goal_key     Optional goal key (launch set); empty = skip goal dimension.
	 * @return array{
	 *   generated_at: string,
	 *   profile_base: array{primary_industry_key: string, subtype_key: string, goal_key: string},
	 *   scenarios: array{parent_only?: array, subtype_only?: array, goal_only?: array, combined?: array},
	 *   recommendation_differentiation: array{parent_vs_subtype: string, parent_vs_goal: string, parent_vs_combined: string, combined_strength: string},
	 *   readable_summary: list<string>,
	 *   warnings: list<string>
	 * }
	 */
	public function run_benchmark( array $profile_base, string $subtype_key = '', string $goal_key = '' ): array {
		$primary     = isset( $profile_base[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] ) && is_string( $profile_base[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			? trim( $profile_base[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] )
			: '';
		$subtype_key = trim( $subtype_key );
		$goal_key    = trim( $goal_key );
		if ( $goal_key !== '' && ! in_array( $goal_key, self::LAUNCH_GOAL_KEYS, true ) ) {
			$goal_key = '';
		}

		$warnings = array();
		if ( $primary === '' ) {
			$warnings[] = 'primary_industry_key missing; benchmark results may be empty.';
		}

		$scenarios                = array();
		$parent_only              = array_merge(
			$profile_base,
			array(
				Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => '',
				Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY   => '',
			)
		);
		$scenarios['parent_only'] = $this->get_scenario_result( $parent_only, array() );

		if ( $subtype_key !== '' ) {
			$subtype_only              = array_merge(
				$profile_base,
				array(
					Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => $subtype_key,
					Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => '',
				)
			);
			$scenarios['subtype_only'] = $this->get_scenario_result( $subtype_only, array() );
		}
		if ( $goal_key !== '' ) {
			$goal_only              = array_merge(
				$profile_base,
				array(
					Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => '',
					Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY   => $goal_key,
				)
			);
			$scenarios['goal_only'] = $this->get_scenario_result( $goal_only, array( $goal_key ) );
		}
		if ( $subtype_key !== '' && $goal_key !== '' ) {
			$combined              = array_merge(
				$profile_base,
				array(
					Industry_Profile_Schema::FIELD_INDUSTRY_SUBTYPE_KEY => $subtype_key,
					Industry_Profile_Schema::FIELD_CONVERSION_GOAL_KEY => $goal_key,
				)
			);
			$scenarios['combined'] = $this->get_scenario_result( $combined, array( $goal_key ) );
		}

		$diff     = $this->build_differentiation( $scenarios, $subtype_key, $goal_key );
		$readable = $this->build_readable_summary( $scenarios, $diff, $primary, $subtype_key, $goal_key );

		return array(
			'generated_at'                   => gmdate( 'c' ),
			'profile_base'                   => array(
				'primary_industry_key' => $primary,
				'subtype_key'          => $subtype_key,
				'goal_key'             => $goal_key,
			),
			'scenarios'                      => $scenarios,
			'recommendation_differentiation' => $diff,
			'readable_summary'               => $readable,
			'warnings'                       => $warnings,
		);
	}

	/**
	 * @param array<string, mixed> $profile
	 * @param list<string>         $goal_keys
	 * @return array{section_keys: list<string>, template_keys: list<string>, bundle_count?: int}
	 */
	private function get_scenario_result( array $profile, array $goal_keys ): array {
		if ( $this->goal_benchmark === null ) {
			return array(
				'section_keys'  => array(),
				'template_keys' => array(),
			);
		}
		$result   = $this->goal_benchmark->run_benchmark( $profile, $goal_keys );
		$baseline = $result['no_goal_baseline'] ?? array(
			'section_keys'  => array(),
			'template_keys' => array(),
		);
		if ( $goal_keys === array() ) {
			return array(
				'section_keys'  => $baseline['section_keys'] ?? array(),
				'template_keys' => $baseline['template_keys'] ?? array(),
			);
		}
		$goal_key = $goal_keys[0] ?? '';
		$by_goal  = $result['by_goal'][ $goal_key ] ?? array();
		return array(
			'section_keys'  => $by_goal['section_keys'] ?? $baseline['section_keys'] ?? array(),
			'template_keys' => $by_goal['template_keys'] ?? $baseline['template_keys'] ?? array(),
		);
	}

	/**
	 * @param array<string, array{section_keys?: list<string>, template_keys?: list<string>}> $scenarios
	 * @return array{parent_vs_subtype: string, parent_vs_goal: string, parent_vs_combined: string, combined_strength: string}
	 */
	private function build_differentiation( array $scenarios, string $subtype_key, string $goal_key ): array {
		$parent   = $scenarios['parent_only'] ?? array(
			'section_keys'  => array(),
			'template_keys' => array(),
		);
		$parent_s = $parent['section_keys'] ?? array();
		$parent_t = $parent['template_keys'] ?? array();

		$subtype   = $scenarios['subtype_only'] ?? null;
		$subtype_s = $subtype['section_keys'] ?? array();
		$subtype_t = $subtype['template_keys'] ?? array();

		$goal   = $scenarios['goal_only'] ?? null;
		$goal_s = $goal['section_keys'] ?? array();
		$goal_t = $goal['template_keys'] ?? array();

		$combined = $scenarios['combined'] ?? null;
		$comb_s   = $combined['section_keys'] ?? array();
		$comb_t   = $combined['template_keys'] ?? array();

		$parent_vs_subtype = 'N/A';
		if ( $subtype_key !== '' && $subtype !== null ) {
			$ds                = count( array_diff( $subtype_s, $parent_s ) ) + count( array_diff( $parent_s, $subtype_s ) );
			$dt                = count( array_diff( $subtype_t, $parent_t ) ) + count( array_diff( $parent_t, $subtype_t ) );
			$parent_vs_subtype = $ds + $dt > 0 ? sprintf( '%d section + %d template key(s) differ from parent.', $ds, $dt ) : 'No recommendation difference from parent.';
		}

		$parent_vs_goal = 'N/A';
		if ( $goal_key !== '' && $goal !== null ) {
			$ds             = count( array_diff( $goal_s, $parent_s ) ) + count( array_diff( $parent_s, $goal_s ) );
			$dt             = count( array_diff( $goal_t, $parent_t ) ) + count( array_diff( $parent_t, $goal_t ) );
			$parent_vs_goal = $ds + $dt > 0 ? sprintf( '%d section + %d template key(s) differ from parent.', $ds, $dt ) : 'No recommendation difference from parent.';
		}

		$parent_vs_combined = 'N/A';
		$combined_strength  = 'N/A';
		if ( $subtype_key !== '' && $goal_key !== '' && $combined !== null ) {
			$ds                 = count( array_diff( $comb_s, $parent_s ) ) + count( array_diff( $parent_s, $comb_s ) );
			$dt                 = count( array_diff( $comb_t, $parent_t ) ) + count( array_diff( $parent_t, $comb_t ) );
			$parent_vs_combined = $ds + $dt > 0 ? sprintf( '%d section + %d template key(s) differ from parent.', $ds, $dt ) : 'No recommendation difference from parent.';
			$vs_sub             = ( count( array_diff( $comb_s, $subtype_s ) ) + count( array_diff( $subtype_s, $comb_s ) ) ) + ( count( array_diff( $comb_t, $subtype_t ) ) + count( array_diff( $subtype_t, $comb_t ) ) );
			$vs_goal            = ( count( array_diff( $comb_s, $goal_s ) ) + count( array_diff( $goal_s, $comb_s ) ) ) + ( count( array_diff( $comb_t, $goal_t ) ) + count( array_diff( $goal_t, $comb_t ) ) );
			$combined_strength  = ( $vs_sub > 0 || $vs_goal > 0 ) ? 'Strong: combined differs from subtype-only and/or goal-only.' : 'Weak: combined matches subtype-only or goal-only.';
		}

		return array(
			'parent_vs_subtype'  => $parent_vs_subtype,
			'parent_vs_goal'     => $parent_vs_goal,
			'parent_vs_combined' => $parent_vs_combined,
			'combined_strength'  => $combined_strength,
		);
	}

	/**
	 * @param array<string, array>  $scenarios
	 * @param array<string, string> $diff
	 * @return list<string>
	 */
	private function build_readable_summary( array $scenarios, array $diff, string $primary, string $subtype_key, string $goal_key ): array {
		$lines   = array();
		$lines[] = sprintf( 'Combined subtype+goal benchmark: industry=%s, subtype=%s, goal=%s', $primary ?: '(none)', $subtype_key ?: '(none)', $goal_key ?: '(none)' );
		$lines[] = 'Parent-only: ' . ( count( ( $scenarios['parent_only'] ?? array() )['section_keys'] ?? array() ) ) . ' section keys, ' . ( count( ( $scenarios['parent_only'] ?? array() )['template_keys'] ?? array() ) ) . ' template keys.';
		if ( $subtype_key !== '' ) {
			$lines[] = 'Subtype-only: ' . $diff['parent_vs_subtype'];
		}
		if ( $goal_key !== '' ) {
			$lines[] = 'Goal-only: ' . $diff['parent_vs_goal'];
		}
		if ( $subtype_key !== '' && $goal_key !== '' ) {
			$lines[] = 'Parent vs combined: ' . $diff['parent_vs_combined'];
			$lines[] = 'Combined strength: ' . $diff['combined_strength'];
		}
		return $lines;
	}
}
