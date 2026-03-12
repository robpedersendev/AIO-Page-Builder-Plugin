<?php
/**
 * Bulk execution request intake and queue dispatch (spec §40.3, §42.1; Prompt 080).
 *
 * Accepts approved Build Plan items, builds ordered envelopes via Bulk_Executor,
 * creates queue jobs via Execution_Job_Dispatcher, optionally runs them synchronously.
 * Returns aggregate result with per-item status and partial-failure reporting.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Executor\Plan_State_For_Execution_Interface;

/**
 * Bulk execution request intake; delegates to Bulk_Executor and Execution_Job_Dispatcher.
 */
final class Execution_Queue_Service {

	/** @var Plan_State_For_Execution_Interface */
	private $plan_state;

	/** @var Bulk_Executor */
	private $bulk_executor;

	/** @var Execution_Job_Dispatcher */
	private $job_dispatcher;

	public function __construct(
		Plan_State_For_Execution_Interface $plan_state,
		Bulk_Executor $bulk_executor,
		Execution_Job_Dispatcher $job_dispatcher
	) {
		$this->plan_state    = $plan_state;
		$this->bulk_executor = $bulk_executor;
		$this->job_dispatcher = $job_dispatcher;
	}

	/**
	 * Request bulk execution for approved plan items. Enqueues jobs; optionally runs them immediately.
	 *
	 * @param string               $plan_id       Plan ID (internal key).
	 * @param array<int, string>|null $item_ids   Plan item IDs to execute; null = all eligible approved items.
	 * @param array<string, mixed> $actor_context Actor context (actor_type, actor_id, capability_checked).
	 * @param array<string, mixed> $options       Optional: run_immediately (bool), priority (int), batch_id (string).
	 * @return array<string, mixed> Bulk result: job_refs, item_results, completed_count, failed_count, refused_count, partial_failure, results_summary.
	 */
	public function request_bulk_execution(
		string $plan_id,
		?array $item_ids,
		array $actor_context,
		array $options = array()
	): array {
		$plan_record = $this->plan_state->get_by_key( $plan_id );
		if ( $plan_record === null ) {
			return $this->bulk_error_result( $plan_id, 'Plan not found.', array(), 0, 0, 0 );
		}
		$plan_post_id = (int) ( $plan_record['id'] ?? 0 );
		$definition   = $this->plan_state->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return $this->bulk_error_result( $plan_id, 'Plan definition not found.', array(), 0, 0, 0 );
		}

		$batch_id = isset( $options['batch_id'] ) && is_string( $options['batch_id'] ) ? $options['batch_id'] : '';
		$envelopes = $this->bulk_executor->build_ordered_envelopes( $plan_id, $definition, $item_ids, $actor_context, $batch_id );
		if ( empty( $envelopes ) ) {
			return $this->bulk_error_result( $plan_id, 'No eligible actions to execute.', array(), 0, 0, 0 );
		}

		$actor_ref = $this->actor_ref_from_context( $actor_context );
		$priority  = isset( $options['priority'] ) && is_numeric( $options['priority'] ) ? (int) $options['priority'] : 0;
		$job_refs  = $this->job_dispatcher->enqueue_batch( $envelopes, $actor_ref, $priority );

		$run_immediately = ! empty( $options['run_immediately'] );
		if ( $run_immediately && ! empty( $job_refs ) ) {
			$results = $this->job_dispatcher->process_batch( $job_refs );
			return $this->aggregate_bulk_result( $plan_id, $job_refs, $results );
		}

		return array(
			'plan_id'         => $plan_id,
			'status'          => 'queued',
			'job_refs'        => $job_refs,
			'item_results'    => array(),
			'completed_count' => 0,
			'failed_count'    => 0,
			'refused_count'   => 0,
			'partial_failure' => false,
			'results_summary' => array(),
			'message'         => __( 'Actions queued.', 'aio-page-builder' ),
		);
	}

	/**
	 * Aggregates per-job results into bulk result with per-item status and partial-failure flag.
	 *
	 * @param string                    $plan_id
	 * @param array<int, string>        $job_refs
	 * @param array<int, Execution_Job_Result> $results
	 * @return array<string, mixed>
	 */
	private function aggregate_bulk_result( string $plan_id, array $job_refs, array $results ): array {
		$item_results = array();
		$completed = 0;
		$failed = 0;
		$refused = 0;
		$summary = array();
		foreach ( $results as $r ) {
			$plan_item_id = $r->get_plan_item_id();
			$status = $r->get_status();
			$item_results[ $plan_item_id ] = array(
				'status'         => $status,
				'job_ref'        => $r->get_job_ref(),
				'action_id'      => $r->get_action_id(),
				'retry_eligible' => $r->is_retry_eligible(),
				'failure_reason' => $r->get_failure_reason(),
			);
			$summary[] = $r->to_array();
			if ( $status === Execution_Job_Result::STATUS_COMPLETED ) {
				++$completed;
			} elseif ( $status === Execution_Job_Result::STATUS_REFUSED ) {
				++$refused;
			} else {
				++$failed;
			}
		}
		$total = $completed + $failed + $refused;
		$partial_failure = $failed > 0 || $refused > 0;
		$overall_status = $refused === $total ? 'refused' : ( $completed === $total ? 'completed' : 'partial' );

		return array(
			'plan_id'         => $plan_id,
			'status'          => $overall_status,
			'job_refs'        => $job_refs,
			'item_results'    => $item_results,
			'completed_count' => $completed,
			'failed_count'     => $failed,
			'refused_count'   => $refused,
			'partial_failure' => $partial_failure,
			'results_summary' => $summary,
			'message'         => $this->bulk_message( $completed, $failed, $refused ),
		);
	}

	private function bulk_error_result( string $plan_id, string $message, array $job_refs, int $completed, int $failed, int $refused ): array {
		return array(
			'plan_id'         => $plan_id,
			'status'          => 'error',
			'job_refs'        => $job_refs,
			'item_results'    => array(),
			'completed_count' => $completed,
			'failed_count'    => $failed,
			'refused_count'   => $refused,
			'partial_failure' => false,
			'results_summary' => array(),
			'message'         => $message,
		);
	}

	private function actor_ref_from_context( array $actor_context ): string {
		$type = isset( $actor_context['actor_type'] ) && is_string( $actor_context['actor_type'] ) ? $actor_context['actor_type'] : 'user';
		$id   = isset( $actor_context['actor_id'] ) && is_string( $actor_context['actor_id'] ) ? $actor_context['actor_id'] : '';
		return $type . ':' . ( $id !== '' ? $id : '0' );
	}

	private function bulk_message( int $completed, int $failed, int $refused ): string {
		$parts = array();
		if ( $completed > 0 ) {
			$parts[] = sprintf( __( '%d completed.', 'aio-page-builder' ), $completed );
		}
		if ( $failed > 0 ) {
			$parts[] = sprintf( __( '%d failed.', 'aio-page-builder' ), $failed );
		}
		if ( $refused > 0 ) {
			$parts[] = sprintf( __( '%d refused.', 'aio-page-builder' ), $refused );
		}
		return implode( ' ', $parts ) !== '' ? implode( ' ', $parts ) : __( 'No results.', 'aio-page-builder' );
	}
}
