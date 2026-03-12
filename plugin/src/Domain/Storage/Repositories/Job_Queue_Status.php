<?php
/**
 * Queue status values (spec §42.6). Used by Job_Queue_Repository and Execution_Job_Dispatcher.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

/** Queue status values (spec §42.6). */
final class Job_Queue_Status {
	public const PENDING   = 'pending';
	public const RUNNING   = 'running';
	public const RETRYING  = 'retrying';
	public const COMPLETED = 'completed';
	public const FAILED    = 'failed';
	public const CANCELLED = 'cancelled';
}
