<?php
/**
 * Unit tests for Secondary_Goal_Starter_Bundle_Overlay_Registry (Prompt 541, 542).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Secondary_Goal_Starter_Bundle_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/StarterBundles/SecondaryGoalOverlays/Builtin_Secondary_Goal_Starter_Bundle_Overlays.php';

final class Secondary_Goal_Starter_Bundle_Overlay_Registry_Test extends TestCase {

	public function test_load_and_get_returns_overlay(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( array(
			array(
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'calls_lead_capture',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY     => 'lead_capture',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF     => '',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture' ),
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRECEDENCE_MARKER     => Secondary_Goal_Starter_Bundle_Overlay_Registry::PRECEDENCE_SECONDARY,
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                 => Secondary_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER        => Secondary_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
			),
		) );
		$ov = $registry->get( 'calls', 'lead_capture', '' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls_lead_capture', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY ] );
		$this->assertSame( 'calls', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY ] );
		$this->assertSame( 'lead_capture', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY ] );
	}

	public function test_get_returns_null_when_primary_equals_secondary(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Secondary_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$this->assertNull( $registry->get( 'calls', 'calls', '' ) );
	}

	public function test_invalid_overlay_skipped_at_load(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( array(
			array(
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'bad',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY       => 'calls',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY     => 'calls',
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis' ),
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRECEDENCE_MARKER     => Secondary_Goal_Starter_Bundle_Overlay_Registry::PRECEDENCE_SECONDARY,
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                 => Secondary_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
				Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER        => Secondary_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
			),
		) );
		$this->assertEmpty( $registry->list_all() );
	}

	public function test_validate_overlay_returns_errors_for_primary_equals_secondary(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$ov = array(
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'same_goal',
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY       => 'bookings',
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY     => 'bookings',
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis' ),
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRECEDENCE_MARKER     => Secondary_Goal_Starter_Bundle_Overlay_Registry::PRECEDENCE_SECONDARY,
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                 => Secondary_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
			Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER        => Secondary_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
		);
		$errors = $registry->validate_overlay( $ov );
		$this->assertContains( 'primary_equals_secondary', $errors );
	}

	public function test_get_with_bundle_key_falls_back_to_generic_overlay(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Secondary_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$ov = $registry->get( 'calls', 'lead_capture', 'realtor_starter' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY ] );
		$this->assertSame( 'lead_capture', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY ] );
	}

	public function test_builtin_definitions_load_and_get_for_primary_secondary(): void {
		$registry = new Secondary_Goal_Starter_Bundle_Overlay_Registry();
		$registry->load( Secondary_Goal_Starter_Bundle_Overlay_Registry::get_builtin_definitions() );
		$for_pair = $registry->get_for_primary_secondary( 'bookings', 'consultations' );
		$this->assertGreaterThanOrEqual( 1, count( $for_pair ) );
		foreach ( $for_pair as $ov ) {
			$this->assertSame( 'bookings', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY ] );
			$this->assertSame( 'consultations', $ov[ Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY ] );
		}
	}
}
