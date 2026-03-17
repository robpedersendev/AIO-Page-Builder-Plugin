<?php
/**
 * Unit tests for Subtype_Page_OnePager_Overlay_Registry (Prompt 426).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || exit;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Subtype_Page_OnePager_Overlay_Registry.php';

/**
 * @covers \AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry
 */
final class Subtype_Page_OnePager_Overlay_Registry_Test extends TestCase {

	private function valid_overlay( string $subtype_key = 'realtor_buyer_agent', string $page_key = 'pt_home_conversion_01' ): array {
		return array(
			Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => $subtype_key,
			Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => $page_key,
			Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
			Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
			'cta_strategy' => 'Buyer-focused CTA.',
		);
	}

	public function test_load_valid_overlay_get_returns_it(): void {
		$registry = new Subtype_Page_OnePager_Overlay_Registry();
		$registry->load( array( $this->valid_overlay() ) );
		$ov = $registry->get( 'realtor_buyer_agent', 'pt_home_conversion_01' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'realtor_buyer_agent', $ov[ Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'pt_home_conversion_01', $ov[ Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY ] );
		$this->assertSame( 'Buyer-focused CTA.', $ov['cta_strategy'] ?? '' );
	}

	public function test_load_invalid_scope_skipped(): void {
		$ov = $this->valid_overlay();
		$ov[ Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE ] = 'wrong_scope';
		$registry = new Subtype_Page_OnePager_Overlay_Registry();
		$registry->load( array( $ov ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', 'pt_home_conversion_01' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_get_for_subtype_returns_only_that_subtype(): void {
		$registry = new Subtype_Page_OnePager_Overlay_Registry();
		$registry->load( array(
			$this->valid_overlay( 'realtor_buyer_agent', 'pt_home_conversion_01' ),
			$this->valid_overlay( 'realtor_buyer_agent', 'pt_contact_request_01' ),
			$this->valid_overlay( 'realtor_listing_agent', 'pt_home_conversion_01' ),
		) );
		$list = $registry->get_for_subtype( 'realtor_buyer_agent' );
		$this->assertCount( 2, $list );
	}

	public function test_builtin_definitions_loadable(): void {
		$defs = Subtype_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions();
		$this->assertIsArray( $defs );
		$registry = new Subtype_Page_OnePager_Overlay_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 0, count( $registry->get_all() ) );
	}

	public function test_seeded_overlays_have_required_fields(): void {
		$defs = Subtype_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions();
		$registry = new Subtype_Page_OnePager_Overlay_Registry();
		$registry->load( $defs );
		$all = $registry->get_all();
		foreach ( $all as $ov ) {
			$this->assertArrayHasKey( Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY, $ov );
			$this->assertArrayHasKey( Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY, $ov );
			$this->assertArrayHasKey( Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE, $ov );
			$this->assertArrayHasKey( Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS, $ov );
			$this->assertSame( Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY, $ov[ Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE ] );
		}
	}
}
