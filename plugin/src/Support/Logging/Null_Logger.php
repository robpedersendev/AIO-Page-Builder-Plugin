<?php
/**
 * No-op logger. Safely accepts records without persisting (bootstrap placeholder).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Implements Logger_Interface with a no-op sink. Callers can depend on the interface now.
 */
final class Null_Logger implements Logger_Interface {

	/** @inheritdoc */
	public function log( Error_Record $record ): void {
		// No-op; no persistence. Replace with real sink when storage contract exists.
	}
}
