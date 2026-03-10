<?php
/**
 * Data access for job queue records (spec §11.3). Backing: table aio_job_queue.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Tables\Table_Names;

/**
 * Repository → storage: Table_Names::JOB_QUEUE (custom table).
 * Key column: job_ref. Skeleton for future queue polling; no insert/update in this prompt.
 */
final class Job_Queue_Repository extends Abstract_Table_Repository {

	/** @inheritdoc */
	protected function get_table_suffix(): string {
		return Table_Names::JOB_QUEUE;
	}

	/** @inheritdoc */
	protected function get_key_column(): string {
		return 'job_ref';
	}
}
