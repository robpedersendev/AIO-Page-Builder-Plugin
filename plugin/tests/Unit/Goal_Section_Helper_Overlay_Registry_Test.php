<?php
/**
 * Unit tests for Goal_Section_Helper_Overlay_Registry (Prompt 506).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Goal_Section_Helper_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/GoalSectionHelperOverlays/Builtin_Goal_Section_Helper_Overlays.php';

final class Goal_Section_Helper_Overlay_Registry_Test extends TestCase {

	public function test_load_and_get_returns_overlay(): void {
		$registry = new Goal_Section_Helper_Overlay_Registry();
		$registry->load( array(
			array(
				Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY   => 'calls',
				Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
				Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE    => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
				Goal_Section_Helper_Overlay_Registry::FIELD_STATUS  => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
			),
		) );
		$ov = $registry->get( 'calls', 'hero_conv_02' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls', $ov[ Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY ] );
		$this->assertSame( 'hero_conv_02', $ov[ Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY ] );
	}

	public function test_get_for_goal_returns_overlays(): void {
		$registry = new Goal_Section_Helper_Overlay_Registry();
		$registry->load( Goal_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions() );
		$for_calls = $registry->get_for_goal( 'calls' );
		$this->assertGreaterThanOrEqual( 1, count( $for_calls ) );
		foreach ( $for_calls as $ov ) {
			$this->assertSame( 'calls', $ov[ Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY ] );
		}
	}
}
