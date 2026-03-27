<?php
/**
 * Internal PSR-4 runtime autoloader for packaged releases that exclude vendor/.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal runtime autoloader for the AIOPageBuilder namespace.
 */
final class Internal_Autoloader {

	private const PREFIX = 'AIOPageBuilder\\';

	/** @var bool */
	private static bool $registered = false;

	/**
	 * Registers the autoloader once. Resolves paths relative to plugin/src (this file lives in src/Bootstrap).
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;
		spl_autoload_register( array( self::class, 'autoload' ), true, true );
	}

	/**
	 * Loads a class from plugin/src using PSR-4 path rules.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	private static function autoload( string $class ): void {
		if ( ! str_starts_with( $class, self::PREFIX ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( self::PREFIX ) );
		if ( $relative_class === '' ) {
			return;
		}

		$relative_path = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';
		// * __DIR__ is .../plugin/src/Bootstrap; project PSR-4 root is .../plugin/src.
		$file = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . $relative_path;

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
