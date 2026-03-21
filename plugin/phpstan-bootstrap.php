<?php
/**
 * PHPStan bootstrap: defines constants expected by plugin code. WordPress symbols come from stubFiles (phpstan.neon.dist).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$wordpress_stubs = __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php';

if ( ! is_file( $wordpress_stubs ) ) {
	fwrite(
		STDERR,
		"PHPStan: WordPress stubs not found at:\n  {$wordpress_stubs}\n"
		. "Install dev dependencies from the plugin directory, e.g.:\n"
		. "  composer install --prefer-dist --no-progress\n"
		. "If Composer cannot extract zip packages, enable the PHP zip extension (ext-zip) or run:\n"
		. "  composer install --prefer-source --no-progress\n"
	);
	exit( 1 );
}

if ( ! defined( 'ABSPATH' ) ) {
	// Trailing slash; DbDelta_Runner resolves ABSPATH . 'wp-admin/includes/upgrade.php' for static analysis.
	define( 'ABSPATH', __DIR__ . '/phpstan-fixtures/wp/' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// Load stub definitions so PHPStan can resolve global WordPress symbols (wp_unslash, etc.).
require_once $wordpress_stubs;

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins' );
}
