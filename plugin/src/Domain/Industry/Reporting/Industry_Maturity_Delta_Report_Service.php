<?php
/**
 * Internal maturity delta report generator (Prompt 560). Compares maturity snapshots over time
 * and produces improvement/stagnation/regression summaries. Advisory only. See industry-maturity-delta-report-contract.md.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;

/**
 * Generates a bounded maturity delta report from current state vs optional baseline snapshot.
 */
final class Industry_Maturity_Delta_Report_Service {

	/** Trend: improvement. */
	public const TREND_IMPROVEMENT = 'improvement';

	/** Trend: stagnation. */
	public const TREND_STAGNATION = 'stagnation';

	/** Trend: regression. */
	public const TREND_REGRESSION = 'regression';

	/** Completeness band order (lower index = lower maturity). */
	private const BAND_ORDER = array(
		Industry_Pack_Completeness_Report_Service::BAND_BELOW_MINIMAL => 0,
		Industry_Pack_Completeness_Report_Service::BAND_MINIMAL_VIABLE  => 1,
		Industry_Pack_Completeness_Report_Service::BAND_STRONG          => 2,
		Industry_Pack_Completeness_Report_Service::BAND_RELEASE_GRADE   => 3,
	);

	/** Capability areas from maturity matrix for capability-level delta (key => default level). */
	private const CAPABILITY_AREAS = array(
		'packs'                    => 'stable',
		'industry_profile'         => 'production_ready',
		'subtypes'                  => 'stable',
		'starter_bundles'          => 'stable',
		'section_overlays'         => 'stable',
		'page_overlays'            => 'stable',
		'recommendation_resolvers'  => 'stable',
		'build_plan_scoring'       => 'stable',
		'ai_overlays'              => 'experimental',
		'diagnostics_health'       => 'stable',
		'import_export_restore'     => 'stable',
		'cache_layers'             => 'stable',
		'support_tooling'          => 'stable',
		'release_authoring_tooling' => 'stable',
		'whatif_simulation'        => 'stable',
		'subtype_goal_benchmark'   => 'stable',
		'degraded_mode'            => 'stable',
	);

	/** Maturity level order (lower index = lower maturity). */
	private const LEVEL_ORDER = array(
		'gap'               => 0,
		'draft'             => 1,
		'experimental'      => 2,
		'stable'            => 3,
		'production_ready'  => 4,
	);

	/** @var Industry_Pack_Completeness_Report_Service|null */
	private $completeness_service;

	public function __construct( ?Industry_Pack_Completeness_Report_Service $completeness_service = null ) {
		$this->completeness_service = $completeness_service;
	}

	/**
	 * Generates the maturity delta report. When baseline is null, returns current snapshot only and no_baseline flag.
	 *
	 * @param array<string, mixed>|null $baseline_snapshot Optional. Keys: families (scope => [band, total]), capability_areas (area => level), captured_at.
	 * @return array{
	 *   summary: array{improved: int, stagnated: int, regressed: int, no_baseline: bool},
	 *   family_deltas: list<array{scope: string, scope_label: string, band_t1: string, band_t2: string, total_t1: int, total_t2: int, trend: string}>,
	 *   capability_deltas: list<array{area: string, level_t1: string, level_t2: string, trend: string}>,
	 *   current_snapshot: array{families: array<string, array{band: string, total: int}>, capability_areas: array<string, string>, captured_at: string},
	 *   generated_at: string
	 * }
	 */
	public function generate_report( ?array $baseline_snapshot = null ): array {
		$current = $this->build_current_snapshot();
		$generated_at = gmdate( 'Y-m-d\TH:i:s\Z' );
		$current_snapshot = array(
			'families'          => $current['families'],
			'capability_areas'   => $current['capability_areas'],
			'captured_at'       => $generated_at,
		);

		if ( $baseline_snapshot === null || ! isset( $baseline_snapshot['families'] ) || ! is_array( $baseline_snapshot['families'] ) ) {
			return array(
				'summary'           => array(
					'improved'    => 0,
					'stagnated'   => 0,
					'regressed'   => 0,
					'no_baseline' => true,
				),
				'family_deltas'     => array(),
				'capability_deltas' => array(),
				'current_snapshot'  => $current_snapshot,
				'generated_at'      => $generated_at,
			);
		}

		$baseline_families = $baseline_snapshot['families'];
		$baseline_capability = isset( $baseline_snapshot['capability_areas'] ) && is_array( $baseline_snapshot['capability_areas'] ) ? $baseline_snapshot['capability_areas'] : array();
		$family_deltas = array();
		$improved = 0;
		$stagnated = 0;
		$regressed = 0;

		foreach ( $current['families'] as $scope => $cur ) {
			$band_t2 = $cur['band'];
			$total_t2 = $cur['total'];
			$baseline_row = $baseline_families[ $scope ] ?? null;
			$band_t1 = '';
			$total_t1 = 0;
			if ( is_array( $baseline_row ) ) {
				$band_t1 = isset( $baseline_row['band'] ) && is_string( $baseline_row['band'] ) ? $baseline_row['band'] : '';
				$total_t1 = isset( $baseline_row['total'] ) ? (int) $baseline_row['total'] : 0;
			}
			$trend = $this->family_trend( $band_t1, $total_t1, $band_t2, $total_t2 );
			$scope_label = strpos( $scope, '|' ) !== false ? str_replace( '|', ' → ', $scope ) : $scope;
			$family_deltas[] = array(
				'scope'       => $scope,
				'scope_label' => $scope_label,
				'band_t1'     => $band_t1,
				'band_t2'     => $band_t2,
				'total_t1'    => $total_t1,
				'total_t2'    => $total_t2,
				'trend'       => $trend,
			);
			if ( $trend === self::TREND_IMPROVEMENT ) {
				++$improved;
			} elseif ( $trend === self::TREND_REGRESSION ) {
				++$regressed;
			} else {
				++$stagnated;
			}
		}

		$capability_deltas = array();
		foreach ( array_keys( self::CAPABILITY_AREAS ) as $area ) {
			$level_t2 = $current['capability_areas'][ $area ] ?? self::CAPABILITY_AREAS[ $area ];
			$level_t1 = $baseline_capability[ $area ] ?? self::CAPABILITY_AREAS[ $area ];
			$trend = $this->level_trend( $level_t1, $level_t2 );
			$capability_deltas[] = array(
				'area'     => $area,
				'level_t1' => $level_t1,
				'level_t2' => $level_t2,
				'trend'    => $trend,
			);
		}

		return array(
			'summary'           => array(
				'improved'    => $improved,
				'stagnated'   => $stagnated,
				'regressed'   => $regressed,
				'no_baseline' => false,
			),
			'family_deltas'     => $family_deltas,
			'capability_deltas' => $capability_deltas,
			'current_snapshot'  => $current_snapshot,
			'generated_at'      => $generated_at,
		);
	}

	/**
	 * Builds current snapshot from completeness report and fixed capability defaults.
	 *
	 * @return array{families: array<string, array{band: string, total: int}>, capability_areas: array<string, string>}
	 */
	private function build_current_snapshot(): array {
		$families = array();
		$capability_areas = array();
		foreach ( self::CAPABILITY_AREAS as $area => $level ) {
			$capability_areas[ $area ] = $level;
		}

		if ( $this->completeness_service instanceof Industry_Pack_Completeness_Report_Service ) {
			$report = $this->completeness_service->generate_report( true );
			$pack_results = isset( $report['pack_results'] ) && is_array( $report['pack_results'] ) ? $report['pack_results'] : array();
			foreach ( $pack_results as $r ) {
				$pack_key = isset( $r['pack_key'] ) && is_string( $r['pack_key'] ) ? $r['pack_key'] : '';
				$subtype_key = isset( $r['subtype_key'] ) && is_string( $r['subtype_key'] ) ? $r['subtype_key'] : '';
				$scope = $subtype_key !== '' ? $pack_key . '|' . $subtype_key : $pack_key;
				if ( $scope === '' ) {
					continue;
				}
				$families[ $scope ] = array(
					'band'  => isset( $r['band'] ) && is_string( $r['band'] ) ? $r['band'] : '',
					'total' => isset( $r['total'] ) ? (int) $r['total'] : 0,
				);
			}
		}

		return array( 'families' => $families, 'capability_areas' => $capability_areas );
	}

	private function family_trend( string $band_t1, int $total_t1, string $band_t2, int $total_t2 ): string {
		$order_t1 = self::BAND_ORDER[ $band_t1 ] ?? -1;
		$order_t2 = self::BAND_ORDER[ $band_t2 ] ?? -1;
		if ( $order_t1 < 0 && $order_t2 < 0 ) {
			return $total_t2 > $total_t1 ? self::TREND_IMPROVEMENT : ( $total_t2 < $total_t1 ? self::TREND_REGRESSION : self::TREND_STAGNATION );
		}
		if ( $order_t2 > $order_t1 ) {
			return self::TREND_IMPROVEMENT;
		}
		if ( $order_t2 < $order_t1 ) {
			return self::TREND_REGRESSION;
		}
		if ( $total_t2 > $total_t1 ) {
			return self::TREND_IMPROVEMENT;
		}
		if ( $total_t2 < $total_t1 ) {
			return self::TREND_REGRESSION;
		}
		return self::TREND_STAGNATION;
	}

	private function level_trend( string $level_t1, string $level_t2 ): string {
		$o1 = self::LEVEL_ORDER[ $level_t1 ] ?? 2;
		$o2 = self::LEVEL_ORDER[ $level_t2 ] ?? 2;
		if ( $o2 > $o1 ) {
			return self::TREND_IMPROVEMENT;
		}
		if ( $o2 < $o1 ) {
			return self::TREND_REGRESSION;
		}
		return self::TREND_STAGNATION;
	}
}
