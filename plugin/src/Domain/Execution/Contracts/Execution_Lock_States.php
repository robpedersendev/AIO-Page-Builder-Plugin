<?php
/**
 * Queue and lock state constants (spec §11.3, §42.6, §42.7; executor-locking-idempotency-contract.md §4).
 *
 * Aligns with aio_job_queue queue_status and lock semantics. No locking logic in this file.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Queue status and lock-related constants. Governed by executor-locking-idempotency-contract.md.
 */
final class Execution_Lock_States {

	// -------------------------------------------------------------------------
	// Queue status (contract §4.1; table column queue_status)
	// -------------------------------------------------------------------------

	public const STATUS_QUEUED     = 'queued';
	public const STATUS_SCHEDULED = 'scheduled';
	public const STATUS_RUNNING   = 'running';
	public const STATUS_RETRYING  = 'retrying';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED    = 'failed';
	public const STATUS_CANCELLED = 'cancelled';
	public const STATUS_DEAD      = 'dead';

	/** @var array<int, string> All queue status values. */
	public const QUEUE_STATUSES = array(
		self::STATUS_QUEUED,
		self::STATUS_SCHEDULED,
		self::STATUS_RUNNING,
		self::STATUS_RETRYING,
		self::STATUS_COMPLETED,
		self::STATUS_FAILED,
		self::STATUS_CANCELLED,
		self::STATUS_DEAD,
	);

	/** Statuses that imply a lock may be held (contract §4.2). */
	public const LOCK_HELD_STATUSES = array(
		self::STATUS_RUNNING,
	);

	/** Statuses that are terminal (no further execution). */
	public const TERMINAL_STATUSES = array(
		self::STATUS_COMPLETED,
		self::STATUS_FAILED,
		self::STATUS_CANCELLED,
		self::STATUS_DEAD,
	);

	/** Statuses that indicate job is in progress (duplicate suppression applies). */
	public const IN_PROGRESS_STATUSES = array(
		self::STATUS_QUEUED,
		self::STATUS_SCHEDULED,
		self::STATUS_RUNNING,
		self::STATUS_RETRYING,
	);

	// -------------------------------------------------------------------------
	// Lock scope key prefixes (contract §3.1)
	// -------------------------------------------------------------------------

	public const SCOPE_PREFIX_JOB        = 'job:';
	public const SCOPE_PREFIX_ACTION     = 'action:';
	public const SCOPE_PREFIX_PLAN       = 'plan:';
	public const SCOPE_PREFIX_PLAN_ITEM  = 'plan_item:';
	public const SCOPE_PREFIX_PAGE       = 'page:';
	public const SCOPE_PREFIX_MENU      = 'menu:';

	// -------------------------------------------------------------------------
	// Stale lock and retry (contract §5.3, §7.2)
	// -------------------------------------------------------------------------

	/** Default maximum run time in seconds before a job is considered stale. */
	public const DEFAULT_MAX_RUN_SECONDS = 600;

	/** Default maximum automatic retry count. */
	public const DEFAULT_MAX_RETRY_COUNT = 3;

	/**
	 * Returns whether the queue status indicates the job is in progress (duplicate suppression).
	 *
	 * @param string $queue_status Value of queue_status column.
	 * @return bool
	 */
	public static function is_in_progress( string $queue_status ): bool {
		return in_array( $queue_status, self::IN_PROGRESS_STATUSES, true );
	}

	/**
	 * Returns whether the queue status is terminal (no further execution).
	 *
	 * @param string $queue_status Value of queue_status column.
	 * @return bool
	 */
	public static function is_terminal( string $queue_status ): bool {
		return in_array( $queue_status, self::TERMINAL_STATUSES, true );
	}

	/**
	 * Returns whether the status typically holds a lock (running).
	 *
	 * @param string $queue_status Value of queue_status column.
	 * @return bool
	 */
	public static function typically_holds_lock( string $queue_status ): bool {
		return in_array( $queue_status, self::LOCK_HELD_STATUSES, true );
	}

	/**
	 * Returns whether the status is valid per contract.
	 *
	 * @param string $queue_status Value of queue_status column.
	 * @return bool
	 */
	public static function is_valid_status( string $queue_status ): bool {
		return in_array( $queue_status, self::QUEUE_STATUSES, true );
	}
}
