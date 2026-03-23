<?php
/**
 * Plugin constants. Single source of truth for runtime identity, paths, and minimum environment.
 * All version and path identifiers resolve from here; the root file must not duplicate these.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Bootstrap;

defined( 'ABSPATH' ) || exit;

/**
 * Defines and exposes plugin path, URL, version, and minimum environment constants.
 * Immutable at runtime; not writable via request data.
 */
final class Constants {

	/** Plugin version (spec §58.1). Must stay in sync with plugin header Version. */
	private const PLUGIN_VERSION = '0.1.0';

	/** Minimum supported WordPress version (spec §6.7). */
	private const MIN_WP_VERSION = '6.6';

	/** Minimum supported PHP version (spec §6.8). */
	private const MIN_PHP_VERSION = '8.1';

	/** Main plugin file name relative to plugin root. */
	private const MAIN_FILE = 'aio-page-builder.php';

	/**
	 * Plugin root directory path (absolute). Derived from this file's location.
	 *
	 * @var string
	 */
	private static string $plugin_dir;

	/**
	 * Main plugin file path (absolute).
	 *
	 * @var string
	 */
	private static string $plugin_file;

	/**
	 * Ensures constants and static state are set. Safe to call multiple times; idempotent.
	 * Root file must require this file and call this once before loading Plugin.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( isset( self::$plugin_file ) ) {
			return;
		}
		$root              = dirname( __DIR__, 2 );
		self::$plugin_dir  = \trailingslashit( $root );
		self::$plugin_file = self::$plugin_dir . self::MAIN_FILE;
		if ( ! \defined( 'AIO_PAGE_BUILDER_DIR' ) ) {
			\define( 'AIO_PAGE_BUILDER_DIR', self::$plugin_dir );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_FILE' ) ) {
			\define( 'AIO_PAGE_BUILDER_FILE', self::$plugin_file );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_URL' ) && \function_exists( 'plugin_dir_url' ) ) {
			\define( 'AIO_PAGE_BUILDER_URL', \plugin_dir_url( self::$plugin_file ) );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_VERSION' ) ) {
			\define( 'AIO_PAGE_BUILDER_VERSION', self::PLUGIN_VERSION );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_BASENAME' ) && \function_exists( 'plugin_basename' ) ) {
			\define( 'AIO_PAGE_BUILDER_BASENAME', \plugin_basename( self::$plugin_file ) );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_MIN_WP_VERSION' ) ) {
			\define( 'AIO_PAGE_BUILDER_MIN_WP_VERSION', self::MIN_WP_VERSION );
		}
		if ( ! \defined( 'AIO_PAGE_BUILDER_MIN_PHP_VERSION' ) ) {
			\define( 'AIO_PAGE_BUILDER_MIN_PHP_VERSION', self::MIN_PHP_VERSION );
		}
	}

	/** @return string Absolute path to main plugin file. */
	public static function plugin_file(): string {
		self::ensure_init();
		return self::$plugin_file;
	}

	/** @return string Absolute path to plugin directory (trailing slash). */
	public static function plugin_dir(): string {
		self::ensure_init();
		return self::$plugin_dir;
	}

	/** @return string Plugin root URL (trailing slash). */
	public static function plugin_url(): string {
		self::ensure_init();
		return defined( 'AIO_PAGE_BUILDER_URL' ) ? AIO_PAGE_BUILDER_URL : '';
	}

	/** @return string Plugin version. */
	public static function plugin_version(): string {
		return self::PLUGIN_VERSION;
	}

	/** @return string Plugin basename for WordPress (e.g. plugin-dir/aio-page-builder.php). */
	public static function plugin_basename(): string {
		self::ensure_init();
		return defined( 'AIO_PAGE_BUILDER_BASENAME' ) ? AIO_PAGE_BUILDER_BASENAME : self::MAIN_FILE;
	}

	/** @return string Minimum supported WordPress version. */
	public static function min_wp_version(): string {
		return self::MIN_WP_VERSION;
	}

	/** @return string Minimum supported PHP version. */
	public static function min_php_version(): string {
		return self::MIN_PHP_VERSION;
	}

	/**
	 * Ensures init has run (for accessors used before explicit init).
	 *
	 * @return void
	 */
	private static function ensure_init(): void {
		if ( ! isset( self::$plugin_file ) ) {
			self::init();
		}
	}
}
