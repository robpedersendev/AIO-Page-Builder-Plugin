<?php
/**
 * Log and error categories (spec §45.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Support\Logging;

defined( 'ABSPATH' ) || exit;

/**
 * Stable category vocabulary for error classification and supportability.
 */
final class Log_Categories {

	public const VALIDATION    = 'validation';
	public const DEPENDENCY    = 'dependency';
	public const EXECUTION     = 'execution';
	public const PROVIDER      = 'provider';
	public const QUEUE         = 'queue';
	public const REPORTING     = 'reporting';
	public const IMPORT_EXPORT = 'import_export';
	public const SECURITY      = 'security';
	public const COMPATIBILITY = 'compatibility';

	/** @var array<string>|null */
	private static ?array $all = null;

	/**
	 * Returns all category values in stable order.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::VALIDATION,
			self::DEPENDENCY,
			self::EXECUTION,
			self::PROVIDER,
			self::QUEUE,
			self::REPORTING,
			self::IMPORT_EXPORT,
			self::SECURITY,
			self::COMPATIBILITY,
		);
		return self::$all;
	}

	/**
	 * Returns whether the value is a valid category.
	 *
	 * @param string $category Category value.
	 * @return bool
	 */
	public static function isValid( string $category ): bool {
		return in_array( $category, self::all(), true );
	}
}
