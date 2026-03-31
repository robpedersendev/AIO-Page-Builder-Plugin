#!/usr/bin/env php
<?php
/**
 * Fails with non-zero exit if {@see Admin_Page_Slug_Scanner} output diverges from {@see Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS}.
 *
 * Usage (from repo root): php tools/admin_route_inventory_check.php
 * Or: composer run admin-route-inventory (from plugin/)
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

$plugin_dir = dirname( __DIR__ ) . '/plugin';
$autoload   = $plugin_dir . '/vendor/autoload.php';
if ( ! is_readable( $autoload ) ) {
	fwrite( STDERR, "admin_route_inventory_check: run `composer install` in plugin/ first.\n" );
	exit( 2 );
}
require_once $autoload;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Page_Slug_Scanner;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Route_Inventory;

$src     = $plugin_dir . '/src';
$found   = Admin_Page_Slug_Scanner::discover_slugs( $src );
$expected = Admin_Route_Inventory::expected_discovered_page_slugs();

if ( $found === $expected ) {
	echo "admin_route_inventory_check: OK (" . count( $found ) . " slugs).\n";
	exit( 0 );
}

$only_found    = array_values( array_diff( $found, $expected ) );
$only_expected = array_values( array_diff( $expected, $found ) );
fwrite( STDERR, "admin_route_inventory_check: MISMATCH.\n" );
if ( $only_found !== array() ) {
	fwrite( STDERR, '  In source but not in Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS: ' . implode( ', ', $only_found ) . "\n" );
}
if ( $only_expected !== array() ) {
	fwrite( STDERR, '  In inventory but not found by scanner (remove stale entries or fix scanner): ' . implode( ', ', $only_expected ) . "\n" );
}
exit( 1 );
