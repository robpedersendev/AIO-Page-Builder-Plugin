<?php
/**
 * Unit tests for Step 2 (new page creation) UI and bulk action logic (spec §33, Prompt 074).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Bulk_Action_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_Detail_Builder;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_UI_Service;
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
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Stepper_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/NewPageCreation/New_Page_Creation_Detail_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/NewPageCreation/New_Page_Creation_Bulk_Action_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/NewPageCreation/New_Page_Creation_UI_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Recommendations/Template_Explanation_Builder_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Build_Plan_Scoring_Interface.php';
require_once $plugin_root . '/src/Domain/Industry/AI/Industry_Build_Plan_Scoring_Service.php';
require_once $plugin_root . '/src/Admin/ViewModels/BuildPlan/Industry_Build_Plan_Explanation_View_Model.php';
require_once $plugin_root . '/src/Domain/Industry/Overrides/Industry_Build_Plan_Item_Override_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository_Interface.php';

final class Build_Plan_Step2_New_Page_Creation_Test extends TestCase {

	/** Example Step 2 row payload (spec §33.3). */
	public const EXAMPLE_STEP2_ROW = array(
		'item_id'               => 'plan_npc_0',
		'status'                => Build_Plan_Item_Statuses::PENDING,
		'status_badge'          => 'pending',
		'summary_columns'       => array(
			'proposed_page_title' => 'Contact Us',
			'proposed_slug'       => 'contact-us',
			'purpose'             => 'Lead capture and support',
			'template_key'        => 'contact',
			'hierarchy_position'  => 'child of /about',
			'page_type'           => 'landing',
			'confidence'          => 'medium',
		),
		'row_actions'           => array(),
		'is_selected'           => false,
		'dependency_validation' => array(
			'blocking' => false,
			'messages' => array(),
		),
		'post_build_status'     => '',
	);

	/** Example Step 2 detail payload (spec §33.3–33.4). */
	public const EXAMPLE_STEP2_DETAIL = array(
		'item_id'     => 'plan_npc_0',
		'sections'    => array(
			array(
				'heading'       => 'Page metadata',
				'key'           => 'metadata',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Parent / child hierarchy',
				'key'           => 'parent_child',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Dependency validation',
				'key'           => 'dependency_validation',
				'content_lines' => array(),
			),
			array(
				'heading'       => 'Post-build status',
				'key'           => 'post_build_status',
				'content_lines' => array(),
			),
			array(
				'heading' => 'Retry and recovery',
				'key'     => 'retry_recovery',
				'content' => '<p>—</p>',
			),
		),
		'row_actions' => array(),
	);

	/** Example dependency-validation payload (spec §33.8). */
	public const EXAMPLE_DEPENDENCY_VALIDATION = array(
		'blocking' => true,
		'messages' => array(
			'Parent page must exist before creating this child.',
			'Template "contact" is not registered.',
		),
	);

	private function step2_plan_definition( int $pending_new_pages = 1, bool $with_blocking = false ): array {
		$items = array();
		for ( $i = 0; $i < $pending_new_pages; $i++ ) {
			$payload = array(
				'proposed_page_title' => 'Contact Us',
				'proposed_slug'       => 'contact-us',
				'purpose'             => 'Lead capture',
				'template_key'        => 'contact',
				'hierarchy_position'  => 'child of /about',
				'page_type'           => 'landing',
				'confidence'          => 'medium',
			);
			if ( $with_blocking ) {
				$payload['dependency_blocking_reasons'] = array( 'Parent page must exist.' );
			}
			$items[] = array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_' . $i,
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => $payload,
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
				Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
				Build_Plan_Item_Schema::KEY_ITEMS     => $items,
			),
		);
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-2',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	/** Eligible item filtering: only new_page and confidence not low. */
	public function test_eligible_items_filter_new_page_only(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->step2_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, New_Page_Creation_Bulk_Action_Service::STEP_INDEX_NEW_PAGES, array( 'can_approve' => true ), null, array() );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( 'plan_npc_0', $workspace['step_list_rows'][0]['item_id'] );
		$this->assertArrayHasKey( 'proposed_page_title', $workspace['step_list_rows'][0]['summary_columns'] );
	}

	/** Low-confidence new_page items excluded. */
	public function test_low_confidence_items_excluded(): void {
		$def = $this->step2_plan_definition( 0 );
		$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][] = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_low',
			Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
			Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
				'proposed_page_title' => 'Maybe Page',
				'proposed_slug'       => 'maybe',
				'purpose'             => 'Tentative',
				'template_key'        => 'default',
				'confidence'          => 'low',
			),
		);
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), null, array() );
		$ids       = array_column( $workspace['step_list_rows'], 'item_id' );
		$this->assertNotContains( 'plan_npc_low', $ids );
	}

	/** Example Step 2 row payload structure. */
	public function test_example_step2_row_payload(): void {
		$row = self::EXAMPLE_STEP2_ROW;
		$this->assertSame( 'plan_npc_0', $row['item_id'] );
		$this->assertArrayHasKey( 'dependency_validation', $row );
		$this->assertArrayHasKey( 'post_build_status', $row );
		$this->assertSame( 'proposed_page_title', array_key_first( $row['summary_columns'] ) );
	}

	/** Detail builder produces sections per spec §33.3–33.10. */
	public function test_detail_builder_sections(): void {
		$item     = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID => 'plan_npc_0',
			Build_Plan_Item_Schema::KEY_STATUS  => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'proposed_page_title' => 'About',
				'proposed_slug'       => 'about',
				'purpose'             => 'Company info',
				'template_key'        => 'default',
				'page_type'           => 'hub',
				'confidence'          => 'high',
			),
		);
		$builder  = new New_Page_Creation_Detail_Builder();
		$sections = $builder->build_sections( $item );
		$this->assertNotEmpty( $sections );
		$headings = array_column( $sections, 'heading' );
		$this->assertContains( 'Page metadata', $headings );
		$this->assertContains( 'Parent / child hierarchy', $headings );
		$this->assertContains( 'Dependency validation', $headings );
		$this->assertContains( 'Post-build status', $headings );
		$this->assertContains( 'Retry and recovery', $headings );
	}

	/** Example detail payload structure. */
	public function test_example_step2_detail_payload(): void {
		$detail = self::EXAMPLE_STEP2_DETAIL;
		$this->assertSame( 'plan_npc_0', $detail['item_id'] );
		$this->assertGreaterThanOrEqual( 4, count( $detail['sections'] ) );
	}

	/** Dependency validation blocking reasons in detail. */
	public function test_dependency_block_message_in_detail(): void {
		$item        = array(
			Build_Plan_Item_Schema::KEY_ITEM_ID => 'plan_npc_0',
			Build_Plan_Item_Schema::KEY_STATUS  => Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Schema::KEY_PAYLOAD => array(
				'proposed_page_title'         => 'Child',
				'dependency_blocking_reasons' => array( 'Parent page must exist before creating this child.' ),
			),
		);
		$builder     = new New_Page_Creation_Detail_Builder();
		$sections    = $builder->build_sections( $item );
		$dep_section = null;
		foreach ( $sections as $s ) {
			if ( ( $s['key'] ?? '' ) === 'dependency_validation' ) {
				$dep_section = $s;
				break;
			}
		}
		$this->assertNotNull( $dep_section );
		$this->assertNotEmpty( $dep_section['content_lines'] );
		$this->assertStringContainsString( 'Parent page must exist', $dep_section['content_lines'][0] );
	}

	/** Example dependency-validation payload structure. */
	public function test_example_dependency_validation_payload(): void {
		$payload = self::EXAMPLE_DEPENDENCY_VALIDATION;
		$this->assertTrue( $payload['blocking'] );
		$this->assertCount( 2, $payload['messages'] );
	}

	/** Bulk eligibility: build_all_eligible, build_selected_eligible, deny_all_eligible. */
	public function test_bulk_eligibility_payload(): void {
		$repo = new Build_Plan_Repository();
		$bulk = new New_Page_Creation_Bulk_Action_Service( $repo );
		$def  = $this->step2_plan_definition( 2 );
		$el   = $bulk->get_bulk_eligibility( $def );
		$this->assertSame( 2, $el['build_all_eligible'] );
		$this->assertSame( 2, $el['build_selected_eligible'] );
		$this->assertSame( 2, $el['deny_all_eligible'] );
	}

	/** Build-all, build-selected, and deny-all disabled when no pending. */
	public function test_bulk_disabled_when_no_eligible(): void {
		$def = $this->step2_plan_definition( 1 );
		$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['status'] = Build_Plan_Item_Statuses::APPROVED;
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), null, array() );
		$states    = $workspace['bulk_action_states'];
		$this->assertFalse( $states['apply_to_all_eligible']['enabled'] );
		$this->assertFalse( $states['deny_all_eligible']['enabled'] );
	}

	/** Deny All Eligible becomes enabled when pending eligible rows exist. */
	public function test_deny_all_enabled_when_eligible(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->step2_plan_definition( 2 );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), null, array() );
		$states    = $workspace['bulk_action_states'];
		$this->assertTrue( $states['deny_all_eligible']['enabled'] );
		$this->assertSame( 2, (int) $states['deny_all_eligible']['count_eligible'] );
	}

	/** Unauthorized: can_approve false disables bulk and row approve/deny. */
	public function test_unauthorized_bulk_disabled(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->step2_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => false ), null, array() );
		$this->assertFalse( $workspace['bulk_action_states']['apply_to_all_eligible']['enabled'] );
		$this->assertFalse( $workspace['bulk_action_states']['deny_all_eligible']['enabled'] );
		$approve_action = null;
		$deny_action    = null;
		foreach ( $workspace['step_list_rows'][0]['row_actions'] as $a ) {
			if ( ( $a['action_id'] ?? '' ) === 'approve' ) {
				$approve_action = $a;
			}
			if ( ( $a['action_id'] ?? '' ) === 'deny' ) {
				$deny_action = $a;
			}
		}
		$this->assertNotNull( $approve_action );
		$this->assertFalse( $approve_action['enabled'] );
		$this->assertNotNull( $deny_action );
		$this->assertFalse( $deny_action['enabled'] );
	}

	/** Post-build result status surfaced in row and detail. */
	public function test_post_build_status_in_payload(): void {
		$def = $this->step2_plan_definition( 1 );
		$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['payload']['post_build_status'] = 'built_successfully';
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), 'plan_npc_0', array() );
		$this->assertSame( 'built_successfully', $workspace['step_list_rows'][0]['post_build_status'] );
		$detail_sections = $workspace['detail_panel']['sections'] ?? array();
		$post_section    = null;
		foreach ( $detail_sections as $s ) {
			if ( ( $s['key'] ?? '' ) === 'post_build_status' ) {
				$post_section = $s;
				break;
			}
		}
		$this->assertNotNull( $post_section );
		$content = implode( ' ', $post_section['content_lines'] ?? array() );
		$this->assertStringContainsString( 'built_successfully', $content );
		// * Label must be truthful — never "placeholder".
		$this->assertStringContainsString( 'Post-build result:', $content );
		$this->assertStringNotContainsString( 'placeholder', strtolower( $content ) );
	}

	/** Column order is Step 2 specific and includes template_links (Prompt 192). */
	public function test_step2_column_order(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$def       = $this->step2_plan_definition( 1 );
		$workspace = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), null, array() );
		$this->assertSame( New_Page_Creation_UI_Service::COLUMN_ORDER, $workspace['column_order'] );
		$this->assertSame( 'proposed_page_title', $workspace['column_order'][0] );
		$this->assertContains( 'template_key', $workspace['column_order'] );
	}

	/** With recommendation builder, rows have proposed_template_summary, group_label, template_links column (Prompt 192). */
	public function test_step2_with_recommendation_builder_enriches_rows(): void {
		require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/Recommendations/Build_Plan_Template_Explanation_Builder.php';
		require_once dirname( __DIR__, 2 ) . '/src/Domain/BuildPlan/UI/New_Page_Template_Recommendation_Builder.php';
		require_once dirname( __DIR__, 2 ) . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
		require_once dirname( __DIR__, 2 ) . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
		$explanation            = new \AIOPageBuilder\Domain\BuildPlan\Recommendations\Build_Plan_Template_Explanation_Builder( new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository(), null );
		$recommendation_builder = new \AIOPageBuilder\Domain\BuildPlan\UI\New_Page_Template_Recommendation_Builder( $explanation );
		$resolver               = new Build_Plan_Row_Action_Resolver();
		$detail                 = new New_Page_Creation_Detail_Builder();
		$bulk                   = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui                     = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk, $recommendation_builder );
		$def                    = $this->step2_plan_definition( 1 );
		$workspace              = $ui->build_workspace( $def, 2, array( 'can_approve' => true ), null, array() );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$row = $workspace['step_list_rows'][0];
		$this->assertArrayHasKey( 'proposed_template_summary', $row );
		$this->assertArrayHasKey( 'group_label', $row );
		$this->assertArrayHasKey( 'hierarchy_context_summary', $row );
		$this->assertArrayHasKey( 'template_selection_reason', $row );
		$this->assertArrayHasKey( 'summary_columns', $row );
		$this->assertArrayHasKey( 'template_links', $row['summary_columns'] );
	}

	/** Approve item (build-intent) updates status via repository. */
	public function test_approve_item_build_intent(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 888;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-step2',
					'post_title'      => 'Test Plan Step2',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk    = new New_Page_Creation_Bulk_Action_Service( $repo );
			$updated = $bulk->approve_item( $post_id, 'plan_npc_0' );
			$this->assertTrue( $updated );
			$def2 = $repo->get_plan_definition( $post_id );
			$item = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0] ?? null;
			$this->assertNotNull( $item );
			$this->assertSame( Build_Plan_Item_Statuses::APPROVED, $item['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Second deny on the same item is a no-op (idempotent / safe for stale UI). */
	public function test_deny_item_step2_second_call_is_noop(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 8891;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-step2-deny-idem',
					'post_title'      => 'Test Plan Step2 Deny Idem',
					'status'          => 'publish',
				)
			);
			$bulk    = new New_Page_Creation_Bulk_Action_Service( $repo );
			$this->assertTrue( $bulk->deny_item( $post_id, 'plan_npc_0', 1 ) );
			$this->assertFalse( $bulk->deny_item( $post_id, 'plan_npc_0', 1 ) );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Deny item (reject) updates status via repository; denied item is terminal and not executed. */
	public function test_deny_item_step2(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 889;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-step2-deny',
					'post_title'      => 'Test Plan Step2 Deny',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk    = new New_Page_Creation_Bulk_Action_Service( $repo );
			$updated = $bulk->deny_item( $post_id, 'plan_npc_0' );
			$this->assertTrue( $updated );
			$def2 = $repo->get_plan_definition( $post_id );
			$item = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0] ?? null;
			$this->assertNotNull( $item );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $item['status'] );
			$this->assertTrue( Build_Plan_Item_Statuses::is_terminal( $item['status'] ) );
			$stepper = new \AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder();
			$steps   = $stepper->build( $def2 );
			$this->assertSame( 0, (int) ( $steps[2]['unresolved_count'] ?? -1 ) );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Bulk deny selected updates only chosen pending items to rejected (spec §33.7). */
	public function test_bulk_deny_selected_step2(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 792;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 3 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny-selected',
					'post_title'      => 'Test Plan Bulk Deny Selected',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk  = new New_Page_Creation_Bulk_Action_Service( $repo );
			$count = $bulk->bulk_deny_selected( $post_id, array( 'plan_npc_0', 'plan_npc_2' ), 42 );
			$this->assertSame( 2, $count );
			$def2  = $repo->get_plan_definition( $post_id );
			$items = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $items[0]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::PENDING, $items[1]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $items[2]['status'] );
			$rd0 = $items[0][ Build_Plan_Item_Schema::KEY_REVIEW_DECISION ] ?? null;
			$this->assertIsArray( $rd0 );
			$this->assertSame( 42, (int) ( $rd0['actor_user_id'] ?? 0 ) );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Bulk deny selected with mixed eligibility: only pending selected rows change; others unchanged. */
	public function test_bulk_deny_selected_skips_non_pending_in_selection(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 7921;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 3 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny-mixed',
					'post_title'      => 'Test Mixed',
					'status'          => 'publish',
				)
			);
			$def0                                   = $repo->get_plan_definition( $post_id );
			$items0                                 = $def0[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ];
			$items0[0]['status']                    = Build_Plan_Item_Statuses::REJECTED;
			$def0[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items0;
			$repo->save_plan_definition( $post_id, $def0 );
			$bulk  = new New_Page_Creation_Bulk_Action_Service( $repo );
			$count = $bulk->bulk_deny_selected( $post_id, array( 'plan_npc_0', 'plan_npc_1' ), 0 );
			$this->assertSame( 1, $count );
			$def2  = $repo->get_plan_definition( $post_id );
			$items = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $items[0]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $items[1]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::PENDING, $items[2]['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Bulk deny all eligible updates all pending to rejected. */
	public function test_bulk_deny_all_eligible_step2(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 790;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 3 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny',
					'post_title'      => 'Test Plan Bulk Deny',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk  = new New_Page_Creation_Bulk_Action_Service( $repo );
			$count = $bulk->bulk_deny_all_eligible( $post_id );
			$this->assertSame( 3, $count );
			$def2  = $repo->get_plan_definition( $post_id );
			$items = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			foreach ( $items as $item ) {
				$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $item['status'] );
			}
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Bulk deny all eligible marks items rejected so execute is disabled in UI. */
	public function test_bulk_deny_all_eligible_not_executable_in_ui(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 793;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 1 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny-not-exec',
					'post_title'      => 'Test Plan Bulk Deny Not Exec',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );

			$bulk  = new New_Page_Creation_Bulk_Action_Service( $repo );
			$count = $bulk->bulk_deny_all_eligible( $post_id );
			$this->assertSame( 1, $count );

			$def2      = $repo->get_plan_definition( $post_id );
			$resolver  = new Build_Plan_Row_Action_Resolver();
			$detail    = new New_Page_Creation_Detail_Builder();
			$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
			$workspace = $ui->build_workspace(
				$def2,
				2,
				array(
					'can_approve' => true,
					'can_execute' => true,
				),
				null,
				array()
			);

			$this->assertCount( 1, $workspace['step_list_rows'] );
			$row_actions    = $workspace['step_list_rows'][0]['row_actions'];
			$execute_action = null;
			foreach ( $row_actions as $a ) {
				if ( ( $a['action_id'] ?? '' ) === 'execute' ) {
					$execute_action = $a;
					break;
				}
			}
			$this->assertNotNull( $execute_action );
			$this->assertFalse( $execute_action['enabled'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Bulk deny all eligible should skip low-confidence pending items. */
	public function test_bulk_deny_all_eligible_skips_low_confidence(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 791;
		try {
			$repo = new Build_Plan_Repository();
			$def  = $this->step2_plan_definition( 2 );
			// plan_npc_0 becomes ineligible for bulk actions.
			$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['payload']['confidence'] = 'low';
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny-low-confidence',
					'post_title'      => 'Test Plan Bulk Deny Low Confidence',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk  = new New_Page_Creation_Bulk_Action_Service( $repo );
			$count = $bulk->bulk_deny_all_eligible( $post_id );
			$this->assertSame( 1, $count );
			$def2  = $repo->get_plan_definition( $post_id );
			$items = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			$this->assertSame( Build_Plan_Item_Statuses::PENDING, $items[0]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $items[1]['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** Denied item shows rejected badge and is excluded from unresolved (terminal status). */
	public function test_denied_item_shows_rejected_and_is_terminal(): void {
		$def = $this->step2_plan_definition( 1 );
		$def[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ][0]['status'] = Build_Plan_Item_Statuses::REJECTED;
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$detail    = new New_Page_Creation_Detail_Builder();
		$bulk      = new New_Page_Creation_Bulk_Action_Service( new Build_Plan_Repository() );
		$ui        = new New_Page_Creation_UI_Service( $resolver, $detail, $bulk );
		$workspace = $ui->build_workspace(
			$def,
			2,
			array(
				'can_approve' => true,
				'can_execute' => true,
			),
			null,
			array()
		);
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( Build_Plan_Item_Statuses::REJECTED, $workspace['step_list_rows'][0]['status'] );
		$this->assertSame( 'rejected', $workspace['step_list_rows'][0]['status_badge'] );
		$this->assertSame( 0, $workspace['bulk_action_states']['apply_to_all_eligible']['count_eligible'] );
		$this->assertSame( 0, $workspace['bulk_action_states']['deny_all_eligible']['count_eligible'] );
		$row_actions    = $workspace['step_list_rows'][0]['row_actions'];
		$execute_action = null;
		$deny_action    = null;
		foreach ( $row_actions as $a ) {
			if ( ( $a['action_id'] ?? '' ) === 'execute' ) {
				$execute_action = $a;
			}
			if ( ( $a['action_id'] ?? '' ) === 'deny' ) {
				$deny_action = $a;
			}
		}
		$this->assertNotNull( $execute_action );
		$this->assertFalse( $execute_action['enabled'] );
		$this->assertNotNull( $deny_action );
		$this->assertFalse( $deny_action['enabled'] );
	}

	/** After bulk deny, stepper unresolved count for Step 2 becomes zero. */
	public function test_bulk_deny_reduces_step2_unresolved_count(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 792;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 2 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-bulk-deny-unresolved-count',
					'post_title'      => 'Test Plan Bulk Deny Unresolved Count',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$bulk = new New_Page_Creation_Bulk_Action_Service( $repo );
			$bulk->bulk_deny_all_eligible( $post_id );
			$def2    = $repo->get_plan_definition( $post_id );
			$stepper = new \AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder();
			$steps   = $stepper->build( $def2 );
			$this->assertSame( 0, (int) ( $steps[2]['unresolved_count'] ?? -1 ) );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}

	/** update_plan_items_by_ids updates only selected pending items. */
	public function test_update_plan_items_by_ids_build_selected(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 777;
		try {
			$repo    = new Build_Plan_Repository();
			$def     = $this->step2_plan_definition( 3 );
			$post_id = $repo->save(
				array(
					'plan_definition' => $def,
					'internal_key'    => 'test-plan-selected',
					'post_title'      => 'Test Plan Selected',
					'status'          => 'publish',
				)
			);
			$this->assertGreaterThan( 0, $post_id );
			$count = $repo->update_plan_items_by_ids( $post_id, 2, array( 'plan_npc_0', 'plan_npc_2' ), Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::PENDING );
			$this->assertSame( 2, $count );
			$def2  = $repo->get_plan_definition( $post_id );
			$items = $def2[ Build_Plan_Schema::KEY_STEPS ][2][ Build_Plan_Item_Schema::KEY_ITEMS ] ?? array();
			$this->assertSame( Build_Plan_Item_Statuses::APPROVED, $items[0]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::PENDING, $items[1]['status'] );
			$this->assertSame( Build_Plan_Item_Statuses::APPROVED, $items[2]['status'] );
		} finally {
			unset( $GLOBALS['_aio_wp_insert_post_return'] );
		}
	}
}
