<?php
/**
 * Unit tests for Subtype_Section_Helper_Overlay_Registry (Prompt 424).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || exit;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Subtype_Section_Helper_Overlay_Registry.php';

/**
 * @covers \AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry
 */
final class Subtype_Section_Helper_Overlay_Registry_Test extends TestCase {

	private function valid_overlay( string $subtype_key = 'realtor_buyer_agent', string $section_key = 'hero_conv_02' ): array {
		return array(
			Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => $subtype_key,
			Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => $section_key,
			Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
			Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS     => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
			'tone_notes' => 'Subtype-specific tone.',
		);
	}

	public function test_load_valid_overlay_get_returns_it(): void {
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( array( $this->valid_overlay() ) );
		$ov = $registry->get( 'realtor_buyer_agent', 'hero_conv_02' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'realtor_buyer_agent', $ov[ Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'hero_conv_02', $ov[ Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
		$this->assertSame( 'Subtype-specific tone.', $ov['tone_notes'] ?? '' );
	}

	public function test_load_invalid_scope_skipped(): void {
		$ov = $this->valid_overlay();
		$ov[ Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE ] = 'wrong_scope';
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( array( $ov ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', 'hero_conv_02' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_duplicate_composite_key_first_wins(): void {
		$first = $this->valid_overlay();
		$first['tone_notes'] = 'First';
		$second = $this->valid_overlay();
		$second['tone_notes'] = 'Second';
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( array( $first, $second ) );
		$ov = $registry->get( 'realtor_buyer_agent', 'hero_conv_02' );
		$this->assertSame( 'First', $ov['tone_notes'] ?? '' );
	}

	public function test_get_for_subtype_returns_only_that_subtype(): void {
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( array(
			$this->valid_overlay( 'realtor_buyer_agent', 'hero_conv_02' ),
			$this->valid_overlay( 'realtor_buyer_agent', 'cta_booking_01' ),
			$this->valid_overlay( 'realtor_listing_agent', 'hero_conv_02' ),
		) );
		$list = $registry->get_for_subtype( 'realtor_buyer_agent' );
		$this->assertCount( 2, $list );
		$sections = array_column( $list, Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY );
		$this->assertContains( 'hero_conv_02', $sections );
		$this->assertContains( 'cta_booking_01', $sections );
	}

	public function test_get_absent_returns_null(): void {
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( array( $this->valid_overlay() ) );
		$this->assertNull( $registry->get( 'unknown_subtype', 'hero_conv_02' ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', 'unknown_section' ) );
	}

	public function test_builtin_definitions_loadable(): void {
		$defs = Subtype_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions();
		$this->assertIsArray( $defs );
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( $defs );
		$this->assertGreaterThanOrEqual( 0, count( $registry->get_all() ) );
	}

	/** Prompt 425: seeded overlays validate and have required fields. */
	public function test_seeded_subtype_overlays_validate_and_have_required_fields(): void {
		$defs = Subtype_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions();
		$registry = new Subtype_Section_Helper_Overlay_Registry();
		$registry->load( $defs );
		$all = $registry->get_all();
		$this->assertGreaterThan( 0, count( $all ), 'Expected at least one seeded subtype overlay' );
		foreach ( $all as $ov ) {
			$this->assertArrayHasKey( Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY, $ov );
			$this->assertArrayHasKey( Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY, $ov );
			$this->assertSame( Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY, $ov[ Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE ] ?? '' );
			$this->assertSame( Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE, $ov[ Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS ] ?? '' );
		}
	}

	/** Prompt 425: composition order — subtype overlay overrides industry when present. */
	public function test_composition_subtype_overlay_overrides_industry_when_present(): void {
		$industry_registry = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry();
		$industry_registry->load( array(
			array(
				\AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY => 'realtor',
				\AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY  => 'hero_conv_02',
				\AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE        => 'section_helper_overlay',
				\AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry::FIELD_STATUS        => 'active',
				'tone_notes' => 'Industry tone.',
			),
		) );
		$subtype_registry = new Subtype_Section_Helper_Overlay_Registry();
		$subtype_registry->load( array(
			array(
				Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
				Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
				Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
				Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				'tone_notes' => 'Buyer-focused subtype tone.',
			),
		) );
		$doc_registry = new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry( new \AIOPageBuilder\Domain\Registries\Docs\Documentation_Loader( dirname( __DIR__, 2 ) . '/src/Domain/Registries/Docs' ) );
		$composer = new \AIOPageBuilder\Domain\Industry\Docs\Industry_Helper_Doc_Composer( $doc_registry, $industry_registry, null, $subtype_registry );
		$result = $composer->compose( 'hero_conv_02', 'realtor', 'realtor_buyer_agent' );
		$composed = $result->get_composed_doc();
		$this->assertTrue( $result->is_overlay_applied() );
		$tone = $composed['tone_notes'] ?? '';
		$this->assertStringContainsString( 'Buyer-focused', $tone, 'Subtype overlay (last in composition order) should override industry tone_notes' );
	}
}
