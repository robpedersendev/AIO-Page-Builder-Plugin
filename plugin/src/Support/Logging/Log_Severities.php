<?php
/**
 * Severity levels for error and log records (spec §45.2).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Stable severity vocabulary. Influences UI messaging, logging prominence, and reporting behavior.
 */
final class Log_Severities {

	public const INFO     = 'info';
	public const WARNING  = 'warning';
	public const ERROR    = 'error';
	public const CRITICAL = 'critical';

	/** @var array<string>|null */
	private static ?array $all = null;

	/**
	 * Returns all severity values in stable order.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array( self::INFO, self::WARNING, self::ERROR, self::CRITICAL );
		return self::$all;
	}

	/**
	 * Returns whether the value is a valid severity.
	 *
	 * @param string $severity Severity value.
	 * @return bool
	 */
	public static function isValid( string $severity ): bool {
		return in_array( $severity, self::all(), true );
	}
}
