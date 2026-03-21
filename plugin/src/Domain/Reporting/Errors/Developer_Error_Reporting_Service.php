<?php
/**
 * Developer error reporting: eligibility, redaction, build, send, dedupe, retry, log (spec §45.7–45.9, §46.6–46.12).
 *
 * Evaluates error events against trigger and severity thresholds, builds redacted payloads,
 * deduplicates, sends via transport, and records reporting log. Never blocks unrelated plugin behavior.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use AIOPageBuilder\Domain\Reporting\Payloads\Template_Library_Report_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;
use AIOPageBuilder\Support\Logging\Error_Record;

/**
 * Orchestrates developer error report evaluation, redaction, delivery, and logging.
 */
final class Developer_Error_Reporting_Service {

	private const REPORTING_LOG_MAX_ENTRIES = 50;
	private const MAX_RETRIES               = 3;
	private const BACKOFF_BASE_SECONDS      = 3600;
	private const SENT_DEDUPE_KEYS_MAX      = 500;

	/** @var Reporting_Eligibility_Evaluator */
	private Reporting_Eligibility_Evaluator $evaluator;

	/** @var Reporting_Redaction_Service */
	private Reporting_Redaction_Service $redaction;

	/** @var Developer_Error_Transport_Interface */
	private Developer_Error_Transport_Interface $transport;

	/** @var Template_Library_Report_Summary_Builder|null */
	private ?Template_Library_Report_Summary_Builder $template_library_report_summary_builder;

	public function __construct(
		?Reporting_Eligibility_Evaluator $evaluator = null,
		?Reporting_Redaction_Service $redaction = null,
		?Developer_Error_Transport_Interface $transport = null,
		?Template_Library_Report_Summary_Builder $template_library_report_summary_builder = null
	) {
		$this->evaluator                               = $evaluator ?? new Reporting_Eligibility_Evaluator();
		$this->redaction                               = $redaction ?? new Reporting_Redaction_Service();
		$this->transport                               = $transport ?? new Wp_Mail_Developer_Error_Transport();
		$this->template_library_report_summary_builder = $template_library_report_summary_builder;
	}

	/**
	 * Evaluates an error for reporting, and if eligible builds redacted payload, dedupes, sends, and logs.
	 * Never throws. Call from diagnostics/execution layer when a structured error is recorded.
	 *
	 * @param Error_Record $record Structured error record (message must be sanitized by caller; redaction applied again).
	 * @param array        $context Optional. repetition_count_24h (int), trigger_type (string), expected_behavior (string), actual_behavior (string), related_plan_id, related_job_id, related_run_id, site_reference_override (string).
	 * @return Developer_Report_Result
	 */
	public function maybe_report( Error_Record $record, array $context = array() ): Developer_Report_Result {
		try {
			$repetition = isset( $context['repetition_count_24h'] ) ? (int) $context['repetition_count_24h'] : 0;
			$trigger    = isset( $context['trigger_type'] ) ? (string) $context['trigger_type'] : '';

			$evaluation = $this->evaluator->evaluate( $record->severity, $record->category, $repetition, $trigger );
			if ( ! $evaluation['eligible'] ) {
				$dedupe_key = Reporting_Payload_Schema::dedupe_key_error_by_log_id( $record->id );
				return Developer_Report_Result::ineligible( $evaluation['reason'], $dedupe_key );
			}

			$summary = $this->redaction->build_sanitized_summary(
				$record->message,
				isset( $context['expected_behavior'] ) ? (string) $context['expected_behavior'] : '',
				isset( $context['actual_behavior'] ) ? (string) $context['actual_behavior'] : ''
			);
			if ( $summary === '' ) {
				return Developer_Report_Result::ineligible( 'sanitized_error_summary empty after redaction', Reporting_Payload_Schema::dedupe_key_error_by_log_id( $record->id ) );
			}

			$dedupe_key = Reporting_Payload_Schema::dedupe_key_error_by_log_id( $record->id );
			$state      = $this->get_error_report_state();

			if ( $this->was_dedupe_key_already_sent( $state, $dedupe_key ) ) {
				return Developer_Report_Result::skipped_dedupe( $dedupe_key );
			}

			$retry_for_key = isset( $state['retry_for_dedupe_key'] ) ? (string) $state['retry_for_dedupe_key'] : '';
			$attempt_count = $retry_for_key === $dedupe_key
				? (int) ( $state['retry_attempt_count'] ?? 0 )
				: 0;
			$last_attempt  = isset( $state['retry_last_attempt_at'] ) ? (string) $state['retry_last_attempt_at'] : '';
			if ( $attempt_count >= self::MAX_RETRIES ) {
				return Developer_Report_Result::ineligible( 'Max retries reached for this report.', $dedupe_key );
			}
			if ( $last_attempt !== '' ) {
				$backoff_seconds = self::BACKOFF_BASE_SECONDS * ( 1 << min( $attempt_count - 1, 2 ) );
				if ( time() < strtotime( $last_attempt ) + $backoff_seconds ) {
					return Developer_Report_Result::ineligible( 'Backoff in progress.', $dedupe_key );
				}
			}

			$site_ref  = isset( $context['site_reference_override'] ) ? (string) $context['site_reference_override'] : $this->get_site_reference();
			$timestamp = gmdate( 'Y-m-d\TH:i:s\Z' );
			$envelope  = $this->build_envelope( $record, $context, $summary, $site_ref, $timestamp, $dedupe_key );
			$log_id    = 'report_error_' . uniqid( '', true );

			$outcome = $this->transport->send( $envelope );

			if ( ! empty( $outcome['success'] ) ) {
				$this->record_sent( $dedupe_key );
				$this->clear_retry_state( $state, $dedupe_key );
				$this->append_reporting_log( $dedupe_key, $timestamp, 'sent', $log_id, '' );
				return Developer_Report_Result::eligible_sent( $dedupe_key, $log_id );
			}

			$failure_reason = (string) $outcome['failure_reason'];
			$this->record_retry_failure( $state, $dedupe_key, $attempt_count, $timestamp );
			$this->append_reporting_log( $dedupe_key, $timestamp, 'failed', $log_id, $failure_reason );
			return Developer_Report_Result::eligible_failed( $dedupe_key, $log_id, $failure_reason );
		} catch ( \Throwable $e ) {
			$dedupe_key = $record->id !== '' ? Reporting_Payload_Schema::dedupe_key_error_by_log_id( $record->id ) : '';
			$log_id     = 'report_error_' . uniqid( '', true );
			$this->append_reporting_log( $dedupe_key, gmdate( 'Y-m-d\TH:i:s\Z' ), 'failed', $log_id, \__( 'Error reporting failed.', 'aio-page-builder' ) );
			return Developer_Report_Result::ineligible( \__( 'Error reporting failed.', 'aio-page-builder' ), $dedupe_key );
		}
	}

	/** @return array{sent_dedupe_keys?: array<string>, retry_for_dedupe_key?: string, retry_attempt_count?: int, retry_last_attempt_at?: string} */
	private function get_error_report_state(): array {
		$raw = \get_option( Option_Names::ERROR_REPORT_STATE, null );
		return is_array( $raw ) ? $raw : array();
	}

	private function was_dedupe_key_already_sent( array $state, string $dedupe_key ): bool {
		$sent = is_array( $state['sent_dedupe_keys'] ?? null ) ? $state['sent_dedupe_keys'] : array();
		return in_array( $dedupe_key, $sent, true );
	}

	private function record_sent( string $dedupe_key ): void {
		$state  = $this->get_error_report_state();
		$sent   = is_array( $state['sent_dedupe_keys'] ?? null ) ? $state['sent_dedupe_keys'] : array();
		$sent[] = $dedupe_key;
		$sent   = array_slice( array_unique( $sent ), -self::SENT_DEDUPE_KEYS_MAX );
		\update_option( Option_Names::ERROR_REPORT_STATE, array_merge( $state, array( 'sent_dedupe_keys' => $sent ) ), false );
	}

	private function clear_retry_state( array $state, string $dedupe_key ): void {
		if ( ( (string) ( $state['retry_for_dedupe_key'] ?? '' ) ) !== $dedupe_key ) {
			return;
		}
		$fresh                          = $this->get_error_report_state();
		$fresh['retry_for_dedupe_key']  = '';
		$fresh['retry_attempt_count']   = 0;
		$fresh['retry_last_attempt_at'] = '';
		\update_option( Option_Names::ERROR_REPORT_STATE, $fresh, false );
	}

	private function record_retry_failure( array $state, string $dedupe_key, int $previous_attempt_count, string $attempted_at ): void {
		$state = $this->get_error_report_state();
		\update_option(
			Option_Names::ERROR_REPORT_STATE,
			array_merge(
				$state,
				array(
					'retry_for_dedupe_key'  => $dedupe_key,
					'retry_attempt_count'   => $previous_attempt_count + 1,
					'retry_last_attempt_at' => $attempted_at,
				)
			),
			false
		);
	}

	private function get_site_reference(): string {
		if ( ! function_exists( 'home_url' ) ) {
			return '';
		}
		$url = (string) home_url( '/', 'https' );
		if ( $url === '' ) {
			$url = (string) home_url( '/', 'http' );
		}
		if ( $url === '' ) {
			return '';
		}
		$parsed = parse_url( $url );
		$host   = isset( $parsed['host'] ) ? trim( (string) $parsed['host'] ) : '';
		if ( $host !== '' ) {
			return $host;
		}
		$sanitized = preg_replace( '/[^a-zA-Z0-9._-]/', '_', $url );
		return is_string( $sanitized ) ? $sanitized : '';
	}

	/**
	 * Builds report envelope and payload (contract §8.3). Redacted; no secrets.
	 *
	 * @param Error_Record $record
	 * @param array        $context
	 * @param string       $sanitized_summary
	 * @param string       $site_ref
	 * @param string       $timestamp
	 * @param string       $dedupe_key
	 * @return array<string, mixed>
	 */
	private function build_envelope( Error_Record $record, array $context, string $sanitized_summary, string $site_ref, string $timestamp, string $dedupe_key ): array {
		$website = function_exists( 'home_url' ) ? (string) home_url( '/', 'https' ) : '';
		if ( $website === '' && function_exists( 'home_url' ) ) {
			$website = (string) home_url( '/', 'http' );
		}
		$wp_version  = isset( $GLOBALS['wp_version'] ) ? (string) $GLOBALS['wp_version'] : '';
		$admin_email = \get_option( 'admin_email', '' );
		$server_ip   = '';
		if ( isset( $_SERVER['SERVER_ADDR'] ) && is_string( $_SERVER['SERVER_ADDR'] ) ) {
			$server_ip = \sanitize_text_field( \wp_unslash( $_SERVER['SERVER_ADDR'] ) );
		}

		$expected = isset( $context['expected_behavior'] ) ? $this->redaction->redact_message( (string) $context['expected_behavior'] ) : '';
		$actual   = isset( $context['actual_behavior'] ) ? $this->redaction->redact_message( (string) $context['actual_behavior'] ) : '';
		if ( strlen( $expected ) > 200 ) {
			$expected = substr( $expected, 0, 197 ) . '...';
		}
		if ( strlen( $actual ) > 200 ) {
			$actual = substr( $actual, 0, 197 ) . '...';
		}

		$payload = array(
			'severity'                => $record->severity,
			'category'                => $record->category,
			'sanitized_error_summary' => $sanitized_summary,
			'expected_behavior'       => $expected,
			'actual_behavior'         => $actual,
			'website_address'         => $website !== '' ? $website : $site_ref,
			'plugin_version'          => Versions::plugin(),
			'wordpress_version'       => $wp_version,
			'php_version'             => PHP_VERSION,
			'admin_contact_email'     => is_string( $admin_email ) ? $admin_email : '',
			'server_ip'               => $server_ip,
			'timestamp'               => $timestamp,
			'log_reference'           => array(
				'log_id'       => $record->id,
				'log_category' => $record->category,
				'log_severity' => $record->severity,
			),
			'related_plan_id'         => isset( $context['related_plan_id'] ) ? (string) $context['related_plan_id'] : '',
			'related_job_id'          => isset( $context['related_job_id'] ) ? (string) $context['related_job_id'] : '',
			'related_run_id'          => isset( $context['related_run_id'] ) ? (string) $context['related_run_id'] : '',
		);
		if ( $this->template_library_report_summary_builder !== null ) {
			$payload['template_library_report_summary'] = $this->template_library_report_summary_builder->build();
		}

		return array(
			'schema_version' => Reporting_Payload_Schema::SCHEMA_VERSION,
			'event_type'     => Reporting_Event_Types::DEVELOPER_ERROR_REPORT,
			'site_reference' => $site_ref,
			'plugin_version' => Versions::plugin(),
			'timestamp'      => $timestamp,
			'dedupe_key'     => $dedupe_key,
			'payload'        => $payload,
		);
	}

	private function append_reporting_log( string $dedupe_key, string $attempted_at, string $delivery_status, string $log_reference, string $failure_reason ): void {
		$entry = array(
			'event_type'      => Reporting_Event_Types::DEVELOPER_ERROR_REPORT,
			'dedupe_key'      => $dedupe_key,
			'attempted_at'    => $attempted_at,
			'delivery_status' => $delivery_status,
			'log_reference'   => $log_reference,
			'failure_reason'  => $failure_reason,
		);
		$log   = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = $entry;
		$log   = array_slice( $log, -self::REPORTING_LOG_MAX_ENTRIES );
		\update_option( Option_Names::REPORTING_LOG, $log, false );
	}
}
