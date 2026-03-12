<?php
/**
 * Persistence contract for operational snapshots (spec §41.2, §41.3, §11.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

/**
 * Save and retrieve operational snapshot records by snapshot_id.
 */
interface Operational_Snapshot_Repository_Interface {

	/**
	 * Saves a full snapshot record. Overwrites if snapshot_id exists.
	 *
	 * @param array<string, mixed> $snapshot Full snapshot root (operational-snapshot-schema.md).
	 * @return bool True if saved; false on failure.
	 */
	public function save( array $snapshot ): bool;

	/**
	 * Retrieves a snapshot by snapshot_id.
	 *
	 * @param string $snapshot_id Snapshot ID.
	 * @return array<string, mixed>|null Full snapshot or null if not found.
	 */
	public function get_by_id( string $snapshot_id ): ?array;
}
