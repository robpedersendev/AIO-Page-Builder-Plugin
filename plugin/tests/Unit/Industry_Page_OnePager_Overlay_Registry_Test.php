<?php
/**
 * Unit tests for Industry_Page_OnePager_Overlay_Registry: valid/invalid overlay, unknown page-template ref,
 * overlay load does not affect base one-pagers (Prompt 327).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Page_OnePager_Overlay_Registry.php';

final class Industry_Page_OnePager_Overlay_Registry_Test extends TestCase {

	private function valid_overlay( string $industry = 'realtor', string $page_key = 'pt_home_trust_01' ): array {
		return array(
			Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY      => $industry,
			Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => $page_key,
			Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Industry_Page_OnePager_Overlay_Registry::SCOPE_PAGE_ONEPAGER_OVERLAY,
			Industry_Page_OnePager_Overlay_Registry::FIELD_STATUS          => 'active',
		);
	}

	public function test_load_and_get_valid_overlay(): void {
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$registry->load( array( $this->valid_overlay( 'realtor', 'pt_landing_contact' ) ) );
		$ov = $registry->get( 'realtor', 'pt_landing_contact' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'realtor', $ov[ Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( 'pt_landing_contact', $ov[ Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY ] );
	}

	public function test_get_returns_null_for_unknown_pair(): void {
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$registry->load( array( $this->valid_overlay() ) );
		$this->assertNull( $registry->get( 'unknown', 'pt_home_trust_01' ) );
		$this->assertNull( $registry->get( 'realtor', 'pt_unknown_page' ) );
	}

	public function test_load_skips_invalid_scope(): void {
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$ov = $this->valid_overlay();
		$ov[ Industry_Page_OnePager_Overlay_Registry::FIELD_SCOPE ] = 'wrong';
		$registry->load( array( $ov ) );
		$this->assertNull( $registry->get( 'realtor', 'pt_home_trust_01' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_get_for_industry_returns_only_that_industry(): void {
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$registry->load( array(
			$this->valid_overlay( 'plumber', 'pt_landing_contact' ),
			$this->valid_overlay( 'cosmetology', 'pt_landing_contact' ),
			$this->valid_overlay( 'plumber', 'pt_home_trust_01' ),
		) );
		$plumber = $registry->get_for_industry( 'plumber' );
		$this->assertCount( 2, $plumber );
	}

	public function test_empty_load_does_not_affect_base_one_pagers(): void {
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$registry->load( array() );
		$this->assertCount( 0, $registry->get_all() );
		$this->assertNull( $registry->get( 'any', 'any_pt' ) );
	}

	/**
	 * Built-in overlay definitions (Prompt 354): four industries × four page families = 16.
	 */
	public function test_builtin_overlay_definitions_load(): void {
		$defs = Industry_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions();
		$this->assertGreaterThanOrEqual( 16, count( $defs ), 'Expected at least 16 built-in page one-pager overlays' );
		$registry = new Industry_Page_OnePager_Overlay_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 16, count( $registry->get_all() ) );
		$realtor = $registry->get_for_industry( 'realtor' );
		$this->assertCount( 4, $realtor, 'Realtor should have 4 page overlays' );
		$home = $registry->get( 'realtor', 'pt_home_conversion_01' );
		$this->assertNotNull( $home );
		$this->assertSame( 'realtor', $home[ Industry_Page_OnePager_Overlay_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( 'pt_home_conversion_01', $home[ Industry_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY ] );
	}
}
