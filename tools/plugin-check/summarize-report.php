<?php
/**
 * Parses wp plugin check output (FILE: lines + JSON arrays, possibly line-wrapped) and prints category tallies.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$path = $argv[1] ?? '';
if ( $path === '' || ! is_readable( $path ) ) {
	fwrite( STDERR, "Usage: php summarize-report.php <plugin-check-output.txt>\n" );
	exit( 1 );
}

$raw = file_get_contents( $path );
if ( false === $raw ) {
	exit( 1 );
}
$raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );

/**
 * @param string $code Sniff / check code.
 * @return string Category bucket for triage.
 */
function aio_plugin_check_category( string $code ): string {
	if ( preg_match( '/^(hidden_files|missing_direct_file_access_protection|application_detected)$/i', $code )
		|| preg_match( '/^PluginCheck\.PluginHeader/i', $code ) ) {
		return 'packaging/headers';
	}
	if ( preg_match( '/EnqueuedResource|PluginCheck\.Admin/i', $code ) ) {
		return 'admin assets';
	}
	if ( preg_match( '/^WordPress\.DB\.(PreparedSQL|SlowDBQuery)|^PluginCheck\.Security/i', $code ) ) {
		return 'security/permissions';
	}
	if ( preg_match( '/^WordPress\.Security\./', $code ) && ! preg_match( '/EscapeOutput/', $code ) ) {
		return 'security/permissions';
	}
	if ( preg_match( '/uninstall|register_uninstall|PluginCheck\.(Lifecycle|Runtime)/i', $code ) ) {
		return 'lifecycle/uninstall';
	}
	if ( preg_match( '/I18n|Translators|TextDomain|Localization/i', $code )
		|| preg_match( '/EscapeOutput/', $code ) ) {
		return 'i18n/escaping';
	}
	if ( preg_match( '/readme|contributing|PluginReadme/i', $code ) ) {
		return 'readme/docs';
	}
	return 'WP conventions';
}

$lines = preg_split( '/\R/', $raw );
$by_cat   = array();
$by_code  = array();
$errors   = 0;
$warnings = 0;
$files    = 0;

$json_lines = array();

$flush = static function () use ( &$json_lines, &$files, &$errors, &$warnings, &$by_cat, &$by_code ): void {
	if ( array() === $json_lines ) {
		return;
	}
	$json_raw = trim( implode( "\n", $json_lines ) );
	$json_lines = array();
	if ( '' === $json_raw ) {
		return;
	}
	++$files;
	$decoded = json_decode( $json_raw, true );
	if ( ! is_array( $decoded ) ) {
		return;
	}
	foreach ( $decoded as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['code'], $item['type'] ) ) {
			continue;
		}
		$code = (string) $item['code'];
		$type = (string) $item['type'];
		if ( 'ERROR' === $type ) {
			++$errors;
		} elseif ( 'WARNING' === $type ) {
			++$warnings;
		}
		$cat = aio_plugin_check_category( $code );
		if ( ! isset( $by_cat[ $cat ] ) ) {
			$by_cat[ $cat ] = array( 'ERROR' => 0, 'WARNING' => 0 );
		}
		++$by_cat[ $cat ][ 'ERROR' === $type ? 'ERROR' : 'WARNING' ];
		if ( ! isset( $by_code[ $code ] ) ) {
			$by_code[ $code ] = 0;
		}
		++$by_code[ $code ];
	}
};

foreach ( $lines as $line ) {
	if ( preg_match( '/^FILE:\s*(.+)$/', $line, $m ) ) {
		$flush();
		// Next lines belong to this file until the next FILE:.
		continue;
	}
	$json_lines[] = $line;
}
$flush();

ksort( $by_cat );
arsort( $by_code );

echo "Files with at least one finding (FILE: sections): {$files}\n";
echo "Totals — ERROR: {$errors}, WARNING: {$warnings}\n\n";
echo "By category (ERROR / WARNING):\n";
foreach ( $by_cat as $cat => $counts ) {
	printf(
		"  %-22s  %5d / %5d\n",
		$cat,
		$counts['ERROR'] ?? 0,
		$counts['WARNING'] ?? 0
	);
}

echo "\nTop 25 codes by count:\n";
$i = 0;
foreach ( $by_code as $c => $n ) {
	if ( $i++ >= 25 ) {
		break;
	}
	printf( "  %5d  %s\n", $n, $c );
}
