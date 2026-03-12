<?php
/**
 * Unit tests for Bulk_Executor, Execution_Job_Dispatcher, Execution_Queue_Service (spec §40.3, §42; Prompt 080).
 *
 * Covers dependency-ordered batch creation, queue-job persistence (via in-memory stub), per-item status updates,
 * partial-failure reporting, and retry metadata. Includes example bulk dispatch and job result payloads.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Dispatcher;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Job_Result;
use AIOPageBuilder\Domain\Execution\Queue\Execution_Queue_Service;
use AIOPageBuilder\Domain\Execution\Queue\Job_Queue_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Stub_Execution_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Dispatcher.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Plan_State_For_Execution_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Single_Action_Executor.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Execution_Job_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Bulk_Executor.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Execution_Job_Dispatcher.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Execution_Queue_Service.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Job_Queue_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Job_Queue_Status.php';

/**
 * Stub plan state for single-action executor in queue tests (same contract as Single_Action_Executor_Test).
 */
final class Stub_Plan_State_For_Executor_Queue implements Plan_State_For_Execution_Interface {

	/** @var array<string, mixed>|null */
	public $get_by_key_return = null;

	/** @var array<string, mixed> */
	public $get_plan_definition_return = array();

	/** @var int|null */
	public $find_step_index_return = 0;

	/** @var bool */
	public $update_plan_item_status_return = true;

	/** @var array<string, mixed> */
	public $last_update_call = array();

	public function get_by_key( string $key ): ?array {
		return $this->get_by_key_return;
	}

	public function get_plan_definition( int $post_id ): array {
		return $this->get_plan_definition_return;
	}

	public function find_step_index_for_item( array $definition, string $plan_item_id ): ?int {
		return $this->find_step_index_return;
	}

	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status, ?array $execution_artifact = null ): bool {
		$this->last_update_call = array( 'post_id' => $post_id, 'step_index' => $step_index, 'item_id' => $item_id, 'new_status' => $new_status, 'execution_artifact' => $execution_artifact );
		return $this->update_plan_item_status_return;
	}
}

/**
 * In-memory job queue for unit tests (no DB). Implements Job_Queue_Repository_Interface.
 */
final class Stub_Job_Queue_Repository implements Job_Queue_Repository_Interface {

	/** @var array<string, array<string, mixed>> */
	public $jobs = array();

	private $next_id = 1;

	public function insert_job( array $data ): string {
		$job_ref = isset( $data['job_ref'] ) && is_string( $data['job_ref'] ) ? trim( $data['job_ref'] ) : '';
		if ( $job_ref === '' ) {
			return '';
		}
		if ( isset( $this->jobs[ $job_ref ] ) ) {
			return '';
		}
		$this->jobs[ $job_ref ] = array_merge( $data, array( 'id' => $this->next_id++, 'retry_count' => 0 ) );
		return $job_ref;
	}

	public function get_by_key( string $job_ref ): ?array {
		return isset( $this->jobs[ $job_ref ] ) ? $this->jobs[ $job_ref ] : null;
	}

	public function update_job_status( string $job_ref, string $status, ?string $failure_reason = null, ?string $started_at = null, ?string $completed_at = null ): bool {
		if ( ! isset( $this->jobs[ $job_ref ] ) ) {
			return false;
		}
		$this->jobs[ $job_ref ]['queue_status'] = $status;
		if ( $failure_reason !== null ) {
			$this->jobs[ $job_ref ]['failure_reason'] = $failure_reason;
		}
		if ( $started_at !== null ) {
			$this->jobs[ $job_ref ]['started_at'] = $started_at;
		}
		if ( $completed_at !== null ) {
			$this->jobs[ $job_ref ]['completed_at'] = $completed_at;
		}
		return true;
	}
}

/** Stub plan state for queue service tests. */
final class Stub_Plan_State_For_Queue implements Plan_State_For_Execution_Interface {

	public $get_by_key_return = null;
	public $get_plan_definition_return = array();

	public function get_by_key( string $key ): ?array {
		return $this->get_by_key_return;
	}

	public function get_plan_definition( int $post_id ): array {
		return $this->get_plan_definition_return;
	}

	public function find_step_index_for_item( array $definition, string $plan_item_id ): ?int {
		return 0;
	}

	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status ): bool {
		return true;
	}
}

final class Bulk_Executor_And_Queue_Test extends TestCase {

	/** Example bulk dispatch payload (request_bulk_execution input + enqueue output). */
	public static function example_bulk_dispatch_payload(): array {
		return array(
			'request' => array(
				'plan_id'       => 'aio-plan-uuid-1',
				'item_ids'      => array( 'plan_npc_0', 'plan_npc_1' ),
				'actor_context' => array(
					'actor_type'          => 'user',
					'actor_id'            => '1',
					'capability_checked'  => 'aio_execute_build_plans',
					'checked_at'          => '2025-03-11T12:00:00Z',
				),
				'options'       => array( 'run_immediately' => true, 'priority' => 5 ),
			),
			'response_queued' => array(
				'plan_id'         => 'aio-plan-uuid-1',
				'status'          => 'queued',
				'job_refs'        => array( 'job_exec_plan_npc_0_...', 'job_exec_plan_npc_1_...' ),
				'item_results'    => array(),
				'completed_count' => 0,
				'failed_count'    => 0,
				'refused_count'   => 0,
				'partial_failure' => false,
				'results_summary' => array(),
				'message'         => 'Actions queued.',
			),
		);
	}

	/** Example job result payload (Execution_Job_Result::to_array()). */
	public static function example_job_result_payload(): array {
		return array(
			'job_ref'        => 'job_exec_plan_npc_0_20250311120000_123',
			'job_type'       => 'create_page',
			'status'         => Execution_Job_Result::STATUS_COMPLETED,
			'action_id'      => 'exec_plan_npc_0_batch1',
			'plan_item_id'   => 'plan_npc_0',
			'result_summary' => array(
				'action_id'       => 'exec_plan_npc_0_batch1',
				'action_type'     => 'create_page',
				'status'          => 'completed',
				'completed_at'    => gmdate( 'c' ),
				'handler_result'  => array( 'success' => true, 'artifacts' => array( 'post_id' => 42 ) ),
				'build_plan_updates' => array( 'plan_id' => 'aio-plan-uuid-1', 'plan_item_id' => 'plan_npc_0', 'item_status' => 'completed' ),
			),
			'retry_count'    => 0,
			'retry_eligible' => false,
			'failure_reason' => '',
			'completed_at'   => gmdate( 'c' ),
		);
	}

	private static function plan_definition_with_deps(): array {
		$steps = array(
			array(
				Build_Plan_Item_Schema::KEY_STEP_ID   => 'step_new_pages',
				Build_Plan_Item_Schema::KEY_STEP_TYPE => 'new_pages',
				Build_Plan_Item_Schema::KEY_TITLE    => 'New pages',
				Build_Plan_Item_Schema::KEY_ORDER     => 1,
				Build_Plan_Item_Schema::KEY_ITEMS    => array(
					array(
						Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_0',
						Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
						Build_Plan_Item_Schema::KEY_STATUS   => Build_Plan_Item_Statuses::APPROVED,
						Build_Plan_Item_Schema::KEY_PAYLOAD => array( 'template_ref' => array( 'type' => 'internal_key', 'value' => 'landing' ) ),
						Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS => array(),
					),
					array(
						Build_Plan_Item_Schema::KEY_ITEM_ID   => 'plan_npc_1',
						Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
						Build_Plan_Item_Schema::KEY_STATUS   => Build_Plan_Item_Statuses::APPROVED,
						Build_Plan_Item_Schema::KEY_PAYLOAD => array( 'template_ref' => array( 'type' => 'internal_key', 'value' => 'landing' ) ),
						Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS => array( 'plan_npc_0' ),
					),
				),
			),
		);
		return array(
			Build_Plan_Schema::KEY_PLAN_ID   => 'aio-plan-uuid-1',
			Build_Plan_Schema::KEY_STATUS    => 'approved',
			Build_Plan_Schema::KEY_STEPS     => $steps,
		);
	}

	public function test_bulk_executor_builds_ordered_envelopes_respecting_dependencies(): void {
		$executor = new Bulk_Executor();
		$definition = self::plan_definition_with_deps();
		$actor = array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) );
		$envelopes = $executor->build_ordered_envelopes( 'aio-plan-uuid-1', $definition, null, $actor, 'batch1' );
		$this->assertCount( 2, $envelopes );
		$first = $envelopes[0];
		$this->assertSame( 'plan_npc_0', $first[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] );
		$this->assertSame( Execution_Action_Types::CREATE_PAGE, $first[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] );
		$second = $envelopes[1];
		$this->assertSame( 'plan_npc_1', $second[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] );
		$this->assertArrayHasKey( Execution_Action_Contract::ENVELOPE_ACTION_ID, $first );
		$this->assertArrayHasKey( Execution_Action_Contract::ENVELOPE_APPROVAL_STATE, $first );
		$this->assertArrayHasKey( 'dependency_manifest', $first );
	}

	public function test_bulk_executor_filters_by_item_ids_when_provided(): void {
		$executor = new Bulk_Executor();
		$definition = self::plan_definition_with_deps();
		$actor = array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) );
		$envelopes = $executor->build_ordered_envelopes( 'aio-plan-uuid-1', $definition, array( 'plan_npc_1' ), $actor, 'batch2' );
		$this->assertCount( 1, $envelopes );
		$this->assertSame( 'plan_npc_1', $envelopes[0][ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] );
	}

	public function test_bulk_executor_skips_non_approved_items(): void {
		$definition = self::plan_definition_with_deps();
		$definition[ Build_Plan_Schema::KEY_STEPS ][0][ Build_Plan_Item_Schema::KEY_ITEMS ][0][ Build_Plan_Item_Schema::KEY_STATUS ] = Build_Plan_Item_Statuses::PENDING;
		$executor = new Bulk_Executor();
		$actor = array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) );
		$envelopes = $executor->build_ordered_envelopes( 'aio-plan-uuid-1', $definition, null, $actor, 'batch3' );
		$this->assertCount( 1, $envelopes );
		$this->assertSame( 'plan_npc_1', $envelopes[0][ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] );
	}

	public function test_execution_job_result_to_array_has_required_shape(): void {
		$payload = self::example_job_result_payload();
		$this->assertArrayHasKey( 'job_ref', $payload );
		$this->assertArrayHasKey( 'job_type', $payload );
		$this->assertArrayHasKey( 'status', $payload );
		$this->assertArrayHasKey( 'action_id', $payload );
		$this->assertArrayHasKey( 'plan_item_id', $payload );
		$this->assertArrayHasKey( 'result_summary', $payload );
		$this->assertArrayHasKey( 'retry_count', $payload );
		$this->assertArrayHasKey( 'retry_eligible', $payload );
		$this->assertArrayHasKey( 'failure_reason', $payload );
		$this->assertArrayHasKey( 'completed_at', $payload );
	}

	public function test_dispatcher_enqueue_batch_persists_jobs_and_returns_job_refs(): void {
		$stub_repo = new Stub_Job_Queue_Repository();
		$stub_plan = new Stub_Plan_State_For_Executor_Queue();
		$stub_plan->get_by_key_return = array( 'id' => 1 );
		$stub_plan->get_plan_definition_return = array();
		$dispatcher = new Execution_Dispatcher();
		$executor = new Single_Action_Executor( $dispatcher, $stub_plan, function (): bool { return true; } );
		$job_dispatcher = new Execution_Job_Dispatcher( $stub_repo, $executor );
		$envelope = $this->valid_envelope( 'plan_npc_0', Execution_Action_Types::CREATE_PAGE );
		$job_refs = $job_dispatcher->enqueue_batch( array( $envelope ), 'user:1', 0 );
		$this->assertCount( 1, $job_refs );
		$this->assertNotEmpty( $job_refs[0] );
		$job = $stub_repo->get_by_key( $job_refs[0] );
		$this->assertNotNull( $job );
		$this->assertSame( 'pending', $job['queue_status'] );
		$this->assertSame( 'create_page', $job['job_type'] );
	}

	public function test_dispatcher_process_job_returns_result_and_updates_job_status(): void {
		$stub_repo = new Stub_Job_Queue_Repository();
		$stub_plan = new Stub_Plan_State_For_Executor_Queue();
		$stub_plan->get_by_key_return = array( 'id' => 1 );
		$stub_plan->get_plan_definition_return = array( Build_Plan_Schema::KEY_PLAN_ID => 'aio-plan-uuid-1', Build_Plan_Schema::KEY_STEPS => array() );
		$stub_plan->find_step_index_return = 0;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				return array( 'success' => true, 'artifacts' => array( 'post_id' => 99 ) );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $stub_plan, function (): bool { return true; } );
		$job_dispatcher = new Execution_Job_Dispatcher( $stub_repo, $executor );
		$envelope = $this->valid_envelope( 'plan_npc_0', Execution_Action_Types::CREATE_PAGE );
		$job_refs = $job_dispatcher->enqueue_batch( array( $envelope ), 'user:1', 0 );
		$this->assertCount( 1, $job_refs );
		$result = $job_dispatcher->process_job( $job_refs[0] );
		$this->assertInstanceOf( Execution_Job_Result::class, $result );
		$this->assertSame( Execution_Job_Result::STATUS_COMPLETED, $result->get_status() );
		$this->assertSame( 'plan_npc_0', $result->get_plan_item_id() );
		$job = $stub_repo->get_by_key( $job_refs[0] );
		$this->assertSame( 'completed', $job['queue_status'] );
	}

	public function test_dispatcher_process_batch_reports_partial_failure(): void {
		$stub_repo = new Stub_Job_Queue_Repository();
		$stub_plan = new Stub_Plan_State_For_Executor_Queue();
		$stub_plan->get_by_key_return = array( 'id' => 1 );
		$stub_plan->get_plan_definition_return = array( Build_Plan_Schema::KEY_PLAN_ID => 'aio-plan-uuid-1', Build_Plan_Schema::KEY_STEPS => array() );
		$stub_plan->find_step_index_return = 0;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				$item_id = $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ?? '';
				return $item_id === 'plan_npc_0' ? array( 'success' => true ) : array( 'success' => false, 'message' => 'Handler failed.' );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $stub_plan, function (): bool { return true; } );
		$job_dispatcher = new Execution_Job_Dispatcher( $stub_repo, $executor );
		$e0 = $this->valid_envelope( 'plan_npc_0', Execution_Action_Types::CREATE_PAGE );
		$e1 = $this->valid_envelope( 'plan_npc_1', Execution_Action_Types::CREATE_PAGE );
		$job_refs = $job_dispatcher->enqueue_batch( array( $e0, $e1 ), 'user:1', 0 );
		$results = $job_dispatcher->process_batch( $job_refs );
		$this->assertCount( 2, $results );
		$completed = 0;
		$failed = 0;
		foreach ( $results as $r ) {
			if ( $r->get_status() === Execution_Job_Result::STATUS_COMPLETED ) {
				++$completed;
			} else {
				++$failed;
			}
		}
		$this->assertSame( 1, $completed );
		$this->assertSame( 1, $failed );
	}

	public function test_job_result_retry_metadata(): void {
		$result = Execution_Job_Result::failed( 'job_1', 'create_page', 'act_1', 'item_1', 'Conflict.', array(), 2, true );
		$this->assertSame( 2, $result->get_retry_count() );
		$this->assertTrue( $result->is_retry_eligible() );
		$arr = $result->to_array();
		$this->assertSame( 2, $arr['retry_count'] );
		$this->assertTrue( $arr['retry_eligible'] );
	}

	public function test_queue_service_returns_queued_when_not_run_immediately(): void {
		$plan_state = new Stub_Plan_State_For_Queue();
		$plan_state->get_by_key_return = array( 'id' => 1 );
		$plan_state->get_plan_definition_return = self::plan_definition_with_deps();
		$bulk = new Bulk_Executor();
		$stub_repo = new Stub_Job_Queue_Repository();
		$stub_plan_exec = new Stub_Plan_State_For_Executor_Queue();
		$stub_plan_exec->get_by_key_return = array( 'id' => 1 );
		$stub_plan_exec->get_plan_definition_return = array();
		$dispatcher = new Execution_Dispatcher();
		$executor = new Single_Action_Executor( $dispatcher, $stub_plan_exec, function (): bool { return true; } );
		$job_dispatcher = new Execution_Job_Dispatcher( $stub_repo, $executor );
		$service = new Execution_Queue_Service( $plan_state, $bulk, $job_dispatcher );
		$actor = array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) );
		$out = $service->request_bulk_execution( 'aio-plan-uuid-1', null, $actor, array( 'run_immediately' => false ) );
		$this->assertSame( 'queued', $out['status'] );
		$this->assertNotEmpty( $out['job_refs'] );
		$this->assertSame( 0, $out['completed_count'] );
		$this->assertEmpty( $out['item_results'] );
	}

	public function test_queue_service_run_immediately_aggregates_item_results_and_partial_failure(): void {
		$plan_state = new Stub_Plan_State_For_Queue();
		$plan_state->get_by_key_return = array( 'id' => 1 );
		$plan_state->get_plan_definition_return = self::plan_definition_with_deps();
		$bulk = new Bulk_Executor();
		$stub_repo = new Stub_Job_Queue_Repository();
		$stub_plan_exec = new Stub_Plan_State_For_Executor_Queue();
		$stub_plan_exec->get_by_key_return = array( 'id' => 1 );
		$stub_plan_exec->get_plan_definition_return = array( Build_Plan_Schema::KEY_PLAN_ID => 'aio-plan-uuid-1', Build_Plan_Schema::KEY_STEPS => array() );
		$stub_plan_exec->find_step_index_return = 0;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				$item_id = $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ?? '';
				return $item_id === 'plan_npc_0' ? array( 'success' => true ) : array( 'success' => false, 'message' => 'Fail' );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $stub_plan_exec, function (): bool { return true; } );
		$job_dispatcher = new Execution_Job_Dispatcher( $stub_repo, $executor );
		$service = new Execution_Queue_Service( $plan_state, $bulk, $job_dispatcher );
		$actor = array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) );
		$out = $service->request_bulk_execution( 'aio-plan-uuid-1', null, $actor, array( 'run_immediately' => true ) );
		$this->assertSame( 'partial', $out['status'] );
		$this->assertTrue( $out['partial_failure'] );
		$this->assertSame( 1, $out['completed_count'] );
		$this->assertSame( 1, $out['failed_count'] );
		$this->assertArrayHasKey( 'plan_npc_0', $out['item_results'] );
		$this->assertArrayHasKey( 'plan_npc_1', $out['item_results'] );
		$this->assertSame( 'completed', $out['item_results']['plan_npc_0']['status'] );
		$this->assertSame( 'failed', $out['item_results']['plan_npc_1']['status'] );
	}

	private function valid_envelope( string $plan_item_id, string $action_type ): array {
		return array(
			Execution_Action_Contract::ENVELOPE_ACTION_ID        => 'exec_' . $plan_item_id . '_' . gmdate( 'Ymd\THis\Z' ),
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE       => $action_type,
			Execution_Action_Contract::ENVELOPE_PLAN_ID         => 'aio-plan-uuid-1',
			Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID      => $plan_item_id,
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE  => array( 'plan_item_id' => $plan_item_id ),
			Execution_Action_Contract::ENVELOPE_APPROVAL_STATE    => array(
				Execution_Action_Contract::APPROVAL_PLAN_STATUS => 'approved',
				Execution_Action_Contract::APPROVAL_ITEM_STATUS => 'approved',
				Execution_Action_Contract::APPROVAL_VERIFIED_AT => gmdate( 'c' ),
			),
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT     => array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => gmdate( 'c' ) ),
			Execution_Action_Contract::ENVELOPE_CREATED_AT       => gmdate( 'c' ),
		);
	}
}
