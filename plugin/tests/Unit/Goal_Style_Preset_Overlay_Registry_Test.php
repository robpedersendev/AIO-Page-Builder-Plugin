<?php
/**
 * Unit tests for Goal_Style_Preset_Overlay_Registry (Prompt 512).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Goal_Style_Preset_Overlay_Registry.php';

final class Goal_Style_Preset_Overlay_Registry_Test extends TestCase {

	public function test_load_and_get_returns_overlay(): void {
		$registry = new Goal_Style_Preset_Overlay_Registry();
		$registry->load(
			array(
				array(
					Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_PRESET_KEY => 'goal_calls_realtor',
					Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY         => 'calls',
					Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF => 'realtor_warm',
					Goal_Style_Preset_Overlay_Registry::FIELD_STATUS          => 'active',
				),
			)
		);
		$ov = $registry->get( 'goal_calls_realtor' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls', $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] );
		$this->assertSame( 'realtor_warm', $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF ] );
	}

	public function test_get_for_goal_returns_overlays(): void {
		$registry = new Goal_Style_Preset_Overlay_Registry();
		$registry->load( Goal_Style_Preset_Overlay_Registry::get_builtin_definitions() );
		$for_calls = $registry->get_for_goal( 'calls' );
		$this->assertGreaterThanOrEqual( 1, count( $for_calls ) );
		foreach ( $for_calls as $ov ) {
			$this->assertSame( 'calls', $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] );
		}
	}

	public function test_get_overlays_for_preset_returns_matching(): void {
		$registry = new Goal_Style_Preset_Overlay_Registry();
		$registry->load( Goal_Style_Preset_Overlay_Registry::get_builtin_definitions() );
		$for_realtor = $registry->get_overlays_for_preset( 'realtor_warm' );
		$this->assertGreaterThanOrEqual( 1, count( $for_realtor ) );
		foreach ( $for_realtor as $ov ) {
			$this->assertSame( 'realtor_warm', $ov[ Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF ] );
		}
	}

	public function test_invalid_goal_key_skipped(): void {
		$registry = new Goal_Style_Preset_Overlay_Registry();
		$registry->load(
			array(
				array(
					Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_PRESET_KEY => 'goal_unknown_x',
					Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY         => 'invalid_goal_xyz',
					Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF => 'realtor_warm',
					Goal_Style_Preset_Overlay_Registry::FIELD_STATUS          => 'active',
				),
			)
		);
		$this->assertNull( $registry->get( 'goal_unknown_x' ) );
		$this->assertSame( array(), $registry->get_all() );
	}
}
