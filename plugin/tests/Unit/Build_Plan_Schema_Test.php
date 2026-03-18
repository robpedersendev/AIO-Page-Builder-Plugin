<?php
/**
 * Unit tests for Build Plan schema: root/item integrity, required fields, status and step type enums (build-plan-schema.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';

final class Build_Plan_Schema_Test extends TestCase {

	public function test_required_root_keys_include_plan_id_status_ai_run_ref_steps_created_at(): void {
		$required = Build_Plan_Schema::get_required_root_keys();
		$this->assertContains( Build_Plan_Schema::KEY_PLAN_ID, $required );
		$this->assertContains( Build_Plan_Schema::KEY_STATUS, $required );
		$this->assertContains( Build_Plan_Schema::KEY_AI_RUN_REF, $required );
		$this->assertContains( Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF, $required );
		$this->assertContains( Build_Plan_Schema::KEY_STEPS, $required );
		$this->assertContains( Build_Plan_Schema::KEY_CREATED_AT, $required );
	}

	public function test_all_status_enum_values_are_valid(): void {
		foreach ( Build_Plan_Schema::STATUS_ENUM as $status ) {
			$this->assertTrue( Build_Plan_Schema::is_valid_status( $status ) );
		}
	}

	public function test_only_approved_is_eligible_for_execution(): void {
		$this->assertTrue( Build_Plan_Schema::is_eligible_for_execution( Build_Plan_Schema::STATUS_APPROVED ) );
		$this->assertFalse( Build_Plan_Schema::is_eligible_for_execution( Build_Plan_Schema::STATUS_PENDING_REVIEW ) );
		$this->assertFalse( Build_Plan_Schema::is_eligible_for_execution( Build_Plan_Schema::STATUS_REJECTED ) );
		$this->assertFalse( Build_Plan_Schema::is_eligible_for_execution( Build_Plan_Schema::STATUS_COMPLETED ) );
	}

	public function test_step_types_include_overview_and_confirmation(): void {
		$this->assertContains( Build_Plan_Schema::STEP_TYPE_OVERVIEW, Build_Plan_Schema::STEP_TYPES );
		$this->assertContains( Build_Plan_Schema::STEP_TYPE_CONFIRMATION, Build_Plan_Schema::STEP_TYPES );
	}

	public function test_each_step_type_is_valid(): void {
		foreach ( Build_Plan_Schema::STEP_TYPES as $step_type ) {
			$this->assertTrue( Build_Plan_Schema::is_valid_step_type( $step_type ) );
		}
	}

	public function test_required_item_keys_include_item_id_item_type_payload(): void {
		$required = Build_Plan_Item_Schema::get_required_item_keys();
		$this->assertContains( Build_Plan_Item_Schema::KEY_ITEM_ID, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_ITEM_TYPE, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_PAYLOAD, $required );
	}

	public function test_required_step_keys_include_step_id_step_type_title_order_items(): void {
		$required = Build_Plan_Item_Schema::get_required_step_keys();
		$this->assertContains( Build_Plan_Item_Schema::KEY_STEP_ID, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_STEP_TYPE, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_TITLE, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_ORDER, $required );
		$this->assertContains( Build_Plan_Item_Schema::KEY_ITEMS, $required );
	}

	public function test_item_types_include_existing_page_change_and_confirmation(): void {
		$this->assertContains( Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE, Build_Plan_Item_Schema::ITEM_TYPES );
		$this->assertContains( Build_Plan_Item_Schema::ITEM_TYPE_CONFIRMATION, Build_Plan_Item_Schema::ITEM_TYPES );
	}

	public function test_each_item_type_is_valid(): void {
		foreach ( Build_Plan_Item_Schema::ITEM_TYPES as $item_type ) {
			$this->assertTrue( Build_Plan_Item_Schema::is_valid_item_type( $item_type ) );
		}
	}

	/** Valid example plan skeleton (build-plan-schema.md §12). */
	public function test_valid_example_plan_skeleton_has_all_required_root_keys(): void {
		$skeleton = $this->valid_plan_skeleton();
		foreach ( Build_Plan_Schema::get_required_root_keys() as $key ) {
			$this->assertArrayHasKey( $key, $skeleton, "Required root key {$key} missing from skeleton" );
		}
		$this->assertIsArray( $skeleton[ Build_Plan_Schema::KEY_STEPS ] );
		$this->assertNotEmpty( $skeleton[ Build_Plan_Schema::KEY_STEPS ] );
	}

	public function test_valid_skeleton_steps_have_required_step_keys(): void {
		$skeleton = $this->valid_plan_skeleton();
		foreach ( $skeleton[ Build_Plan_Schema::KEY_STEPS ] as $step ) {
			foreach ( Build_Plan_Item_Schema::get_required_step_keys() as $key ) {
				$this->assertArrayHasKey( $key, $step, "Step missing required key {$key}" );
			}
			$this->assertTrue( Build_Plan_Schema::is_valid_step_type( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ) );
		}
	}

	public function test_valid_skeleton_items_have_required_item_keys(): void {
		$skeleton = $this->valid_plan_skeleton();
		foreach ( $skeleton[ Build_Plan_Schema::KEY_STEPS ] as $step ) {
			foreach ( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] as $item ) {
				foreach ( Build_Plan_Item_Schema::get_required_item_keys() as $key ) {
					$this->assertArrayHasKey( $key, $item, "Item missing required key {$key}" );
				}
				$this->assertTrue( Build_Plan_Item_Schema::is_valid_item_type( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ) );
			}
		}
	}

	public function test_invalid_status_rejected(): void {
		$this->assertFalse( Build_Plan_Schema::is_valid_status( 'invalid_status' ) );
	}

	public function test_invalid_step_type_rejected(): void {
		$this->assertFalse( Build_Plan_Schema::is_valid_step_type( 'invalid_step' ) );
	}

	public function test_invalid_item_type_rejected(): void {
		$this->assertFalse( Build_Plan_Item_Schema::is_valid_item_type( 'invalid_item' ) );
	}

	/**
	 * Returns a valid plan skeleton per build-plan-schema.md §12.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_plan_skeleton(): array {
		return array(
			Build_Plan_Schema::KEY_PLAN_ID               => 'plan_550e8400-e29b-41d4-a716-446655440000',
			Build_Plan_Schema::KEY_STATUS                => Build_Plan_Schema::STATUS_PENDING_REVIEW,
			Build_Plan_Schema::KEY_AI_RUN_REF            => 'aio-run-550e8400-e29b-41d4-a716-446655440001',
			Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF => 'aio-run-550e8400-e29b-41d4-a716-446655440001:normalized_output',
			Build_Plan_Schema::KEY_PLAN_TITLE            => 'Site audit plan – March 2025',
			Build_Plan_Schema::KEY_PLAN_SUMMARY          => 'Draft plan for contact and consultation focus.',
			Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY  => 'Local accounting firm; contact and consultation focus.',
			Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY     => 'Home, About, Services, Contact as top-level.',
			Build_Plan_Schema::KEY_STEPS                 => array(
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 'step_overview_0',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_OVERVIEW,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Overview',
					Build_Plan_Item_Schema::KEY_ORDER     => 0,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item_overview_0',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE,
							Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
								'summary_text'  => 'Draft plan.',
								'planning_mode' => 'mixed',
							),
						),
					),
				),
				array(
					Build_Plan_Item_Schema::KEY_STEP_ID   => 'step_confirm_1',
					Build_Plan_Item_Schema::KEY_STEP_TYPE => Build_Plan_Schema::STEP_TYPE_CONFIRMATION,
					Build_Plan_Item_Schema::KEY_TITLE     => 'Confirm',
					Build_Plan_Item_Schema::KEY_ORDER     => 1,
					Build_Plan_Item_Schema::KEY_ITEMS     => array(),
				),
			),
			Build_Plan_Schema::KEY_CREATED_AT            => '2025-03-11T12:00:00Z',
		);
	}
}
