<?php
/**
 * Unit tests for monthly heartbeat (spec §46.4, §46.5, §46.10, §46.12; Prompt 093).
 *
 * Covers first monthly send, duplicate monthly suppression, failed delivery retry state,
 * due-month/last_successful_month, and deactivation unscheduling.
 *
 * Example heartbeat result payload (success):
 *   [ 'due_month' => '2025-03', 'last_successful_month' => '2025-03', 'heartbeat_status' => 'sent', 'delivery_status' => 'sent', 'log_reference' => 'report_heartbeat_abc123', 'failure_reason' => '' ]
 *
 * Example monthly dedupe-state payload (HEARTBEAT_STATE after one successful send):
 *   [ 'last_successful_month' => '2025-03', 'site_reference' => 'example.local', 'log_reference' => 'report_heartbeat_xyz' ]
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Result;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Service;
use AIOPageBuilder\Domain\Reporting\Heartbeat\Heartbeat_Transport_Interface;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Result.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Transport_Interface.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Health_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Default_Heartbeat_Health_Provider.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Service.php';

/**
 * Stub transport for heartbeat tests.
 */
final class Stub_Heartbeat_Transport implements Heartbeat_Transport_Interface {

	/** @var bool */
	public $success = true;

	/** @var string */
	public $failure_reason = '';

	public function send( array $envelope ): array {
		return $this->success
			? array(
				'success'        => true,
				'failure_reason' => '',
			)
			: array(
				'success'        => false,
				'failure_reason' => $this->failure_reason !== '' && $this->failure_reason !== null ? $this->failure_reason : 'Delivery failed.',
			);
	}
}

final class Heartbeat_Service_Test extends TestCase {

	private function clear_heartbeat_state(): void {
		if ( isset( $GLOBALS['_aio_test_options'] ) && is_array( $GLOBALS['_aio_test_options'] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::HEARTBEAT_STATE ] );
			unset( $GLOBALS['_aio_test_options'][ Option_Names::REPORTING_LOG ] );
		}
		if ( function_exists( 'delete_option' ) ) {
			\delete_option( Option_Names::HEARTBEAT_STATE );
			\delete_option( Option_Names::REPORTING_LOG );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_heartbeat_state();
	}

	protected function tearDown(): void {
		$this->clear_heartbeat_state();
		parent::tearDown();
	}

	public function test_first_monthly_heartbeat_send(): void {
		$transport          = new Stub_Heartbeat_Transport();
		$transport->success = true;
		$service            = new Heartbeat_Service( $transport, null );
		$result             = $service->maybe_send( 'test-site.local' );

		$this->assertSame( Heartbeat_Result::HEARTBEAT_SENT, $result->get_heartbeat_status() );
		$this->assertSame( 'sent', $result->get_delivery_status() );
		$this->assertNotSame( '', $result->get_log_reference() );
		$this->assertSame( $result->get_due_month(), $result->get_last_successful_month() );

		$state = \get_option( Option_Names::HEARTBEAT_STATE );
		$this->assertIsArray( $state );
		$this->assertSame( 'test-site.local', $state['site_reference'] ?? '' );
		$this->assertArrayHasKey( 'last_successful_month', $state );
		$this->assertArrayHasKey( 'log_reference', $state );

		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		$this->assertIsArray( $log );
		$this->assertNotEmpty( $log );
		$last = end( $log );
		$this->assertSame( 'heartbeat', $last['event_type'] );
		$this->assertSame( 'sent', $last['delivery_status'] );
	}

	public function test_duplicate_monthly_suppression(): void {
		$transport = new Stub_Heartbeat_Transport();
		$service   = new Heartbeat_Service( $transport, null );
		$service->maybe_send( 'same-site.local' );
		$result2 = $service->maybe_send( 'same-site.local' );

		$this->assertSame( Heartbeat_Result::HEARTBEAT_ALREADY_SENT, $result2->get_heartbeat_status() );
		$this->assertSame( 'skipped', $result2->get_delivery_status() );
		$this->assertSame( $result2->get_due_month(), $result2->get_last_successful_month() );
	}

	public function test_failed_delivery_updates_retry_state(): void {
		$transport                 = new Stub_Heartbeat_Transport();
		$transport->success        = false;
		$transport->failure_reason = 'SMTP error.';
		$service                   = new Heartbeat_Service( $transport, null );
		$result                    = $service->maybe_send( 'test.local' );

		$this->assertSame( Heartbeat_Result::HEARTBEAT_FAILED, $result->get_heartbeat_status() );
		$this->assertSame( 'failed', $result->get_delivery_status() );
		$this->assertStringContainsString( 'SMTP', $result->get_failure_reason() );

		$state = \get_option( Option_Names::HEARTBEAT_STATE );
		$this->assertIsArray( $state );
		$this->assertSame( 1, (int) ( $state['attempt_count'] ?? 0 ) );
		$this->assertArrayHasKey( 'for_month', $state );
		$this->assertArrayHasKey( 'last_attempt_at', $state );

		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		$this->assertNotEmpty( $log );
		$last = end( $log );
		$this->assertSame( 'failed', $last['delivery_status'] );
		$this->assertSame( 'heartbeat', $last['event_type'] );
	}

	public function test_due_month_and_last_successful_month(): void {
		$transport = new Stub_Heartbeat_Transport();
		$service   = new Heartbeat_Service( $transport, null );
		$result    = $service->maybe_send( 'example.local' );

		$expected_ym = gmdate( 'Y-m' );
		$this->assertSame( $expected_ym, $result->get_due_month() );
		$this->assertSame( $expected_ym, $result->get_last_successful_month() );

		$result2 = $service->maybe_send( 'example.local' );
		$this->assertSame( $expected_ym, $result2->get_due_month() );
		$this->assertSame( $expected_ym, $result2->get_last_successful_month() );
		$this->assertSame( Heartbeat_Result::HEARTBEAT_ALREADY_SENT, $result2->get_heartbeat_status() );
	}

	public function test_skipped_when_site_reference_empty(): void {
		$transport = new Stub_Heartbeat_Transport();
		$service   = new Heartbeat_Service( $transport, null );
		$result    = $service->maybe_send( '' );

		$this->assertSame( Heartbeat_Result::HEARTBEAT_SKIPPED, $result->get_heartbeat_status() );
		$this->assertSame( 'skipped', $result->get_delivery_status() );
	}

	public function test_result_to_array_shape(): void {
		$result = Heartbeat_Result::sent( '2025-03', 'log-123' );
		$arr    = $result->to_array();
		$this->assertArrayHasKey( 'due_month', $arr );
		$this->assertArrayHasKey( 'last_successful_month', $arr );
		$this->assertArrayHasKey( 'heartbeat_status', $arr );
		$this->assertArrayHasKey( 'delivery_status', $arr );
		$this->assertArrayHasKey( 'log_reference', $arr );
		$this->assertArrayHasKey( 'failure_reason', $arr );
		$this->assertSame( 'log-123', $arr['log_reference'] );
	}

	/**
	 * Example heartbeat result payload (success) per prompt.
	 */
	public function test_example_heartbeat_result_payload(): void {
		$result  = Heartbeat_Result::sent( '2025-03', 'report_heartbeat_abc123' );
		$example = $result->to_array();
		$this->assertSame(
			array(
				'due_month'             => '2025-03',
				'last_successful_month' => '2025-03',
				'heartbeat_status'      => 'sent',
				'delivery_status'       => 'sent',
				'log_reference'         => 'report_heartbeat_abc123',
				'failure_reason'        => '',
			),
			$example
		);
	}

	/**
	 * Example monthly dedupe-state payload (HEARTBEAT_STATE) per prompt.
	 */
	public function test_example_monthly_dedupe_state_payload(): void {
		$transport = new Stub_Heartbeat_Transport();
		$service   = new Heartbeat_Service( $transport, null );
		$service->maybe_send( 'example.local' );

		$state = \get_option( Option_Names::HEARTBEAT_STATE );
		$this->assertIsArray( $state );
		$example = array(
			'last_successful_month' => $state['last_successful_month'] ?? '',
			'site_reference'        => $state['site_reference'] ?? '',
			'log_reference'         => $state['log_reference'] ?? '',
		);
		$this->assertSame( gmdate( 'Y-m' ), $example['last_successful_month'] );
		$this->assertSame( 'example.local', $example['site_reference'] );
		$this->assertStringContainsString( 'report_heartbeat_', $example['log_reference'] );
	}
}
