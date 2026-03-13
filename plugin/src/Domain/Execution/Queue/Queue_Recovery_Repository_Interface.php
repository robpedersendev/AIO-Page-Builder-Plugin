<?php
/**
 * Contract for queue persistence used by Queue_Recovery_Service (spec §42.4, §42.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal queue access for recovery: get job, update status, reset for retry.
 */
interface Queue_Recovery_Repository_Interface {

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

	/**
	 * Resets a failed job for retry (pending, clear lock). Caller enforces eligibility.
	 *
	 * @param string $job_ref
	 * @return bool
	 */
	public function reset_for_retry( string $job_ref ): bool;
}
