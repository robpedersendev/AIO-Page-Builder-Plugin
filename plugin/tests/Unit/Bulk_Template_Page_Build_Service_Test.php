<?php
/**
 * Unit tests for Bulk_Template_Page_Build_Service (spec §33.6, §33.7, §33.8, §33.10; Prompt 195).
 *
 * Covers parent-first ordering (via stub envelopes), slug collision handling, partial-failure
 * reporting, retry_eligible_item_ids, per-item status retention.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;
use AIOPageBuilder\Domain\Execution\Pages\Bulk_Template_Page_Build_Result;
use AIOPageBuilder\Domain\Execution\Pages\Bulk_Template_Page_Build_Service;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Bulk_Template_Page_Build_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Bulk_Template_Page_Build_Service.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Execution_Job_Result.php';

final class Bulk_Template_Page_Build_Service_Test extends TestCase {

	public function test_run_bulk_new_pages_returns_error_when_plan_not_found(): void {
		$plan_state = $this->createMock( Plan_State_For_Execution_Interface::class );
		$plan_state->method( 'get_by_key' )->with( 'missing' )->willReturn( null );
		$bulk_executor  = $this->createMock( Bulk_Executor::class );
		$job_dispatcher = $this->createMock( Execution_Job_Dispatcher::class );

		$service = new Bulk_Template_Page_Build_Service( $plan_state, $bulk_executor, $job_dispatcher );
		$result  = $service->run_bulk_new_pages(
			'missing',
			null,
			array(
				'actor_type' => 'user',
				'actor_id'   => '1',
			),
			array()
		);

		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_ERROR, $result->get_status() );
		$this->assertSame( 'Plan not found.', $result->get_message() );
		$this->assertSame( 0, $result->get_completed_count() );
	}

	public function test_run_bulk_new_pages_returns_error_when_no_create_page_envelopes(): void {
		$plan_state = $this->createMock( Plan_State_For_Execution_Interface::class );
		$plan_state->method( 'get_by_key' )->willReturn( array( 'id' => 1 ) );
		$plan_state->method( 'get_plan_definition' )->willReturn( array( 'steps' => array() ) );
		$bulk_executor = $this->createMock( Bulk_Executor::class );
		$bulk_executor->method( 'build_ordered_envelopes' )->willReturn(
			array(
				array(
					Execution_Action_Contract::ENVELOPE_ACTION_ID => 'exec_1_b',
					Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::UPDATE_MENU,
					Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID => 'item_1',
					Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(),
				),
			)
		);
		$job_dispatcher = $this->createMock( Execution_Job_Dispatcher::class );

		$service = new Bulk_Template_Page_Build_Service( $plan_state, $bulk_executor, $job_dispatcher );
		$result  = $service->run_bulk_new_pages( 'plan_1', null, array(), array() );

		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_ERROR, $result->get_status() );
		$this->assertStringContainsString( 'No eligible new-page', $result->get_message() );
	}

	public function test_run_bulk_new_pages_slug_collision_marks_items_refused(): void {
		$plan_state = $this->createMock( Plan_State_For_Execution_Interface::class );
		$plan_state->method( 'get_by_key' )->willReturn( array( 'id' => 1 ) );
		$plan_state->method( 'get_plan_definition' )->willReturn( array( 'steps' => array() ) );
		$envelopes     = array(
			$this->create_page_envelope( 'item_1', 'same-slug' ),
			$this->create_page_envelope( 'item_2', 'same-slug' ),
		);
		$bulk_executor = $this->createMock( Bulk_Executor::class );
		$bulk_executor->method( 'build_ordered_envelopes' )->willReturn( $envelopes );
		$job_dispatcher = $this->createMock( Execution_Job_Dispatcher::class );
		$job_dispatcher->method( 'enqueue_batch' )->willReturn( array() );

		$service = new Bulk_Template_Page_Build_Service( $plan_state, $bulk_executor, $job_dispatcher );
		$result  = $service->run_bulk_new_pages( 'plan_1', null, array(), array() );

		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_QUEUED, $result->get_status() );
		$this->assertGreaterThanOrEqual( 1, count( $result->get_slug_collisions() ) );
		$this->assertNotEmpty( $result->get_item_results() );
		foreach ( $result->get_item_results() as $ir ) {
			$this->assertTrue( $ir['slug_conflict'] );
			$this->assertSame( 'refused', $ir['status'] );
		}
	}

	public function test_run_bulk_new_pages_queued_without_run_immediately(): void {
		$plan_state = $this->createMock( Plan_State_For_Execution_Interface::class );
		$plan_state->method( 'get_by_key' )->willReturn( array( 'id' => 1 ) );
		$plan_state->method( 'get_plan_definition' )->willReturn( array( 'steps' => array() ) );
		$envelopes     = array( $this->create_page_envelope( 'item_1', 'unique-slug-1' ) );
		$bulk_executor = $this->createMock( Bulk_Executor::class );
		$bulk_executor->method( 'build_ordered_envelopes' )->willReturn( $envelopes );
		$job_dispatcher = $this->createMock( Execution_Job_Dispatcher::class );
		$job_dispatcher->method( 'enqueue_batch' )->willReturn( array( 'job_ref_1' ) );

		$service = new Bulk_Template_Page_Build_Service( $plan_state, $bulk_executor, $job_dispatcher );
		$result  = $service->run_bulk_new_pages( 'plan_1', array( 'item_1' ), array(), array() );

		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_QUEUED, $result->get_status() );
		$this->assertSame( array( 'job_ref_1' ), $result->get_job_refs() );
		$this->assertSame( 0, $result->get_completed_count() );
	}

	public function test_run_bulk_new_pages_run_immediately_aggregates_per_item_status(): void {
		$plan_state = $this->createMock( Plan_State_For_Execution_Interface::class );
		$plan_state->method( 'get_by_key' )->willReturn( array( 'id' => 1 ) );
		$plan_state->method( 'get_plan_definition' )->willReturn( array( 'steps' => array() ) );
		$envelopes     = array(
			$this->create_page_envelope( 'item_1', 'slug-a' ),
			$this->create_page_envelope( 'item_2', 'slug-b' ),
		);
		$bulk_executor = $this->createMock( Bulk_Executor::class );
		$bulk_executor->method( 'build_ordered_envelopes' )->willReturn( $envelopes );
		$job_dispatcher = $this->createMock( Execution_Job_Dispatcher::class );
		$job_dispatcher->method( 'enqueue_batch' )->willReturn( array( 'job_1', 'job_2' ) );
		$job_dispatcher->method( 'process_batch' )->willReturn(
			array(
				Execution_Job_Result::completed(
					'job_1',
					'create_page',
					'exec_1',
					'item_1',
					array(
						'artifacts' => array(
							'post_id'      => 101,
							'template_key' => 'tpl_hub',
						),
					)
				),
				Execution_Job_Result::failed( 'job_2', 'create_page', 'exec_2', 'item_2', 'Template not found.', array(), 0, true ),
			)
		);

		$service = new Bulk_Template_Page_Build_Service( $plan_state, $bulk_executor, $job_dispatcher );
		$result  = $service->run_bulk_new_pages( 'plan_1', null, array(), array( 'run_immediately' => true ) );

		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_PARTIAL, $result->get_status() );
		$this->assertTrue( $result->is_partial_failure() );
		$this->assertSame( 1, $result->get_completed_count() );
		$this->assertSame( 1, $result->get_failed_count() );
		$this->assertSame( array( 'item_2' ), $result->get_retry_eligible_item_ids() );
		$item_results = $result->get_item_results();
		$this->assertSame( 101, $item_results['item_1']['post_id'] );
		$this->assertSame( 'tpl_hub', $item_results['item_1']['template_key'] );
		$this->assertSame( 'Template not found.', $item_results['item_2']['failure_reason'] );
	}

	private function create_page_envelope( string $plan_item_id, string $slug ): array {
		return array(
			Execution_Action_Contract::ENVELOPE_ACTION_ID  => 'exec_' . $plan_item_id . '_b',
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::CREATE_PAGE,
			Execution_Action_Contract::ENVELOPE_PLAN_ID    => 'plan_1',
			Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID => $plan_item_id,
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'plan_item_id'        => $plan_item_id,
				'template_key'        => 'tpl_hub',
				'proposed_page_title' => 'Page',
				'proposed_slug'       => $slug,
			),
			Execution_Action_Contract::ENVELOPE_APPROVAL_STATE => array(),
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array(),
			Execution_Action_Contract::ENVELOPE_CREATED_AT => gmdate( 'c' ),
		);
	}
}
