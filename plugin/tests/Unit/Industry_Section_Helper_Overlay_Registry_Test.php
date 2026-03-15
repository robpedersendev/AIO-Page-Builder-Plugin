<?php
/**
 * Unit tests for Industry_Section_Helper_Overlay_Registry: valid overlay load, invalid scope/keys,
 * registry does not affect base helpers (Prompt 326).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Industry_Section_Helper_Overlay_Registry.php';

final class Industry_Section_Helper_Overlay_Registry_Test extends TestCase {

	private function valid_overlay( string $industry = 'legal', string $section = 'st01_hero' ): array {
		return array(
			Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY => $industry,
			Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY  => $section,
			Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Industry_Section_Helper_Overlay_Registry::SCOPE_SECTION_HELPER_OVERLAY,
			Industry_Section_Helper_Overlay_Registry::FIELD_STATUS      => 'active',
		);
	}

	public function test_load_and_get_valid_overlay(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$registry->load( array( $this->valid_overlay( 'legal', 'cta_contact_01' ) ) );
		$ov = $registry->get( 'legal', 'cta_contact_01' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'legal', $ov[ Industry_Section_Helper_Overlay_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( 'cta_contact_01', $ov[ Industry_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
	}

	public function test_get_returns_null_for_unknown_pair(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$registry->load( array( $this->valid_overlay() ) );
		$this->assertNull( $registry->get( 'unknown', 'st01_hero' ) );
		$this->assertNull( $registry->get( 'legal', 'unknown_section' ) );
	}

	public function test_load_skips_invalid_scope(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$ov = $this->valid_overlay();
		$ov[ Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE ] = 'wrong_scope';
		$registry->load( array( $ov ) );
		$this->assertNull( $registry->get( 'legal', 'st01_hero' ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_load_skips_invalid_industry_key(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$ov = $this->valid_overlay( 'Invalid Key!', 'st01_hero' );
		$registry->load( array( $ov ) );
		$this->assertCount( 0, $registry->get_all() );
	}

	public function test_get_for_industry_returns_only_that_industry(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$registry->load( array(
			$this->valid_overlay( 'legal', 'st01_hero' ),
			$this->valid_overlay( 'healthcare', 'st01_hero' ),
			$this->valid_overlay( 'legal', 'cta_contact_01' ),
		) );
		$legal = $registry->get_for_industry( 'legal' );
		$this->assertCount( 2, $legal );
	}

	public function test_load_duplicate_composite_key_first_wins(): void {
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$first  = $this->valid_overlay( 'legal', 'st01_hero' );
		$first['tone_notes'] = 'First';
		$second = $this->valid_overlay( 'legal', 'st01_hero' );
		$second['tone_notes'] = 'Second';
		$registry->load( array( $first, $second ) );
		$ov = $registry->get( 'legal', 'st01_hero' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'First', $ov['tone_notes'] ?? '' );
		$this->assertCount( 1, $registry->get_all() );
	}

	/** Prompt 353: built-in overlays load and validate for all four industries. */
	public function test_builtin_overlay_definitions_load_and_validate(): void {
		$definitions = Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions();
		$this->assertIsArray( $definitions );
		$this->assertGreaterThanOrEqual( 20, count( $definitions ), 'Expected 5 sections × 4 industries' );
		$registry = new Industry_Section_Helper_Overlay_Registry();
		$registry->load( $definitions );
		$cosmetology = $registry->get_for_industry( 'cosmetology_nail' );
		$this->assertCount( 5, $cosmetology );
		$ov = $registry->get( 'cosmetology_nail', 'hero_conv_02' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'section_helper_overlay', $ov[ Industry_Section_Helper_Overlay_Registry::FIELD_SCOPE ] );
		$this->assertSame( 'active', $ov[ Industry_Section_Helper_Overlay_Registry::FIELD_STATUS ] );
		$this->assertArrayHasKey( 'tone_notes', $ov );
		$disaster = $registry->get( 'disaster_recovery', 'cta_booking_01' );
		$this->assertNotNull( $disaster );
		$this->assertArrayHasKey( 'cta_usage_notes', $disaster );
	}
}
