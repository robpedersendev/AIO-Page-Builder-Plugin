<?php
/**
 * One-off: aggregate Plugin Check JSON report by sniff code.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$path = $argv[1] ?? dirname( __DIR__, 2 ) . '/tools/plugin-check/output/plugin-check-report.json';
$raw  = file_get_contents( $path );
if ( false === $raw ) {
	fwrite( STDERR, "Cannot read: {$path}\n" );
	exit( 1 );
}

$errors   = array();
$warnings = array();

foreach ( preg_split( '/\R/', $raw ) as $line ) {
	if ( ! str_starts_with( $line, '[' ) ) {
		continue;
	}
	$decoded = json_decode( $line, true );
	if ( ! is_array( $decoded ) ) {
		continue;
	}
	foreach ( $decoded as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['type'], $item['code'] ) ) {
			continue;
		}
		$code = (string) $item['code'];
		if ( 'ERROR' === $item['type'] ) {
			$errors[ $code ] = ( $errors[ $code ] ?? 0 ) + 1;
		} else {
			$warnings[ $code ] = ( $warnings[ $code ] ?? 0 ) + 1;
		}
	}
}

arsort( $errors );
arsort( $warnings );

echo 'ERROR unique: ' . count( $errors ) . ', total: ' . array_sum( $errors ) . "\n";
foreach ( $errors as $c => $n ) {
	echo "{$n}\t{$c}\n";
}
echo "\nWARNING unique: " . count( $warnings ) . ', total: ' . array_sum( $warnings ) . "\n";
$i = 0;
foreach ( $warnings as $c => $n ) {
	echo "{$n}\t{$c}\n";
	if ( ++$i >= 25 ) {
		break;
	}
}
