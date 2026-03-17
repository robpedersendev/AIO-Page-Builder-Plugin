<?php
/**
 * Unit tests for Goal_Page_OnePager_Overlay_Registry (Prompt 508).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Docs/Goal_Page_OnePager_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Docs/GoalPageOnePagerOverlays/Builtin_Goal_Page_OnePager_Overlays.php';

final class Goal_Page_OnePager_Overlay_Registry_Test extends TestCase {

	public function test_load_and_get_returns_overlay(): void {
		$registry = new Goal_Page_OnePager_Overlay_Registry();
		$registry->load( array(
			array(
				Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
				Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_home_conversion_01',
				Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE    => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
				Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS  => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
			),
		) );
		$ov = $registry->get( 'calls', 'pt_home_conversion_01' );
		$this->assertNotNull( $ov );
		$this->assertSame( 'calls', $ov[ Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY ] );
		$this->assertSame( 'pt_home_conversion_01', $ov[ Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY ] );
	}

	public function test_get_for_goal_returns_overlays(): void {
		$registry = new Goal_Page_OnePager_Overlay_Registry();
		$registry->load( Goal_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions() );
		$for_calls = $registry->get_for_goal( 'calls' );
		$this->assertGreaterThanOrEqual( 1, count( $for_calls ) );
		foreach ( $for_calls as $ov ) {
			$this->assertSame( 'calls', $ov[ Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY ] );
		}
	}

	public function test_invalid_scope_skipped(): void {
		$registry = new Goal_Page_OnePager_Overlay_Registry();
		$registry->load( array(
			array(
				Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
				Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_home_conversion_01',
				Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE    => 'wrong_scope',
				Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS  => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
			),
		) );
		$this->assertNull( $registry->get( 'calls', 'pt_home_conversion_01' ) );
		$this->assertSame( array(), $registry->get_all() );
	}
}
