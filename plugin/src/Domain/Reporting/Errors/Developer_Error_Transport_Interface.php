<?php
/**
 * Transport for developer error report delivery (spec §45.8, §46.12).
 *
 * Implementations send the report to the approved destination. Failure must not throw.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Sends developer error report envelope to the approved destination.
 */
interface Developer_Error_Transport_Interface {

	/**
	 * Sends the developer error report. Does not throw.
	 *
	 * @param array<string, mixed> $envelope Full report envelope (schema_version, event_type, payload, etc.).
	 * @return array{success: bool, failure_reason: string}
	 */
	public function send( array $envelope ): array;
}
