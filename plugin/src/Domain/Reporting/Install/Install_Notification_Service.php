<?php
/**
 * Install notification: eligibility, payload build, send, dedupe, and reporting log (spec §46.2, §46.3, §46.10, §46.12).
 *
 * Fires only after activation preconditions pass. Duplicate suppressed except reinstall/domain change.
 * Failure to deliver must never block activation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Install;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Payload_Schema;
use AIOPageBuilder\Domain\Reporting\Payloads\Template_Library_Report_Summary_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * One-time install notification: build contract payload, send via transport, record state and log.
 */
final class Install_Notification_Service {

	private const REPORTING_LOG_MAX_ENTRIES = 50;

	/** @var Install_Notification_Transport_Interface */
	private Install_Notification_Transport_Interface $transport;

	/** @var Template_Library_Report_Summary_Builder|null */
	private ?Template_Library_Report_Summary_Builder $template_library_report_summary_builder;

	public function __construct(
		?Install_Notification_Transport_Interface $transport = null,
		?Template_Library_Report_Summary_Builder $template_library_report_summary_builder = null
	) {
		$this->transport = $transport ?? new Wp_Mail_Install_Transport();
		$this->template_library_report_summary_builder = $template_library_report_summary_builder;
	}

	/**
	 * Runs install notification if eligible: checks dedupe, builds payload, sends, records state and log.
	 * Never throws; never blocks. Call after activation preconditions pass.
	 *
	 * @param string      $dependency_readiness_summary Short summary (e.g. "all ready"); redaction-safe.
	 * @param string|null $site_reference_override      Optional override for testing (e.g. domain-change resend).
	 * @return Install_Notification_Result
	 */
	public function maybe_send( string $dependency_readiness_summary = 'all ready', ?string $site_reference_override = null ): Install_Notification_Result {
		try {
			$site_ref = $site_reference_override !== null ? $site_reference_override : $this->get_site_reference();
			if ( $site_ref === '' ) {
				return Install_Notification_Result::skipped( 'Missing site reference.' );
			}

			$state = $this->get_sent_state();
			if ( $this->should_suppress_duplicate( $state, $site_ref ) ) {
				return Install_Notification_Result::already_sent( (string) ( $state['log_reference'] ?? '' ) );
			}

			$timestamp = gmdate( 'Y-m-d\TH:i:s\Z' );
			$dedupe_key = Reporting_Payload_Schema::dedupe_key_install( $site_ref, $timestamp );
			$envelope   = $this->build_envelope( $site_ref, $timestamp, $dedupe_key, $dependency_readiness_summary );
			$log_id     = 'report_install_' . uniqid( '', true );

			$outcome = $this->transport->send( $envelope );

			if ( ! empty( $outcome['success'] ) ) {
				$this->record_sent_state( $dedupe_key, $timestamp, $site_ref, $log_id );
				$this->append_reporting_log( Reporting_Event_Types::INSTALL_NOTIFICATION, $dedupe_key, $timestamp, 'sent', $log_id, '' );
				return Install_Notification_Result::sent( $log_id );
			}

			$failure_reason = isset( $outcome['failure_reason'] ) ? (string) $outcome['failure_reason'] : __( 'Email delivery failed.', 'aio-page-builder' );
			$this->append_reporting_log( Reporting_Event_Types::INSTALL_NOTIFICATION, $dedupe_key, $timestamp, 'failed', $log_id, $failure_reason );
			return Install_Notification_Result::failed( $failure_reason, $log_id );
		} catch ( \Throwable $e ) {
			$log_id = 'report_install_' . uniqid( '', true );
			$reason = __( 'Install notification error.', 'aio-page-builder' );
			$this->append_reporting_log( Reporting_Event_Types::INSTALL_NOTIFICATION, '', gmdate( 'Y-m-d\TH:i:s\Z' ), 'failed', $log_id, $reason );
			return Install_Notification_Result::failed( $reason, $log_id );
		}
	}

	/**
	 * Returns opaque site identifier (no credentials). Domain or host from home URL.
	 */
	private function get_site_reference(): string {
		if ( ! function_exists( 'home_url' ) ) {
			return '';
		}
		$url = home_url( '/', 'https' );
		$url = $url ?: home_url( '/', 'http' );
		if ( $url === '' || $url === false ) {
			return '';
		}
		$parsed = parse_url( (string) $url );
		$host   = isset( $parsed['host'] ) ? trim( (string) $parsed['host'] ) : '';
		if ( $host !== '' ) {
			return $host;
		}
		return preg_replace( '/[^a-zA-Z0-9._-]/', '_', (string) $url ) ?: '';
	}

	/** @return array{dedupe_key?: string, site_reference?: string, sent_at?: string, log_reference?: string} */
	private function get_sent_state(): array {
		$raw = \get_option( Option_Names::INSTALL_NOTICE_STATE, null );
		if ( ! is_array( $raw ) ) {
			return array();
		}
		return $raw;
	}

	private function should_suppress_duplicate( array $state, string $current_site_ref ): bool {
		$sent_ref = isset( $state['site_reference'] ) ? (string) $state['site_reference'] : '';
		if ( $sent_ref === '' ) {
			return false;
		}
		return $sent_ref === $current_site_ref;
	}

	/**
	 * Builds contract envelope and payload (spec §2, §8.1).
	 *
	 * @param string $site_ref
	 * @param string $timestamp ISO 8601 UTC.
	 * @param string $dedupe_key
	 * @param string $dependency_readiness_summary
	 * @return array<string, mixed>
	 */
	private function build_envelope( string $site_ref, string $timestamp, string $dedupe_key, string $dependency_readiness_summary ): array {
		$website = function_exists( 'home_url' ) ? (string) home_url( '/', 'https' ) : '';
		if ( $website === '' && function_exists( 'home_url' ) ) {
			$website = (string) home_url( '/', 'http' );
		}
		$wp_version = isset( $GLOBALS['wp_version'] ) ? (string) $GLOBALS['wp_version'] : '';
		$php_version = PHP_VERSION;
		$admin_email = \get_option( 'admin_email', '' );
		$server_ip   = '';
		if ( isset( $_SERVER['SERVER_ADDR'] ) && is_string( $_SERVER['SERVER_ADDR'] ) ) {
			$server_ip = sanitize_text_field( $_SERVER['SERVER_ADDR'] );
		}

		$payload = array(
			'website_address'               => $website !== '' ? $website : $site_ref,
			'plugin_version'                => Versions::plugin(),
			'wordpress_version'             => $wp_version,
			'php_version'                   => $php_version,
			'server_ip'                     => $server_ip,
			'admin_contact_email'           => is_string( $admin_email ) ? $admin_email : '',
			'timestamp'                     => $timestamp,
			'dependency_readiness_summary'  => $dependency_readiness_summary,
		);
		if ( $this->template_library_report_summary_builder !== null ) {
			$payload['template_library_report_summary'] = $this->template_library_report_summary_builder->build();
		}

		return array(
			'schema_version' => Reporting_Payload_Schema::SCHEMA_VERSION,
			'event_type'     => Reporting_Event_Types::INSTALL_NOTIFICATION,
			'site_reference' => $site_ref,
			'plugin_version' => Versions::plugin(),
			'timestamp'      => $timestamp,
			'dedupe_key'     => $dedupe_key,
			'payload'        => $payload,
		);
	}

	private function record_sent_state( string $dedupe_key, string $sent_at, string $site_reference, string $log_reference ): void {
		\update_option( Option_Names::INSTALL_NOTICE_STATE, array(
			'dedupe_key'     => $dedupe_key,
			'sent_at'        => $sent_at,
			'site_reference' => $site_reference,
			'log_reference'  => $log_reference,
		), false );
	}

	private function append_reporting_log( string $event_type, string $dedupe_key, string $attempted_at, string $delivery_status, string $log_reference, string $failure_reason ): void {
		$entry = array(
			'event_type'      => $event_type,
			'dedupe_key'      => $dedupe_key,
			'attempted_at'    => $attempted_at,
			'delivery_status' => $delivery_status,
			'log_reference'   => $log_reference,
			'failure_reason'  => $failure_reason,
		);
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = $entry;
		$log   = array_slice( $log, -self::REPORTING_LOG_MAX_ENTRIES );
		\update_option( Option_Names::REPORTING_LOG, $log, false );
	}
}
