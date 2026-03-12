<?php
/**
 * Unit tests for Queue & Logs screen and state builders (spec §49.11, Prompt 095).
 *
 * Covers slug, capability, tab keys, state shapes, reporting health summary shape.
 * Example queue tab payload and reporting-health summary payload in test methods.
 *
 * Manual verification checklist (spec §49.11):
 * - [ ] Queue & Logs submenu appears under AIO Page Builder; capability aio_view_logs.
 * - [ ] Queue tab: table shows job_ref, type, status, created_at, completed_at, failure_reason; "Open plan" link when related_plan_id present.
 * - [ ] Execution Logs tab: same columns; row-to-plan link.
 * - [ ] AI Runs tab: run_id, status, created_at; "View details" links to AI Runs screen; "Open AI Runs" button.
 * - [ ] Reporting Logs tab: event_type, attempted_at, delivery_status, log_reference, failure_reason; status badges.
 * - [ ] Import/Export Logs tab: placeholder "No import/export log entries yet" or table when data exists.
 * - [ ] Critical Errors tab: failed developer_error_report entries; status badges.
 * - [ ] Reporting health card: summary_message, last_heartbeat_month, recent_failures_count when degraded.
 * - [ ] Without aio_view_logs: menu item not visible or screen shows no content.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Logs\Queue_Logs_Screen;
use AIOPageBuilder\Domain\Reporting\UI\Logs_Monitoring_State_Builder;
use AIOPageBuilder\Domain\Reporting\UI\Reporting_Health_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Reporting_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Reporting/UI/Logs_Monitoring_State_Builder.php';
require_once $plugin_root . '/src/Admin/Screens/Logs/Queue_Logs_Screen.php';

final class Queue_Logs_Screen_Test extends TestCase {

	public function test_screen_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder-queue-logs', Queue_Logs_Screen::SLUG );
	}

	public function test_screen_capability_is_view_logs(): void {
		$screen = new Queue_Logs_Screen();
		$this->assertSame( Capabilities::VIEW_LOGS, $screen->get_capability() );
	}

	public function test_screen_has_title(): void {
		$screen = new Queue_Logs_Screen();
		$this->assertNotEmpty( $screen->get_title() );
	}

	public function test_state_builder_returns_all_tab_keys(): void {
		$builder = new Logs_Monitoring_State_Builder( null, null );
		$state   = $builder->build();
		$this->assertArrayHasKey( 'queue', $state );
		$this->assertArrayHasKey( 'execution_logs', $state );
		$this->assertArrayHasKey( 'ai_runs', $state );
		$this->assertArrayHasKey( 'reporting_logs', $state );
		$this->assertArrayHasKey( 'import_export_logs', $state );
		$this->assertArrayHasKey( 'critical_errors', $state );
		$this->assertIsArray( $state['queue'] );
		$this->assertIsArray( $state['reporting_logs'] );
	}

	public function test_reporting_health_summary_returns_expected_keys(): void {
		$builder = new Reporting_Health_Summary_Builder();
		$summary = $builder->build();
		$this->assertArrayHasKey( 'recent_failures_count', $summary );
		$this->assertArrayHasKey( 'last_heartbeat_month', $summary );
		$this->assertArrayHasKey( 'reporting_degraded', $summary );
		$this->assertArrayHasKey( 'summary_message', $summary );
		$this->assertArrayHasKey( 'failed_events_by_type', $summary );
		$this->assertIsInt( $summary['recent_failures_count'] );
		$this->assertIsBool( $summary['reporting_degraded'] );
	}

	/**
	 * Example queue tab payload (one row shape) per prompt.
	 */
	public function test_example_queue_tab_payload_shape(): void {
		$example = array(
			'job_ref'         => 'job-uuid-1',
			'job_type'        => 'replace_page',
			'queue_status'    => 'completed',
			'created_at'      => '2025-03-15 10:00:00',
			'completed_at'    => '2025-03-15 10:01:00',
			'failure_reason'  => '',
			'related_plan_id' => 'plan-abc-123',
		);
		$this->assertArrayHasKey( 'job_ref', $example );
		$this->assertArrayHasKey( 'queue_status', $example );
		$this->assertArrayHasKey( 'related_plan_id', $example );
		$this->assertSame( 'completed', $example['queue_status'] );
	}

	/**
	 * Example reporting-health summary payload per prompt.
	 */
	public function test_example_reporting_health_summary_payload(): void {
		$builder = new Reporting_Health_Summary_Builder();
		$example = $builder->build();
		$this->assertArrayHasKey( 'recent_failures_count', $example );
		$this->assertArrayHasKey( 'last_heartbeat_month', $example );
		$this->assertArrayHasKey( 'reporting_degraded', $example );
		$this->assertArrayHasKey( 'summary_message', $example );
		$this->assertIsArray( $example['failed_events_by_type'] );
	}
}
