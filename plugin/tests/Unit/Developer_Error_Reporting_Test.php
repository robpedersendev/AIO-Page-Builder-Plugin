<?php
/**
 * Unit tests for developer error reporting (spec §45.7–45.9, §46.6–46.12; Prompt 094).
 *
 * Covers critical immediate reporting, repeated-error threshold, warning-only local logging,
 * secret redaction, failed-send retry handling, and dedupe suppression.
 *
 * Example eligible redacted report payload (envelope.payload shape):
 *   [ 'severity' => 'critical', 'category' => 'execution', 'sanitized_error_summary' => 'Build Plan finalization failed at publish stage.', 'expected_behavior' => '...', 'actual_behavior' => '...', 'website_address' => '...', 'log_reference' => [ 'log_id' => 'err-a1b2c3', 'log_category' => 'execution', 'log_severity' => 'critical' ], ... ]
 *
 * Example ineligible/local-only evaluation result (Developer_Report_Result::to_array):
 *   [ 'report_eligible' => false, 'threshold_reason' => 'local log only (info)', 'dedupe_key' => 'error_err_xyz', 'redaction_applied' => false, 'delivery_status' => 'skipped', 'report_log_reference' => '', 'failure_reason' => '' ]
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Errors\Developer_Report_Result;
use AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Reporting_Service;
use AIOPageBuilder\Domain\Reporting\Errors\Developer_Error_Transport_Interface;
use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Eligibility_Evaluator;
use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Log_Severities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';
require_once $plugin_root . '/src/Support/Logging/Error_Record.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Payload_Schema.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Developer_Report_Result.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Reporting_Redaction_Service.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Reporting_Eligibility_Evaluator.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Developer_Error_Transport_Interface.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Wp_Mail_Developer_Error_Transport.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Developer_Error_Reporting_Service.php';

/**
 * Stub transport for developer error report tests.
 */
final class Stub_Developer_Error_Transport implements Developer_Error_Transport_Interface {

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

final class Developer_Error_Reporting_Test extends TestCase {

	private function clear_error_report_state(): void {
		if ( isset( $GLOBALS['_aio_test_options'] ) && is_array( $GLOBALS['_aio_test_options'] ) ) {
			unset( $GLOBALS['_aio_test_options'][ Option_Names::ERROR_REPORT_STATE ] );
			unset( $GLOBALS['_aio_test_options'][ Option_Names::REPORTING_LOG ] );
		}
		if ( function_exists( 'delete_option' ) ) {
			\delete_option( Option_Names::ERROR_REPORT_STATE );
			\delete_option( Option_Names::REPORTING_LOG );
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->clear_error_report_state();
	}

	protected function tearDown(): void {
		$this->clear_error_report_state();
		parent::tearDown();
	}

	private function make_record( string $id, string $severity, string $category, string $message ): Error_Record {
		return new Error_Record( $id, $category, $severity, $message );
	}

	public function test_critical_immediate_reporting(): void {
		$transport          = new Stub_Developer_Error_Transport();
		$transport->success = true;
		$service            = new Developer_Error_Reporting_Service( null, null, $transport );
		$record             = $this->make_record( 'err-critical-1', Log_Severities::CRITICAL, Log_Categories::EXECUTION, 'Build Plan finalization failed at publish stage.' );

		$result = $service->maybe_report( $record, array( 'site_reference_override' => 'test.local' ) );

		$this->assertTrue( $result->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_SENT, $result->get_delivery_status() );
		$this->assertNotSame( '', $result->get_dedupe_key() );
		$this->assertTrue( $result->was_redaction_applied() );
	}

	public function test_repeated_error_threshold_triggering(): void {
		$transport = new Stub_Developer_Error_Transport();
		$service   = new Developer_Error_Reporting_Service( null, null, $transport );
		$record    = $this->make_record( 'err-repeated-1', Log_Severities::ERROR, Log_Categories::QUEUE, 'Queue job failed.' );

		$result = $service->maybe_report(
			$record,
			array(
				'site_reference_override' => 'test.local',
				'repetition_count_24h'    => 3,
			)
		);

		$this->assertTrue( $result->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_SENT, $result->get_delivery_status() );
	}

	public function test_warning_only_local_logging(): void {
		$transport = new Stub_Developer_Error_Transport();
		$service   = new Developer_Error_Reporting_Service( null, null, $transport );
		$record    = $this->make_record( 'err-warn-1', Log_Severities::WARNING, Log_Categories::VALIDATION, 'Validation warning.' );

		$result = $service->maybe_report(
			$record,
			array(
				'site_reference_override' => 'test.local',
				'repetition_count_24h'    => 2,
			)
		);

		$this->assertFalse( $result->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_SKIPPED, $result->get_delivery_status() );
		$this->assertStringContainsString( 'local log only', $result->get_threshold_reason() );
	}

	public function test_secret_redaction(): void {
		$redaction = new Reporting_Redaction_Service();
		$message   = 'Connection failed: password=secret123 and api_key=sk-abc.';
		$out       = $redaction->redact_message( $message );

		$this->assertStringNotContainsString( 'secret123', $out );
		$this->assertStringNotContainsString( 'sk-abc', $out );
		$this->assertStringContainsString( '[redacted]', $out );
	}

	public function test_failed_send_retry_state(): void {
		$transport                 = new Stub_Developer_Error_Transport();
		$transport->success        = false;
		$transport->failure_reason = 'SMTP error.';
		$service                   = new Developer_Error_Reporting_Service( null, null, $transport );
		$record                    = $this->make_record( 'err-fail-1', Log_Severities::CRITICAL, Log_Categories::EXECUTION, 'Critical failure.' );

		$result = $service->maybe_report( $record, array( 'site_reference_override' => 'test.local' ) );

		$this->assertTrue( $result->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_FAILED, $result->get_delivery_status() );
		$this->assertStringContainsString( 'SMTP', $result->get_failure_reason() );

		$state = \get_option( Option_Names::ERROR_REPORT_STATE );
		$this->assertIsArray( $state );
		$this->assertArrayHasKey( 'retry_for_dedupe_key', $state );
		$this->assertSame( 1, (int) ( $state['retry_attempt_count'] ?? 0 ) );
	}

	public function test_dedupe_suppression(): void {
		$transport = new Stub_Developer_Error_Transport();
		$service   = new Developer_Error_Reporting_Service( null, null, $transport );
		$record    = $this->make_record( 'err-dedupe-1', Log_Severities::CRITICAL, Log_Categories::EXECUTION, 'Same error.' );

		$result1 = $service->maybe_report( $record, array( 'site_reference_override' => 'test.local' ) );
		$result2 = $service->maybe_report( $record, array( 'site_reference_override' => 'test.local' ) );

		$this->assertTrue( $result1->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_SENT, $result1->get_delivery_status() );
		$this->assertTrue( $result2->is_report_eligible() );
		$this->assertSame( Developer_Report_Result::DELIVERY_SKIPPED, $result2->get_delivery_status() );
		$this->assertStringContainsString( 'dedupe', $result2->get_threshold_reason() );
	}

	public function test_ineligible_info_severity(): void {
		$evaluator = new Reporting_Eligibility_Evaluator();
		$out       = $evaluator->evaluate( Log_Severities::INFO, Log_Categories::EXECUTION, 0, '' );

		$this->assertFalse( $out['eligible'] );
		$this->assertStringContainsString( 'info', $out['reason'] );
	}

	/**
	 * Example eligible redacted report payload (payload shape only) per prompt.
	 */
	public function test_example_eligible_redacted_report_payload(): void {
		$captured           = new \stdClass();
		$captured->envelope = null;
		$stub               = new class( $captured ) implements Developer_Error_Transport_Interface {
			/** @var \stdClass */
			public $ref;
			public function __construct( \stdClass $ref ) {
				$this->ref = $ref;
			}
			public function send( array $envelope ): array {
				$this->ref->envelope = $envelope;
				return array(
					'success'        => true,
					'failure_reason' => '',
				);
			}
		};
		$service            = new Developer_Error_Reporting_Service( null, null, $stub );
		$record             = $this->make_record( 'err-a1b2c3', Log_Severities::CRITICAL, Log_Categories::EXECUTION, 'Build Plan finalization failed at publish stage.' );

		$service->maybe_report(
			$record,
			array(
				'site_reference_override' => 'example.com',
				'expected_behavior'       => 'Plan state transitions to finalized; changes published.',
				'actual_behavior'         => 'Publish step returned error; plan left in confirmation state.',
			)
		);

		$this->assertNotNull( $captured->envelope );
		$payload = $captured->envelope['payload'] ?? array();
		$this->assertSame( 'critical', $payload['severity'] );
		$this->assertSame( 'execution', $payload['category'] );
		$this->assertArrayHasKey( 'sanitized_error_summary', $payload );
		$this->assertArrayHasKey( 'log_reference', $payload );
		$this->assertSame( 'err-a1b2c3', $payload['log_reference']['log_id'] ?? '' );
		$this->assertArrayHasKey( 'expected_behavior', $payload );
		$this->assertArrayHasKey( 'actual_behavior', $payload );
	}

	/**
	 * Example ineligible/local-only evaluation result per prompt.
	 */
	public function test_example_ineligible_evaluation_result(): void {
		$service = new Developer_Error_Reporting_Service( null, null, new Stub_Developer_Error_Transport() );
		$record  = $this->make_record( 'err_xyz', Log_Severities::INFO, Log_Categories::VALIDATION, 'Informational message.' );

		$result = $service->maybe_report( $record, array( 'site_reference_override' => 'test.local' ) );
		$arr    = $result->to_array();

		$this->assertFalse( $arr['report_eligible'] );
		$this->assertSame( 'local log only (info)', $arr['threshold_reason'] );
		$this->assertSame( 'skipped', $arr['delivery_status'] );
		$this->assertSame( '', $arr['report_log_reference'] );
		$this->assertSame( '', $arr['failure_reason'] );
	}
}
