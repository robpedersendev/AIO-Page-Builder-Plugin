#!/usr/bin/env php
<?php
/**
 * Reports classified admin URL / redirect emissions and fails if unknown literal aio `page` slugs appear.
 *
 * Usage: php tools/admin_url_emission_report.php [--json]
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$plugin_dir = dirname( __DIR__ ) . '/plugin';
$autoload   = $plugin_dir . '/vendor/autoload.php';
if ( ! is_readable( $autoload ) ) {
	fwrite( STDERR, "admin_url_emission_report: run `composer install` in plugin/ first.\n" );
	exit( 2 );
}
require_once $autoload;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Url_Emission_Scanner;

$src    = $plugin_dir . '/src';
$asJson = in_array( '--json', $argv, true );

$by_file = Admin_Url_Emission_Scanner::classify_emissions_by_file( $src );
$unknown = Admin_Url_Emission_Scanner::unknown_literal_slugs( $src );
$hits    = Admin_Url_Emission_Scanner::literal_aio_page_slugs( $src );

if ( $asJson ) {
	$json = json_encode(
		array(
			'emissions_by_file' => $by_file,
			'literal_slug_hits' => $hits,
			'unknown_slugs'     => $unknown,
		),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
	);
	if ( false === $json ) {
		fwrite( STDERR, "admin_url_emission_report: json_encode failed.\n" );
		exit( 2 );
	}
	echo $json . "\n";
} else {
	$totals = array(
		'admin_url'         => 0,
		'wp_safe_redirect'  => 0,
		'wp_redirect'       => 0,
		'tab_url'           => 0,
		'subtab_url'        => 0,
		'admin_post_action' => 0,
		'router_url'        => 0,
	);
	foreach ( $by_file as $counts ) {
		foreach ( $counts as $k => $n ) {
			$totals[ $k ] = ( $totals[ $k ] ?? 0 ) + $n;
		}
	}
	echo "admin_url_emission_report: emission totals (plugin/src)\n";
	foreach ( $totals as $k => $n ) {
		echo sprintf( "  %-20s %d\n", $k, $n );
	}
	echo '  literal aio page slug hits: ' . count( $hits ) . "\n";
	if ( $unknown !== array() ) {
		fwrite( STDERR, 'admin_url_emission_report: UNKNOWN literal slugs (add screen + update Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS): ' . implode( ', ', $unknown ) . "\n" );
		exit( 1 );
	}
	echo "admin_url_emission_report: OK (no unknown literal aio page slugs).\n";
}

if ( $unknown !== array() ) {
	exit( 1 );
}
