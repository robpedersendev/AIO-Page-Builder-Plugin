<?php
/**
 * Unit tests for Template_Finalization_Service (spec §59.10; Prompt 208).
 *
 * Covers successful finalization summary, partial-run state, failed-run state,
 * summary artifact generation, and trace-link completeness in closure record.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Result;
use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/Execution/Finalize/Template_Finalization_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Finalize/Template_Finalization_Service.php';

final class Template_Finalization_Service_Test extends TestCase {

	public function test_successful_finalization_produces_complete_state(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::COMPLETED,
						),
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i2',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::COMPLETED,
						),
					),
				),
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, array() );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_COMPLETE, $result->get_run_completion_state() );
		$summary = $result->get_finalization_summary();
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 1, $summary['updated'] );
		$this->assertSame( 0, $summary['failed'] );
		$this->assertSame( 0, $summary['pending'] );
	}

	public function test_partial_run_produces_warning_or_partial_state(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::COMPLETED,
						),
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i2',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::FAILED,
						),
					),
				),
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, array() );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_WARNING, $result->get_run_completion_state() );
		$summary = $result->get_finalization_summary();
		$this->assertSame( 1, $summary['created'] );
		$this->assertSame( 1, $summary['failed'] );
	}

	public function test_failed_run_produces_failed_state(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::FAILED,
						),
					),
				),
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, array() );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_FAILED, $result->get_run_completion_state() );
		$this->assertSame( 1, $result->get_finalization_summary()['failed'] );
	}

	public function test_conflicts_produce_failed_state(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'i1',
							Build_Plan_Item_Schema::KEY_STATUS    => Build_Plan_Item_Statuses::COMPLETED,
						),
					),
				),
			),
		);
		$conflicts  = array(
			array(
				'type' => 'slug_conflict',
				'slug' => 'about',
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, $conflicts );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_FAILED, $result->get_run_completion_state() );
		$this->assertSame( 1, $result->get_finalization_summary()['blocked'] );
	}

	public function test_template_execution_closure_record_includes_trace_links(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID => 'item-1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS => Build_Plan_Item_Statuses::COMPLETED,
							Build_Plan_Item_Schema::KEY_PAYLOAD => array( 'template_key' => 'tpl_services' ),
							'execution_artifact' => array(
								'target_post_id' => 42,
								'template_build_execution_result' => array(
									'template_key'    => 'tpl_services',
									'template_family' => 'services',
									'one_pager_ref'   => 'doc/one-pager-services',
								),
							),
						),
					),
				),
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, array() );
		$closure    = $result->get_template_execution_closure_record();
		$this->assertCount( 1, $closure );
		$this->assertSame( 'item-1', $closure[0]['plan_item_id'] ?? '' );
		$this->assertSame( 'create', $closure[0]['action_taken'] ?? '' );
		$this->assertSame( 'tpl_services', $closure[0]['template_key'] ?? '' );
		$this->assertSame( 'services', $closure[0]['template_family'] ?? '' );
		$this->assertSame( 42, $closure[0]['post_id'] ?? 0 );
		$this->assertSame( 'doc/one-pager-services', $closure[0]['one_pager_ref'] ?? '' );
	}

	public function test_one_pager_retention_summary_aggregates_by_template_key(): void {
		$definition = array(
			Build_Plan_Schema::KEY_STEPS => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID => 'i1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS => Build_Plan_Item_Statuses::COMPLETED,
							'execution_artifact' => array(
								'template_build_execution_result' => array(
									'template_key'  => 'tpl_about',
									'one_pager_ref' => 'doc/about',
								),
							),
						),
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID => 'i2',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS => Build_Plan_Item_Statuses::COMPLETED,
							'execution_artifact' => array(
								'template_build_execution_result' => array(
									'template_key'  => 'tpl_about',
									'one_pager_ref' => 'doc/about',
								),
							),
						),
					),
				),
			),
		);
		$service    = new Template_Finalization_Service();
		$result     = $service->build( $definition, array() );
		$one_pager  = $result->get_one_pager_retention_summary();
		$this->assertArrayHasKey( 'tpl_about', $one_pager );
		$this->assertSame( 2, $one_pager['tpl_about']['count'] ?? 0 );
		$this->assertContains( 'doc/about', $one_pager['tpl_about']['one_pager_refs'] ?? array() );
	}
}
