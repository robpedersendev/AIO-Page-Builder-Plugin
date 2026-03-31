<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Url_Emission_Scanner;
use PHPUnit\Framework\TestCase;

final class Admin_Url_Emission_Scanner_Test extends TestCase {

	public function test_no_unknown_literal_aio_page_slugs_in_sources(): void {
		$src = dirname( __DIR__, 2 ) . '/src';
		$this->assertSame(
			array(),
			Admin_Url_Emission_Scanner::unknown_literal_slugs( $src ),
			'Update Admin_Route_Inventory::ALL_DISCOVERED_ADMIN_PAGE_SLUGS for new literal page= slugs, or avoid misleading literals in comments.'
		);
	}

	public function test_classify_emissions_returns_non_empty_totals(): void {
		$src     = dirname( __DIR__, 2 ) . '/src';
		$by_file = Admin_Url_Emission_Scanner::classify_emissions_by_file( $src );
		$this->assertNotSame( array(), $by_file, 'Expected emission patterns under src/.' );
	}
}
