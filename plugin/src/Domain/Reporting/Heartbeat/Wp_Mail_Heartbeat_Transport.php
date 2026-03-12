<?php
/**
 * wp_mail-based transport for heartbeat (spec §46.5).
 *
 * Subject: Heart beat - [website address] - [status]. No secrets in body.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

/**
 * Sends heartbeat via wp_mail to the approved support address.
 */
final class Wp_Mail_Heartbeat_Transport implements Heartbeat_Transport_Interface {

	private const DESTINATION_EMAIL = 'AIOpagebuilder@steadyhandmarketing.com';

	/** @var array<string> Valid status values for subject. */
	private const STATUS_VALUES = array( 'healthy', 'warning', 'degraded', 'critical' );

	public function send( array $envelope ): array {
		$payload  = isset( $envelope['payload'] ) && is_array( $envelope['payload'] ) ? $envelope['payload'] : array();
		$website  = isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '';
		$status   = isset( $payload['current_health_summary'] ) ? (string) $payload['current_health_summary'] : 'healthy';
		if ( ! in_array( $status, self::STATUS_VALUES, true ) ) {
			$status = 'healthy';
		}
		$subject  = sprintf(
			/* translators: 1: website address, 2: status (healthy, warning, degraded, critical) */
			__( 'Heart beat - %1$s - %2$s', 'aio-page-builder' ),
			$website !== '' ? $website : __( 'Unknown site', 'aio-page-builder' ),
			$status
		);
		$body = $this->build_body( $payload );
		$sent = \wp_mail( self::DESTINATION_EMAIL, $subject, $body );
		if ( $sent ) {
			return array( 'success' => true, 'failure_reason' => '' );
		}
		return array(
			'success'        => false,
			'failure_reason' => __( 'Email delivery failed.', 'aio-page-builder' ),
		);
	}

	/** @param array<string, mixed> $payload */
	private function build_body( array $payload ): string {
		$lines = array(
			'website_address: ' . ( isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '' ),
			'plugin_version: ' . ( isset( $payload['plugin_version'] ) ? (string) $payload['plugin_version'] : '' ),
			'wordpress_version: ' . ( isset( $payload['wordpress_version'] ) ? (string) $payload['wordpress_version'] : '' ),
			'php_version: ' . ( isset( $payload['php_version'] ) ? (string) $payload['php_version'] : '' ),
			'admin_contact_email: ' . ( isset( $payload['admin_contact_email'] ) ? (string) $payload['admin_contact_email'] : '' ),
			'server_ip: ' . ( isset( $payload['server_ip'] ) ? (string) $payload['server_ip'] : '' ),
			'last_successful_ai_run_at: ' . ( isset( $payload['last_successful_ai_run_at'] ) ? (string) $payload['last_successful_ai_run_at'] : '' ),
			'last_successful_build_plan_execution_at: ' . ( isset( $payload['last_successful_build_plan_execution_at'] ) ? (string) $payload['last_successful_build_plan_execution_at'] : '' ),
			'current_health_summary: ' . ( isset( $payload['current_health_summary'] ) ? (string) $payload['current_health_summary'] : '' ),
			'current_queue_warning_count: ' . ( isset( $payload['current_queue_warning_count'] ) ? (int) $payload['current_queue_warning_count'] : 0 ),
			'current_unresolved_critical_error_count: ' . ( isset( $payload['current_unresolved_critical_error_count'] ) ? (int) $payload['current_unresolved_critical_error_count'] : 0 ),
			'timestamp: ' . ( isset( $payload['timestamp'] ) ? (string) $payload['timestamp'] : '' ),
		);
		return implode( "\n", $lines );
	}
}
