<?php
/**
 * Creates queue jobs from action envelopes and dispatches them via Single_Action_Executor (spec §40.3, §42; Prompt 080).
 * Supports rollback job type (spec §38.5, §59.11; Prompt 090).
 *
 * Enqueues batches with stable job metadata; processes one job at a time through the single-action executor.
 * Records per-job status and result; supports retry metadata. No queue monitoring UI.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Executor\Execution_Result;
use AIOPageBuilder\Domain\Execution\Executor\Single_Action_Executor;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Executor;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Result;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Status;

/**
 * Queue job creation and dispatch through the single-action executor or rollback executor.
 */
final class Execution_Job_Dispatcher {

	/** @var Job_Queue_Repository_Interface */
	private $job_queue_repository;

	/** @var Single_Action_Executor */
	private $single_action_executor;

	/** @var Rollback_Executor|null */
	private $rollback_executor;

	public function __construct(
		Job_Queue_Repository_Interface $job_queue_repository,
		Single_Action_Executor $single_action_executor,
		?Rollback_Executor $rollback_executor = null
	) {
		$this->job_queue_repository   = $job_queue_repository;
		$this->single_action_executor = $single_action_executor;
		$this->rollback_executor      = $rollback_executor;
	}

	/**
	 * Creates one queue job per envelope and persists them in dependency order.
	 * Job payload stores envelope JSON in related_object_refs; payload_ref = action_id.
	 *
	 * @param array<int, array<string, mixed>> $envelopes Ordered action envelopes.
	 * @param string                           $actor_ref Actor reference for the batch.
	 * @param int                              $priority  Optional priority (higher = earlier).
	 * @return array<int, string> List of job_refs in order.
	 */
	public function enqueue_batch( array $envelopes, string $actor_ref, int $priority = 0 ): array {
		$job_refs = array();
		foreach ( $envelopes as $envelope ) {
			$action_id   = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] : '';
			$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] : '';
			if ( $action_id === '' || $action_type === '' ) {
				continue;
			}
			$job_ref = $this->generate_job_ref( $action_id );
			$payload_json = \wp_json_encode( $envelope );
			$data = array(
				'job_ref'              => $job_ref,
				'job_type'              => $action_type,
				'queue_status'          => Job_Queue_Status::PENDING,
				'priority'              => $priority,
				'payload_ref'           => $action_id,
				'actor_ref'             => $actor_ref,
				'related_object_refs'   => $payload_json !== false ? $payload_json : '{}',
			);
			$inserted = $this->job_queue_repository->insert_job( $data );
			if ( $inserted !== '' ) {
				$job_refs[] = $inserted;
			}
		}
		return $job_refs;
	}

	/**
	 * Loads a job by job_ref, runs it through the single-action executor, updates job status, returns result.
	 *
	 * @param string $job_ref
	 * @return Execution_Job_Result|null Result or null if job not found / invalid payload.
	 */
	public function process_job( string $job_ref ): ?Execution_Job_Result {
		$job = $this->job_queue_repository->get_by_key( $job_ref );
		if ( $job === null ) {
			return null;
		}
		$job_type = isset( $job['job_type'] ) && is_string( $job['job_type'] ) ? $job['job_type'] : '';
		if ( $job_type === Execution_Action_Types::ROLLBACK_ACTION ) {
			return $this->process_rollback_job( $job_ref, $job );
		}

		$envelope = $this->envelope_from_job( $job );
		if ( $envelope === null ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, 'Invalid or missing envelope.', null, current_time( 'mysql' ) );
			return null;
		}
		$action_id    = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] : '';
		$action_type  = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] : '';
		$plan_item_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';
		$retry_count  = isset( $job['retry_count'] ) && is_numeric( $job['retry_count'] ) ? (int) $job['retry_count'] : 0;

		$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::RUNNING, null, current_time( 'mysql' ), null );

		$result = $this->single_action_executor->execute( $envelope );
		$status = $result->get_execution_status();
		$summary = $result->to_array();

		if ( $status === Execution_Action_Contract::STATUS_COMPLETED ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::COMPLETED, null, null, current_time( 'mysql' ) );
			return Execution_Job_Result::completed( $job_ref, $action_type, $action_id, $plan_item_id, $summary, $retry_count );
		}

		if ( $status === Execution_Action_Contract::STATUS_REFUSED ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, $result->get_error_message(), null, current_time( 'mysql' ) );
			return Execution_Job_Result::refused( $job_ref, $action_type, $action_id, $plan_item_id, $result->get_error_message(), $summary );
		}

		$failure_reason = $result->get_error_message();
		$retry_eligible = $this->is_retry_eligible( $result );
		$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, $failure_reason, null, current_time( 'mysql' ) );
		return Execution_Job_Result::failed( $job_ref, $action_type, $action_id, $plan_item_id, $failure_reason, $summary, $retry_count, $retry_eligible );
	}

	/**
	 * Processes a rollback job: revalidates eligibility, runs handler, updates job status (spec §38.5, §59.11).
	 *
	 * @param string               $job_ref
	 * @param array<string, mixed> $job
	 * @return Execution_Job_Result|null
	 */
	private function process_rollback_job( string $job_ref, array $job ): ?Execution_Job_Result {
		$payload = $this->envelope_from_job( $job );
		if ( $payload === null || ! is_array( $payload ) ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, 'Invalid or missing rollback payload.', null, current_time( 'mysql' ) );
			return null;
		}
		$payload['job_id'] = $job_ref;
		$plan_item_id = isset( $payload['plan_item_ref'] ) && is_string( $payload['plan_item_ref'] ) ? $payload['plan_item_ref'] : '';
		$execution_ref = isset( $payload['execution_ref'] ) && is_string( $payload['execution_ref'] ) ? $payload['execution_ref'] : $job_ref;

		if ( $this->rollback_executor === null ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, 'Rollback executor not configured.', null, current_time( 'mysql' ) );
			return Execution_Job_Result::refused( $job_ref, Execution_Action_Types::ROLLBACK_ACTION, $execution_ref, $plan_item_id, 'Rollback executor not configured.', array() );
		}

		$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::RUNNING, null, current_time( 'mysql' ), null );
		$rollback_result = $this->rollback_executor->execute( $payload );
		$summary = $rollback_result->to_array();

		if ( $rollback_result->is_success() ) {
			$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::COMPLETED, null, null, current_time( 'mysql' ) );
			return Execution_Job_Result::completed( $job_ref, Execution_Action_Types::ROLLBACK_ACTION, $execution_ref, $plan_item_id, $summary, 0 );
		}

		$failure_reason = $rollback_result->get_failure_reason() !== '' ? $rollback_result->get_failure_reason() : $rollback_result->get_message();
		$refused = $rollback_result->get_status() === Rollback_Result::STATUS_INELIGIBLE;
		$this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::FAILED, $failure_reason, null, current_time( 'mysql' ) );
		return $refused
			? Execution_Job_Result::refused( $job_ref, Execution_Action_Types::ROLLBACK_ACTION, $execution_ref, $plan_item_id, $failure_reason, $summary )
			: Execution_Job_Result::failed( $job_ref, Execution_Action_Types::ROLLBACK_ACTION, $execution_ref, $plan_item_id, $failure_reason, $summary, 0, false );
	}

	/**
	 * Enqueues a single rollback job. Payload must contain pre_snapshot_id, post_snapshot_id, rollback_handler_key, target_ref; optional execution_ref, build_plan_ref, plan_item_ref.
	 *
	 * @param array<string, mixed> $payload   Rollback payload (pre_snapshot_id, post_snapshot_id, rollback_handler_key, target_ref, ...).
	 * @param string               $actor_ref Actor reference.
	 * @param int                  $priority  Queue priority.
	 * @return string Job ref of enqueued job, or empty on failure.
	 */
	public function enqueue_rollback_job( array $payload, string $actor_ref, int $priority = 0 ): string {
		$pre_id  = isset( $payload['pre_snapshot_id'] ) && is_string( $payload['pre_snapshot_id'] ) ? trim( $payload['pre_snapshot_id'] ) : '';
		$post_id = isset( $payload['post_snapshot_id'] ) && is_string( $payload['post_snapshot_id'] ) ? trim( $payload['post_snapshot_id'] ) : '';
		if ( $pre_id === '' || $post_id === '' ) {
			return '';
		}
		$job_ref = 'job_rollback_' . substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $pre_id . '_' . $post_id ), 0, 30 ) . '_' . gmdate( 'YmdHis' ) . '_' . wp_rand( 100, 999 );
		$payload['job_id'] = $job_ref;
		$payload_json = \wp_json_encode( $payload );
		$data = array(
			'job_ref'              => $job_ref,
			'job_type'              => Execution_Action_Types::ROLLBACK_ACTION,
			'queue_status'          => Job_Queue_Status::PENDING,
			'priority'              => $priority,
			'payload_ref'           => $pre_id . ':' . $post_id,
			'actor_ref'             => $actor_ref,
			'related_object_refs'   => $payload_json !== false ? $payload_json : '{}',
		);
		$inserted = $this->job_queue_repository->insert_job( $data );
		return $inserted !== '' ? $inserted : '';
	}

	/**
	 * Runs all jobs in the given list in order via process_job. Returns list of Execution_Job_Result.
	 *
	 * @param array<int, string> $job_refs
	 * @return array<int, Execution_Job_Result>
	 */
	public function process_batch( array $job_refs ): array {
		$results = array();
		foreach ( $job_refs as $job_ref ) {
			$r = $this->process_job( $job_ref );
			if ( $r !== null ) {
				$results[] = $r;
			}
		}
		return $results;
	}

	/**
	 * Marks a job as cancelled (hook for UI/callers; no full cancel UI in scope).
	 *
	 * @param string $job_ref
	 * @return bool
	 */
	public function mark_job_cancelled( string $job_ref ): bool {
		$job = $this->job_queue_repository->get_by_key( $job_ref );
		if ( $job === null ) {
			return false;
		}
		$status = isset( $job['queue_status'] ) && is_string( $job['queue_status'] ) ? $job['queue_status'] : '';
		if ( $status !== Job_Queue_Status::PENDING && $status !== Job_Queue_Status::RETRYING ) {
			return false;
		}
		return $this->job_queue_repository->update_job_status( $job_ref, Job_Queue_Status::CANCELLED, 'Cancelled.', null, current_time( 'mysql' ) );
	}

	/**
	 * Extracts envelope from job record (related_object_refs as JSON).
	 *
	 * @param array<string, mixed> $job
	 * @return array<string, mixed>|null
	 */
	private function envelope_from_job( array $job ): ?array {
		$raw = isset( $job['related_object_refs'] ) && is_string( $job['related_object_refs'] ) ? $job['related_object_refs'] : '';
		if ( $raw === '' ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		return is_array( $decoded ) ? $decoded : null;
	}

	private function generate_job_ref( string $action_id ): string {
		$suffix = substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $action_id ), 0, 40 );
		return 'job_' . $suffix . '_' . gmdate( 'YmdHis' ) . '_' . wp_rand( 100, 999 );
	}

	/**
	 * Whether a failed result is eligible for retry (transient/repairable; spec §40.6).
	 *
	 * @param \AIOPageBuilder\Domain\Execution\Executor\Execution_Result $result
	 * @return bool
	 */
	private function is_retry_eligible( Execution_Result $result ): bool {
		if ( $result->is_refusable() ) {
			return false;
		}
		$code = $result->get_error_code();
		$retryable = array( Execution_Action_Contract::ERROR_CONFLICT );
		return in_array( $code, $retryable, true );
	}
}