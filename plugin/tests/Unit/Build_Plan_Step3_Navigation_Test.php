<?php
/**
 * Unit tests for Step 3 (navigation) UI and bulk action logic (spec §34, Prompt 075).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Detail_Panel_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/Navigation/Navigation_Detail_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/Navigation/Navigation_Bulk_Action_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/Navigation/Navigation_Step_UI_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';

final class Build_Plan_Step3_Navigation_Test extends TestCase {

	/** Example navigation diff payload (spec §34.1, §34.3). */
	public const EXAMPLE_NAVIGATION_DIFF = array(
		'header' => array(
			'current'   => 'Primary Menu',
			'proposed'  => 'Main Navigation',
			'diff_hint' => array(
				'Missing pages in navigation: /about, /contact',
				'Outdated menu labels: Home → Homepage',
				'Ordering differences detected',
			),
		),
		'footer' => array(
			'current'   => 'Footer Links',
			'proposed'  => 'Footer Navigation',
			'diff_hint' => array( 'Location-assignment differences' ),
		),
	);

	/** Example Step 3 detail payload (spec §34.4–34.7). */
	public const EXAMPLE_NAVIGATION_DETAIL = array(
		'item_id'     => 'plan_mcp_0',
		'sections'    => array(
			array(
				'heading'       => 'Navigation context',
				'key'           => 'navigation_context',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Current vs proposed navigation',
				'key'           => 'current_vs_proposed',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Detected differences',
				'key'           => 'diff_summary',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Menu rename / create / location',
				'key'           => 'menu_action',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Menu item assignment',
				'key'           => 'item_assignment',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Menu location assignment',
				'key'           => 'location_assignment',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Navigation validation',
				'key'           => 'validation',
				'content_lines' => array(),
			),
		),
		'row_actions' => array(),
	);

	/** Example validation summary payload (spec §34.10). */
	public const EXAMPLE_VALIDATION_SUMMARY = array(
		'valid'    => false,
		'messages' => array(
			'Referenced page /about does not exist.',
			'Theme location "primary" is not registered.',
		),
	);

	private function navigation_plan_definition( int $menu_change_count = 1 ): array {
		$items = array();
		for ( $i = 0; $i < $menu_change_count; $i++ ) {
			$items[] = array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_mcp_' . $i,
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'menu_context'       => $i === 0 ? 'header' : 'footer',
					'action'             => $i === 0 ? 'rename' : 'update_existing',
					'current_menu_name'  => $i === 0 ? 'Primary Menu' : 'Footer Links',
					'proposed_menu_name' => $i === 0 ? 'Main Navigation' : 'Footer Navigation',
					'items'              => array(
						array(
							'label' => 'Home',
							'url'   => '/',
							'order' => 1,
						),
						array(
							'label' => 'About',
							'url'   => '/about',
							'order' => 2,
						),
					),
					'diff_summary'       => array( 'Missing pages in navigation', 'Ordering differences' ),
				),
			);
		}
		$steps = array(
			array(
				'step_type' => 'overview',
				'items'     => array(),
			),
			array(
				'step_type' => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
				'items'     => array(),
			),
			array(
				'step_type' => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
				'items'     => array(),
			),
			array(
				'step_type' => Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW,
				'items'     => array(),
			),
			array(
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NAVIGATION,
				Build_Plan_Item_Schema::KEY_ITEMS     => $items,
			),
		);
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-nav',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	/** Eligible items: only menu_change in navigation step. */
	public function test_eligible_items_filter_menu_change_only(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Navigation_Detail_Builder();
		$bulk      = new Navigation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new Navigation_Step_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->navigation_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION, array( 'can_approve' => true ), null, array() );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( 'plan_mcp_0', $workspace['step_list_rows'][0]['item_id'] );
		$this->assertArrayHasKey( 'menu_context', $workspace['step_list_rows'][0]['summary_columns'] );
	}

	/** Example navigation diff payload structure. */
	public function test_example_navigation_diff_payload(): void {
		$diff = self::EXAMPLE_NAVIGATION_DIFF;
		$this->assertArrayHasKey( 'header', $diff );
		$this->assertArrayHasKey( 'current', $diff['header'] );
		$this->assertArrayHasKey( 'proposed', $diff['header'] );
		$this->assertArrayHasKey( 'diff_hint', $diff['header'] );
		$this->assertNotEmpty( $diff['header']['diff_hint'] );
	}

	/** Detail builder produces sections per spec §34.1–34.10. */
	public function test_detail_builder_sections(): void {
		$item     = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID => 'plan_mcp_0',
			Build_Plan_Item_Schema::KEY_STATUS  => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'menu_context'       => 'header',
				'action'             => 'rename',
				'current_menu_name'  => 'Primary',
				'proposed_menu_name' => 'Main Nav',
				'items'              => array(),
				'diff_summary'       => array( 'Outdated labels' ),
			),
		);
		$builder  = new Navigation_Detail_Builder();
		$sections = $builder->build_sections( $item );
		$this->assertNotEmpty( $sections );
		$headings = array_column( $sections, 'heading' );
		$this->assertContains( 'Navigation context', $headings );
		$this->assertContains( 'Current vs proposed navigation', $headings );
		$this->assertContains( 'Detected differences', $headings );
		$this->assertContains( 'Menu rename / create / location', $headings );
		$this->assertContains( 'Navigation validation', $headings );
	}

	/** Example detail payload structure. */
	public function test_example_navigation_detail_payload(): void {
		$detail = self::EXAMPLE_NAVIGATION_DETAIL;
		$this->assertSame( 'plan_mcp_0', $detail['item_id'] );
		$this->assertGreaterThanOrEqual( 5, count( $detail['sections'] ) );
	}

	/** Validation summary in workspace. */
	public function test_validation_summary_payload(): void {
		$summary = self::EXAMPLE_VALIDATION_SUMMARY;
		$this->assertFalse( $summary['valid'] );
		$this->assertCount( 2, $summary['messages'] );
	}

	/** Navigation comparison built from items. */
	public function test_navigation_comparison_built(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Navigation_Detail_Builder();
		$bulk      = new Navigation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new Navigation_Step_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->navigation_plan_definition( 2 );
		$workspace = $ui->build_workspace( $def, Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION, array( 'can_approve' => true ), null, array() );
		$this->assertArrayHasKey( 'navigation_comparison', $workspace );
		$this->assertArrayHasKey( 'validation_summary', $workspace );
		$this->assertArrayHasKey( 'header', $workspace['navigation_comparison'] );
		$this->assertArrayHasKey( 'footer', $workspace['navigation_comparison'] );
		$this->assertArrayHasKey( 'valid', $workspace['validation_summary'] );
		$this->assertArrayHasKey( 'messages', $workspace['validation_summary'] );
	}

	/** Bulk eligibility: approve_all and deny_all. */
	public function test_bulk_eligibility_payload(): void {
		$repo = new Build_Plan_Repository();
		$bulk = new Navigation_Bulk_Action_Service( $repo );
		$def  = $this->navigation_plan_definition( 2 );
		$el   = $bulk->get_bulk_eligibility( $def );
		$this->assertSame( 2, $el['approve_all_eligible'] );
		$this->assertSame( 2, $el['deny_all_eligible'] );
	}

	/** Per-item approve updates status. */
	public function test_approve_item_state_change(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 666;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->navigation_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-nav-approve',
					'post_title'      => 'Test Nav',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk    = new Navigation_Bulk_Action_Service( $repo );
			$updated = $bulk->approve_item( $post_id, 'plan_mcp_0' );
			$this->assertTrue( $updated );
			$def2  = $repo->get_plan_definition( $post_id );
			$step4 = $def2[ Build_Plan_Schema::KEY_STEPS ][ Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION ] ?? null;
			$this->assertNotNull( $step4 );
			$item = $step4[ Build_Plan_Item_Schema::KEY_ITEMS ][0] ?? null;
			$this->assertNotNull( $item );
			$this->assertSame( Build_Plan_Item_Statuses::APPROVED, $item['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Per-item deny updates status. */
	public function test_deny_item_state_change(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 665;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->navigation_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-nav-deny',
					'post_title'      => 'Test Nav Deny',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk    = new Navigation_Bulk_Action_Service( $repo );
			$updated = $bulk->deny_item( $post_id, 'plan_mcp_0' );
			$this->assertTrue( $updated );
			$def2 = $repo->get_plan_definition( $post_id );
			$item = $def2[ Build_Plan_Schema::KEY_STEPS ][ Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION ][ Build_Plan_Item_Schema::KEY_ITEMS ][0] ?? null;
			$this->assertNotNull( $item );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $item['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Unauthorized: can_approve false disables bulk and row approve/deny. */
	public function test_unauthorized_bulk_disabled(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Navigation_Detail_Builder();
		$bulk      = new Navigation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new Navigation_Step_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->navigation_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION, array( 'can_approve' => false ), null, array() );
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

	/** Validation messages aggregated in validation_summary. */
	public function test_validation_messages_aggregated(): void {
		$def = $this->navigation_plan_definition( 1 );
		$def[ Build_Plan_Schema::KEY_STEPS ][4][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['payload']['validation_messages'] = array( 'Page /x not found.' );
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Navigation_Detail_Builder();
		$bulk      = new Navigation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new Navigation_Step_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION, array( 'can_approve' => true ), null, array() );
		$this->assertFalse( $workspace['validation_summary']['valid'] );
		$this->assertContains( 'Page /x not found.', $workspace['validation_summary']['messages'] );
	}

	/** Column order is Step 3 (navigation) specific. */
	public function test_navigation_column_order(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new Navigation_Detail_Builder();
		$bulk      = new Navigation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new Navigation_Step_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->navigation_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION, array( 'can_approve' => true ), null, array() );
		$this->assertSame( Navigation_Step_UI_Service::COLUMN_ORDER, $workspace['column_order'] );
		$this->assertSame( 'menu_context', $workspace['column_order'][0] );
	}
}
