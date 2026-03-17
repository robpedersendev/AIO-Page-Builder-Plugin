<?php
/**
 * Unit tests for Industry_Guided_Repair_Screen and view model (Prompt 527). Repair review rendering and bounded behavior.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Guided_Repair_Screen;
use AIOPageBuilder\Admin\ViewModels\Industry\Industry_Guided_Repair_View_Model;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Admin/ViewModels/Industry/Industry_Guided_Repair_View_Model.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Guided_Repair_Screen.php';

final class Industry_Guided_Repair_Screen_Test extends TestCase {

	public function test_screen_has_expected_slug_and_capability(): void {
		$screen = new Industry_Guided_Repair_Screen( null );
		$this->assertSame( 'aio-page-builder-industry-guided-repair', Industry_Guided_Repair_Screen::SLUG );
		$this->assertSame( 'aio_manage_settings', $screen->get_capability() );
	}

	public function test_view_model_empty_candidates_returns_structure(): void {
		$vm = new Industry_Guided_Repair_View_Model( array(), array( 'health_report' => '#' ), '', '' );
		$this->assertCount( 0, $vm->get_candidates() );
		$this->assertArrayHasKey( 'health_report', $vm->get_links() );
		$this->assertSame( '', $vm->get_message() );
		$arr = $vm->to_array();
		$this->assertArrayHasKey( Industry_Guided_Repair_View_Model::KEY_CANDIDATES, $arr );
		$this->assertArrayHasKey( Industry_Guided_Repair_View_Model::KEY_LINKS, $arr );
	}

	public function test_view_model_with_candidate_has_action_types_bounded(): void {
		$candidates = array(
			array(
				'source'            => Industry_Guided_Repair_View_Model::SOURCE_HEALTH_ERROR,
				'object_type'       => 'profile',
				'key'               => 'primary_industry_key',
				'issue_summary'     => 'Pack not found.',
				'related_refs'      => array(),
				'repair_suggestion' => null,
				'is_advisory_only'  => true,
				'action_type'       => Industry_Guided_Repair_View_Model::ACTION_NONE,
				'conflict'          => null,
				'profile_field'     => '',
				'suggested_value'   => '',
			),
		);
		$vm = new Industry_Guided_Repair_View_Model( $candidates, array() );
		$this->assertCount( 1, $vm->get_candidates() );
		$this->assertSame( Industry_Guided_Repair_View_Model::ACTION_NONE, $vm->get_candidates()[0]['action_type'] );
		$this->assertTrue( $vm->get_candidates()[0]['is_advisory_only'] );
	}

	public function test_ambiguous_case_remains_advisory_only(): void {
		$candidates = array(
			array(
				'source'            => Industry_Guided_Repair_View_Model::SOURCE_HEALTH_WARNING,
				'object_type'       => 'pack',
				'key'               => 'realtor',
				'issue_summary'     => 'Pack CTA ref does not resolve.',
				'related_refs'      => array( 'cta_unknown' ),
				'repair_suggestion' => null,
				'is_advisory_only'  => true,
				'action_type'       => Industry_Guided_Repair_View_Model::ACTION_NONE,
				'conflict'          => null,
				'profile_field'     => '',
				'suggested_value'   => '',
			),
		);
		$vm = new Industry_Guided_Repair_View_Model( $candidates, array() );
		$this->assertSame( Industry_Guided_Repair_View_Model::ACTION_NONE, $vm->get_candidates()[0]['action_type'] );
	}
}
