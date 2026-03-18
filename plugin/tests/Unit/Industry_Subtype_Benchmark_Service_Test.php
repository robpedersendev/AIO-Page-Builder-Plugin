<?php
/**
 * Unit tests for Industry_Subtype_Benchmark_Service (Prompt 480). run_benchmark(), report shape, differentiation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Subtype_Benchmark_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Subtype_Benchmark_Service.php';

final class Industry_Subtype_Benchmark_Service_Test extends TestCase {

	public function test_run_benchmark_returns_expected_structure(): void {
		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load(
			array(
				array(
					Industry_Subtype_Registry::FIELD_SUBTYPE_KEY => 'test_sub',
					Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY => 'realtor',
					Industry_Subtype_Registry::FIELD_LABEL => 'Test Subtype',
					Industry_Subtype_Registry::FIELD_STATUS => Industry_Subtype_Registry::STATUS_ACTIVE,
					Industry_Subtype_Registry::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$service = new Industry_Subtype_Benchmark_Service( $subtype_registry );
		$report  = $service->run_benchmark();
		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertArrayHasKey( 'subtypes_evaluated', $report );
		$this->assertArrayHasKey( 'per_subtype', $report );
		$this->assertArrayHasKey( 'summary', $report );
		$this->assertContains( 'test_sub', $report['subtypes_evaluated'] );
		$this->assertArrayHasKey( 'test_sub', $report['per_subtype'] );
		$this->assertSame( 'weak', $report['per_subtype']['test_sub']['strength'] );
		$this->assertGreaterThanOrEqual( 0, $report['summary']['total_subtypes'] );
	}
}
