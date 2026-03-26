<?php
/**
 * Legacy free-form debug line. Prefer Named_Debug_Log::event( Named_Debug_Log_Event::*, $detail ).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Development-only sink; no-op in production when debug log is off.
 */
final class Internal_Debug_Log {

	/**
	 * Emits one line when WordPress debug logging is active.
	 */
	public static function line( string $message ): void {
		Named_Debug_Log::event( Named_Debug_Log_Event::INTERNAL_DEBUG_LINE, $message );
	}
}
