<?php
/**
 * Unit tests for Build Plan stepper and UI state builders (spec §31.3, §31.4).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Stepper_Builder;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_UI_State_Builder;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_Stepper_Builder.php';
require_once $plugin_root . '/src/Domain/BuildPlan/UI/Build_Plan_UI_State_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Analytics/Build_Plan_List_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';

final class Build_Plan_Stepper_And_UI_State_Test extends TestCase {

	private function minimal_plan_definition_with_steps(): array {
		return array(
			Build_Plan_Schema::KEY_PLAN_ID => 'aio-plan-test-1',
			Build_Plan_Schema::KEY_STATUS  => 'pending_review',
			Build_Plan_Schema::KEY_STEPS   => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 'plan_step_overview',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Overview',
					Build_Plan_Item_Schema::KEY_ORDER     => 0,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'ov_0',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE,
							Build_Plan_Item_Schema::KEY_PAYLOAD   => array(),
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::PENDING,
						),
					),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 'plan_step_existing',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Existing page changes',
					Build_Plan_Item_Schema::KEY_ORDER     => 1,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID => 'ep_0',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
							Build_Plan_Item_Schema::KEY_PAYLOAD => array(),
							Build_Plan_Item_Schema::KEY_STATUS  => Build_Plan_Item_Statuses::PENDING,
						),
					),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 'plan_step_new',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_NEW_PAGES,
					Build_Plan_Item_Schema::KEY_TITLE     => 'New pages',
					Build_Plan_Item_Schema::KEY_ORDER     => 2,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
			),
		);
	}

	public function test_stepper_builder_returns_one_entry_per_step(): void {
		$builder = new Build_Plan_Stepper_Builder();
		$def     = $this->minimal_plan_definition_with_steps();
		$steps   = $builder->build( $def );
		$this->assertCount( 3, $steps );
		$this->assertSame( 'overview', $steps[0]['step_type'] );
		$this->assertSame( 'existing_page_changes', $steps[1]['step_type'] );
		$this->assertSame( 'new_pages', $steps[2]['step_type'] );
	}

	public function test_stepper_builder_includes_status_badge_and_unresolved_count(): void {
		$builder = new Build_Plan_Stepper_Builder();
		$def     = $this->minimal_plan_definition_with_steps();
		$steps   = $builder->build( $def );
		$this->assertArrayHasKey( 'status_badge', $steps[0] );
		$this->assertArrayHasKey( 'unresolved_count', $steps[0] );
		$this->assertArrayHasKey( 'is_blocked', $steps[0] );
		$this->assertSame( 0, $steps[0]['unresolved_count'] );
		$this->assertSame( 1, $steps[1]['unresolved_count'] );
		$this->assertTrue( $steps[2]['is_blocked'], 'Step 2 (new_pages) is blocked because step 1 has unresolved items.' );
	}

	public function test_stepper_builder_step_number_is_one_based(): void {
		$builder = new Build_Plan_Stepper_Builder();
		$steps   = $builder->build( $this->minimal_plan_definition_with_steps() );
		$this->assertSame( 1, $steps[0]['step_number'] );
		$this->assertSame( 2, $steps[1]['step_number'] );
	}

	public function test_ui_state_builder_returns_null_for_empty_plan_id(): void {
		$repo    = new Build_Plan_Repository();
		$stepper = new Build_Plan_Stepper_Builder();
		$builder = new Build_Plan_UI_State_Builder( $repo, $stepper );
		$this->assertNull( $builder->build( '' ) );
	}

	public function test_ui_state_builder_returns_null_when_plan_not_found(): void {
		$repo    = new Build_Plan_Repository();
		$stepper = new Build_Plan_Stepper_Builder();
		$builder = new Build_Plan_UI_State_Builder( $repo, $stepper );
		$this->assertNull( $builder->build( 'nonexistent-plan-id-' . \uniqid( 'plan-', true ) ) );
	}

	/**
	 * Example Build Plan UI state payload used by the shell (spec §31.4). Keys and structure only.
	 */
	public function test_example_ui_state_payload_keys(): void {
		$example = array(
			'plan_id'         => 'aio-plan-uuid',
			'plan_post_id'    => 1,
			'plan_definition' => array(
				'plan_id' => 'aio-plan-uuid',
				'steps'   => array(),
			),
			'context_rail'    => array(
				'plan_title'                => 'Plan title',
				'plan_id'                   => 'aio-plan-uuid',
				'ai_run_ref'                => 'run-1',
				'normalized_output_ref'     => 'run-1:normalized_output',
				'plan_status'               => 'pending_review',
				'site_purpose_summary'      => 'Site purpose',
				'site_flow_summary'         => 'Site flow',
				'unresolved_counts_by_step' => array(
					'overview'              => 0,
					'existing_page_changes' => 1,
				),
				'warnings_summary'          => array(),
			),
			'stepper_steps'   => array(
				array(
					'step_id'          => 'x_step_overview',
					'step_type'        => 'overview',
					'title'            => 'Overview',
					'order'            => 0,
					'step_number'      => 1,
					'status_badge'     => 'complete',
					'unresolved_count' => 0,
					'is_blocked'       => false,
				),
			),
		);
		$this->assertArrayHasKey( 'plan_id', $example );
		$this->assertArrayHasKey( 'context_rail', $example );
		$this->assertArrayHasKey( 'stepper_steps', $example );
		$this->assertArrayHasKey( 'plan_title', $example['context_rail'] );
		$this->assertArrayHasKey( 'unresolved_counts_by_step', $example['context_rail'] );
	}
}
