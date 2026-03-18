<?php
/**
 * Unit tests for Secondary_Goal_Section_Helper_Overlay_Registry (Prompt 543, 544).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Secondary_Goal_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/SecondaryGoalSectionHelperOverlays/Builtin_Secondary_Goal_Section_Helper_Overlays.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/Goal_Section_Helper_Overlay_Registry.php';

final class Secondary_Goal_Section_Helper_Overlay_Registry_Test extends TestCase {

	public function test_load_and_get_returns_overlay(): void {
		$registry = new Secondary_Goal_Section_Helper_Overlay_Registry();
		$registry->load(
			array(
				array(
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'calls',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY        => 'hero_conv_02',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				),
			)
		);
		$ov = $registry->get( 'calls', 'lead_capture', 'hero_conv_02' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls', $ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY ] );
		$this->assertSame( 'lead_capture', $ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY ] );
		$this->assertSame( 'hero_conv_02', $ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
	}

	public function test_get_returns_null_when_primary_equals_secondary(): void {
		$registry = new Secondary_Goal_Section_Helper_Overlay_Registry();
		$registry->load( Secondary_Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
		$this->assertNull( $registry->get( 'calls', 'calls', 'hero_conv_02' ) );
	}

	public function test_invalid_overlay_skipped_at_load(): void {
		$registry = new Secondary_Goal_Section_Helper_Overlay_Registry();
		$registry->load(
			array(
				array(
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'bookings',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'bookings',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY       => 'cta_booking_01',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				),
			)
		);
		$this->assertEmpty( $registry->get_all() );
	}

	public function test_get_for_primary_secondary_returns_overlays(): void {
		$registry = new Secondary_Goal_Section_Helper_Overlay_Registry();
		$registry->load( Secondary_Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
		$for_pair = $registry->get_for_primary_secondary( 'calls', 'lead_capture' );
		$this->assertGreaterThanOrEqual( 1, count( $for_pair ) );
		foreach ( $for_pair as $ov ) {
			$this->assertSame( 'calls', $ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY ] );
			$this->assertSame( 'lead_capture', $ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY ] );
		}
	}

	/**
	 * Composition order: primary-goal overlay applies first, then secondary-goal overlay.
	 * Asserts both registries can be used in sequence for the same section (primary then secondary).
	 */
	public function test_composition_order_primary_then_secondary_retrievable(): void {
		$primary_registry = new Goal_Section_Helper_Overlay_Registry();
		$primary_registry->load(
			array(
				array(
					Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY   => 'calls',
					Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
					Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE    => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
					Goal_Section_Helper_Overlay_Registry::FIELD_STATUS   => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				),
			)
		);
		$secondary_registry = new Secondary_Goal_Section_Helper_Overlay_Registry();
		$secondary_registry->load(
			array(
				array(
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'calls',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY        => 'hero_conv_02',
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
					Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
				),
			)
		);
		$primary_ov = $primary_registry->get( 'calls', 'hero_conv_02' );
		$this->assertNotNull( $primary_ov );
		$secondary_ov = $secondary_registry->get( 'calls', 'lead_capture', 'hero_conv_02' );
		$this->assertNotNull( $secondary_ov );
		$this->assertSame( 'hero_conv_02', $primary_ov[ Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
		$this->assertSame( 'hero_conv_02', $secondary_ov[ Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
	}
}
