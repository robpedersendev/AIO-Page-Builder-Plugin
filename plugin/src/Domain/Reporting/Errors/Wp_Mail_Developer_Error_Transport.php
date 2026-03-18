<?php
/**
 * wp_mail-based transport for developer error reports (spec §45.8, §46.8).
 *
 * Sends to approved destination. Subject and body use only redacted/sanitized data.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Sends developer error report via wp_mail to the approved support address.
 */
final class Wp_Mail_Developer_Error_Transport implements Developer_Error_Transport_Interface {

	/** Approved destination (spec §46.3, same as install/heartbeat). */
	private const DESTINATION_EMAIL = 'AIOpagebuilder@steadyhandmarketing.com';

	/**
	 * Sends the developer error report email. Does not throw.
	 *
	 * @param array<string, mixed> $envelope Full report envelope with payload.
	 * @return array{success: bool, failure_reason: string}
	 */
	public function send( array $envelope ): array {
		$payload  = isset( $envelope['payload'] ) && is_array( $envelope['payload'] ) ? $envelope['payload'] : array();
		$website  = isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '';
		$severity = isset( $payload['severity'] ) ? (string) $payload['severity'] : 'error';
		$category = isset( $payload['category'] ) ? (string) $payload['category'] : '';

		$subject = sprintf(
			/* translators: 1: severity, 2: website/site, 3: category */
			\__( 'Developer Error - %1$s - %2$s - %3$s', 'aio-page-builder' ),
			$severity,
			$website !== '' ? $website : \__( 'Unknown site', 'aio-page-builder' ),
			$category !== '' ? $category : 'general'
		);
		$body = $this->build_body( $payload );
		$sent = \wp_mail( self::DESTINATION_EMAIL, $subject, $body );
		if ( $sent ) {
			return array(
				'success'        => true,
				'failure_reason' => '',
			);
		}
		return array(
			'success'        => false,
			'failure_reason' => \__( 'Email delivery failed.', 'aio-page-builder' ),
		);
	}

	/**
	 * Builds plain-text body from payload (contract §8.3, §45.8).
	 *
	 * @param array<string, mixed> $payload
	 * @return string
	 */
	private function build_body( array $payload ): string {
		$log_ref = isset( $payload['log_reference'] ) && is_array( $payload['log_reference'] )
			? ( (string) ( $payload['log_reference']['log_id'] ?? '' ) )
			: ( (string) ( $payload['log_reference'] ?? '' ) );
		$lines   = array(
			'severity: ' . ( isset( $payload['severity'] ) ? (string) $payload['severity'] : '' ),
			'category: ' . ( isset( $payload['category'] ) ? (string) $payload['category'] : '' ),
			'sanitized_error_summary: ' . ( isset( $payload['sanitized_error_summary'] ) ? (string) $payload['sanitized_error_summary'] : '' ),
			'expected_behavior: ' . ( isset( $payload['expected_behavior'] ) ? (string) $payload['expected_behavior'] : '' ),
			'actual_behavior: ' . ( isset( $payload['actual_behavior'] ) ? (string) $payload['actual_behavior'] : '' ),
			'website_address: ' . ( isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '' ),
			'plugin_version: ' . ( isset( $payload['plugin_version'] ) ? (string) $payload['plugin_version'] : '' ),
			'wordpress_version: ' . ( isset( $payload['wordpress_version'] ) ? (string) $payload['wordpress_version'] : '' ),
			'php_version: ' . ( isset( $payload['php_version'] ) ? (string) $payload['php_version'] : '' ),
			'admin_contact_email: ' . ( isset( $payload['admin_contact_email'] ) ? (string) $payload['admin_contact_email'] : '' ),
			'server_ip: ' . ( isset( $payload['server_ip'] ) ? (string) $payload['server_ip'] : '' ),
			'timestamp: ' . ( isset( $payload['timestamp'] ) ? (string) $payload['timestamp'] : '' ),
			'log_reference (log_id): ' . $log_ref,
			'related_plan_id: ' . ( isset( $payload['related_plan_id'] ) ? (string) $payload['related_plan_id'] : '' ),
			'related_job_id: ' . ( isset( $payload['related_job_id'] ) ? (string) $payload['related_job_id'] : '' ),
			'related_run_id: ' . ( isset( $payload['related_run_id'] ) ? (string) $payload['related_run_id'] : '' ),
		);
		return implode( "\n", $lines );
	}
}
