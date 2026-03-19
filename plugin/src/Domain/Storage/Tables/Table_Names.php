<?php
/**
 * Stable logical table suffixes for custom tables (spec §11, custom-table-manifest.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Physical table name is {wpdb->prefix}{SUFFIX}. Do not rename suffixes; they are the manifest contract.
 */
final class Table_Names {

	/** §11.1 Crawl snapshots. */
	public const CRAWL_SNAPSHOTS = 'aio_crawl_snapshots';

	/** §11.2 AI artifacts. */
	public const AI_ARTIFACTS = 'aio_ai_artifacts';

	/** §11.3 Job queue. */
	public const JOB_QUEUE = 'aio_job_queue';

	/** §11.4 Execution log. */
	public const EXECUTION_LOG = 'aio_execution_log';

	/** §11.5 Rollback records. */
	public const ROLLBACK_RECORDS = 'aio_rollback_records';

	/** §11.6 Token sets. */
	public const TOKEN_SETS = 'aio_token_sets';

	/** §11.7 Assignment maps. */
	public const ASSIGNMENT_MAPS = 'aio_assignment_maps';

	/** §11.8 Reporting records. */
	public const REPORTING_RECORDS = 'aio_reporting_records';

	/** @var array<int, string>|null */
	private static ?array $all = null;

	/**
	 * Returns all table suffixes in stable order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		if ( self::$all !== null ) {
			return self::$all;
		}
		self::$all = array(
			self::CRAWL_SNAPSHOTS,
			self::AI_ARTIFACTS,
			self::JOB_QUEUE,
			self::EXECUTION_LOG,
			self::ROLLBACK_RECORDS,
			self::TOKEN_SETS,
			self::ASSIGNMENT_MAPS,
			self::REPORTING_RECORDS,
		);
		return self::$all;
	}

	/**
	 * Returns full table name with prefix.
	 *
	 * @param \wpdb|object $wpdb WordPress database abstraction (must have property prefix).
	 * @param string       $suffix One of the constants.
	 * @return string
	 */
	public static function full_name( $wpdb, string $suffix ): string {
		return $wpdb->prefix . $suffix;
	}
}
