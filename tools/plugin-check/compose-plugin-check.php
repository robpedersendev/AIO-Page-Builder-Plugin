<?php
/**
 * Runs Plugin Check via Docker Compose from the tools/plugin-check directory (Composer script entry point).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$tools = __DIR__;
chdir( $tools );

$is_windows = ( defined( 'PHP_OS_FAMILY' ) && 'Windows' === PHP_OS_FAMILY )
	|| ( isset( PHP_OS ) && strncasecmp( (string) PHP_OS, 'WIN', 3 ) === 0 );

if ( $is_windows ) {
	passthru( 'powershell -NoProfile -ExecutionPolicy Bypass -File "' . $tools . '/run-plugin-check.ps1"', $code );
} else {
	passthru( 'bash "' . $tools . '/run-plugin-check.sh"', $code );
}

exit( $code ?? 1 );
