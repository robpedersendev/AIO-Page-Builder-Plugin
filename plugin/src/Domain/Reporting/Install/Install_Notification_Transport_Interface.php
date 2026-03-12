<?php
/**
 * Transport for install notification delivery (spec §46.3, §46.10).
 *
 * Implementations send the report to the approved destination. Failure must not throw.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Install;

defined( 'ABSPATH' ) || exit;

/**
 * Sends install notification envelope to the approved destination.
 * Caller must only pass contract-compliant, redacted envelopes.
 */
interface Install_Notification_Transport_Interface {

	/**
	 * Sends the install notification. Does not throw; returns delivery outcome.
	 *
	 * @param array<string, mixed> $envelope Full report envelope (schema_version, event_type, payload, etc.).
	 * @return array{success: bool, failure_reason: string} success true if delivered; failure_reason sanitized if failed.
	 */
	public function send( array $envelope ): array;
}
