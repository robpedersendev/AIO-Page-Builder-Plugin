<?php
/**
 * Unit tests for ACF handoff group marker (Prompt 315).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Uninstall\ACF_Handoff_Group_Marker;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Uninstall/ACF_Handoff_Group_Marker.php';

final class ACF_Handoff_Group_Marker_Test extends TestCase {

	public function test_mark_adds_origin_key_and_value(): void {
		$group  = array(
			'key'    => 'group_aio_st01_hero',
			'title'  => 'Hero Fields',
			'fields' => array(),
		);
		$marked = ACF_Handoff_Group_Marker::mark( $group );

		$this->assertArrayHasKey( ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_KEY, $marked );
		$this->assertSame( ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_VALUE, $marked[ ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_KEY ] );
		$this->assertSame( 'group_aio_st01_hero', $marked['key'] );
	}

	public function test_is_handoff_group_returns_true_for_marked_group(): void {
		$group = array(
			'key'                                        => 'group_aio_st01_hero',
			ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_KEY => ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_VALUE,
		);
		$this->assertTrue( ACF_Handoff_Group_Marker::is_handoff_group( $group ) );
	}

	public function test_is_handoff_group_returns_false_for_unmarked_group(): void {
		$group = array(
			'key'   => 'group_other_plugin',
			'title' => 'Other',
		);
		$this->assertFalse( ACF_Handoff_Group_Marker::is_handoff_group( $group ) );
	}

	public function test_is_handoff_group_returns_false_for_different_origin_value(): void {
		$group = array(
			'key'                                        => 'group_aio_st01_hero',
			ACF_Handoff_Group_Marker::HANDOFF_ORIGIN_KEY => 'other_plugin',
		);
		$this->assertFalse( ACF_Handoff_Group_Marker::is_handoff_group( $group ) );
	}
}
