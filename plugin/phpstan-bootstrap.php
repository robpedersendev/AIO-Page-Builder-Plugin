<?php
/**
 * PHPStan bootstrap: loads WordPress function/class stubs from php-stubs/wordpress-stubs.
 * Path is resolved from this file so analysis works regardless of process CWD.
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

require $wordpress_stubs;
