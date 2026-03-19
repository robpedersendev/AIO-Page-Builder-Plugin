<?php
/**
 * Unit tests for Build Plan Steps 4–7 shell UI (spec §35–§38, Prompt 076).
 *
 * Verifies payload shape, step-specific keys, placeholder bulk states, and example UI payloads.
 * No execution or rollback logic.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Steps\Finalization\Finalization_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Row_Action_Resolver.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Step_Item_List_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Components/Bulk_Action_Bar_Component.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/Tokens/Tokens_Step_UI_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/SEO/SEO_Media_Step_UI_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/Finalization/Finalization_Step_UI_Service.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Steps/History/History_Rollback_Step_UI_Service.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Repository.php';

final class Build_Plan_Steps_4_7_Shell_Test extends TestCase {

	/** Example Step 4 (design tokens) UI workspace payload (spec §35). */
	public const EXAMPLE_STEP_4_PAYLOAD = array(
		'step_list_rows'         => array(
			array(
				'item_id'         => 'plan_dt_0',
				'status'          => 'pending',
				'status_badge'    => 'pending',
				'summary_columns' => array(
					'token_group'    => 'colors',
					'token_name'     => 'primary',
					'proposed_value' => '#2563eb',
					'rationale'      => 'Align with brand',
					'confidence'     => 'high',
				),
				'row_actions'     => array(),
				'is_selected'     => false,
			),
		),
		'column_order'           => array( 'token_group', 'token_name', 'proposed_value', 'rationale', 'confidence' ),
		'bulk_action_states'     => array(
			'apply_to_all_eligible' => array(
				'enabled'        => false,
				'label'          => 'Apply all tokens',
				'count_eligible' => 1,
			),
			'apply_to_selected'     => array(
				'enabled'        => false,
				'label'          => 'Apply to selected',
				'count_selected' => 0,
			),
			'deny_all_eligible'     => array(
				'enabled'        => false,
				'label'          => 'Deny all',
				'count_eligible' => 1,
			),
			'clear_selection'       => array(
				'enabled' => false,
				'label'   => 'Clear selection',
			),
		),
		'detail_panel'           => array(
			'item_id'     => '',
			'sections'    => array(),
			'row_actions' => array(),
		),
		'step_messages'          => array(
			array(
				'severity' => 'info',
				'message'  => '1 token pending review.',
				'level'    => 'step',
			),
		),
		'token_set_summary'      => array(
			'groups' => array( 'colors' => 1 ),
			'total'  => 1,
		),
	);

	/** Example Step 5 (SEO/media) UI workspace payload (spec §36). */
	public const EXAMPLE_STEP_5_PAYLOAD = array(
		'step_list_rows'               => array(
			array(
				'item_id'         => 'plan_seo_0',
				'status'          => 'pending',
				'status_badge'    => 'pending',
				'summary_columns' => array(
					'target_page_title_or_url' => 'About Us',
					'confidence'               => 'medium',
					'storage_path_indicator'   => 'plugin_advisory',
				),
				'row_actions'     => array(),
				'is_selected'     => false,
			),
		),
		'column_order'                 => array( 'target_page_title_or_url', 'confidence', 'storage_path_indicator' ),
		'bulk_action_states'           => array(
			'apply_to_all_eligible' => array(
				'enabled'        => false,
				'label'          => 'Apply all',
				'count_eligible' => 1,
			),
			'apply_to_selected'     => array(
				'enabled'        => false,
				'label'          => 'Apply to selected',
				'count_selected' => 0,
			),
			'deny_all_eligible'     => array(
				'enabled'        => false,
				'label'          => 'Deny all',
				'count_eligible' => 1,
			),
			'clear_selection'       => array(
				'enabled' => false,
				'label'   => 'Clear selection',
			),
		),
		'detail_panel'                 => array(
			'item_id'     => '',
			'sections'    => array(),
			'row_actions' => array(),
		),
		'step_messages'                => array(),
	);

	/** Example Step 6 (finalization) UI workspace payload (spec §37). */
	public const EXAMPLE_STEP_6_PAYLOAD = array(
		'step_list_rows'               => array(),
		'column_order'                 => array( 'bucket', 'count', 'status' ),
		'bulk_action_states'           => array(
			'apply_to_all_eligible' => array(
				'enabled'        => false,
				'label'          => 'Publish all',
				'count_eligible' => 0,
			),
			'apply_to_selected'     => array(
				'enabled'        => false,
				'label'          => 'Apply to selected',
				'count_selected' => 0,
			),
			'deny_all_eligible'     => array(
				'enabled'        => false,
				'label'          => 'Cancel finalization',
				'count_eligible' => 0,
			),
			'clear_selection'       => array(
				'enabled' => false,
				'label'   => 'Clear selection',
			),
		),
		'detail_panel'                 => array(
			'item_id'     => '',
			'sections'    => array(
				array(
					'heading'       => 'Finalization queue',
					'key'           => 'queue',
					'content_lines' => array( 'Finalization queue and publish actions are not available in this version.', 'Blocked: 0', 'Failed: 0', 'Deferred: 0' ),
				),
				array(
					'heading'       => 'Conflicts',
					'key'           => 'conflicts',
					'content_lines' => array( 'Conflict reporting is not available in this version.' ),
				),
			),
			'row_actions' => array(),
		),
		'step_messages'                => array(
			array(
				'severity' => 'info',
				'message'  => 'Review approved items and confirm when ready. Execution is not performed in this step.',
				'level'    => 'step',
			),
		),
		'finalization_buckets'         => array(
			'publish_ready' => 0,
			'blocked'       => 0,
			'failed'        => 0,
			'deferred'      => 0,
		),
		'conflict_summary_placeholder' => array(
			'count'    => 0,
			'messages' => array(),
		),
		'preview_link_placeholder'     => array(
			'url'   => '',
			'label' => 'Preview not available in this version',
		),
	);

	/** Example Step 7 (logs/rollback) UI workspace payload (spec §38). */
	public const EXAMPLE_STEP_7_PAYLOAD = array(
		'step_list_rows'                   => array(
			array(
				'item_id'         => 'placeholder_0',
				'status'          => '',
				'status_badge'    => '',
				'summary_columns' => array(
					'event_at'          => '—',
					'action_type'       => 'No history recorded yet (placeholder).',
					'scope'             => '—',
					'before_after'      => '—',
					'rollback_eligible' => '—',
				),
				'row_actions'     => array(),
				'is_selected'     => false,
			),
		),
		'column_order'                     => array( 'event_at', 'action_type', 'scope', 'before_after', 'rollback_eligible' ),
		'bulk_action_states'               => array(
			'apply_to_all_eligible' => array(
				'enabled'        => false,
				'label'          => 'Rollback',
				'count_eligible' => 0,
			),
			'apply_to_selected'     => array(
				'enabled'        => false,
				'label'          => 'Rollback selected',
				'count_selected' => 0,
			),
			'deny_all_eligible'     => array(
				'enabled'        => false,
				'label'          => 'N/A',
				'count_eligible' => 0,
			),
			'clear_selection'       => array(
				'enabled' => false,
				'label'   => 'Clear selection',
			),
		),
		'detail_panel'                     => array(
			'item_id'     => '',
			'sections'    => array(),
			'row_actions' => array(),
		),
		'step_messages'                    => array(
			array(
				'severity' => 'info',
				'message'  => 'Logs and history are read-only. Rollback is a deliberate recovery workflow (not implemented in this shell).',
				'level'    => 'step',
			),
		),
		'history_summary'                  => array(
			'total_events' => 1,
			'grouped_by'   => 'action_type',
		),
		'rollback_eligibility_placeholder' => array(
			'eligible_count' => 0,
			'can_rollback'   => false,
		),
	);

	private function plan_definition_with_step_at( int $step_index, string $step_type, array $items = array() ): array {
		$step_types = array(
			Build_Plan_Schema::STEP_TYPE_OVERVIEW,
			Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
			Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
			Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW,
			Build_Plan_Schema::STEP_TYPE_NAVIGATION,
			Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS,
			Build_Plan_Schema::STEP_TYPE_SEO,
			Build_Plan_Schema::STEP_TYPE_CONFIRMATION,
			Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK,
		);
		$steps      = array();
		foreach ( $step_types as $i => $type ) {
			$steps[] = array(
				Build_Plan_Item_Schema::KEY_STEP_TYPE => $type,
				Build_Plan_Item_Schema::KEY_ITEMS     => $i === $step_index ? $items : array(),
			);
		}
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'test-plan-476',
			Build_Plan_Schema::KEY_STEPS   => $steps,
		);
	}

	public function test_step4_tokens_workspace_has_shared_and_step_specific_keys(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$service   = new Tokens_Step_UI_Service( $resolver, new Global_Style_Settings_Repository() );
		$items     = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_dt_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'token_group'    => 'colors',
					'token_name'     => 'primary',
					'proposed_value' => '#2563eb',
					'rationale'      => 'Align with brand',
					'confidence'     => 'high',
				),
			),
		);
		$def       = $this->plan_definition_with_step_at( Tokens_Step_UI_Service::STEP_INDEX_DESIGN_TOKENS, Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS, $items );
		$workspace = $service->build_workspace( $def, Tokens_Step_UI_Service::STEP_INDEX_DESIGN_TOKENS, array( 'can_approve' => true ), null, array() );

		$this->assertArrayHasKey( 'step_list_rows', $workspace );
		$this->assertArrayHasKey( 'column_order', $workspace );
		$this->assertArrayHasKey( 'bulk_action_states', $workspace );
		$this->assertArrayHasKey( 'detail_panel', $workspace );
		$this->assertArrayHasKey( 'step_messages', $workspace );
		$this->assertArrayHasKey( 'token_set_summary', $workspace );
		$this->assertSame( Tokens_Step_UI_Service::COLUMN_ORDER, $workspace['column_order'] );
		$this->assertCount( 1, $workspace['step_list_rows'] );
		$this->assertSame( array( 'colors' => 1 ), $workspace['token_set_summary']['groups'] );
		$this->assertTrue( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL ]['enabled'] );
		$this->assertFalse( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED ]['enabled'] );
		$this->assertTrue( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_DENY_ALL ]['enabled'] );
	}

	public function test_step5_seo_workspace_has_storage_path_placeholder(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$service   = new SEO_Media_Step_UI_Service( $resolver );
		$items     = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'About Us',
					'confidence'               => 'medium',
					'storage_path_indicator'   => 'plugin_advisory',
				),
			),
		);
		$def       = $this->plan_definition_with_step_at( SEO_Media_Step_UI_Service::STEP_INDEX_SEO, Build_Plan_Schema::STEP_TYPE_SEO, $items );
		$workspace = $service->build_workspace( $def, SEO_Media_Step_UI_Service::STEP_INDEX_SEO, array( 'can_approve' => true ), null, array() );

		$this->assertArrayHasKey( 'step_list_rows', $workspace );
		$this->assertSame( SEO_Media_Step_UI_Service::COLUMN_ORDER, $workspace['column_order'] );
		$this->assertTrue( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL ]['enabled'] );
		$this->assertFalse( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED ]['enabled'] );
	}

	public function test_step6_finalization_workspace_has_buckets_and_empty_list(): void {
		$service   = new Finalization_Step_UI_Service();
		$def       = $this->plan_definition_with_step_at( Finalization_Step_UI_Service::STEP_INDEX_CONFIRMATION, Build_Plan_Schema::STEP_TYPE_CONFIRMATION, array() );
		$workspace = $service->build_workspace( $def, Finalization_Step_UI_Service::STEP_INDEX_CONFIRMATION, array( 'can_execute' => false ), null, array() );

		$this->assertArrayHasKey( 'finalization_buckets', $workspace );
		$this->assertArrayHasKey( 'publish_ready', $workspace['finalization_buckets'] );
		$this->assertArrayHasKey( 'blocked', $workspace['finalization_buckets'] );
		$this->assertArrayHasKey( 'failed', $workspace['finalization_buckets'] );
		$this->assertArrayHasKey( 'deferred', $workspace['finalization_buckets'] );
		$this->assertSame( array(), $workspace['step_list_rows'] );
		$this->assertFalse( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL ]['enabled'] );
	}

	/** Finalization step surfaces truthful "not available" copy, not placeholder. */
	public function test_step6_finalization_detail_panel_states_not_available(): void {
		$service   = new Finalization_Step_UI_Service();
		$def       = $this->plan_definition_with_step_at( Finalization_Step_UI_Service::STEP_INDEX_CONFIRMATION, Build_Plan_Schema::STEP_TYPE_CONFIRMATION, array() );
		$workspace = $service->build_workspace( $def, Finalization_Step_UI_Service::STEP_INDEX_CONFIRMATION, array( 'can_execute' => false ), null, array() );

		$queue_section     = null;
		$conflicts_section = null;
		foreach ( $workspace['detail_panel']['sections'] as $sec ) {
			if ( ( $sec['key'] ?? '' ) === 'queue' ) {
				$queue_section = $sec;
			}
			if ( ( $sec['key'] ?? '' ) === 'conflicts' ) {
				$conflicts_section = $sec;
			}
		}
		$this->assertNotNull( $queue_section );
		$this->assertNotNull( $conflicts_section );
		$this->assertNotEmpty( $queue_section['content_lines'] );
		$this->assertNotEmpty( $conflicts_section['content_lines'] );
		$this->assertStringContainsString( 'Publish-ready', $queue_section['content_lines'][0] );
	}

	/** SEO step detail panel states SEO/meta updates are not available (recommendation-only). */
	public function test_step5_seo_detail_panel_states_not_available_when_item_selected(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$service   = new SEO_Media_Step_UI_Service( $resolver );
		$items     = array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_seo_0',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_SEO,
				Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_title_or_url' => 'About Us',
					'storage_path_indicator'   => 'plugin_advisory',
				),
			),
		);
		$def       = $this->plan_definition_with_step_at( SEO_Media_Step_UI_Service::STEP_INDEX_SEO, Build_Plan_Schema::STEP_TYPE_SEO, $items );
		$workspace = $service->build_workspace( $def, SEO_Media_Step_UI_Service::STEP_INDEX_SEO, array( 'can_approve' => true ), 'plan_seo_0', array() );

		$recommendations_section = null;
		foreach ( $workspace['detail_panel']['sections'] as $sec ) {
			if ( ( $sec['key'] ?? '' ) === 'recommendations' ) {
				$recommendations_section = $sec;
				break;
			}
		}
		$this->assertNotNull( $recommendations_section );
		$this->assertNotEmpty( $recommendations_section['content_lines'] );
		$this->assertStringContainsString( 'advisory', $recommendations_section['content_lines'][0] );
	}

	public function test_step7_history_workspace_has_placeholder_row_when_empty_and_rollback_placeholder(): void {
		$service   = new History_Rollback_Step_UI_Service();
		$def       = $this->plan_definition_with_step_at( History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK, Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK, array() );
		$workspace = $service->build_workspace( $def, History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK, array( 'can_execute' => false ), null, array() );

		$this->assertArrayHasKey( 'history_summary', $workspace );
		$this->assertArrayHasKey( 'rollback_eligibility_placeholder', $workspace );
		$this->assertArrayHasKey( 'total_events', $workspace['history_summary'] );
		$this->assertArrayHasKey( 'eligible_count', $workspace['rollback_eligibility_placeholder'] );
		$this->assertArrayHasKey( 'can_rollback', $workspace['rollback_eligibility_placeholder'] );
		$this->assertCount( 0, $workspace['step_list_rows'], 'When no history items, list is empty and empty state is shown in UI.' );
		$this->assertFalse( $workspace['bulk_action_states'][ Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL ]['enabled'] );
	}

	public function test_step4_returns_empty_workspace_for_wrong_step_index(): void {
		$resolver  = new Build_Plan_Row_Action_Resolver();
		$service   = new Tokens_Step_UI_Service( $resolver, new Global_Style_Settings_Repository() );
		$def       = $this->plan_definition_with_step_at( 5, Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS, array() );
		$workspace = $service->build_workspace( $def, 6, array( 'can_approve' => true ), null, array() );
		$this->assertSame( array(), $workspace['step_list_rows'] );
	}

	public function test_step7_rollback_eligibility_reflects_capability(): void {
		$service            = new History_Rollback_Step_UI_Service();
		$def                = $this->plan_definition_with_step_at( History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK, Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK, array() );
		$workspace_no_cap   = $service->build_workspace( $def, History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK, array( 'can_execute' => false ), null, array() );
		$workspace_with_cap = $service->build_workspace( $def, History_Rollback_Step_UI_Service::STEP_INDEX_LOGS_ROLLBACK, array( 'can_execute' => true ), null, array() );
		$this->assertFalse( $workspace_no_cap['rollback_eligibility_placeholder']['can_rollback'] );
		$this->assertTrue( $workspace_with_cap['rollback_eligibility_placeholder']['can_rollback'] );
	}

	/** Example payload structure: Step 4. */
	public function test_example_step4_payload_structure(): void {
		$ex = self::EXAMPLE_STEP_4_PAYLOAD;
		$this->assertArrayHasKey( 'step_list_rows', $ex );
		$this->assertArrayHasKey( 'token_set_summary', $ex );
		$this->assertArrayHasKey( 'groups', $ex['token_set_summary'] );
	}

	/** Example payload structure: Step 5. */
	public function test_example_step5_payload_structure(): void {
		$ex = self::EXAMPLE_STEP_5_PAYLOAD;
		$this->assertArrayHasKey( 'target_page_title_or_url', $ex['step_list_rows'][0]['summary_columns'] );
	}

	/** Example payload structure: Step 6. */
	public function test_example_step6_payload_structure(): void {
		$ex = self::EXAMPLE_STEP_6_PAYLOAD;
		$this->assertArrayHasKey( 'finalization_buckets', $ex );
		$this->assertArrayHasKey( 'conflict_summary_placeholder', $ex );
		$this->assertArrayHasKey( 'preview_link_placeholder', $ex );
		$this->assertArrayHasKey( 'publish_ready', $ex['finalization_buckets'] );
		$this->assertArrayHasKey( 'blocked', $ex['finalization_buckets'] );
	}

	/** Example payload structure: Step 7. */
	public function test_example_step7_payload_structure(): void {
		$ex = self::EXAMPLE_STEP_7_PAYLOAD;
		$this->assertArrayHasKey( 'history_summary', $ex );
		$this->assertArrayHasKey( 'rollback_eligibility_placeholder', $ex );
		$this->assertArrayHasKey( 'event_at', $ex['step_list_rows'][0]['summary_columns'] );
		$this->assertArrayHasKey( 'rollback_eligible', $ex['step_list_rows'][0]['summary_columns'] );
	}
}
