<?php
/**
 * Reporting payload schema constants and validation helpers (spec §45, §46, reporting-payload-contract.md).
 *
 * Declarative, version-aware. Used to validate payload shape and build dedupe keys.
 * Does not send or queue reports.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Payload contract version and required/prohibited field definitions.
 */
final class Reporting_Payload_Schema {

	/** Current payload contract schema version (reporting-payload-contract.md). */
	public const SCHEMA_VERSION = '1.0';

	/** Envelope: required root keys. */
	public const ENVELOPE_KEYS = array(
		'schema_version',
		'event_type',
		'site_reference',
		'plugin_version',
		'timestamp',
		'dedupe_key',
		'payload',
	);

	/** Fields that must never appear in any report payload (spec §46.9). */
	public const PROHIBITED_KEYS = array(
		'password',
		'api_key',
		'bearer_token',
		'auth_cookie',
		'nonce',
		'database_credentials',
		'raw_ai_payload',
		'session_id',
		'secret',
	);

	/** Optional payload key for template-library health context (Prompt 214). When present, value is template_library_report_summary structure. */
	public const OPTIONAL_TEMPLATE_LIBRARY_REPORT_SUMMARY_KEY = 'template_library_report_summary';

	/** Required payload body keys per event type (payload only, not envelope). */
	private const INSTALL_PAYLOAD_KEYS = array(
		'website_address',
		'plugin_version',
		'wordpress_version',
		'php_version',
		'admin_contact_email',
		'timestamp',
		'dependency_readiness_summary',
	);

	private const HEARTBEAT_PAYLOAD_KEYS = array(
		'website_address',
		'plugin_version',
		'wordpress_version',
		'php_version',
		'admin_contact_email',
		'last_successful_ai_run_at',
		'last_successful_build_plan_execution_at',
		'current_health_summary',
		'current_queue_warning_count',
		'current_unresolved_critical_error_count',
		'timestamp',
	);

	private const DEVELOPER_ERROR_PAYLOAD_KEYS = array(
		'severity',
		'category',
		'sanitized_error_summary',
		'expected_behavior',
		'actual_behavior',
		'website_address',
		'plugin_version',
		'wordpress_version',
		'php_version',
		'admin_contact_email',
		'timestamp',
		'log_reference',
		'related_plan_id',
		'related_job_id',
		'related_run_id',
	);

	/**
	 * Returns required payload (body) keys for the given event type.
	 *
	 * @param string $event_type One of Reporting_Event_Types.
	 * @return list<string>
	 */
	public static function get_required_payload_keys( string $event_type ): array {
		switch ( $event_type ) {
			case Reporting_Event_Types::INSTALL_NOTIFICATION:
				return self::INSTALL_PAYLOAD_KEYS;
			case Reporting_Event_Types::HEARTBEAT:
				return self::HEARTBEAT_PAYLOAD_KEYS;
			case Reporting_Event_Types::DEVELOPER_ERROR_REPORT:
				return self::DEVELOPER_ERROR_PAYLOAD_KEYS;
			default:
				return array();
		}
	}

	/**
	 * Checks that envelope has all required root keys.
	 *
	 * @param array<string, mixed> $envelope Assumed to be the full report envelope.
	 * @return bool True if all ENVELOPE_KEYS are present.
	 */
	public static function envelope_has_required_keys( array $envelope ): bool {
		foreach ( self::ENVELOPE_KEYS as $key ) {
			if ( ! array_key_exists( $key, $envelope ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks that payload (body) has all required keys for the given event type.
	 *
	 * @param array<string, mixed> $payload Event payload only (envelope['payload']).
	 * @param string               $event_type One of Reporting_Event_Types.
	 * @return bool True if all required keys are present.
	 */
	public static function payload_has_required_keys( array $payload, string $event_type ): bool {
		$required = self::get_required_payload_keys( $event_type );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $payload ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Checks that array does not contain any prohibited keys (recursive one level for nested objects).
	 *
	 * @param array<string, mixed> $data Payload or envelope to check.
	 * @param bool                 $recursive If true, check one level of nested arrays for prohibited keys.
	 * @return bool True if no prohibited key is present.
	 */
	public static function has_no_prohibited_keys( array $data, bool $recursive = true ): bool {
		foreach ( array_keys( $data ) as $key ) {
			$lower = strtolower( (string) $key );
			foreach ( self::PROHIBITED_KEYS as $prohibited ) {
				if ( str_contains( $lower, $prohibited ) || $lower === $prohibited ) {
					return false;
				}
			}
			if ( $recursive && is_array( $data[ $key ] ) ) {
				if ( ! self::has_no_prohibited_keys( $data[ $key ], false ) ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Builds dedupe key for install notification (contract §6.1).
	 *
	 * @param string $site_reference Site reference (e.g. domain).
	 * @param string $activation_timestamp ISO 8601 or equivalent for first activation.
	 * @return string
	 */
	public static function dedupe_key_install( string $site_reference, string $activation_timestamp ): string {
		$site = preg_replace( '/[^a-zA-Z0-9._-]/', '_', $site_reference );
		$ts   = preg_replace( '/[^0-9TZ:-]/', '', $activation_timestamp );
		return 'install_' . $site . '_' . $ts;
	}

	/**
	 * Builds dedupe key for heartbeat (contract §6.1). One per site per calendar month.
	 *
	 * @param string $site_reference Site reference.
	 * @param string $year_month YYYY-MM.
	 * @return string
	 */
	public static function dedupe_key_heartbeat( string $site_reference, string $year_month ): string {
		$site = preg_replace( '/[^a-zA-Z0-9._-]/', '_', $site_reference );
		$ym   = preg_replace( '/[^0-9-]/', '', $year_month );
		return 'heartbeat_' . $site . '_' . $ym;
	}

	/**
	 * Builds dedupe key for developer error report (contract §6.1).
	 *
	 * @param string $log_id Local error record ID.
	 * @return string
	 */
	public static function dedupe_key_error_by_log_id( string $log_id ): string {
		$safe = preg_replace( '/[^a-zA-Z0-9_-]/', '_', $log_id );
		return 'error_' . $safe;
	}

	/**
	 * Builds dedupe key for developer error by signature and date (e.g. same error 24h window).
	 *
	 * @param string $category Error category.
	 * @param string $signature Sanitized signature (e.g. hash of sanitized message).
	 * @param string $date YYYY-MM-DD.
	 * @return string
	 */
	public static function dedupe_key_error_by_signature( string $category, string $signature, string $date ): string {
		$cat = preg_replace( '/[^a-z_]/', '', $category );
		$sig = substr( preg_replace( '/[^a-zA-Z0-9]/', '', $signature ), 0, 32 );
		$d   = preg_replace( '/[^0-9-]/', '', $date );
		return 'error_' . $cat . '_' . $sig . '_' . $d;
	}
}
