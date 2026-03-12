<?php
/**
 * Contract for job queue persistence used by Execution_Job_Dispatcher (spec §11.3, §42; Prompt 080).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal queue persistence for dispatch: insert, get by ref, update status.
 */
interface Job_Queue_Repository_Interface {

	/**
	 * Inserts a new job.
	 *
	 * @param array<string, mixed> $data job_ref, job_type, queue_status, optional actor_ref, priority, payload_ref, related_object_refs.
	 * @return string job_ref of inserted job, or empty on failure.
	 */
	public function insert_job( array $data ): string;

	/**
	 * Gets a job by job_ref.
	 *
	 * @param string $job_ref
	 * @return array<string, mixed>|null
	 */
	public function get_by_key( string $job_ref ): ?array;

	/**
	 * Updates job status and optional timestamps/failure reason.
	 *
	 * @param string      $job_ref
	 * @param string      $status
	 * @param string|null $failure_reason
	 * @param string|null $started_at
	 * @param string|null $completed_at
	 * @return bool
	 */
	public function update_job_status( string $job_ref, string $status, ?string $failure_reason = null, ?string $started_at = null, ?string $completed_at = null ): bool;
}
