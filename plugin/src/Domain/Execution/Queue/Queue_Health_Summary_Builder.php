<?php
/**
 * Builds queue health summary for monitoring (spec §42, §49.11, §59.12).
 *
 * Surfaces stale-lock and blocked-job diagnostics, bottleneck summary,
 * long-running job warnings, and retry-eligible counts. Stable payload shape.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Status;

/**
 * Builds queue health summary from job queue repository. No mutation.
 */
final class Queue_Health_Summary_Builder {

	/** Seconds after which running/retrying job is considered stale (spec executor-locking-idempotency). */
	private const STALE_LOCK_SECONDS = 3600;

	/** Pending count above this triggers bottleneck warning. */
	private const BOTTLENECK_PENDING_THRESHOLD = 50;

	/** Max retries for retry-eligibility (spec §42.4). */
	private const MAX_RETRY_COUNT = 5;

	/** @var object|null Repository with list_by_status( string, int, int ): array. */
	private $job_queue_repository;

	public function __construct( ?object $job_queue_repository = null ) {
		$this->job_queue_repository = $job_queue_repository;
	}

	/**
	 * Builds the queue health summary. Stable shape for UI and diagnostics.
	 *
	 * @return array{
	 *   total_pending: int,
	 *   total_running: int,
	 *   total_retrying: int,
	 *   total_failed: int,
	 *   total_completed: int,
	 *   total_cancelled: int,
	 *   stale_lock_count: int,
	 *   stale_lock_job_refs: list<string>,
	 *   long_running_count: int,
	 *   long_running_job_refs: list<string>,
	 *   retry_eligible_count: int,
	 *   retry_eligible_job_refs: list<string>,
	 *   bottleneck_warning: bool,
	 *   summary_message: string
	 * }
	 */
	public function build(): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return $this->empty_summary();
		}

		$statuses = array(
			Job_Queue_Status::PENDING,
			Job_Queue_Status::RUNNING,
			Job_Queue_Status::RETRYING,
			Job_Queue_Status::FAILED,
			Job_Queue_Status::COMPLETED,
			Job_Queue_Status::CANCELLED,
		);
		$counts   = array_fill_keys( $statuses, 0 );
		$stale    = array();
		$long     = array();
		$retry_eligible = array();
		$now_ts   = time();

		foreach ( $statuses as $status ) {
			$rows = $this->job_queue_repository->list_by_status( $status, 500, 0 );
			$counts[ $status ] = count( $rows );
			foreach ( $rows as $row ) {
				$job_ref   = isset( $row['job_ref'] ) && is_string( $row['job_ref'] ) ? trim( $row['job_ref'] ) : '';
				$started   = isset( $row['started_at'] ) && is_string( $row['started_at'] ) ? $row['started_at'] : '';
				$retry_cnt = isset( $row['retry_count'] ) && is_numeric( $row['retry_count'] ) ? (int) $row['retry_count'] : 0;
				$job_type  = isset( $row['job_type'] ) && is_string( $row['job_type'] ) ? $row['job_type'] : '';

				if ( $job_ref === '' ) {
					continue;
				}
				if ( ( $status === Job_Queue_Status::RUNNING || $status === Job_Queue_Status::RETRYING ) && $started !== '' ) {
					$start_ts = strtotime( $started );
					if ( $start_ts !== false && ( $now_ts - $start_ts ) > self::STALE_LOCK_SECONDS ) {
						$stale[] = $job_ref;
						$long[]  = $job_ref;
					} elseif ( $start_ts !== false && ( $now_ts - $start_ts ) > ( self::STALE_LOCK_SECONDS / 2 ) ) {
						$long[] = $job_ref;
					}
				}
				if ( $status === Job_Queue_Status::FAILED && $retry_cnt < self::MAX_RETRY_COUNT && $this->is_retryable_job_type( $job_type ) ) {
					$retry_eligible[] = $job_ref;
				}
			}
		}

		$stale_count = count( array_unique( $stale ) );
		$long_count  = count( array_unique( $long ) );
		$retry_count = count( array_unique( $retry_eligible ) );
		$bottleneck  = $counts[ Job_Queue_Status::PENDING ] >= self::BOTTLENECK_PENDING_THRESHOLD;

		$summary_message = $this->summary_message( $counts, $stale_count, $bottleneck, $retry_count );

		return array(
			'total_pending'           => $counts[ Job_Queue_Status::PENDING ],
			'total_running'           => $counts[ Job_Queue_Status::RUNNING ],
			'total_retrying'          => $counts[ Job_Queue_Status::RETRYING ],
			'total_failed'            => $counts[ Job_Queue_Status::FAILED ],
			'total_completed'         => $counts[ Job_Queue_Status::COMPLETED ],
			'total_cancelled'         => $counts[ Job_Queue_Status::CANCELLED ],
			'stale_lock_count'        => $stale_count,
			'stale_lock_job_refs'     => array_values( array_unique( $stale ) ),
			'long_running_count'      => $long_count,
			'long_running_job_refs'   => array_values( array_unique( $long ) ),
			'retry_eligible_count'    => $retry_count,
			'retry_eligible_job_refs' => array_values( array_unique( $retry_eligible ) ),
			'bottleneck_warning'      => $bottleneck,
			'summary_message'         => $summary_message,
		);
	}

	/**
	 * @return array{total_pending: int, total_running: int, total_retrying: int, total_failed: int, total_completed: int, total_cancelled: int, stale_lock_count: int, stale_lock_job_refs: list<string>, long_running_count: int, long_running_job_refs: list<string>, retry_eligible_count: int, retry_eligible_job_refs: list<string>, bottleneck_warning: bool, summary_message: string}
	 */
	private function empty_summary(): array {
		return array(
			'total_pending'           => 0,
			'total_running'           => 0,
			'total_retrying'          => 0,
			'total_failed'            => 0,
			'total_completed'         => 0,
			'total_cancelled'         => 0,
			'stale_lock_count'        => 0,
			'stale_lock_job_refs'     => array(),
			'long_running_count'      => 0,
			'long_running_job_refs'    => array(),
			'retry_eligible_count'    => 0,
			'retry_eligible_job_refs' => array(),
			'bottleneck_warning'       => false,
			'summary_message'         => __( 'Queue health: no data (repository unavailable).', 'aio-page-builder' ),
		);
	}

	private function is_retryable_job_type( string $job_type ): bool {
		return in_array( $job_type, array(
			Execution_Action_Types::CREATE_PAGE,
			Execution_Action_Types::REPLACE_PAGE,
			Execution_Action_Types::UPDATE_PAGE_METADATA,
			Execution_Action_Types::UPDATE_MENU,
			Execution_Action_Types::APPLY_TOKEN_SET,
			Execution_Action_Types::FINALIZE_PLAN,
			Execution_Action_Types::ROLLBACK_ACTION,
		), true );
	}

	/**
	 * @param array<string, int> $counts
	 */
	private function summary_message( array $counts, int $stale_count, bool $bottleneck, int $retry_eligible_count ): string {
		$parts = array();
		$pending = $counts[ Job_Queue_Status::PENDING ] ?? 0;
		$running = $counts[ Job_Queue_Status::RUNNING ] ?? 0;
		$failed  = $counts[ Job_Queue_Status::FAILED ] ?? 0;
		if ( $stale_count > 0 ) {
			$parts[] = sprintf( __( '%d stale lock(s) detected.', 'aio-page-builder' ), $stale_count );
		}
		if ( $bottleneck ) {
			$parts[] = sprintf( __( 'Bottleneck: %d pending jobs.', 'aio-page-builder' ), $pending );
		}
		if ( $retry_eligible_count > 0 ) {
			$parts[] = sprintf( __( '%d failed job(s) eligible for retry.', 'aio-page-builder' ), $retry_eligible_count );
		}
		if ( empty( $parts ) ) {
			$parts[] = sprintf(
				__( 'Queue: %d pending, %d running, %d failed.', 'aio-page-builder' ),
				$pending,
				$running,
				$failed
			);
		}
		return implode( ' ', $parts );
	}
}
