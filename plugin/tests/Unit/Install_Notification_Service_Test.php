<?php
/**
 * Unit tests for install notification (spec §46.2, §46.3, §46.10; Prompt 092).
 *
 * Covers first successful send, duplicate suppression, domain-change resend,
 * failed-send logging, non-blocking behavior.
 *
 * Example install-notification result payload (success):
 *   [ 'eligible' => true, 'attempted' => true, 'delivery_status' => 'sent', 'dedupe_state' => 'sent', 'log_reference' => 'report_install_abc123', 'failure_reason' => '' ]
 *
 * Example failed-delivery log summary payload (one entry in REPORTING_LOG option):
 *   [ 'event_type' => 'install_notification', 'dedupe_key' => 'install_example.local_2025-03-15T12:00:00Z', 'attempted_at' => '2025-03-15T12:00:00Z', 'delivery_status' => 'failed', 'log_reference' => 'report_install_xyz', 'failure_reason' => 'Email delivery failed.' ]
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Result;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Service;
use AIOPageBuilder\Domain\Reporting\Install\Install_Notification_Transport_Interface;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Reporting/Install/Install_Notification_Result.php';
require_once $plugin_root . '/src/Domain/Reporting/Install/Install_Notification_Transport_Interface.php';
require_once $plugin_root . '/src/Domain/Reporting/Install/Install_Notification_Service.php';

/**
 * Stub transport: configurable success/failure for tests.
 */
final class Stub_Install_Transport implements Install_Notification_Transport_Interface {

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

final class Install_Notification_Service_Test extends TestCase {

	private function clear_install_state(): void {
		if ( isset( $GLOBALS['_aio_test_options'] ) && is_array( $GLOBALS['_aio_test_options'] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::INSTALL_NOTICE_STATE ] );
			unset( $GLOBALS['_aio_test_options'][ Option_Names::REPORTING_LOG ] );
		}
		if ( function_exists( 'delete_option' ) ) {
			\delete_option( Option_Names::INSTALL_NOTICE_STATE );
			\delete_option( Option_Names::REPORTING_LOG );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_install_state();
	}

	protected function tearDown(): void {
		$this->clear_install_state();
		parent::tearDown();
	}

	public function test_first_successful_activation_sends(): void {
		$transport          = new Stub_Install_Transport();
		$transport->success = true;
		$service            = new Install_Notification_Service( $transport );
		$result             = $service->maybe_send( 'all ready', 'test-site.local' );

		$this->assertTrue( $result->is_eligible() );
		$this->assertTrue( $result->was_attempted() );
		$this->assertSame( 'sent', $result->get_delivery_status() );
		$this->assertSame( Install_Notification_Result::DEDUPE_SENT, $result->get_dedupe_state() );
		$this->assertNotSame( '', $result->get_log_reference() );

		$state = \get_option( Option_Names::INSTALL_NOTICE_STATE );
		$this->assertIsArray( $state );
		$this->assertSame( 'test-site.local', $state['site_reference'] ?? '' );
		$this->assertArrayHasKey( 'dedupe_key', $state );
	}

	public function test_duplicate_suppression_after_send(): void {
		$transport = new Stub_Install_Transport();
		$service   = new Install_Notification_Service( $transport );
		$service->maybe_send( 'all ready', 'same-site.local' );
		$result2 = $service->maybe_send( 'all ready', 'same-site.local' );

		$this->assertFalse( $result2->is_eligible() );
		$this->assertFalse( $result2->was_attempted() );
		$this->assertSame( 'skipped', $result2->get_delivery_status() );
		$this->assertSame( Install_Notification_Result::DEDUPE_ALREADY_SENT, $result2->get_dedupe_state() );
	}

	public function test_domain_change_allows_resend(): void {
		$transport = new Stub_Install_Transport();
		$service   = new Install_Notification_Service( $transport );
		$service->maybe_send( 'all ready', 'old-domain.local' );
		$result2 = $service->maybe_send( 'all ready', 'new-domain.local' );

		$this->assertTrue( $result2->is_eligible() );
		$this->assertTrue( $result2->was_attempted() );
		$this->assertSame( 'sent', $result2->get_delivery_status() );
		$state = \get_option( Option_Names::INSTALL_NOTICE_STATE );
		$this->assertSame( 'new-domain.local', $state['site_reference'] ?? '' );
	}

	public function test_failed_send_logs_and_returns_failed_result(): void {
		$transport                 = new Stub_Install_Transport();
		$transport->success        = false;
		$transport->failure_reason = 'SMTP error.';
		$service                   = new Install_Notification_Service( $transport );
		$result                    = $service->maybe_send( 'all ready', 'test.local' );

		$this->assertTrue( $result->was_attempted() );
		$this->assertSame( 'failed', $result->get_delivery_status() );
		$this->assertSame( Install_Notification_Result::DEDUPE_FAILED, $result->get_dedupe_state() );
		$this->assertStringContainsString( 'SMTP', $result->get_failure_reason() );

		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		$this->assertIsArray( $log );
		$this->assertNotEmpty( $log );
		$last = end( $log );
		$this->assertSame( 'failed', $last['delivery_status'] );
		$this->assertSame( 'install_notification', $last['event_type'] );
	}

	public function test_activation_never_blocks_on_failure(): void {
		$transport          = new Stub_Install_Transport();
		$transport->success = false;
		$service            = new Install_Notification_Service( $transport );
		$result             = $service->maybe_send( 'all ready', 'test.local' );

		$this->assertTrue( $result->was_attempted() );
		$this->assertSame( 'failed', $result->get_delivery_status() );
		// * Result is returned; caller (Lifecycle_Manager) must not treat as blocking.
		$this->assertFalse( $result->is_eligible() === false && $result->get_dedupe_state() === 'skipped' );
	}

	public function test_skipped_when_site_reference_empty(): void {
		$transport = new Stub_Install_Transport();
		$service   = new Install_Notification_Service( $transport );
		$result    = $service->maybe_send( 'all ready', '' );

		$this->assertFalse( $result->is_eligible() );
		$this->assertFalse( $result->was_attempted() );
		$this->assertSame( Install_Notification_Result::DEDUPE_SKIPPED, $result->get_dedupe_state() );
	}

	public function test_result_to_array_shape(): void {
		$result = Install_Notification_Result::sent( 'log-123' );
		$arr    = $result->to_array();
		$this->assertArrayHasKey( 'eligible', $arr );
		$this->assertArrayHasKey( 'attempted', $arr );
		$this->assertArrayHasKey( 'delivery_status', $arr );
		$this->assertArrayHasKey( 'dedupe_state', $arr );
		$this->assertArrayHasKey( 'log_reference', $arr );
		$this->assertArrayHasKey( 'failure_reason', $arr );
		$this->assertSame( 'log-123', $arr['log_reference'] );
	}

	/**
	 * Example install-notification result payload (success) per prompt.
	 */
	public function test_example_install_notification_result_payload(): void {
		$result  = Install_Notification_Result::sent( 'report_install_abc123' );
		$example = $result->to_array();
		$this->assertSame(
			array(
				'eligible'        => true,
				'attempted'       => true,
				'delivery_status' => 'sent',
				'dedupe_state'    => 'sent',
				'log_reference'   => 'report_install_abc123',
				'failure_reason'  => '',
			),
			$example
		);
	}

	/**
	 * Example failed-delivery log summary payload per prompt.
	 */
	public function test_example_failed_delivery_log_summary_payload(): void {
		$transport                 = new Stub_Install_Transport();
		$transport->success        = false;
		$transport->failure_reason = 'Email delivery failed.';
		$service                   = new Install_Notification_Service( $transport );
		$service->maybe_send( 'all ready', 'example.local' );
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		$this->assertNotEmpty( $log );
		$failed_entry = end( $log );
		$example      = array(
			'event_type'      => $failed_entry['event_type'],
			'dedupe_key'      => $failed_entry['dedupe_key'],
			'attempted_at'    => $failed_entry['attempted_at'],
			'delivery_status' => $failed_entry['delivery_status'],
			'log_reference'   => $failed_entry['log_reference'],
			'failure_reason'  => $failed_entry['failure_reason'],
		);
		$this->assertSame( 'failed', $example['delivery_status'] );
		$this->assertSame( 'install_notification', $example['event_type'] );
		$this->assertStringContainsString( 'Email delivery', $example['failure_reason'] );
	}
}
