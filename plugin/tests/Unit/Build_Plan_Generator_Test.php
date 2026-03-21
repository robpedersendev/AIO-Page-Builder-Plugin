<?php
/**
 * Unit tests for Build Plan generation: success, omission, refusal on invalid input (spec §30.3).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Item_Generator;
use AIOPageBuilder\Domain\BuildPlan\Generation\Omitted_Recommendation_Report;
use AIOPageBuilder\Domain\BuildPlan\Generation\Plan_Generation_Result;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\AI\Validation\Build_Plan_Draft_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Validation/Build_Plan_Draft_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Statuses.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Omitted_Recommendation_Report.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Plan_Generation_Result.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Build_Plan_Item_Generator.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Generation/Build_Plan_Generator.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository.php';

final class Build_Plan_Generator_Test extends TestCase {

	private function valid_normalized_output(): array {
		return array(
			Build_Plan_Draft_Schema::KEY_SCHEMA_VERSION   => '1',
			Build_Plan_Draft_Schema::KEY_RUN_SUMMARY      => array(
				Build_Plan_Draft_Schema::RUN_SUMMARY_SUMMARY_TEXT => 'Draft plan for contact focus.',
				Build_Plan_Draft_Schema::RUN_SUMMARY_PLANNING_MODE => 'mixed',
				Build_Plan_Draft_Schema::RUN_SUMMARY_OVERALL_CONFIDENCE => 'medium',
			),
			Build_Plan_Draft_Schema::KEY_SITE_PURPOSE     => array( 'summary' => 'Local firm.' ),
			Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE   => array(
				'recommended_top_level_pages' => array( 'Home', 'About' ),
				'navigation_summary'          => 'Home, About, Contact.',
			),
			Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES => array(
				array(
					'current_page_url'   => '/',
					'current_page_title' => 'Home',
					'action'             => 'keep',
					'reason'             => 'Keep as is.',
					'risk_level'         => 'low',
					'confidence'         => 'high',
				),
			),
			Build_Plan_Draft_Schema::KEY_NEW_PAGES_TO_CREATE => array(),
			Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN => array(),
			Build_Plan_Draft_Schema::KEY_DESIGN_TOKEN_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_SEO_RECOMMENDATIONS => array(),
			Build_Plan_Draft_Schema::KEY_WARNINGS         => array(),
			Build_Plan_Draft_Schema::KEY_ASSUMPTIONS      => array(),
			Build_Plan_Draft_Schema::KEY_CONFIDENCE       => array( 'overall' => 'medium' ),
		);
	}

	public function test_generate_refuses_invalid_normalized_output_missing_required_key(): void {
		$repo   = new Build_Plan_Repository();
		$item   = new Build_Plan_Item_Generator();
		$gen    = new Build_Plan_Generator( $repo, $item );
		$input  = array( 'run_summary' => array() );
		$result = $gen->generate( $input, 'run-1', 'run-1:normalized_output', array() );
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
		$this->assertNull( $result->get_plan_id() );
	}

	public function test_generate_refuses_when_run_summary_not_array(): void {
		$output = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_RUN_SUMMARY ] = 'invalid';
		$repo   = new Build_Plan_Repository();
		$item   = new Build_Plan_Item_Generator();
		$gen    = new Build_Plan_Generator( $repo, $item );
		$result = $gen->generate( $output, 'run-1', 'run-1:normalized_output', array() );
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_generate_succeeds_with_valid_normalized_output_and_persists_plan(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 42;
		$repo                                  = new Build_Plan_Repository();
		$item                                  = new Build_Plan_Item_Generator();
		$gen                                   = new Build_Plan_Generator( $repo, $item );
		$result                                = $gen->generate( $this->valid_normalized_output(), 'run-1', 'run-1:normalized_output', array() );
		$this->assertTrue( $result->is_success(), implode( ', ', $result->get_errors() ) );
		$this->assertNotNull( $result->get_plan_id() );
		$this->assertStringStartsWith( 'aio-plan-', $result->get_plan_id() );
		$this->assertSame( 42, $result->get_plan_post_id() );
		$payload = $result->get_plan_payload();
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_PLAN_ID, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_STEPS, $payload );
		$this->assertSame( Build_Plan_Schema::STATUS_PENDING_REVIEW, $payload[ Build_Plan_Schema::KEY_STATUS ] );
		$this->assertSame( 'run-1', $payload[ Build_Plan_Schema::KEY_AI_RUN_REF ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_generated_plan_has_overview_and_confirmation_steps(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$repo                                  = new Build_Plan_Repository();
		$gen                                   = new Build_Plan_Generator( $repo, new Build_Plan_Item_Generator() );
		$result                                = $gen->generate( $this->valid_normalized_output(), 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );
		$steps = $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ];
		$types = array_column( $steps, Build_Plan_Item_Schema::KEY_STEP_TYPE );
		$this->assertContains( Build_Plan_Schema::STEP_TYPE_OVERVIEW, $types );
		$this->assertContains( Build_Plan_Schema::STEP_TYPE_CONFIRMATION, $types );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_omitted_report_structure(): void {
		$report = Omitted_Recommendation_Report::report(
			array(
				Omitted_Recommendation_Report::entry( 'existing_page_changes', 0, 'insufficient_data', 'Missing: action', null ),
			)
		);
		$this->assertArrayHasKey( 'omitted', $report );
		$this->assertArrayHasKey( 'count', $report );
		$this->assertSame( 1, $report['count'] );
		$this->assertSame( 'existing_page_changes', $report['omitted'][0]['section'] );
		$this->assertSame( 'insufficient_data', $report['omitted'][0]['reason'] );
	}

	public function test_item_generator_omits_record_with_missing_required_fields(): void {
		$item_gen = new Build_Plan_Item_Generator();
		$records  = array(
			array(
				'current_page_url'   => '/',
				'current_page_title' => 'Home',
			),
		);
		$out      = $item_gen->generate_for_section( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $records, 'plan-x' );
		$this->assertEmpty( $out['items'] );
		$this->assertCount( 1, $out['omitted'] );
		$this->assertSame( 'insufficient_data', $out['omitted'][0]['reason'] );
	}

	public function test_item_generator_produces_item_with_source_section_and_index(): void {
		$item_gen = new Build_Plan_Item_Generator();
		$records  = array(
			array(
				'current_page_url'   => '/',
				'current_page_title' => 'Home',
				'action'             => 'keep',
				'reason'             => 'Keep.',
				'risk_level'         => 'low',
				'confidence'         => 'high',
			),
		);
		$out      = $item_gen->generate_for_section( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $records, 'plan-x' );
		$this->assertCount( 1, $out['items'] );
		$this->assertSame( Build_Plan_Draft_Schema::KEY_EXISTING_PAGE_CHANGES, $out['items'][0]['source_section'] );
		$this->assertSame( 0, $out['items'][0]['source_index'] );
		$this->assertSame( 'pending', $out['items'][0]['status'] );
	}

	/**
	 * Example generated Build Plan root payload (spec §30.3). Structure returned from successful generation.
	 */
	public function test_example_generated_plan_root_payload(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 100;
		$repo                                  = new Build_Plan_Repository();
		$gen                                   = new Build_Plan_Generator( $repo, new Build_Plan_Item_Generator() );
		$result                                = $gen->generate(
			$this->valid_normalized_output(),
			'run-1',
			'run-1:normalized_output',
			array(
				'crawl_snapshot_ref' => 'crawl-123',
			)
		);
		$this->assertTrue( $result->is_success() );
		$payload = $result->get_plan_payload();
		// Root keys (example shape for spec §30.1).
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_PLAN_ID, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_STATUS, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_AI_RUN_REF, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_PLAN_TITLE, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_PLAN_SUMMARY, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_STEPS, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_CREATED_AT, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_WARNINGS, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_ASSUMPTIONS, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_CONFIDENCE, $payload );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_CRAWL_SNAPSHOT_REF, $payload );
		$this->assertSame( 'crawl-123', $payload[ Build_Plan_Schema::KEY_CRAWL_SNAPSHOT_REF ] );
		$this->assertIsArray( $payload[ Build_Plan_Schema::KEY_STEPS ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	// --- Hierarchy step generation tests ---

	public function test_hierarchy_assignments_emit_executable_items(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] = array(
			'hierarchy_assignments' => array(
				array(
					'page_id'        => 5,
					'parent_page_id' => 10,
					'note'           => 'Services under Home.',
				),
				array(
					'page_id'        => 7,
					'parent_page_id' => 0,
				),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );
		$steps          = $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ];
		$hierarchy_step = null;
		foreach ( $steps as $step ) {
			if ( ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) === Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW ) {
				$hierarchy_step = $step;
				break;
			}
		}
		$this->assertNotNull( $hierarchy_step, 'Hierarchy step must be present.' );
		$items = $hierarchy_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 2, $items, 'Two hierarchy_assignment items expected.' );
		foreach ( $items as $item ) {
			$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT, $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] );
		}
		$this->assertSame( 5, $items[0][ Build_Plan_Item_Schema::KEY_PAYLOAD ]['page_id'] );
		$this->assertSame( 10, $items[0][ Build_Plan_Item_Schema::KEY_PAYLOAD ]['parent_page_id'] );
		$this->assertSame( 7, $items[1][ Build_Plan_Item_Schema::KEY_PAYLOAD ]['page_id'] );
		$this->assertSame( 0, $items[1][ Build_Plan_Item_Schema::KEY_PAYLOAD ]['parent_page_id'] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_unresolvable_assignment_emitted_as_hierarchy_note(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_SITE_STRUCTURE ] = array(
			'hierarchy_assignments' => array(
				array(
					'page_id'        => 0,
					'parent_page_id' => 10,
					'note'           => 'Missing page ID.',
				),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );
		$steps          = $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ];
		$hierarchy_step = null;
		foreach ( $steps as $step ) {
			if ( ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) === Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW ) {
				$hierarchy_step = $step;
				break;
			}
		}
		$this->assertNotNull( $hierarchy_step );
		$items = $hierarchy_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 1, $items );
		$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE, $items[0][ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_recommended_top_level_pages_still_emit_hierarchy_note(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		// site_structure already has recommended_top_level_pages in valid_normalized_output().
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );
		$steps          = $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ];
		$hierarchy_step = null;
		foreach ( $steps as $step ) {
			if ( ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) === Build_Plan_Schema::STEP_TYPE_HIERARCHY_FLOW ) {
				$hierarchy_step = $step;
				break;
			}
		}
		$this->assertNotNull( $hierarchy_step );
		$items = $hierarchy_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 1, $items );
		$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE, $items[0][ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] );
		$this->assertArrayHasKey( 'recommended_top_level_pages', $items[0][ Build_Plan_Item_Schema::KEY_PAYLOAD ] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	// -----------------------------------------------------------------------
	// Navigation step: ITEM_TYPE_MENU_NEW vs ITEM_TYPE_MENU_CHANGE.
	// -----------------------------------------------------------------------

	public function test_menu_create_action_emits_item_type_menu_new(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN ] = array(
			array(
				'menu_context'       => 'primary',
				'action'             => 'create',
				'proposed_menu_name' => 'Main Navigation',
				'items'              => array(
					array(
						'title' => 'Home',
						'url'   => '/',
					),
				),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );

		$nav_step = $this->find_step( $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ], Build_Plan_Schema::STEP_TYPE_NAVIGATION );
		$this->assertNotNull( $nav_step );
		$items = $nav_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 1, $items );
		$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, $items[0][ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] );

		$payload = $items[0][ Build_Plan_Item_Schema::KEY_PAYLOAD ];
		$this->assertSame( 'Main Navigation', $payload['menu_name'], 'menu_name must be set from proposed_menu_name for Create_Menu_Handler.' );
		$this->assertSame( 'primary', $payload['theme_location'], 'theme_location must be set from menu_context.' );
		$this->assertIsArray( $payload['items'] );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_menu_rename_action_still_emits_item_type_menu_change(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN ] = array(
			array(
				'menu_context'       => 'header',
				'action'             => 'rename',
				'proposed_menu_name' => 'New Header Nav',
				'items'              => array(),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );

		$nav_step = $this->find_step( $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ], Build_Plan_Schema::STEP_TYPE_NAVIGATION );
		$this->assertNotNull( $nav_step );
		$items = $nav_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 1, $items );
		$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE, $items[0][ Build_Plan_Item_Schema::KEY_ITEM_TYPE ], 'rename action must remain ITEM_TYPE_MENU_CHANGE.' );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_menu_update_existing_action_still_emits_item_type_menu_change(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN ] = array(
			array(
				'menu_context'       => 'footer',
				'action'             => 'update_existing',
				'proposed_menu_name' => 'Footer Nav',
				'items'              => array(),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );

		$nav_step = $this->find_step( $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ], Build_Plan_Schema::STEP_TYPE_NAVIGATION );
		$items    = $nav_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertSame( Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE, $items[0][ Build_Plan_Item_Schema::KEY_ITEM_TYPE ], 'update_existing action must remain ITEM_TYPE_MENU_CHANGE.' );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	public function test_mixed_menu_actions_produce_distinct_item_types(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 1;
		$output                                = $this->valid_normalized_output();
		$output[ Build_Plan_Draft_Schema::KEY_MENU_CHANGE_PLAN ] = array(
			array(
				'menu_context'       => 'primary',
				'action'             => 'create',
				'proposed_menu_name' => 'New Nav',
				'items'              => array(),
			),
			array(
				'menu_context'       => 'footer',
				'action'             => 'update_existing',
				'proposed_menu_name' => 'Existing Footer',
				'items'              => array(),
			),
		);
		$gen    = new Build_Plan_Generator( new Build_Plan_Repository(), new Build_Plan_Item_Generator() );
		$result = $gen->generate( $output, 'run-1', 'run-1:out', array() );
		$this->assertTrue( $result->is_success() );

		$nav_step = $this->find_step( $result->get_plan_payload()[ Build_Plan_Schema::KEY_STEPS ], Build_Plan_Schema::STEP_TYPE_NAVIGATION );
		$items    = $nav_step[ Build_Plan_Item_Schema::KEY_ITEMS ];
		$this->assertCount( 2, $items );

		$types = array_column( $items, Build_Plan_Item_Schema::KEY_ITEM_TYPE );
		$this->assertContains( Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW, $types, 'create record must produce ITEM_TYPE_MENU_NEW.' );
		$this->assertContains( Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE, $types, 'update_existing record must produce ITEM_TYPE_MENU_CHANGE.' );
		unset( $GLOBALS['_aio_wp_insert_post_return'] );
	}

	/** Helper: find a step by step_type from the plan steps array. */
	private function find_step( array $steps, string $step_type ): ?array {
		foreach ( $steps as $step ) {
			if ( ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) === $step_type ) {
				return $step;
			}
		}
		return null;
	}

	/**
	 * Example omitted-recommendation report payload (spec §30.3). Structure of Omitted_Recommendation_Report::report().
	 */
	public function test_example_omitted_recommendation_report_payload(): void {
		$entries = array(
			Omitted_Recommendation_Report::entry( 'existing_page_changes', 0, 'insufficient_data', 'Missing: action', null ),
			Omitted_Recommendation_Report::entry( 'new_pages_to_create', 1, 'invalid_reference', 'Unknown template_key', array( 'proposed_page_title' => 'Extra' ) ),
		);
		$report  = Omitted_Recommendation_Report::report( $entries );
		$this->assertSame( array( 'omitted', 'count' ), array_keys( $report ) );
		$this->assertCount( 2, $report['omitted'] );
		$this->assertSame( 2, $report['count'] );
		$this->assertSame( 'existing_page_changes', $report['omitted'][0]['section'] );
		$this->assertSame( 0, $report['omitted'][0]['index'] );
		$this->assertSame( 'insufficient_data', $report['omitted'][0]['reason'] );
		$this->assertSame( 'Missing: action', $report['omitted'][0]['message'] );
		$this->assertSame( 'new_pages_to_create', $report['omitted'][1]['section'] );
		$this->assertSame( 'invalid_reference', $report['omitted'][1]['reason'] );
		$this->assertArrayHasKey( 'record_snapshot', $report['omitted'][1] );
		$this->assertSame( array( 'proposed_page_title' => 'Extra' ), $report['omitted'][1]['record_snapshot'] );
	}
}
