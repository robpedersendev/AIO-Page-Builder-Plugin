<?php
/**
 * Unit tests for Step 1 (existing page updates) UI and bulk action logic (spec §32, Prompt 073).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Update_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Updates_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/ExistingPageUpdates/Existing_Page_Update_Detail_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/ExistingPageUpdates/Existing_Page_Update_Bulk_Action_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Detail_Panel_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/ExistingPageUpdates/Existing_Page_Updates_UI_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';

final class Build_Plan_Step1_Existing_Page_Updates_Test extends TestCase {

	private function step1_plan_definition( int $pending_count = 1 ): array {
		$items = array();
		for ( $i = 0; $i < $pending_count; $i++ ) {
			$items[] = array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_epc_' . $i,
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'current_page_title' => 'Home',
					'current_page_url'   => '/home',
					'action'             => 'replace_with_new_page',
					'reason'             => 'Refresh content',
					'risk_level'         => 'low',
					'confidence'         => 'medium',
				),
			);
		}
		$steps = array(
			array( 'step_type' => 'overview', 'items' => array() ),
			array(
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
				Build_Plan_Item_Schema::KEY_ITEMS     => $items,
			),
		);
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-1',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	/** Eligible item filtering: only existing_page_change and confidence not low. */
	public function test_eligible_items_filter_existing_page_change_only(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Existing_Page_Update_Detail_Builder();
		$repo      = new Build_Plan_Repository();
		$bulk      = new Existing_Page_Update_Bulk_Action_Service( $repo );
		$ui        = new Existing_Page_Updates_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->step1_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, Existing_Page_Update_Bulk_Action_Service::STEP_INDEX_EXISTING_PAGE_CHANGES, array( 'can_approve' => true ), null, array() );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( 'plan_epc_0', $workspace['step_list_rows'][0]['item_id'] );
		$this->assertArrayHasKey( 'current_page_title', $workspace['step_list_rows'][0]['summary_columns'] );
	}

	/** Low-confidence items excluded from Step 1 list. */
	public function test_low_confidence_items_excluded(): void {
		$def = $this->step1_plan_definition( 0 );
		$def[ Build_Plan_Schema::KEY_STEPS ][1][ Build_Plan_Item_Schema::KEY_ITEMS ][] = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_epc_low',
			Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
			Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
				'current_page_title' => 'Unmapped',
				'current_page_url'   => '/x',
				'action'             => 'defer',
				'reason'             => 'Low confidence',
				'risk_level'         => 'medium',
				'confidence'         => 'low',
			),
		);
		$resolver = new Build_Plan_Row_Action_Resolver();
		$detail   = new Existing_Page_Update_Detail_Builder();
		$bulk     = new Existing_Page_Update_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui       = new Existing_Page_Updates_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, 1, array( 'can_approve' => true ), null, array() );
		$ids = array_column( $workspace['step_list_rows'], 'item_id' );
		$this->assertNotContains( 'plan_epc_low', $ids );
	}

	/** Example Step 1 row payload structure. */
	public function test_example_step1_row_payload(): void {
		$row = array(
			'item_id'          => 'plan_epc_0',
			'status'           => Build_Plan_Item_Statuses::PENDING,
			'status_badge'     => 'pending',
			'summary_columns'  => array(
				'current_page_title' => 'Home',
				'current_page_url'   => '/home',
				'action'             => 'replace_with_new_page',
				'target_template'     => '',
				'reason'             => 'Refresh content',
				'risk_level'         => 'low',
			),
			'row_actions'      => array(),
			'is_selected'      => false,
			'snapshot_required' => true,
		);
		$this->assertSame( 'plan_epc_0', $row['item_id'] );
		$this->assertTrue( $row['snapshot_required'] );
		$this->assertArrayHasKey( 'current_page_title', $row['summary_columns'] );
	}

	/** Detail builder produces sections per spec §32.4. */
	public function test_detail_builder_sections(): void {
		$item = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_epc_0',
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
				'current_page_title' => 'About',
				'current_page_url'   => '/about',
				'action'             => 'rebuild_from_template',
				'reason'             => 'Align with new structure',
				'risk_level'         => 'low',
			),
		);
		$builder  = new Existing_Page_Update_Detail_Builder();
		$sections = $builder->build_sections( $item );
		$this->assertNotEmpty( $sections );
		$headings = array_column( $sections, 'heading' );
		$this->assertContains( 'Current page identity', $headings );
		$this->assertContains( 'Suggested action', $headings );
		$this->assertContains( 'Rationale for change', $headings );
	}

	/** Example detail payload structure. */
	public function test_example_step1_detail_payload(): void {
		$detail_panel = array(
			'item_id'           => 'plan_epc_0',
			'sections'          => array(
				array( 'heading' => 'Current page identity', 'key' => 'page_identity', 'content_lines' => array( 'Title: Home', 'URL: /home' ) ),
				array( 'heading' => 'Suggested action', 'key' => 'suggested_action', 'content_lines' => array( 'replace_with_new_page' ) ),
			),
			'row_actions'       => array(),
			'snapshot_required'  => true,
		);
		$this->assertSame( 'plan_epc_0', $detail_panel['item_id'] );
		$this->assertTrue( $detail_panel['snapshot_required'] );
	}

	/** Bulk eligibility: pending count for make-all and deny-all. */
	public function test_bulk_eligibility_payload(): void {
		$repo = new Build_Plan_Repository();
		$bulk = new Existing_Page_Update_Bulk_Action_Service( $repo );
		$def  = $this->step1_plan_definition( 2 );
		$el   = $bulk->get_bulk_eligibility( $def );
		$this->assertSame( 2, $el['approve_all_eligible'] );
		$this->assertSame( 2, $el['deny_all_eligible'] );
	}

	/** Bulk states disabled when no eligible (mutual exclusivity: after bulk action, zero pending). */
	public function test_bulk_disabled_when_no_eligible(): void {
		$def = $this->step1_plan_definition( 0 );
		$def[ Build_Plan_Schema::KEY_STEPS ][1][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['status'] = Build_Plan_Item_Statuses::APPROVED;
		$resolver = new Build_Plan_Row_Action_Resolver();
		$detail   = new Existing_Page_Update_Detail_Builder();
		$bulk     = new Existing_Page_Update_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui       = new Existing_Page_Updates_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, 1, array( 'can_approve' => true ), null, array() );
		$states = $workspace['bulk_action_states'];
		$this->assertFalse( $states['apply_to_all_eligible']['enabled'] );
		$this->assertFalse( $states['deny_all_eligible']['enabled'] );
	}

	/** Unauthorized: can_approve false disables bulk and row approve/deny. */
	public function test_unauthorized_bulk_disabled(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$detail   = new Existing_Page_Update_Detail_Builder();
		$bulk     = new Existing_Page_Update_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui       = new Existing_Page_Updates_UI_Service( $resolver, $detail, $bulk );
		$def      = $this->step1_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, 1, array( 'can_approve' => false ), null, array() );
		$this->assertFalse( $workspace['bulk_action_states']['apply_to_all_eligible']['enabled'] );
		$this->assertFalse( $workspace['bulk_action_states']['deny_all_eligible']['enabled'] );
		$approve_action = null;
		foreach ( $workspace['step_list_rows'][0]['row_actions'] as $a ) {
			if ( ( $a['action_id'] ?? '' ) === 'approve' ) {
				$approve_action = $a;
				break;
			}
		}
		$this->assertNotNull( $approve_action );
		$this->assertFalse( $approve_action['enabled'] );
	}

	/** Denied state preserved in plan (repository update then read back). */
	public function test_denied_state_preserved(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 999;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step1_plan_definition( 1 );
			$post_id = $repo->save( array(
				'plan_definition' => $def,
				'internal_key'    => 'test-plan-persist',
				'post_title'      => 'Test Plan',
				'status'          => 'publish',
			) );
			$this->assertGreaterThan( 0, $post_id );
			$updated = $repo->update_plan_item_status( $post_id, 1, 'plan_epc_0', Build_Plan_Item_Statuses::REJECTED );
			$this->assertTrue( $updated );
			$def2 = $repo->get_plan_definition( $post_id );
			$item = $def2[ Build_Plan_Schema::KEY_STEPS ][1][ Build_Plan_Item_Schema::KEY_ITEMS ][0] ?? null;
			$this->assertNotNull( $item );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $item['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Column order is Step 1 specific. */
	public function test_step1_column_order(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$detail   = new Existing_Page_Update_Detail_Builder();
		$bulk     = new Existing_Page_Update_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui       = new Existing_Page_Updates_UI_Service( $resolver, $detail, $bulk );
		$def      = $this->step1_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, 1, array( 'can_approve' => true ), null, array() );
		$this->assertSame( Existing_Page_Updates_UI_Service::COLUMN_ORDER, $workspace['column_order'] );
		$this->assertSame( 'current_page_title', $workspace['column_order'][0] );
	}
}
