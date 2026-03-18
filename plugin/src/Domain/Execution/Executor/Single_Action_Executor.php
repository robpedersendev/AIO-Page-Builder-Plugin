<?php
/**
 * Single-action executor: validate, lock, dispatch, record, update plan (spec §39.3–39.8, §40.2, §32.9; Prompt 079, 196).
 *
 * Consumes governed action envelopes; validates authorization and dependencies; acquires locks;
 * invokes snapshot preflight where required; delegates to Execution_Dispatcher; records outcomes;
 * updates Build Plan item state. For replace_page, handler result artifacts include
 * template_replacement_execution_result and replacement_trace_record when Template_Page_Replacement_Service is used.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Reusable single-action execution flow. Handlers are registered on the dispatcher.
 */
final class Single_Action_Executor {

	/** @var Execution_Dispatcher */
	private $dispatcher;

	/** @var Plan_State_For_Execution_Interface */
	private $plan_state;

	/** @var callable(): bool Permission check (default: current user has capability from envelope). */
	private $capability_checker;

	/** @var callable(array): string|null Snapshot preflight; returns snapshot_ref or null. */
	private $snapshot_preflight;

	/** @var callable(array): bool Lock acquire; returns true if acquired. */
	private $lock_acquire;

	/** @var callable(): void Lock release. */
	private $lock_release;

	/** @var callable(array, array): void|null Optional post-success snapshot capture (envelope, handler_result). */
	private $post_capture_snapshot;

	public function __construct(
		Execution_Dispatcher $dispatcher,
		Plan_State_For_Execution_Interface $plan_state,
		?callable $capability_checker = null,
		?callable $snapshot_preflight = null,
		?callable $lock_acquire = null,
		?callable $lock_release = null,
		?callable $post_capture_snapshot = null
	) {
		$this->dispatcher  = $dispatcher;
		$this->plan_state  = $plan_state;
		$this->capability_checker    = $capability_checker ?? array( $this, 'default_capability_check' );
		$this->snapshot_preflight    = $snapshot_preflight ?? array( $this, 'default_snapshot_preflight' );
		$this->lock_acquire          = $lock_acquire ?? array( $this, 'default_lock_acquire' );
		$this->lock_release          = $lock_release ?? array( $this, 'default_lock_release' );
		$this->post_capture_snapshot = $post_capture_snapshot;
	}

	/**
	 * Runs the single-action execution flow. Returns structured result; no exception for refusal/failure.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return Execution_Result
	 */
	public function execute( array $envelope ): Execution_Result {
		$action_id   = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] : '';
		$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] : '';
		$plan_id     = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] : '';
		$plan_item_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';

		$shape_errors = Execution_Action_Contract::validate_envelope_shape( $envelope );
		if ( ! empty( $shape_errors ) ) {
			$first = $shape_errors[0];
			return Execution_Result::refused( $action_id, $action_type, $first['code'] ?? Execution_Action_Contract::ERROR_INVALID_ENVELOPE, __( 'Invalid action envelope.', 'aio-page-builder' ) );
		}

		$approval_errors = Execution_Action_Contract::validate_approval_precondition( $envelope );
		if ( ! empty( $approval_errors ) ) {
			$first = $approval_errors[0];
			return Execution_Result::refused( $action_id, $action_type, $first['code'] ?? Execution_Action_Contract::ERROR_UNAUTHORIZED, __( 'Approval precondition not met.', 'aio-page-builder' ) );
		}

		$plan_record = $this->plan_state->get_by_key( $plan_id );
		if ( $plan_record === null ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_TARGET_NOT_FOUND, __( 'Build Plan not found.', 'aio-page-builder' ) );
		}

		$plan_post_id = (int) ( $plan_record['id'] ?? 0 );
		$definition   = $this->plan_state->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_TARGET_NOT_FOUND, __( 'Plan definition not found.', 'aio-page-builder' ) );
		}

		if ( ! ( $this->capability_checker )( $envelope ) ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_UNAUTHORIZED, __( 'Permission denied.', 'aio-page-builder' ) );
		}

		$dep = $envelope['dependency_manifest'] ?? null;
		if ( is_array( $dep ) && isset( $dep['resolved'] ) && $dep['resolved'] === false && ! empty( $dep['resolution_errors'] ) ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_DEPENDENCY_FAILED, __( 'Dependencies not satisfied.', 'aio-page-builder' ) );
		}

		if ( ! $this->dispatcher->has_handler( $action_type ) ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_ACTION_NOT_AVAILABLE, __( 'This action type is not available in this version.', 'aio-page-builder' ) );
		}

		$snapshot_required = ! empty( $envelope['snapshot_required'] );
		$snapshot_ref      = isset( $envelope['snapshot_ref'] ) && is_string( $envelope['snapshot_ref'] ) ? trim( $envelope['snapshot_ref'] ) : '';
		if ( $snapshot_required && $snapshot_ref === '' ) {
			$preflight_ref = ( $this->snapshot_preflight )( $envelope );
			if ( $preflight_ref !== null && $preflight_ref !== '' ) {
				$snapshot_ref = $preflight_ref;
				$envelope['snapshot_ref'] = $snapshot_ref;
				$envelope['operational_pre_snapshot_id'] = $snapshot_ref;
			}
			// * Fail safely: if preflight returns null (e.g. capture failed), log and proceed without blocking execution (spec §41.2, Prompt 087).
		}

		$scope_keys = $this->scope_keys_for_envelope( $envelope );
		if ( ! ( $this->lock_acquire )( $scope_keys ) ) {
			return Execution_Result::refused( $action_id, $action_type, Execution_Action_Contract::ERROR_CONFLICT, __( 'Could not acquire lock.', 'aio-page-builder' ) );
		}

		try {
			$handler_result = $this->dispatcher->dispatch( $envelope );
			$success        = ! empty( $handler_result['success'] );

			if ( $success ) {
				if ( $this->post_capture_snapshot !== null ) {
					( $this->post_capture_snapshot )( $envelope, $handler_result );
				}
				$build_plan_updates = array();
				if ( $plan_item_id !== '' && $action_type !== Execution_Action_Types::FINALIZE_PLAN && $action_type !== Execution_Action_Types::ROLLBACK_ACTION ) {
					$step_index = $this->plan_state->find_step_index_for_item( $definition, $plan_item_id );
					if ( $step_index !== null ) {
						$artifacts = isset( $handler_result['artifacts'] ) && is_array( $handler_result['artifacts'] ) ? $handler_result['artifacts'] : null;
						$this->plan_state->update_plan_item_status( $plan_post_id, $step_index, $plan_item_id, Build_Plan_Item_Statuses::COMPLETED, $artifacts );
						$build_plan_updates = array( 'plan_id' => $plan_id, 'plan_item_id' => $plan_item_id, 'item_status' => Build_Plan_Item_Statuses::COMPLETED );
					}
				}
				return Execution_Result::completed( $action_id, $action_type, $handler_result, $snapshot_ref, $build_plan_updates, array(), '' );
			}

			$message = isset( $handler_result['message'] ) && is_string( $handler_result['message'] ) ? $handler_result['message'] : __( 'Handler reported failure.', 'aio-page-builder' );
			$build_plan_updates = array();
			if ( $plan_item_id !== '' ) {
				$step_index = $this->plan_state->find_step_index_for_item( $definition, $plan_item_id );
				if ( $step_index !== null ) {
					$this->plan_state->update_plan_item_status( $plan_post_id, $step_index, $plan_item_id, Build_Plan_Item_Statuses::FAILED );
					$build_plan_updates = array( 'plan_id' => $plan_id, 'plan_item_id' => $plan_item_id, 'item_status' => Build_Plan_Item_Statuses::FAILED );
				}
			}
			return Execution_Result::failed( $action_id, $action_type, Execution_Action_Contract::ERROR_EXECUTION_FAILED, $message, $handler_result, $build_plan_updates, '' );
		} catch ( \Throwable $e ) {
			$build_plan_updates = array();
			if ( $plan_item_id !== '' ) {
				$step_index = $this->plan_state->find_step_index_for_item( $definition, $plan_item_id );
				if ( $step_index !== null ) {
					$this->plan_state->update_plan_item_status( $plan_post_id, $step_index, $plan_item_id, Build_Plan_Item_Statuses::FAILED );
					$build_plan_updates = array( 'plan_id' => $plan_id, 'plan_item_id' => $plan_item_id, 'item_status' => Build_Plan_Item_Statuses::FAILED );
				}
			}
			return Execution_Result::failed( $action_id, $action_type, Execution_Action_Contract::ERROR_EXECUTION_FAILED, $e->getMessage(), array(), $build_plan_updates, '' );
		} finally {
			( $this->lock_release )();
		}
	}

	/**
	 * Default capability check: current user has the capability from envelope actor_context.
	 *
	 * @param array<string, mixed> $envelope
	 * @return bool
	 */
	private function default_capability_check( array $envelope ): bool {
		$actor = $envelope[ Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT ] ?? array();
		if ( ! is_array( $actor ) ) {
			return false;
		}
		$cap = $actor[ Execution_Action_Contract::ACTOR_CAPABILITY_CHECKED ] ?? '';
		return is_string( $cap ) && $cap !== '' && \current_user_can( $cap );
	}

	/**
	 * Default snapshot preflight: return envelope snapshot_ref or null (no capture).
	 *
	 * @param array<string, mixed> $envelope
	 * @return string|null
	 */
	private function default_snapshot_preflight( array $envelope ): ?string {
		$ref = $envelope['snapshot_ref'] ?? null;
		return is_string( $ref ) && trim( $ref ) !== '' ? trim( $ref ) : null;
	}

	/**
	 * Default lock acquire: no-op, always succeeds.
	 *
	 * @param array<int, string> $scope_keys
	 * @return bool
	 */
	private function default_lock_acquire( array $scope_keys ): bool {
		return true;
	}

	/**
	 * Default lock release: no-op.
	 *
	 * @return void
	 */
	private function default_lock_release(): void {
	}

	/**
	 * Builds scope keys for lock acquisition (contract §3.1).
	 *
	 * @param array<string, mixed> $envelope
	 * @return array<int, string>
	 */
	private function scope_keys_for_envelope( array $envelope ): array {
		$action_id   = $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ?? '';
		$plan_id     = $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ?? '';
		$plan_item_id = $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ?? '';
		$keys = array();
		if ( is_string( $action_id ) && $action_id !== '' ) {
			$keys[] = 'action:' . $action_id;
		}
		if ( is_string( $plan_id ) && $plan_id !== '' ) {
			$keys[] = 'plan:' . $plan_id;
		}
		if ( is_string( $plan_item_id ) && $plan_item_id !== '' ) {
			$keys[] = 'plan_item:' . $plan_id . ':' . $plan_item_id;
		}
		return $keys;
	}
}
