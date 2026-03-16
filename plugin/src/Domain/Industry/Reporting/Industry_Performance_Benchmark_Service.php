<?php
/**
 * Internal performance benchmark harness for the industry subsystem (Prompt 451).
 * Measures representative operations: recommendation resolution, overlay composition, preview assembly, bundle comparison, health check.
 * Internal-only; no production behavior change. Results inform optimization, not replace it.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;

/**
 * Runs bounded benchmark scenarios and returns timing summaries. No sensitive data in output.
 */
final class Industry_Performance_Benchmark_Service {

	/** Default iterations per scenario. */
	private const DEFAULT_ITERATIONS = 5;

	/** Scenario: section preview resolution (recommendation + helper composition). */
	public const SCENARIO_SECTION_PREVIEW = 'section_preview_resolution';

	/** Scenario: page template preview resolution (recommendation + one-pager composition). */
	public const SCENARIO_PAGE_PREVIEW = 'page_preview_resolution';

	/** Scenario: bundle comparison (diff). */
	public const SCENARIO_BUNDLE_COMPARISON = 'bundle_comparison';

	/** Scenario: health check run. */
	public const SCENARIO_HEALTH_CHECK = 'health_report';

	/** @var Service_Container|null */
	private ?Service_Container $container;

	public function __construct( ?Service_Container $container = null ) {
		$this->container = $container;
	}

	/**
	 * Runs all benchmark scenarios (or those with available services) and returns timing summary.
	 *
	 * @param int $iterations Per-scenario iteration count (bounded; max 20).
	 * @return array<string, array{iterations: int, total_ms: float, mean_ms: float, skipped: bool}>
	 */
	public function run_benchmark( int $iterations = self::DEFAULT_ITERATIONS ): array {
		$iterations = max( 1, min( 20, $iterations ) );
		$out = array();
		$out[ self::SCENARIO_SECTION_PREVIEW ]   = $this->run_section_preview( $iterations );
		$out[ self::SCENARIO_PAGE_PREVIEW ]     = $this->run_page_preview( $iterations );
		$out[ self::SCENARIO_BUNDLE_COMPARISON ] = $this->run_bundle_comparison( $iterations );
		$out[ self::SCENARIO_HEALTH_CHECK ]      = $this->run_health_check( $iterations );
		return $out;
	}

	/**
	 * @param int $n
	 * @return array{iterations: int, total_ms: float, mean_ms: float, skipped: bool}
	 */
	private function run_section_preview( int $n ): array {
		if ( $this->container === null || ! $this->container->has( 'industry_section_preview_resolver' ) ) {
			return array( 'iterations' => 0, 'total_ms' => 0.0, 'mean_ms' => 0.0, 'skipped' => true );
		}
		$resolver = $this->container->get( 'industry_section_preview_resolver' );
		$definition = array( 'internal_key' => 'hero_cred_01' );
		$start = microtime( true );
		for ( $i = 0; $i < $n; $i++ ) {
			$resolver->resolve( 'hero_cred_01', $definition, array() );
		}
		$total_ms = ( microtime( true ) - $start ) * 1000;
		return array( 'iterations' => $n, 'total_ms' => round( $total_ms, 2 ), 'mean_ms' => round( $total_ms / $n, 2 ), 'skipped' => false );
	}

	/**
	 * @param int $n
	 * @return array{iterations: int, total_ms: float, mean_ms: float, skipped: bool}
	 */
	private function run_page_preview( int $n ): array {
		if ( $this->container === null || ! $this->container->has( 'industry_page_template_preview_resolver' ) ) {
			return array( 'iterations' => 0, 'total_ms' => 0.0, 'mean_ms' => 0.0, 'skipped' => true );
		}
		$resolver = $this->container->get( 'industry_page_template_preview_resolver' );
		$definition = array( 'internal_key' => 'pt_home_trust_01' );
		$start = microtime( true );
		for ( $i = 0; $i < $n; $i++ ) {
			$resolver->resolve( 'pt_home_trust_01', $definition, array() );
		}
		$total_ms = ( microtime( true ) - $start ) * 1000;
		return array( 'iterations' => $n, 'total_ms' => round( $total_ms, 2 ), 'mean_ms' => round( $total_ms / $n, 2 ), 'skipped' => false );
	}

	/**
	 * @param int $n
	 * @return array{iterations: int, total_ms: float, mean_ms: float, skipped: bool}
	 */
	private function run_bundle_comparison( int $n ): array {
		if ( $this->container === null || ! $this->container->has( 'industry_starter_bundle_diff_service' ) ) {
			return array( 'iterations' => 0, 'total_ms' => 0.0, 'mean_ms' => 0.0, 'skipped' => true );
		}
		$service = $this->container->get( 'industry_starter_bundle_diff_service' );
		$start = microtime( true );
		for ( $i = 0; $i < $n; $i++ ) {
			$service->compare( array( 'plumber_starter', 'plumber_residential_starter' ) );
		}
		$total_ms = ( microtime( true ) - $start ) * 1000;
		return array( 'iterations' => $n, 'total_ms' => round( $total_ms, 2 ), 'mean_ms' => round( $total_ms / $n, 2 ), 'skipped' => false );
	}

	/**
	 * @param int $n
	 * @return array{iterations: int, total_ms: float, mean_ms: float, skipped: bool}
	 */
	private function run_health_check( int $n ): array {
		if ( $this->container === null || ! $this->container->has( 'industry_health_check_service' ) ) {
			return array( 'iterations' => 0, 'total_ms' => 0.0, 'mean_ms' => 0.0, 'skipped' => true );
		}
		$service = $this->container->get( 'industry_health_check_service' );
		$start = microtime( true );
		for ( $i = 0; $i < $n; $i++ ) {
			$service->run();
		}
		$total_ms = ( microtime( true ) - $start ) * 1000;
		return array( 'iterations' => $n, 'total_ms' => round( $total_ms, 2 ), 'mean_ms' => round( $total_ms / $n, 2 ), 'skipped' => false );
	}
}
