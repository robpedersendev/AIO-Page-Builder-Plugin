<?php
/**
 * Tests for Industry_Subtype_Preview_Influence_View_Model and subtype-aware preview (Prompt 441).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\ViewModels\Industry\Industry_Subtype_Preview_Influence_View_Model;
use PHPUnit\Framework\TestCase;

/**
 * @group industry
 */
final class Industry_Subtype_Preview_Influence_View_Model_Test extends TestCase {

	public static function setUpBeforeClass(): void {
		$plugin_root = \dirname( __DIR__, 2 );
		require_once $plugin_root . '/src/Admin/ViewModels/Industry/Industry_Subtype_Preview_Influence_View_Model.php';
	}

	public function test_none_returns_has_subtype_false(): void {
		$vm = Industry_Subtype_Preview_Influence_View_Model::none();
		$this->assertFalse( $vm->has_subtype() );
		$arr = $vm->to_array();
		$this->assertArrayHasKey( 'has_subtype', $arr );
		$this->assertFalse( $arr['has_subtype'] );
		$this->assertSame( '', $arr['subtype_key'] );
		$this->assertFalse( $arr['helper_refinement_applied'] );
		$this->assertFalse( $arr['onepager_refinement_applied'] );
	}

	public function test_to_array_includes_all_keys_when_has_subtype(): void {
		$vm = new Industry_Subtype_Preview_Influence_View_Model(
			true,
			'realtor_buyer_agent',
			'Buyer Agent',
			'Realtor subtype for buyer-focused agents.',
			true,
			false,
			array( 'Note one' ),
			''
		);
		$arr = $vm->to_array();
		$this->assertTrue( $arr['has_subtype'] );
		$this->assertSame( 'realtor_buyer_agent', $arr['subtype_key'] );
		$this->assertSame( 'Buyer Agent', $arr['subtype_label'] );
		$this->assertSame( 'Realtor subtype for buyer-focused agents.', $arr['subtype_summary'] );
		$this->assertTrue( $arr['helper_refinement_applied'] );
		$this->assertFalse( $arr['onepager_refinement_applied'] );
		$this->assertSame( array( 'Note one' ), $arr['caution_notes'] );
		$this->assertSame( '', $arr['bundle_context'] );
	}

	public function test_subtype_influence_array_shape_is_bounded(): void {
		$arr = Industry_Subtype_Preview_Influence_View_Model::none()->to_array();
		$expected_keys = array( 'has_subtype', 'subtype_key', 'subtype_label', 'subtype_summary', 'helper_refinement_applied', 'onepager_refinement_applied', 'caution_notes', 'bundle_context' );
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $arr, "Subtype influence must include key: {$key}" );
		}
		$this->assertCount( count( $expected_keys ), $arr, 'Subtype influence must be bounded to expected keys only' );
	}
}
