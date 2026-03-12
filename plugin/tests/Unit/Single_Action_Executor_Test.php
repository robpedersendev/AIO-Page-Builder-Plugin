<?php
/**
 * Unit tests for Single_Action_Executor (spec §40.2; Prompt 079).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Dispatcher;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Result;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
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

/**
 * Stub plan state for executor tests.
 */
final class Stub_Plan_State_For_Executor implements \AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface {

	/** @var array<string, mixed>|null */
	public $get_by_key_return = null;

	/** @var array<string, mixed> */
	public $get_plan_definition_return = array();

	/** @var int|null */
	public $find_step_index_return = 0;

	/** @var bool */
	public $update_plan_item_status_return = true;

	/** @var array<string, mixed> Last call to update_plan_item_status. */
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

	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status ): bool {
		$this->last_update_call = array( 'post_id' => $post_id, 'step_index' => $step_index, 'item_id' => $item_id, 'new_status' => $new_status );
		return $this->update_plan_item_status_return;
	}
}

final class Single_Action_Executor_Test extends TestCase {

	/** Example execution action input (valid envelope for create_page). */
	public const EXAMPLE_EXECUTION_INPUT = array(
		'action_id'        => 'exec_create_plan_npc_0_20250311T120000Z',
		'action_type'      => 'create_page',
		'plan_id'         => 'aio-plan-uuid-1',
		'plan_item_id'    => 'plan_npc_0',
		'target_reference' => array(
			'plan_item_id'  => 'plan_npc_0',
			'template_ref'  => array( 'type' => 'internal_key', 'value' => 'template_landing' ),
		),
		'approval_state'  => array(
			'plan_status'   => 'in_progress',
			'item_status'   => 'approved',
			'verified_at'   => '2025-03-11T11:59:00Z',
		),
		'actor_context'   => array(
			'actor_type'    => 'user',
			'actor_id'      => '1',
			'capability_checked' => 'aio_execute_build_plans',
			'checked_at'    => '2025-03-11T11:59:00Z',
		),
		'created_at'      => '2025-03-11T12:00:00Z',
	);

	/** Example execution result payload (handler stub returns not implemented → failed). */
	public static function example_result_payload(): array {
		return array(
			'action_id'       => 'exec_create_plan_npc_0_20250311T120000Z',
			'action_type'     => 'create_page',
			'status'          => 'failed',
			'completed_at'    => gmdate( 'c' ),
			'handler_result'  => array( 'success' => false, 'message' => 'Action type "create_page" is not yet implemented.', 'artifacts' => array() ),
			'snapshot_reference' => '',
			'warnings'        => array(),
			'build_plan_updates' => array( 'plan_id' => 'aio-plan-uuid-1', 'plan_item_id' => 'plan_npc_0', 'item_status' => 'failed' ),
			'log_reference'   => '',
			'error'           => array( 'code' => 'execution_failed', 'message' => 'Action type "create_page" is not yet implemented.', 'refusable' => false ),
		);
	}

	/** Invalid envelope: pending plan and item (must be refused). */
	private static function invalid_envelope(): array {
		return array(
			'action_id'        => 'exec_replace_ep_0_20250311T120001Z',
			'action_type'      => 'replace_page',
			'plan_id'          => 'aio-plan-uuid-1',
			'plan_item_id'     => 'plan_ep_0',
			'target_reference' => array( 'page_ref' => array( 'type' => 'post_id', 'value' => 42 ), 'plan_item_id' => 'plan_ep_0' ),
			'approval_state'   => array( 'plan_status' => 'pending_review', 'item_status' => 'pending', 'verified_at' => '2025-03-11T11:00:00Z' ),
			'actor_context'    => array( 'actor_type' => 'user', 'actor_id' => '1', 'capability_checked' => 'aio_execute_build_plans', 'checked_at' => '2025-03-11T12:00:01Z' ),
			'created_at'       => '2025-03-11T12:00:01Z',
		);
	}

	public function test_approval_rejection_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo );
		$envelope   = self::invalid_envelope();
		$result     = $executor->execute( $envelope );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_UNAUTHORIZED, $result->get_error_code() );
		$this->assertTrue( $result->is_refusable() );
	}

	public function test_invalid_envelope_shape_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo );
		$envelope   = array( 'action_id' => 'x', 'action_type' => 'create_page' );
		$result     = $executor->execute( $envelope );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_INVALID_ENVELOPE, $result->get_error_code() );
	}

	public function test_plan_not_found_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = null;
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; } );
		$result     = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND, $result->get_error_code() );
	}

	public function test_permission_rejection_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo, function (): bool {
			return false;
		} );
		$result = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_UNAUTHORIZED, $result->get_error_code() );
	}

	public function test_dependency_rejection_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; } );
		$envelope   = self::EXAMPLE_EXECUTION_INPUT;
		$envelope['dependency_manifest'] = array( 'resolved' => false, 'resolution_errors' => array( 'Parent page missing' ) );
		$result = $executor->execute( $envelope );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_DEPENDENCY_FAILED, $result->get_error_code() );
	}

	public function test_snapshot_required_refusal_when_preflight_returns_null(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; }, function (): ?string {
			return null;
		} );
		$envelope = self::EXAMPLE_EXECUTION_INPUT;
		$envelope['snapshot_required'] = true;
		$envelope['snapshot_ref']      = '';
		$result = $executor->execute( $envelope );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_SNAPSHOT_REQUIRED, $result->get_error_code() );
	}

	public function test_lock_acquire_failure_returns_refused(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$dispatcher = new Execution_Dispatcher();
		$executor   = new Single_Action_Executor( $dispatcher, $repo, function (): bool {
			return true;
		}, null, function (): bool {
			return false;
		} );
		$result = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_CONFLICT, $result->get_error_code() );
	}

	public function test_handler_failure_updates_plan_item_to_failed(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$repo->find_step_index_return = 2;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				return array( 'success' => false, 'message' => 'Handler failed.', 'artifacts' => array() );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; } );
		$result   = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertSame( Execution_Action_Contract::STATUS_FAILED, $result->get_execution_status() );
		$this->assertSame( Execution_Action_Contract::ERROR_EXECUTION_FAILED, $result->get_error_code() );
		$this->assertNotEmpty( $repo->last_update_call );
		$this->assertSame( 'failed', $repo->last_update_call['new_status'] );
		$this->assertSame( 'plan_npc_0', $repo->last_update_call['item_id'] );
	}

	public function test_handler_success_updates_plan_item_to_completed(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$repo->find_step_index_return = 2;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				return array( 'success' => true, 'artifacts' => array( 'post_id' => 42 ) );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; } );
		$result   = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertSame( Execution_Action_Contract::STATUS_COMPLETED, $result->get_execution_status() );
		$this->assertSame( 42, ( $result->get_handler_result()['artifacts']['post_id'] ?? 0 ) );
		$this->assertSame( 'completed', $repo->last_update_call['new_status'] );
		$this->assertNotEmpty( $result->get_build_plan_updates() );
	}

	public function test_lock_release_called_after_handler_throws(): void {
		$repo = new Stub_Plan_State_For_Executor();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array( Build_Plan_Schema::KEY_STEPS => array() );
		$repo->find_step_index_return = 0;
		$released = false;
		$dispatcher = new Execution_Dispatcher();
		$dispatcher->register_handler( Execution_Action_Types::CREATE_PAGE, new class() implements Execution_Handler_Interface {
			public function execute( array $envelope ): array {
				throw new \RuntimeException( 'Handler threw.' );
			}
		} );
		$executor = new Single_Action_Executor( $dispatcher, $repo, function (): bool { return true; }, null, null, function () use ( &$released ): void {
			$released = true;
		} );
		$result = $executor->execute( self::EXAMPLE_EXECUTION_INPUT );
		$this->assertTrue( $released );
		$this->assertSame( Execution_Action_Contract::STATUS_FAILED, $result->get_execution_status() );
	}

	public function test_execution_result_to_array_has_required_shape(): void {
		$payload = self::example_result_payload();
		$this->assertArrayHasKey( 'action_id', $payload );
		$this->assertArrayHasKey( 'status', $payload );
		$this->assertArrayHasKey( 'handler_result', $payload );
		$this->assertArrayHasKey( 'build_plan_updates', $payload );
		$this->assertArrayHasKey( 'error', $payload );
		$this->assertSame( 'failed', $payload['status'] );
	}
}
