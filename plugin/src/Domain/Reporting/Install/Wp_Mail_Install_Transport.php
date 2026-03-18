<?php
/**
 * wp_mail-based transport for install notification (spec §46.3).
 *
 * Sends to approved destination. No secrets in subject or body.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Sends install notification via wp_mail to the approved support address.
 */
final class Wp_Mail_Install_Transport implements Install_Notification_Transport_Interface {

	/** Approved destination (spec §46.3). */
	private const DESTINATION_EMAIL = 'AIOpagebuilder@steadyhandmarketing.com';

	/**
	 * Sends the install notification email. Does not throw.
	 *
	 * @param array<string, mixed> $envelope Full report envelope with payload.
	 * @return array{success: bool, failure_reason: string}
	 */
	public function send( array $envelope ): array {
		$payload = isset( $envelope['payload'] ) && is_array( $envelope['payload'] ) ? $envelope['payload'] : array();
		$website = isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '';
		$subject = sprintf(
			/* translators: %s: website address */
			__( 'Plugin successfully installed on %s', 'aio-page-builder' ),
			$website !== '' ? $website : __( 'Unknown site', 'aio-page-builder' )
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
			'failure_reason' => __( 'Email delivery failed.', 'aio-page-builder' ),
		);
	}

	/**
	 * Builds plain-text body from payload (contract §46.3 required body fields).
	 *
	 * @param array<string, mixed> $payload
	 * @return string
	 */
	private function build_body( array $payload ): string {
		$lines = array(
			'website_address: ' . ( isset( $payload['website_address'] ) ? (string) $payload['website_address'] : '' ),
			'plugin_version: ' . ( isset( $payload['plugin_version'] ) ? (string) $payload['plugin_version'] : '' ),
			'wordpress_version: ' . ( isset( $payload['wordpress_version'] ) ? (string) $payload['wordpress_version'] : '' ),
			'php_version: ' . ( isset( $payload['php_version'] ) ? (string) $payload['php_version'] : '' ),
			'server_ip: ' . ( isset( $payload['server_ip'] ) ? (string) $payload['server_ip'] : '' ),
			'admin_contact_email: ' . ( isset( $payload['admin_contact_email'] ) ? (string) $payload['admin_contact_email'] : '' ),
			'timestamp: ' . ( isset( $payload['timestamp'] ) ? (string) $payload['timestamp'] : '' ),
			'dependency_readiness_summary: ' . ( isset( $payload['dependency_readiness_summary'] ) ? (string) $payload['dependency_readiness_summary'] : '' ),
		);
		return implode( "\n", $lines );
	}
}
