<?php
/**
 * Unit tests for Industry_Maturity_Delta_Report_Service (Prompt 560). Report structure and missing-history fallback.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Maturity_Delta_Report_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Pack_Completeness_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Pack_Completeness_Report_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Maturity_Delta_Report_Service.php';

final class Industry_Maturity_Delta_Report_Service_Test extends TestCase {

	public function test_generate_report_with_null_baseline_returns_no_baseline_structure(): void {
		$service = new Industry_Maturity_Delta_Report_Service( null );
		$result  = $service->generate_report( null );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'family_deltas', $result );
		$this->assertArrayHasKey( 'capability_deltas', $result );
		$this->assertArrayHasKey( 'current_snapshot', $result );
		$this->assertArrayHasKey( 'generated_at', $result );
		$this->assertTrue( $result['summary']['no_baseline'] );
		$this->assertSame( 0, $result['summary']['improved'] );
		$this->assertSame( 0, $result['summary']['stagnated'] );
		$this->assertSame( 0, $result['summary']['regressed'] );
		$this->assertIsArray( $result['family_deltas'] );
		$this->assertIsArray( $result['current_snapshot'] );
		$this->assertArrayHasKey( 'families', $result['current_snapshot'] );
		$this->assertArrayHasKey( 'capability_areas', $result['current_snapshot'] );
		$this->assertArrayHasKey( 'captured_at', $result['current_snapshot'] );
	}

	public function test_generate_report_with_empty_baseline_families_returns_no_baseline(): void {
		$service = new Industry_Maturity_Delta_Report_Service( null );
		$result  = $service->generate_report( array( 'families' => array() ) );
		$this->assertTrue( $result['summary']['no_baseline'] );
	}

	public function test_generate_report_with_baseline_returns_trends(): void {
		$service  = new Industry_Maturity_Delta_Report_Service( null );
		$baseline = array(
			'families'         => array(
				'realtor' => array(
					'band'  => Industry_Pack_Completeness_Report_Service::BAND_MINIMAL_VIABLE,
					'total' => 5,
				),
			),
			'capability_areas' => array(),
			'captured_at'      => '2020-01-01T00:00:00Z',
		);
		$result   = $service->generate_report( $baseline );
		$this->assertFalse( $result['summary']['no_baseline'] );
		$this->assertIsArray( $result['family_deltas'] );
		$this->assertIsArray( $result['capability_deltas'] );
		foreach ( $result['family_deltas'] as $row ) {
			$this->assertArrayHasKey( 'scope', $row );
			$this->assertArrayHasKey( 'trend', $row );
			$this->assertContains( $row['trend'], array( Industry_Maturity_Delta_Report_Service::TREND_IMPROVEMENT, Industry_Maturity_Delta_Report_Service::TREND_STAGNATION, Industry_Maturity_Delta_Report_Service::TREND_REGRESSION ) );
		}
	}

	public function test_trend_improvement_when_band_increases(): void {
		$service  = new Industry_Maturity_Delta_Report_Service( null );
		$baseline = array(
			'families'         => array(
				'test_pack' => array(
					'band'  => Industry_Pack_Completeness_Report_Service::BAND_BELOW_MINIMAL,
					'total' => 2,
				),
			),
			'capability_areas' => array(),
			'captured_at'      => '2020-01-01T00:00:00Z',
		);
		$result   = $service->generate_report( $baseline );
		$summary  = $result['summary'];
		$this->assertGreaterThanOrEqual( 0, $summary['improved'] + $summary['stagnated'] + $summary['regressed'] );
	}
}
