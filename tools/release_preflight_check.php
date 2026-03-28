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

$base = isset( $argv[1] ) && is_string( $argv[1] ) && $argv[1] !== ''
	? rtrim( $argv[1], DIRECTORY_SEPARATOR )
	: dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'plugin';
if ( ! is_dir( $base ) ) {
	echo "FAIL: Not a directory: {$base}\n";
	exit( 1 );
}

$main_file = $base . DIRECTORY_SEPARATOR . 'aio-page-builder.php';
$results   = array();
$ok        = true;
$content   = '';

// 1. Main plugin file exists.
if ( ! file_exists( $main_file ) || ! is_readable( $main_file ) ) {
	$results[] = array( 'fail', 'Main plugin file missing or unreadable: aio-page-builder.php' );
	$ok        = false;
} else {
	$results[] = array( 'pass', 'Main plugin file present' );
	$content   = (string) file_get_contents( $main_file );

	// 2. Required headers (simple line match).
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

	// Version format sanity (x.y.z or x.y.z-rcN).
	if ( preg_match( '/^\s*\*\s*Version\s*:\s*(\S+)/m', $content, $m ) ) {
		$ver = trim( (string) $m[1] );
		if ( ! preg_match( '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.-]+)?$/', $ver ) ) {
			$results[] = array( 'warn', "Version format non-standard: {$ver}" );
		}
	}
}

// 3. Bootstrap files.
$bootstrap_files = array(
	'src/Bootstrap/Constants.php',
	'src/Bootstrap/Plugin.php',
	'src/Bootstrap/Internal_Autoloader.php',
);
foreach ( $bootstrap_files as $rel ) {
	$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( file_exists( $path ) && is_readable( $path ) ) {
		$results[] = array( 'pass', "Required file: {$rel}" );
	} else {
		$results[] = array( 'fail', "Missing or unreadable: {$rel}" );
		$ok        = false;
	}
}

// 4. Runtime autoload viability.
$vendor_autoload_path   = $base . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
$internal_autoload_path = $base . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Bootstrap' . DIRECTORY_SEPARATOR . 'Internal_Autoloader.php';
$references_vendor      = false !== strpos( $content, '/vendor/autoload.php' );
$references_internal    = false !== strpos( $content, '/src/Bootstrap/Internal_Autoloader.php' );

if ( file_exists( $vendor_autoload_path ) && is_readable( $vendor_autoload_path ) ) {
	$results[] = array( 'pass', 'Composer runtime autoload present' );
} else {
	$results[] = array( 'warn', 'Composer runtime autoload absent (acceptable only when internal runtime autoloader is shipped)' );
}

if ( file_exists( $internal_autoload_path ) && is_readable( $internal_autoload_path ) ) {
	$results[] = array( 'pass', 'Internal runtime autoloader present' );
} else {
	$results[] = array( 'fail', 'Internal runtime autoloader missing: src/Bootstrap/Internal_Autoloader.php' );
	$ok        = false;
}

if ( $references_vendor && ! file_exists( $vendor_autoload_path ) && ! file_exists( $internal_autoload_path ) ) {
	$results[] = array( 'fail', 'Main plugin file references vendor/autoload.php but no runtime autoloader is shippable' );
	$ok        = false;
}

if ( $references_internal ) {
	$results[] = array( 'pass', 'Main plugin file references internal runtime autoloader fallback' );
} else {
	$results[] = array( 'warn', 'Main plugin file does not reference the internal runtime autoloader fallback' );
}

// 5. Development-only artifacts should be absent in production package.
$dev_artifacts = array( 'tests/bootstrap.php', 'phpstan.neon.dist', '.wp-env.json' );
foreach ( $dev_artifacts as $rel ) {
	$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( file_exists( $path ) ) {
		$results[] = array( 'warn', "Development artifact present (exclude for production): {$rel}" );
	}
}

// 6. Template-lab / chat / apply runtime files (private distribution must not omit these).
$template_lab_runtime = array(
	'src/Admin/Screens/Templates/Template_Lab_Chat_Screen.php',
	'src/Admin/Actions/Template_Lab_Canonical_Admin_Actions.php',
	'src/Admin/Actions/Template_Lab_Chat_Admin_Actions.php',
	'src/Domain/AI/TemplateLab/Template_Lab_Canonical_Apply_Service.php',
	'src/Infrastructure/Rest/AI_Chat_REST_Controller.php',
);
foreach ( $template_lab_runtime as $rel ) {
	$path = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
	if ( file_exists( $path ) && is_readable( $path ) ) {
		$results[] = array( 'pass', "Template-lab runtime file: {$rel}" );
	} else {
		$results[] = array( 'fail', "Missing template-lab runtime file: {$rel}" );
		$ok        = false;
	}
}

// Output.
foreach ( $results as $r ) {
	list( $status, $msg ) = $r;
	$prefix = 'pass' === $status ? 'PASS' : ( 'fail' === $status ? 'FAIL' : 'WARN' );
	echo "[{$prefix}] {$msg}\n";
}

echo "\n";
if ( $ok ) {
	echo "Preflight summary: PASS (all required checks passed). Warnings may indicate dev-only content; exclude for production ZIP.\n";
	exit( 0 );
}

echo "Preflight summary: FAIL (one or more required checks failed). Do not ship until resolved.\n";
exit( 1 );
