<?php
/**
 * Logger interface for structured records. Sink is replaceable; no persistence contract.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Callers log Error_Record instances. Implementation may no-op, buffer, or persist; no assumption here.
 */
interface Logger_Interface {

	/**
	 * Accepts a structured record. Must not throw; may no-op or route to controlled sink.
	 *
	 * @param Error_Record $record Pre-sanitized record; must not contain secrets.
	 * @return void
	 */
	public function log( Error_Record $record ): void;
}
