<?php
/**
 * Monthly heartbeat: build payload, send, dedupe per month, retry, reporting log (spec §46.4, §46.5, §46.10, §46.12).
 *
 * One successful heartbeat per site per calendar month. Failed delivery may be retried (up to 3, exponential backoff).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use AIOPageBuilder\Domain\Reporting\Payloads\Template_Library_Report_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Builds heartbeat envelope, enforces monthly dedupe, sends via transport, records state and log.
 */
final class Heartbeat_Service {

	private const REPORTING_LOG_MAX_ENTRIES = 50;
	private const MAX_RETRIES               = 3;
	/** Base delay in seconds for exponential backoff (1h, 2h, 4h). */
	private const BACKOFF_BASE_SECONDS = 3600;

	/** @var Heartbeat_Transport_Interface */
	private Heartbeat_Transport_Interface $transport;

	/** @var Heartbeat_Health_Provider_Interface */
	private Heartbeat_Health_Provider_Interface $health_provider;

	/** @var Template_Library_Report_Summary_Builder|null */
	private ?Template_Library_Report_Summary_Builder $template_library_report_summary_builder;

	public function __construct(
		?Heartbeat_Transport_Interface $transport = null,
		?Heartbeat_Health_Provider_Interface $health_provider = null,
		?Template_Library_Report_Summary_Builder $template_library_report_summary_builder = null
	) {
		$this->transport                               = $transport ?? new Wp_Mail_Heartbeat_Transport();
		$this->health_provider                         = $health_provider ?? new Default_Heartbeat_Health_Provider();
		$this->template_library_report_summary_builder = $template_library_report_summary_builder;
	}

	/**
	 * Runs heartbeat if due and not already sent this month. Handles retry with backoff. Never throws.
	 *
	 * @param string|null $site_reference_override Optional override for testing.
	 * @return Heartbeat_Result
	 */
	public function maybe_send( ?string $site_reference_override = null ): Heartbeat_Result {
		try {
			$site_ref = $site_reference_override !== null ? $site_reference_override : $this->get_site_reference();
			if ( $site_ref === '' ) {
				return Heartbeat_Result::skipped( $this->current_year_month(), \__( 'Missing site reference.', 'aio-page-builder' ) );
			}

			$current_ym = $this->current_year_month();
			$state      = $this->get_heartbeat_state();

			if ( isset( $state['last_successful_month'] ) && (string) $state['last_successful_month'] === $current_ym ) {
				return Heartbeat_Result::already_sent( $current_ym, $current_ym, (string) ( $state['log_reference'] ?? '' ) );
			}

			$for_month     = isset( $state['for_month'] ) ? (string) $state['for_month'] : '';
			$attempt_count = isset( $state['attempt_count'] ) ? (int) $state['attempt_count'] : 0;
			$last_attempt  = isset( $state['last_attempt_at'] ) ? (string) $state['last_attempt_at'] : '';

			if ( $for_month === $current_ym && $attempt_count >= self::MAX_RETRIES ) {
				return Heartbeat_Result::skipped( $current_ym, \__( 'Max retries reached for this month.', 'aio-page-builder' ) );
			}

			if ( $for_month === $current_ym && $last_attempt !== '' ) {
				$backoff_seconds = self::BACKOFF_BASE_SECONDS * ( 1 << min( $attempt_count - 1, 2 ) );
				if ( time() < strtotime( $last_attempt ) + $backoff_seconds ) {
					return Heartbeat_Result::skipped( $current_ym, \__( 'Backoff in progress.', 'aio-page-builder' ) );
				}
			}

			$timestamp  = gmdate( 'Y-m-d\TH:i:s\Z' );
			$dedupe_key = Reporting_Payload_Schema::dedupe_key_heartbeat( $site_ref, $current_ym );
			$envelope   = $this->build_envelope( $site_ref, $current_ym, $timestamp, $dedupe_key );
			$log_id     = 'report_heartbeat_' . uniqid( '', true );

			$outcome = $this->transport->send( $envelope );

			if ( ! empty( $outcome['success'] ) ) {
				$this->record_success( $current_ym, $site_ref, $log_id );
				$this->append_reporting_log( $dedupe_key, $timestamp, 'sent', $log_id, '' );
				return Heartbeat_Result::sent( $current_ym, $log_id );
			}

			$failure_reason = isset( $outcome['failure_reason'] ) ? (string) $outcome['failure_reason'] : \__( 'Email delivery failed.', 'aio-page-builder' );
			$this->record_failure( $current_ym, $attempt_count, $timestamp );
			$this->append_reporting_log( $dedupe_key, $timestamp, 'failed', $log_id, $failure_reason );
			$last_ok = isset( $state['last_successful_month'] ) ? (string) $state['last_successful_month'] : '';
			return Heartbeat_Result::failed( $current_ym, $last_ok, $failure_reason, $log_id );
		} catch ( \Throwable $e ) {
			$current_ym = $this->current_year_month();
			$log_id     = 'report_heartbeat_' . uniqid( '', true );
			$reason     = \__( 'Heartbeat error.', 'aio-page-builder' );
			$this->append_reporting_log( '', gmdate( 'Y-m-d\TH:i:s\Z' ), 'failed', $log_id, $reason );
			return Heartbeat_Result::failed( $current_ym, '', $reason, $log_id );
		}
	}

	private function current_year_month(): string {
		return gmdate( 'Y-m' );
	}

	private function get_site_reference(): string {
		if ( ! function_exists( 'home_url' ) ) {
			return '';
		}
		$url = home_url( '/', 'https' );
		$url = ( $url !== '' && $url !== false ) ? $url : home_url( '/', 'http' );
		if ( $url === '' || $url === false ) {
			return '';
		}
		$parsed = parse_url( (string) $url );
		$host   = isset( $parsed['host'] ) ? trim( (string) $parsed['host'] ) : '';
		if ( $host !== '' ) {
			return $host;
		}
		$sanitized = preg_replace( '/[^a-zA-Z0-9._-]/', '_', (string) $url );
		return $sanitized !== '' ? $sanitized : '';
	}

	/** @return array{last_successful_month?: string, for_month?: string, attempt_count?: int, last_attempt_at?: string, site_reference?: string, log_reference?: string} */
	private function get_heartbeat_state(): array {
		$raw = \get_option( Option_Names::HEARTBEAT_STATE, null );
		return is_array( $raw ) ? $raw : array();
	}

	private function record_success( string $year_month, string $site_ref, string $log_reference ): void {
		\update_option(
			Option_Names::HEARTBEAT_STATE,
			array(
				'last_successful_month' => $year_month,
				'site_reference'        => $site_ref,
				'log_reference'         => $log_reference,
			),
			false
		);
	}

	private function record_failure( string $for_month, int $previous_attempt_count, string $attempted_at ): void {
		$state = $this->get_heartbeat_state();
		\update_option(
			Option_Names::HEARTBEAT_STATE,
			array_merge(
				$state,
				array(
					'for_month'       => $for_month,
					'attempt_count'   => $previous_attempt_count + 1,
					'last_attempt_at' => $attempted_at,
				)
			),
			false
		);
	}

	/** @return array<string, mixed> */
	private function build_envelope( string $site_ref, string $year_month, string $timestamp, string $dedupe_key ): array {
		$health  = $this->health_provider->get_health_data();
		$website = function_exists( 'home_url' ) ? (string) home_url( '/', 'https' ) : '';
		if ( $website === '' && function_exists( 'home_url' ) ) {
			$website = (string) home_url( '/', 'http' );
		}
		$wp_version  = isset( $GLOBALS['wp_version'] ) ? (string) $GLOBALS['wp_version'] : '';
		$admin_email = \get_option( 'admin_email', '' );
		$server_ip   = '';
		if ( isset( $_SERVER['SERVER_ADDR'] ) && is_string( $_SERVER['SERVER_ADDR'] ) ) {
			$server_ip = sanitize_text_field( $_SERVER['SERVER_ADDR'] );
		}

		$payload = array(
			'website_address'                         => $website !== '' ? $website : $site_ref,
			'plugin_version'                          => Versions::plugin(),
			'wordpress_version'                       => $wp_version,
			'php_version'                             => PHP_VERSION,
			'admin_contact_email'                     => is_string( $admin_email ) ? $admin_email : '',
			'server_ip'                               => $server_ip,
			'last_successful_ai_run_at'               => $health['last_successful_ai_run_at'] ?? '',
			'last_successful_build_plan_execution_at' => $health['last_successful_build_plan_execution_at'] ?? '',
			'current_health_summary'                  => $health['current_health_summary'] ?? 'healthy',
			'current_queue_warning_count'             => (int) ( $health['current_queue_warning_count'] ?? 0 ),
			'current_unresolved_critical_error_count' => (int) ( $health['current_unresolved_critical_error_count'] ?? 0 ),
			'timestamp'                               => $timestamp,
		);
		if ( $this->template_library_report_summary_builder !== null ) {
			$payload['template_library_report_summary'] = $this->template_library_report_summary_builder->build();
		}

		return array(
			'schema_version' => Reporting_Payload_Schema::SCHEMA_VERSION,
			'event_type'     => Reporting_Event_Types::HEARTBEAT,
			'site_reference' => $site_ref,
			'plugin_version' => Versions::plugin(),
			'timestamp'      => $timestamp,
			'dedupe_key'     => $dedupe_key,
			'payload'        => $payload,
		);
	}

	private function append_reporting_log( string $dedupe_key, string $attempted_at, string $delivery_status, string $log_reference, string $failure_reason ): void {
		$entry = array(
			'event_type'      => Reporting_Event_Types::HEARTBEAT,
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
