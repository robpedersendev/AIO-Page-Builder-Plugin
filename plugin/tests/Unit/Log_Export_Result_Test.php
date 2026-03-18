<?php
/**
 * Unit tests for Log_Export_Result: payload shape, success/failure, metadata (spec 48.10).
 *
 * Example log export result payload (no pseudocode):
 * success: true, message: "Log export completed successfully.",
 * exported_log_types: ["queue", "execution", "reporting"],
 * filter_summary: { "date_from": "2025-07-01", "date_to": "2025-07-15" },
 * redaction_applied: true,
 * export_file_reference: "aio-log-export-20250715-120000.json",
 * export_log_reference: "log-export-2025-07-15T12:00:00Z"
 *
 * Example structured exported log row (execution):
 * job_ref: "job-abc-123", job_type: "build_plan_step", queue_status: "completed",
 * created_at: "2025-07-15 10:00:00", completed_at: "2025-07-15 10:05:00",
 * failure_reason: "", related_plan_id: "42"
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Reporting/Logs/Log_Export_Result.php';

final class Log_Export_Result_Test extends TestCase {

	public function test_success_result_has_expected_payload_keys(): void {
		$r = Log_Export_Result::success(
			array( 'queue', 'reporting' ),
			array( 'date_from' => '2025-07-01' ),
			'aio-log-export-20250715-120000.json',
			'log-export-1'
		);
		$this->assertTrue( $r->is_success() );
		$p = $r->to_payload();
		$this->assertArrayHasKey( 'success', $p );
		$this->assertArrayHasKey( 'exported_log_types', $p );
		$this->assertArrayHasKey( 'filter_summary', $p );
		$this->assertArrayHasKey( 'redaction_applied', $p );
		$this->assertArrayHasKey( 'export_file_reference', $p );
		$this->assertArrayHasKey( 'export_log_reference', $p );
		$this->assertTrue( $p['redaction_applied'] );
	}

	public function test_failure_result_has_no_file_reference(): void {
		$r = Log_Export_Result::failure( 'Exports directory unavailable.', 'log-ref' );
		$this->assertFalse( $r->is_success() );
		$this->assertSame( '', $r->get_export_file_reference() );
		$this->assertSame( array(), $r->get_exported_log_types() );
		$this->assertFalse( $r->is_redaction_applied() );
	}

	public function test_filter_summary_preserved_in_payload(): void {
		$filters = array(
			'date_from' => '2025-07-01',
			'date_to'   => '2025-07-15',
			'plan_id'   => '99',
		);
		$r       = Log_Export_Result::success( array( 'execution' ), $filters, 'file.json', 'ref' );
		$this->assertSame( $filters, $r->get_filter_summary() );
		$this->assertSame( $filters, $r->to_payload()['filter_summary'] );
	}
}
