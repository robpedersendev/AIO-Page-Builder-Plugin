<?php
/**
 * Exits with status 1 if Plugin Check report contains any ERROR-level finding (JSON "type":"ERROR").
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$path = $argv[1] ?? '';
if ( $path === '' || ! is_readable( $path ) ) {
	fwrite( STDERR, "Usage: php exit-if-errors.php <plugin-check-report.txt>\n" );
	exit( 2 );
}

$raw = file_get_contents( $path );
if ( false === $raw ) {
	exit( 2 );
}

$n = preg_match_all( '/"type":"ERROR"/', $raw, $m );
if ( ! is_int( $n ) || $n < 1 ) {
	echo "Plugin Check: 0 ERROR-level findings in report.\n";
	exit( 0 );
}

echo "Plugin Check: {$n} ERROR-level findings (fail gate).\n";
exit( 1 );
