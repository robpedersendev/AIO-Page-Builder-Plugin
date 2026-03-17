<?php
/**
 * Unit tests for Subtype_Goal_Starter_Bundle_Overlay_Registry (Prompt 551, 552).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Subtype_Goal_Starter_Bundle_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/StarterBundles/SubtypeGoalOverlays/Builtin_Subtype_Goal_Starter_Bundle_Overlays.php';

final class Subtype_Goal_Starter_Bundle_Overlay_Registry_Test extends TestCase {

	public function test_load_builtin_and_get_returns_overlay(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Subtype_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$ov = $registry->get( 'cosmetology_nail_mobile_tech', 'bookings', 'cosmetology_nail_mobile_tech_starter' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'cosmetology_nail_mobile_tech_bookings', $ov[ Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY ] );
		$this->assertSame( 'cosmetology_nail_mobile_tech', $ov[ Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertSame( 'bookings', $ov[ Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY ] );
	}

	public function test_get_returns_null_when_subtype_or_goal_empty(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Subtype_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$this->assertNull( $registry->get( '', 'bookings', '' ) );
		$this->assertNull( $registry->get( 'realtor_buyer_agent', '', '' ) );
	}

	public function test_get_returns_null_when_no_matching_overlay(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Subtype_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$this->assertNull( $registry->get( 'unknown_subtype', 'calls', '' ) );
	}

	public function test_invalid_overlay_skipped_at_load(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( array(
			array(
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'bad_goal',
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'realtor_buyer_agent',
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY                => 'invalid_goal',
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis' ),
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                  => Subtype_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
				Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER          => Subtype_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
			),
		) );
		$this->assertEmpty( $registry->list_all() );
	}

	public function test_get_for_subtype_goal_returns_matching_overlays(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Subtype_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$list = $registry->get_for_subtype_goal( 'plumber_commercial', 'estimates' );
		$this->assertCount( 1, $list );
		$this->assertSame( 'plumber_commercial_estimates', $list[0][ Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY ] );
	}

	public function test_fallback_when_no_combined_overlay_returns_null(): void {
		$registry = new Subtype_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Subtype_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$this->assertNull( $registry->get( 'realtor_listing_agent', 'valuations', '' ) );
	}
}
