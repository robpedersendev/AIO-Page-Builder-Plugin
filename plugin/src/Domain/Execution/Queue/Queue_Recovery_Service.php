<?php
/**
 * Safe queue recovery actions for operators (spec §42.4, §42.5, §49.11).
 *
 * Retry eligible failed jobs, cancel/defer where policy allows. All actions
 * are server-authoritative, permission-gated by caller, and audit-logged.
 * No bypass of executor; no direct unsafe state mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Status;

/**
 * Performs retry and cancel recovery actions; logs all operator actions.
 */
final class Queue_Recovery_Service {

	private const MAX_RETRY_COUNT = 5;
	private const AUDIT_OPTION    = 'aio_page_builder_queue_recovery_audit';
	private const AUDIT_MAX       = 100;

	/** @var Queue_Recovery_Repository_Interface */
	private $job_repository;

	/** @var \Psr\Log\LoggerInterface|null */
	private $logger;

	public function __construct( Queue_Recovery_Repository_Interface $job_repository, $logger = null ) {
		$this->job_repository = $job_repository;
		$this->logger         = $logger;
	}

	/**
	 * Retries a failed job if eligible (spec §42.4). Sets status to pending and clears lock; executor will run it again.
	 *
	 * @param string $job_ref   Job reference.
	 * @param string $actor_ref Actor reference (e.g. user:id) for audit.
	 * @return array{success: bool, action: string, job_ref: string, message: string, previous_status: string}
	 */
	public function retry_job( string $job_ref, string $actor_ref = '' ): array {
		$job_ref = trim( $job_ref );
		$out     = array(
			'success'         => false,
			'action'          => 'retry',
			'job_ref'         => $job_ref,
			'message'         => '',
			'previous_status' => '',
		);
		if ( $job_ref === '' ) {
			$out['message'] = __( 'Missing job reference.', 'aio-page-builder' );
			return $out;
		}

		$job = $this->job_repository->get_by_key( $job_ref );
		if ( $job === null ) {
			$out['message'] = __( 'Job not found.', 'aio-page-builder' );
			return $out;
		}

		$status                 = isset( $job['queue_status'] ) && is_string( $job['queue_status'] ) ? $job['queue_status'] : '';
		$out['previous_status'] = $status;

		if ( $status !== Job_Queue_Status::FAILED ) {
			$out['message'] = __( 'Only failed jobs can be retried.', 'aio-page-builder' );
			return $out;
		}

		$job_type    = isset( $job['job_type'] ) && is_string( $job['job_type'] ) ? $job['job_type'] : '';
		$retry_count = isset( $job['retry_count'] ) && is_numeric( $job['retry_count'] ) ? (int) $job['retry_count'] : 0;
		if ( $retry_count >= self::MAX_RETRY_COUNT ) {
			$out['message'] = __( 'Job has exceeded maximum retry count.', 'aio-page-builder' );
			return $out;
		}
		if ( ! $this->is_retryable_job_type( $job_type ) ) {
			$out['message'] = __( 'Job type does not allow manual retry.', 'aio-page-builder' );
			return $out;
		}

		$ok = $this->job_repository->reset_for_retry( $job_ref );
		if ( ! $ok ) {
			$out['message'] = __( 'Failed to reset job for retry.', 'aio-page-builder' );
			return $out;
		}

		$out['success'] = true;
		$out['message'] = __( 'Job queued for retry.', 'aio-page-builder' );
		$this->log_recovery_action( 'retry', $job_ref, $status, $actor_ref, true );
		return $out;
	}

	/**
	 * Cancels a job where policy allows (pending, retrying, running, failed). Terminal states are not changed.
	 *
	 * @param string $job_ref   Job reference.
	 * @param string $actor_ref Actor reference for audit.
	 * @return array{success: bool, action: string, job_ref: string, message: string, previous_status: string}
	 */
	public function cancel_job( string $job_ref, string $actor_ref = '' ): array {
		$job_ref = trim( $job_ref );
		$out     = array(
			'success'         => false,
			'action'          => 'cancel',
			'job_ref'         => $job_ref,
			'message'         => '',
			'previous_status' => '',
		);
		if ( $job_ref === '' ) {
			$out['message'] = __( 'Missing job reference.', 'aio-page-builder' );
			return $out;
		}

		$job = $this->job_repository->get_by_key( $job_ref );
		if ( $job === null ) {
			$out['message'] = __( 'Job not found.', 'aio-page-builder' );
			return $out;
		}

		$status                 = isset( $job['queue_status'] ) && is_string( $job['queue_status'] ) ? $job['queue_status'] : '';
		$out['previous_status'] = $status;

		$cancelable = array(
			Job_Queue_Status::PENDING,
			Job_Queue_Status::RETRYING,
			Job_Queue_Status::RUNNING,
			Job_Queue_Status::FAILED,
		);
		if ( ! in_array( $status, $cancelable, true ) ) {
			$out['message'] = __( 'Job cannot be cancelled in its current state.', 'aio-page-builder' );
			return $out;
		}

		$ok = $this->job_repository->update_job_status( $job_ref, Job_Queue_Status::CANCELLED, __( 'Cancelled by operator.', 'aio-page-builder' ), null, current_time( 'mysql' ) );
		if ( ! $ok ) {
			$out['message'] = __( 'Failed to cancel job.', 'aio-page-builder' );
			return $out;
		}

		$out['success'] = true;
		$out['message'] = __( 'Job cancelled.', 'aio-page-builder' );
		$this->log_recovery_action( 'cancel', $job_ref, $status, $actor_ref, true );
		return $out;
	}

	private function is_retryable_job_type( string $job_type ): bool {
		return in_array(
			$job_type,
			array(
				Execution_Action_Types::CREATE_PAGE,
				Execution_Action_Types::REPLACE_PAGE,
				Execution_Action_Types::UPDATE_MENU,
				Execution_Action_Types::APPLY_TOKEN_SET,
				Execution_Action_Types::FINALIZE_PLAN,
				Execution_Action_Types::ROLLBACK_ACTION,
			),
			true
		);
	}

	private function log_recovery_action( string $action, string $job_ref, string $previous_status, string $actor_ref, bool $success ): void {
		$entry = array(
			'action'          => $action,
			'job_ref'         => $job_ref,
			'previous_status' => $previous_status,
			'actor_ref'       => $actor_ref,
			'success'         => $success,
			'recorded_at'     => current_time( 'mysql' ),
		);
		$log   = \get_option( self::AUDIT_OPTION, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$log[] = $entry;
		$log   = array_slice( $log, -self::AUDIT_MAX );
		\update_option( self::AUDIT_OPTION, $log, false );

		if ( $this->logger !== null && method_exists( $this->logger, 'info' ) ) {
			$this->logger->info( 'Queue recovery action', array( 'recovery' => $entry ) );
		}
	}
}
