<?php
/**
 * Unit tests for Industry_Drift_Report_Service (Prompt 562). Report structure and grouping.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Drift_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Drift_Report_Service.php';

final class Industry_Drift_Report_Service_Test extends TestCase {

	public function test_generate_report_returns_required_structure(): void {
		$service = new Industry_Drift_Report_Service( null );
		$result  = $service->generate_report();
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'by_severity', $result );
		$this->assertArrayHasKey( 'by_type', $result );
		$this->assertArrayHasKey( 'generated_at', $result );
		$this->assertArrayHasKey( 'total', $result['summary'] );
		$this->assertArrayHasKey( 'severe', $result['summary'] );
		$this->assertArrayHasKey( 'minor', $result['summary'] );
		$this->assertArrayHasKey( 'by_type', $result['summary'] );
	}

	public function test_each_item_has_required_keys(): void {
		$service = new Industry_Drift_Report_Service( null );
		$result  = $service->generate_report();
		$this->assertIsArray( $result['items'] );
		$keys = array( 'drift_type', 'severity', 'evidence_refs', 'explanation', 'suggested_review_path' );
		foreach ( $result['items'] as $item ) {
			foreach ( $keys as $key ) {
				$this->assertArrayHasKey( $key, $item );
			}
			$this->assertContains( $item['severity'], array( Industry_Drift_Report_Service::SEVERITY_SEVERE, Industry_Drift_Report_Service::SEVERITY_MINOR ) );
			$this->assertContains( $item['drift_type'], array( Industry_Drift_Report_Service::DRIFT_TYPE_SCHEMA, Industry_Drift_Report_Service::DRIFT_TYPE_CONVENTION ) );
		}
	}

	public function test_severity_grouping_consistent_with_summary(): void {
		$service      = new Industry_Drift_Report_Service( null );
		$result       = $service->generate_report();
		$severe_count = count( $result['by_severity'][ Industry_Drift_Report_Service::SEVERITY_SEVERE ] ?? array() );
		$minor_count  = count( $result['by_severity'][ Industry_Drift_Report_Service::SEVERITY_MINOR ] ?? array() );
		$this->assertSame( $result['summary']['severe'], $severe_count );
		$this->assertSame( $result['summary']['minor'], $minor_count );
		$this->assertSame( count( $result['items'] ), $result['summary']['total'] );
	}
}
