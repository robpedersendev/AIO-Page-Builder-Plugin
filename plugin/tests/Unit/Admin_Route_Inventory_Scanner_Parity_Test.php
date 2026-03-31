<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Page_Slug_Scanner;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Route_Inventory;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;
use PHPUnit\Framework\TestCase;

final class Admin_Route_Inventory_Scanner_Parity_Test extends TestCase {

	public function test_scanner_matches_inventory_allowlist(): void {
		$src = dirname( __DIR__, 2 ) . '/src';
		$this->assertSame(
			Admin_Route_Inventory::expected_discovered_page_slugs(),
			Admin_Page_Slug_Scanner::discover_slugs( $src )
		);
	}

	public function test_menu_slug_union_is_subset_of_discovered_slugs(): void {
		$all = Admin_Route_Inventory::expected_discovered_page_slugs();
		foreach ( Admin_Route_Inventory::all_registered_menu_slugs_union() as $slug ) {
			$this->assertContains( $slug, $all, 'Registered menu slug must have a screen SLUG constant.' );
		}
	}

	public function test_legacy_redirect_slugs_are_subset_of_discovered(): void {
		$all = Admin_Route_Inventory::expected_discovered_page_slugs();
		foreach ( Admin_Route_Inventory::LEGACY_REDIRECT_PAGE_SLUGS as $slug ) {
			$this->assertContains( $slug, $all );
		}
	}

	public function test_admin_router_route_names_match_inventory(): void {
		$router = new Admin_Router();
		$this->assertEqualsCanonicalizing(
			Admin_Route_Inventory::ADMIN_ROUTER_ROUTE_NAMES,
			$router->list_route_names()
		);
	}
}
