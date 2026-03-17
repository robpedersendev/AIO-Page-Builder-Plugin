<?php
/**
 * Unit tests for Industry_Override_Audit_Report_Service (Prompt 437). build_report(), bounded output.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Overrides\Industry_Build_Plan_Item_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Override_Schema;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Page_Template_Override_Service;
use AIOPageBuilder\Domain\Industry\Overrides\Industry_Section_Override_Service;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Override_Audit_Report_Service as AuditService;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Section_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Page_Template_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Override_Read_Model_Builder.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Override_Audit_Report_Service.php';

final class Industry_Override_Audit_Report_Service_Test extends TestCase {

	private function clear_options(): void {
		foreach ( array( Option_Names::INDUSTRY_SECTION_OVERRIDES, Option_Names::INDUSTRY_PAGE_TEMPLATE_OVERRIDES, Option_Names::INDUSTRY_BUILD_PLAN_ITEM_OVERRIDES ) as $key ) {
			if ( isset( $GLOBALS['_aio_test_options'][ $key ] ) ) {
				unset( $GLOBALS['_aio_test_options'][ $key ] );
			}
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_options();
	}

	protected function tearDown(): void {
		$this->clear_options();
		parent::tearDown();
	}

	public function test_build_report_returns_expected_structure(): void {
		$service = new AuditService();
		$report = $service->build_report();
		$this->assertIsArray( $report );
		$this->assertArrayHasKey( 'generated_at', $report );
		$this->assertArrayHasKey( 'total_count', $report );
		$this->assertArrayHasKey( 'by_type', $report );
		$this->assertArrayHasKey( 'by_industry_context', $report );
		$this->assertIsInt( $report['total_count'] );
		$this->assertIsArray( $report['by_type'] );
		$this->assertArrayHasKey( Industry_Override_Schema::TARGET_TYPE_SECTION, $report['by_type'] );
		$this->assertArrayHasKey( Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE, $report['by_type'] );
		$this->assertArrayHasKey( Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM, $report['by_type'] );
	}

	public function test_build_report_with_mixed_overrides_has_counts_and_bounded_items(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'sec_1', Industry_Override_Schema::STATE_ACCEPTED, 'Note' );
		$template = new Industry_Page_Template_Override_Service();
		$template->record_override( 'tpl_1', Industry_Override_Schema::STATE_REJECTED, '' );
		$plan = new Industry_Build_Plan_Item_Override_Service();
		$plan->record_override( 'plan-1', 'item-1', Industry_Override_Schema::STATE_ACCEPTED, '' );

		$service = new AuditService();
		$report = $service->build_report();
		$this->assertSame( 3, $report['total_count'] );
		$this->assertSame( 1, $report['by_type'][ Industry_Override_Schema::TARGET_TYPE_SECTION ]['count'] );
		$this->assertSame( 1, $report['by_type'][ Industry_Override_Schema::TARGET_TYPE_PAGE_TEMPLATE ]['count'] );
		$this->assertSame( 1, $report['by_type'][ Industry_Override_Schema::TARGET_TYPE_BUILD_PLAN_ITEM ]['count'] );

		$section_items = $report['by_type'][ Industry_Override_Schema::TARGET_TYPE_SECTION ]['items'];
		$this->assertCount( 1, $section_items );
		$this->assertArrayHasKey( 'target_key', $section_items[0] );
		$this->assertArrayHasKey( 'plan_id', $section_items[0] );
		$this->assertArrayHasKey( 'state', $section_items[0] );
		$this->assertArrayHasKey( 'reason_length', $section_items[0] );
		$this->assertSame( 'sec_1', $section_items[0]['target_key'] );
		$this->assertSame( 4, $section_items[0]['reason_length'] );
	}

	public function test_build_report_does_not_include_raw_reason_text(): void {
		$section = new Industry_Section_Override_Service();
		$section->record_override( 'sec_1', Industry_Override_Schema::STATE_ACCEPTED, 'Sensitive note' );

		$service = new AuditService();
		$report = $service->build_report();
		$json = \json_encode( $report );
		$this->assertIsString( $json );
		$this->assertStringNotContainsString( 'Sensitive note', $json );
		$this->assertStringContainsString( 'reason_length', $json );
	}
}
