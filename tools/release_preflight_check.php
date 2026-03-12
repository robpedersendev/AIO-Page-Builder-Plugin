<?php
/**
 * Release preflight check — verifies packaging expectations for the plugin directory.
 * Does not mutate the codebase. Run from CLI: php release_preflight_check.php [path-to-plugin-root]
 *
 * Usage: php tools/release_preflight_check.php [path]
 *   path: Path to the plugin root (directory containing aio-page-builder.php). Default: ../plugin relative to this script.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

if ( PHP_SAPI !== 'cli' ) {
	echo "CLI only.\n";
	exit( 1 );
}

$base = $argc > 1 ? rtrim( $argv[1], DIRECTORY_SEPARATOR ) : dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'plugin';
if ( ! is_dir( $base ) ) {
	echo "FAIL: Not a directory: {$base}\n";
	exit( 1 );
}

$main_file = $base . DIRECTORY_SEPARATOR . 'aio-page-builder.php';
$results   = array();
$ok        = true;

// 1. Main plugin file exists
if ( ! file_exists( $main_file ) || ! is_readable( $main_file ) ) {
	$results[] = array( 'fail', 'Main plugin file missing or unreadable: aio-page-builder.php' );
	$ok        = false;
} else {
	$results[] = array( 'pass', 'Main plugin file present' );
	$content   = (string) file_get_contents( $main_file );

	// 2. Required headers (simple line match)
	$headers = array( 'Plugin Name', 'Version', 'Requires at least', 'Requires PHP', 'Text Domain' );
	foreach ( $headers as $name ) {
		$pattern = '/^\s*\*\s*' . preg_quote( $name, '/' ) . '\s*:\s*(.+)$/m';
		if ( preg_match( $pattern, $content, $m ) && trim( (string) $m[1] ) !== '' ) {
			$results[] = array( 'pass', "Header '{$name}' present" );
		} else {
			$results[] = array( 'fail', "Header '{$name}' missing or empty" );
			$ok        = false;
		}
	}

	// Version format sanity (x.y.z or x.y.z-rcN)
	if ( preg_match( '/^\s*\*\s*Version\s*:\s*(\S+)/m', $content, $m ) ) {
		$ver = trim( (string) $m[1] );
		if ( ! preg_match( '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.-]+)?$/', $ver ) ) {
			$results[] = array( 'warn', "Version format non-standard: {$ver}" );
		}
	}
}

// 3. Bootstrap files
$bootstrap_files = array( 'src/Bootstrap/Constants.php', 'src/Bootstrap/Plugin.php' );
foreach ( $bootstrap_files as $rel ) {
	$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( file_exists( $path ) && is_readable( $path ) ) {
		$results[] = array( 'pass', "Required file: {$rel}" );
	} else {
		$results[] = array( 'fail', "Missing or unreadable: {$rel}" );
		$ok        = false;
	}
}

// 4. Development-only artifacts should be absent in production package
$dev_artifacts = array( 'tests/bootstrap.php', 'phpstan.neon.dist', '.wp-env.json' );
foreach ( $dev_artifacts as $rel ) {
	$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( file_exists( $path ) ) {
		$results[] = array( 'warn', "Development artifact present (exclude for production): {$rel}" );
	}
}

// Output
foreach ( $results as $r ) {
	list( $status, $msg ) = $r;
	$prefix = $status === 'pass' ? 'PASS' : ( $status === 'fail' ? 'FAIL' : 'WARN' );
	echo "[{$prefix}] {$msg}\n";
}

echo "\n";
if ( $ok ) {
	echo "Preflight summary: PASS (all required checks passed). Warnings may indicate dev-only content; exclude for production ZIP.\n";
	exit( 0 );
} else {
	echo "Preflight summary: FAIL (one or more required checks failed). Do not ship until resolved.\n";
	exit( 1 );
}
