<?php
/**
 * Unit tests for Industry_Style_Layer_Diff_Service: compare returns parent, goal, combined, diff rows (Prompt 549).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Goal_Style_Preset_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Style_Layer_Diff_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Goal_Style_Preset_Overlay_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Style_Layer_Diff_Service.php';

final class Industry_Style_Layer_Diff_Service_Test extends TestCase {

	public function test_compare_with_null_registry_returns_parent_not_present(): void {
		$service = new Industry_Style_Layer_Diff_Service( null, null );
		$result  = $service->compare( 'some_preset', 'calls' );
		$this->assertFalse( $result[ Industry_Style_Layer_Diff_Service::RESULT_PARENT ]['present'] );
		$this->assertNull( $result[ Industry_Style_Layer_Diff_Service::RESULT_SUBTYPE ] );
		$this->assertFalse( $result[ Industry_Style_Layer_Diff_Service::RESULT_GOAL ]['present'] );
		$this->assertSame( array(), $result[ Industry_Style_Layer_Diff_Service::RESULT_TOKEN_DIFF_ROWS ] );
		$this->assertSame( array(), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMPONENT_DIFF_ROWS ] );
	}

	public function test_compare_with_preset_only_returns_parent_present_combined_equals_parent(): void {
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array(
			array(
				Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => 'test_preset',
				Industry_Style_Preset_Registry::FIELD_LABEL            => 'Test Preset',
				Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => '1',
				Industry_Style_Preset_Registry::FIELD_STATUS          => Industry_Style_Preset_Registry::STATUS_ACTIVE,
				Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY    => 'test_industry',
				Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES    => array( '--aio-color-primary' => '#111' ),
				Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS => array( 'button_primary' ),
			),
		) );
		$service = new Industry_Style_Layer_Diff_Service( $preset_registry, null );
		$result  = $service->compare( 'test_preset', '' );
		$this->assertTrue( $result[ Industry_Style_Layer_Diff_Service::RESULT_PARENT ]['present'] );
		$this->assertFalse( $result[ Industry_Style_Layer_Diff_Service::RESULT_GOAL ]['present'] );
		$this->assertSame( array( '--aio-color-primary' => '#111' ), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMBINED ]['token_values'] );
		$this->assertSame( array( 'button_primary' ), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMBINED ]['component_override_refs'] );
		$this->assertCount( 1, $result[ Industry_Style_Layer_Diff_Service::RESULT_TOKEN_DIFF_ROWS ] );
		$this->assertCount( 1, $result[ Industry_Style_Layer_Diff_Service::RESULT_COMPONENT_DIFF_ROWS ] );
	}

	public function test_compare_with_preset_and_goal_overlay_merges_combined(): void {
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array(
			array(
				Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => 'test_preset',
				Industry_Style_Preset_Registry::FIELD_LABEL            => 'Test Preset',
				Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => '1',
				Industry_Style_Preset_Registry::FIELD_STATUS          => Industry_Style_Preset_Registry::STATUS_ACTIVE,
				Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY    => 'test_industry',
				Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES    => array( '--aio-color-primary' => '#111' ),
				Industry_Style_Preset_Registry::FIELD_COMPONENT_OVERRIDE_REFS => array(),
			),
		) );
		$overlay_registry = new Goal_Style_Preset_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_PRESET_KEY => 'calls_test',
				Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY        => 'calls',
				Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF => 'test_preset',
				Goal_Style_Preset_Overlay_Registry::FIELD_STATUS         => Goal_Style_Preset_Overlay_Registry::STATUS_ACTIVE,
				Goal_Style_Preset_Overlay_Registry::FIELD_TOKEN_VALUES   => array( '--aio-color-primary' => '#0066cc' ),
				Goal_Style_Preset_Overlay_Registry::FIELD_COMPONENT_OVERRIDE_REFS => array( 'cta_phone' ),
			),
		) );
		$this->assertNotNull( $preset_registry->get( 'test_preset' ), 'Preset test_preset must be in registry' );
		$overlays = $overlay_registry->get_overlays_for_preset( 'test_preset' );
		$this->assertCount( 1, $overlays, 'Overlay for test_preset must be loaded' );
		$this->assertSame( 'calls', $overlays[0][ Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY ] ?? '', 'Overlay goal_key must match' );
		$service = new Industry_Style_Layer_Diff_Service( $preset_registry, $overlay_registry );
		$result  = $service->compare( 'test_preset', 'calls' );
		$this->assertTrue( $result[ Industry_Style_Layer_Diff_Service::RESULT_PARENT ]['present'], 'Parent preset must be present' );
		// Combined reflects goal overlay merge when overlay is found for preset+goal.
		$this->assertSame( array( '--aio-color-primary' => '#0066cc' ), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMBINED ]['token_values'] );
		$this->assertSame( array( 'cta_phone' ), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMBINED ]['component_override_refs'] );
		$token_rows = $result[ Industry_Style_Layer_Diff_Service::RESULT_TOKEN_DIFF_ROWS ];
		$this->assertCount( 1, $token_rows );
		$this->assertTrue( $token_rows[0]['changed'] );
		$this->assertSame( '#0066cc', $token_rows[0]['combined'] );
		$this->assertTrue( $result[ Industry_Style_Layer_Diff_Service::RESULT_GOAL ]['present'], 'Goal overlay should be present when overlay exists for preset+goal' );
	}

	public function test_compare_missing_preset_returns_parent_not_present(): void {
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array() );
		$service = new Industry_Style_Layer_Diff_Service( $preset_registry, null );
		$result  = $service->compare( 'nonexistent', '' );
		$this->assertFalse( $result[ Industry_Style_Layer_Diff_Service::RESULT_PARENT ]['present'] );
		$this->assertSame( array(), $result[ Industry_Style_Layer_Diff_Service::RESULT_COMBINED ]['token_values'] );
	}

	public function test_compare_goal_empty_skips_overlay(): void {
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array(
			array(
				Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => 'p1',
				Industry_Style_Preset_Registry::FIELD_LABEL            => 'P1',
				Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => '1',
				Industry_Style_Preset_Registry::FIELD_STATUS          => Industry_Style_Preset_Registry::STATUS_ACTIVE,
				Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY    => 'test_industry',
				Industry_Style_Preset_Registry::FIELD_TOKEN_VALUES    => array(),
			),
		) );
		$overlay_registry = new Goal_Style_Preset_Overlay_Registry();
		$overlay_registry->load( array(
			array(
				Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_PRESET_KEY => 'g1',
				Goal_Style_Preset_Overlay_Registry::FIELD_GOAL_KEY        => 'calls',
				Goal_Style_Preset_Overlay_Registry::FIELD_TARGET_PRESET_REF => 'p1',
				Goal_Style_Preset_Overlay_Registry::FIELD_STATUS         => Goal_Style_Preset_Overlay_Registry::STATUS_ACTIVE,
			),
		) );
		$service = new Industry_Style_Layer_Diff_Service( $preset_registry, $overlay_registry );
		$result  = $service->compare( 'p1', '' );
		$this->assertFalse( $result[ Industry_Style_Layer_Diff_Service::RESULT_GOAL ]['present'] );
	}
}
