<?php
/**
 * Transport for heartbeat delivery (spec §46.5).
 *
 * Implementations send the report to the approved destination. Failure must not throw.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

/**
 * Sends heartbeat envelope to the approved destination.
 */
interface Heartbeat_Transport_Interface {

	/**
	 * Sends the heartbeat. Does not throw.
	 *
	 * @param array<string, mixed> $envelope Full report envelope (schema_version, event_type, payload, etc.).
	 * @return array{success: bool, failure_reason: string}
	 */
	public function send( array $envelope ): array;
}
