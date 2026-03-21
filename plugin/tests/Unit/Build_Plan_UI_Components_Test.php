<?php
/**
 * Unit tests for Build Plan reusable UI components (spec §31.5–31.10, Prompt 072).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Detail_Panel_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Status_Badge_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Message_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Step_Workspace_Payload_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Status_Badge_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Detail_Panel_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Message_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Step_Workspace_Payload_Builder.php';

final class Build_Plan_UI_Components_Test extends TestCase {

	/** Example step_list_rows payload: one row. */
	public function test_step_list_row_payload_structure(): void {
		$row = array(
			Step_Item_List_Component::ROW_KEY_ITEM_ID      => 'ep_0',
			Step_Item_List_Component::ROW_KEY_STATUS       => Build_Plan_Item_Statuses::PENDING,
			Step_Item_List_Component::ROW_KEY_STATUS_BADGE => 'pending',
			Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => array(
				'title'       => 'Home',
				'action_type' => 'Update',
				'rationale'   => 'Refresh content',
			),
			Step_Item_List_Component::ROW_KEY_ROW_ACTIONS  => array(
				array(
					'action_id' => 'view_detail',
					'label'     => 'View detail',
					'enabled'   => true,
				),
				array(
					'action_id' => 'approve',
					'label'     => 'Approve',
					'enabled'   => true,
				),
			),
			Step_Item_List_Component::ROW_KEY_IS_SELECTED  => false,
		);
		$this->assertSame( 'ep_0', $row[ Step_Item_List_Component::ROW_KEY_ITEM_ID ] );
		$this->assertArrayHasKey( 'summary_columns', $row );
		$this->assertCount( 2, $row[ Step_Item_List_Component::ROW_KEY_ROW_ACTIONS ] );
	}

	/** Step_Item_List_Component renders table with one row. */
	public function test_step_item_list_component_renders_row(): void {
		$payload   = array(
			Step_Item_List_Component::KEY_STEP_LIST_ROWS => array(
				array(
					Step_Item_List_Component::ROW_KEY_ITEM_ID         => 'ep_0',
					Step_Item_List_Component::ROW_KEY_STATUS_BADGE     => 'pending',
					Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => array(
						'title'       => 'Home',
						'action_type' => 'Update',
					),
					Step_Item_List_Component::ROW_KEY_ROW_ACTIONS     => array(
						array(
							'action_id' => 'view_detail',
							'label'     => 'View detail',
							'enabled'   => true,
						),
					),
					Step_Item_List_Component::ROW_KEY_IS_SELECTED    => false,
				),
			),
			Step_Item_List_Component::KEY_COLUMN_ORDER   => array( 'title', 'action_type' ),
		);
		$component = new Step_Item_List_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-step-item-list', $html );
		$this->assertStringContainsString( 'ep_0', $html );
		$this->assertStringContainsString( 'Home', $html );
		$this->assertStringContainsString( 'View detail', $html );
	}

	/** Step_Item_List_Component renders nothing when rows empty. */
	public function test_step_item_list_component_empty_rows_outputs_nothing(): void {
		$payload   = array(
			Step_Item_List_Component::KEY_STEP_LIST_ROWS => array(),
			Step_Item_List_Component::KEY_COLUMN_ORDER   => array(),
		);
		$component = new Step_Item_List_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertSame( '', $html );
	}

	/** Detail_Panel_Component renders sections and empty state. */
	public function test_detail_panel_component_renders_sections(): void {
		$payload   = array(
			Detail_Panel_Component::KEY_ITEM_ID     => 'ep_0',
			Detail_Panel_Component::KEY_SECTIONS    => array(
				array(
					Detail_Panel_Component::SECTION_KEY_HEADING => 'Details',
					Detail_Panel_Component::SECTION_KEY_CONTENT => '<p>Item content</p>',
				),
			),
			Detail_Panel_Component::KEY_ROW_ACTIONS => array(),
		);
		$component = new Detail_Panel_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-detail-panel', $html );
		$this->assertStringContainsString( 'Details', $html );
		$this->assertStringContainsString( 'Item content', $html );
		$this->assertStringContainsString( 'data-item-id="ep_0"', $html );
	}

	/** Detail_Panel_Component renders empty state when item_id empty. */
	public function test_detail_panel_component_empty_item_id(): void {
		$payload   = array(
			Detail_Panel_Component::KEY_ITEM_ID  => '',
			Detail_Panel_Component::KEY_SECTIONS => array(),
		);
		$component = new Detail_Panel_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'Select a row to view details', $html );
		$this->assertStringContainsString( 'aio-detail-panel-empty', $html );
	}

	/** Example detail_panel payload structure. */
	public function test_detail_panel_payload_structure(): void {
		$detail_panel = array(
			'item_id'     => 'ep_0',
			'sections'    => array(
				array(
					'heading' => 'Details',
					'key'     => 'details',
					'content' => '<dl></dl>',
				),
				array(
					'heading'       => 'Status',
					'key'           => 'status',
					'content_lines' => array( 'ep_0', 'pending' ),
				),
			),
			'row_actions' => array(
				array(
					'action_id' => 'approve',
					'label'     => 'Approve',
					'enabled'   => true,
				),
			),
		);
		$this->assertSame( 'ep_0', $detail_panel['item_id'] );
		$this->assertCount( 2, $detail_panel['sections'] );
	}

	/** Bulk_Action_Bar_Component: disabled when no eligible. */
	public function test_bulk_action_bar_disabled_when_no_eligible(): void {
		$payload   = array(
			Bulk_Action_Bar_Component::KEY_BULK_ACTION_STATES => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Apply to all eligible',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_ELIGIBLE => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Apply to selected',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_SELECTED => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Deny all eligible',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_ELIGIBLE => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Clear selection',
				),
			),
		);
		$component = new Bulk_Action_Bar_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-bulk-action-bar', $html );
		$this->assertStringContainsString( 'disabled="disabled"', $html );
	}

	/** Bulk_Action_Bar_Component: enabled when eligible count > 0. */
	public function test_bulk_action_bar_enabled_when_eligible(): void {
		$payload   = array(
			Bulk_Action_Bar_Component::KEY_BULK_ACTION_STATES => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => true,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Apply to all eligible',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_ELIGIBLE => 3,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Apply to selected',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_SELECTED => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => true,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Deny all eligible',
					Bulk_Action_Bar_Component::STATE_KEY_COUNT_ELIGIBLE => 3,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					Bulk_Action_Bar_Component::STATE_KEY_ENABLED => false,
					Bulk_Action_Bar_Component::STATE_KEY_LABEL  => 'Clear selection',
				),
			),
		);
		$component = new Bulk_Action_Bar_Component();
		\ob_start();
		$component->render( $payload );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-bulk-count', $html );
		$this->assertStringContainsString( '(3)', $html );
		$this->assertStringContainsString( 'aio-bulk-apply_to_all_eligible" data-bulk-action', $html );
		$this->assertStringNotContainsString( 'aio-bulk-apply_to_all_eligible" disabled', $html );
	}

	/** Example bulk_action_states payload. */
	public function test_bulk_action_states_payload_structure(): void {
		$bulk_states = array(
			'apply_to_all_eligible' => array(
				'enabled'        => true,
				'label'          => 'Apply to all eligible',
				'count_eligible' => 2,
			),
			'apply_to_selected'     => array(
				'enabled'        => false,
				'label'          => 'Apply to selected',
				'count_selected' => 0,
			),
			'deny_all_eligible'     => array(
				'enabled'        => true,
				'label'          => 'Deny all eligible',
				'count_eligible' => 2,
			),
			'clear_selection'       => array(
				'enabled' => false,
				'label'   => 'Clear selection',
			),
		);
		$this->assertTrue( $bulk_states['apply_to_all_eligible']['enabled'] );
		$this->assertSame( 2, $bulk_states['apply_to_all_eligible']['count_eligible'] );
	}

	/** Status_Badge_Component outputs correct class and label. */
	public function test_status_badge_component_output(): void {
		$component = new Status_Badge_Component();
		\ob_start();
		$component->render(
			array(
				Status_Badge_Component::KEY_STATUS_BADGE => 'pending',
				Status_Badge_Component::KEY_LABEL        => 'Pending',
			)
		);
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-status-badge', $html );
		$this->assertStringContainsString( 'aio-badge-pending', $html );
		$this->assertStringContainsString( 'Pending', $html );
	}

	/** Step_Message_Component severity classes. */
	public function test_step_message_component_severity_display(): void {
		$component = new Step_Message_Component();
		\ob_start();
		$component->render(
			array(
				Step_Message_Component::KEY_SEVERITY => 'error',
				Step_Message_Component::KEY_MESSAGE  => 'Something failed',
				Step_Message_Component::KEY_LEVEL    => 'step',
			)
		);
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'aio-message-error', $html );
		$this->assertStringContainsString( 'aio-message-level-step', $html );
		$this->assertStringContainsString( 'Something failed', $html );
	}

	/** Step_Message_Component render_list. */
	public function test_step_message_component_render_list(): void {
		$component = new Step_Message_Component();
		$messages  = array(
			array(
				'severity' => 'info',
				'message'  => 'First',
			),
			array(
				'severity' => 'warning',
				'message'  => 'Second',
			),
		);
		\ob_start();
		$component->render_list( $messages );
		$html = \ob_get_clean();
		$this->assertStringContainsString( 'First', $html );
		$this->assertStringContainsString( 'Second', $html );
		$this->assertStringContainsString( 'aio-message-warning', $html );
	}

	/** Row_Action_Resolver: approve/deny enabled for pending when can_approve. */
	public function test_row_action_resolver_pending_with_capability(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$item     = array(
			'item_id'   => 'ep_0',
			'status'    => Build_Plan_Item_Statuses::PENDING,
			'item_type' => Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
		);
		$caps     = array(
			'can_approve' => true,
			'can_execute' => false,
		);
		$actions  = $resolver->resolve( $item, $caps );
		$approve  = $this->find_action( $actions, 'approve' );
		$deny     = $this->find_action( $actions, 'deny' );
		$this->assertNotNull( $approve );
		$this->assertNotNull( $deny );
		$this->assertTrue( $approve['enabled'] );
		$this->assertTrue( $deny['enabled'] );
		$execute = $this->find_action( $actions, 'execute' );
		$this->assertFalse( $execute['enabled'] );
	}

	/** Row_Action_Resolver: approve/deny disabled when no can_approve. */
	public function test_row_action_resolver_pending_without_capability(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$item     = array( 'status' => Build_Plan_Item_Statuses::PENDING );
		$actions  = $resolver->resolve( $item, array() );
		$approve  = $this->find_action( $actions, 'approve' );
		$this->assertFalse( $approve['enabled'] );
	}

	/** Row_Action_Resolver: execute enabled for approved when can_execute. */
	public function test_row_action_resolver_approved_execute_enabled(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$item     = array( 'status' => Build_Plan_Item_Statuses::APPROVED );
		$item['item_type'] = Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN;
		$caps     = array(
			'can_approve' => true,
			'can_execute' => true,
		);
		$actions  = $resolver->resolve( $item, $caps );
		$execute  = $this->find_action( $actions, 'execute' );
		$this->assertTrue( $execute['enabled'] );
	}

	/** Row_Action_Resolver: SEO items do not expose execute/retry (advisory-only step). */
	public function test_row_action_resolver_seo_has_no_execute_or_retry(): void {
		$resolver = new Build_Plan_Row_Action_Resolver();
		$item     = array(
			'status'    => Build_Plan_Item_Statuses::APPROVED,
			'item_type' => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
		);
		$caps     = array(
			'can_approve' => true,
			'can_execute' => true,
		);
		$actions  = $resolver->resolve( $item, $caps );
		$this->assertNull( $this->find_action( $actions, 'execute' ) );
		$this->assertNull( $this->find_action( $actions, 'retry' ) );
	}

	/** Step_Workspace_Payload_Builder returns step_list_rows and bulk_action_states. */
	public function test_step_workspace_payload_builder_returns_expected_keys(): void {
		$resolver   = new Build_Plan_Row_Action_Resolver();
		$builder    = new Step_Workspace_Payload_Builder( $resolver );
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'ep_0',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
							Build_Plan_Item_Schema::KEY_STATUS   => Build_Plan_Item_Statuses::PENDING,
							Build_Plan_Item_Schema::KEY_PAYLOAD  => array( 'title' => 'Home' ),
						),
					),
				),
			),
		);
		$workspace  = $builder->build(
			$definition,
			0,
			array(
				'can_approve' => true,
				'can_execute' => false,
			),
			null,
			array()
		);
		$this->assertArrayHasKey( 'step_list_rows', $workspace );
		$this->assertArrayHasKey( 'column_order', $workspace );
		$this->assertArrayHasKey( 'bulk_action_states', $workspace );
		$this->assertArrayHasKey( 'detail_panel', $workspace );
		$this->assertArrayHasKey( 'step_messages', $workspace );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( 'ep_0', $workspace['step_list_rows'][0]['item_id'] );
		$this->assertTrue( $workspace['bulk_action_states']['apply_to_all_eligible']['enabled'] );
	}

	private function find_action( array $actions, string $action_id ): ?array {
		foreach ( $actions as $a ) {
			if ( ( $a['action_id'] ?? '' ) === $action_id ) {
				return $a;
			}
		}
		return null;
	}
}
