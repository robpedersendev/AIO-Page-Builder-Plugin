<?php
/**
 * Unit tests for Industry_Performance_Benchmark_Service: null container skips all; container runs bundle_comparison and health_report (Prompt 451).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Performance_Benchmark_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Starter_Bundle_Diff_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Starter_Bundle_Diff_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Performance_Benchmark_Service.php';

final class Industry_Performance_Benchmark_Service_Test extends TestCase {

	public function test_run_benchmark_with_null_container_skips_all_scenarios(): void {
		$service = new Industry_Performance_Benchmark_Service( null );
		$result  = $service->run_benchmark( 2 );
		$this->assertArrayHasKey( Industry_Performance_Benchmark_Service::SCENARIO_SECTION_PREVIEW, $result );
		$this->assertArrayHasKey( Industry_Performance_Benchmark_Service::SCENARIO_PAGE_PREVIEW, $result );
		$this->assertArrayHasKey( Industry_Performance_Benchmark_Service::SCENARIO_BUNDLE_COMPARISON, $result );
		$this->assertArrayHasKey( Industry_Performance_Benchmark_Service::SCENARIO_HEALTH_CHECK, $result );
		foreach ( $result as $scenario ) {
			$this->assertTrue( $scenario['skipped'], 'Each scenario should be skipped when container is null' );
			$this->assertSame( 0, $scenario['iterations'] );
			$this->assertArrayHasKey( 'total_ms', $scenario );
			$this->assertArrayHasKey( 'mean_ms', $scenario );
		}
	}

	public function test_run_benchmark_with_container_runs_bundle_comparison_and_health_report(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load(
			array(
				array(
					Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => 'bench_a',
					Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
					Industry_Starter_Bundle_Registry::FIELD_LABEL       => 'Bench A',
					Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => '1',
				),
				array(
					Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => 'bench_b',
					Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
					Industry_Starter_Bundle_Registry::FIELD_LABEL        => 'Bench B',
					Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$diff_service   = new Industry_Starter_Bundle_Diff_Service( $registry );
		$health_service = new Industry_Health_Check_Service( null, null, null, null, null, null, null, null, null, null, null );

		$container = new Service_Container();
		$container->register(
			'industry_starter_bundle_diff_service',
			function () use ( $diff_service ) {
				return $diff_service;
			}
		);
		$container->register(
			'industry_health_check_service',
			function () use ( $health_service ) {
				return $health_service;
			}
		);

		$service = new Industry_Performance_Benchmark_Service( $container );
		$result  = $service->run_benchmark( 2 );

		$this->assertFalse( $result[ Industry_Performance_Benchmark_Service::SCENARIO_BUNDLE_COMPARISON ]['skipped'] );
		$this->assertSame( 2, $result[ Industry_Performance_Benchmark_Service::SCENARIO_BUNDLE_COMPARISON ]['iterations'] );
		$this->assertGreaterThanOrEqual( 0, $result[ Industry_Performance_Benchmark_Service::SCENARIO_BUNDLE_COMPARISON ]['total_ms'] );
		$this->assertGreaterThanOrEqual( 0, $result[ Industry_Performance_Benchmark_Service::SCENARIO_BUNDLE_COMPARISON ]['mean_ms'] );

		$this->assertFalse( $result[ Industry_Performance_Benchmark_Service::SCENARIO_HEALTH_CHECK ]['skipped'] );
		$this->assertSame( 2, $result[ Industry_Performance_Benchmark_Service::SCENARIO_HEALTH_CHECK ]['iterations'] );
		$this->assertGreaterThanOrEqual( 0, $result[ Industry_Performance_Benchmark_Service::SCENARIO_HEALTH_CHECK ]['total_ms'] );
	}

	public function test_run_benchmark_caps_iterations_at_20(): void {
		$service = new Industry_Performance_Benchmark_Service( null );
		$result  = $service->run_benchmark( 99 );
		foreach ( $result as $scenario ) {
			$this->assertLessThanOrEqual( 20, $scenario['iterations'] );
		}
	}
}
