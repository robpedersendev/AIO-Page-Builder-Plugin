<?php
/**
 * Unit tests for Industry_Asset_Aging_Report_Service (Prompt 556). Report structure and severity grouping.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Asset_Aging_Report_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Asset_Aging_Report_Service.php';

final class Industry_Asset_Aging_Report_Service_Test extends TestCase {

	public function test_generate_report_returns_required_structure(): void {
		$service = new Industry_Asset_Aging_Report_Service();
		$result  = $service->generate_report();
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'by_class', $result );
		$this->assertArrayHasKey( 'by_severity', $result );
		$this->assertArrayHasKey( 'high_impact_stale', $result );
		$this->assertArrayHasKey( 'generated_at', $result );
		$this->assertIsArray( $result['summary'] );
		$this->assertIsArray( $result['items'] );
		$this->assertIsArray( $result['by_class'] );
		$this->assertIsArray( $result['by_severity'] );
		$this->assertIsArray( $result['high_impact_stale'] );
		$this->assertArrayHasKey( 'total', $result['summary'] );
		$this->assertArrayHasKey( 'benign', $result['summary'] );
		$this->assertArrayHasKey( 'advisory', $result['summary'] );
		$this->assertArrayHasKey( 'high_impact', $result['summary'] );
		$this->assertArrayHasKey( 'by_class', $result['summary'] );
	}

	public function test_severity_grouping_uses_only_defined_bands(): void {
		$service = new Industry_Asset_Aging_Report_Service();
		$result  = $service->generate_report();
		$allowed = array(
			Industry_Asset_Aging_Report_Service::SEVERITY_BENIGN,
			Industry_Asset_Aging_Report_Service::SEVERITY_ADVISORY,
			Industry_Asset_Aging_Report_Service::SEVERITY_HIGH_IMPACT,
		);
		foreach ( array_keys( $result['by_severity'] ) as $band ) {
			$this->assertContains( $band, $allowed, "Severity band must be one of benign, advisory, high_impact: got {$band}" );
		}
	}

	public function test_each_item_has_required_keys(): void {
		$service = new Industry_Asset_Aging_Report_Service();
		$result  = $service->generate_report();
		$keys    = array(
			Industry_Asset_Aging_Report_Service::ITEM_ASSET_REF,
			Industry_Asset_Aging_Report_Service::ITEM_ASSET_CLASS,
			Industry_Asset_Aging_Report_Service::ITEM_DAYS_OLD,
			Industry_Asset_Aging_Report_Service::ITEM_AGE_SCORE,
			Industry_Asset_Aging_Report_Service::ITEM_SEVERITY,
			Industry_Asset_Aging_Report_Service::ITEM_RATIONALE,
			Industry_Asset_Aging_Report_Service::ITEM_SUGGESTED_REVIEW_PRIORITY,
		);
		foreach ( array_slice( $result['items'], 0, 5 ) as $item ) {
			foreach ( $keys as $key ) {
				$this->assertArrayHasKey( $key, $item );
			}
			$this->assertContains( $item[ Industry_Asset_Aging_Report_Service::ITEM_SEVERITY ], array( 'benign', 'advisory', 'high_impact' ) );
			$this->assertGreaterThanOrEqual( 1, $item[ Industry_Asset_Aging_Report_Service::ITEM_SUGGESTED_REVIEW_PRIORITY ] );
			$this->assertLessThanOrEqual( 5, $item[ Industry_Asset_Aging_Report_Service::ITEM_SUGGESTED_REVIEW_PRIORITY ] );
		}
	}

	public function test_summary_counts_match_items_and_groupings(): void {
		$service = new Industry_Asset_Aging_Report_Service();
		$result  = $service->generate_report();
		$this->assertSame( count( $result['items'] ), $result['summary']['total'] );
		$benign_count   = count( $result['by_severity'][ Industry_Asset_Aging_Report_Service::SEVERITY_BENIGN ] ?? array() );
		$advisory_count = count( $result['by_severity'][ Industry_Asset_Aging_Report_Service::SEVERITY_ADVISORY ] ?? array() );
		$high_count     = count( $result['high_impact_stale'] );
		$this->assertSame( $benign_count, $result['summary']['benign'] );
		$this->assertSame( $advisory_count, $result['summary']['advisory'] );
		$this->assertSame( $high_count, $result['summary']['high_impact'] );
	}
}
