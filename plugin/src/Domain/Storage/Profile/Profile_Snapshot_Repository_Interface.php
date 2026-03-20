<?php
/**
 * Contract for profile snapshot persistence (v2-scope-backlog.md §3). Allows test doubles without requiring wpdb.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Minimum CRUD contract for profile snapshot storage.
 */
interface Profile_Snapshot_Repository_Interface {

	/**
	 * Persists a snapshot. Returns true on insert, false when the ID already exists or insert fails.
	 *
	 * @param Profile_Snapshot_Data $snapshot
	 * @return bool
	 */
	public function save( Profile_Snapshot_Data $snapshot ): bool;

	/**
	 * Returns a snapshot by its string ID, or null when not found.
	 *
	 * @param string $snapshot_id
	 * @return Profile_Snapshot_Data|null
	 */
	public function get_by_id( string $snapshot_id ): ?Profile_Snapshot_Data;

	/**
	 * Deletes a snapshot by its ID. Returns true when a row was removed.
	 *
	 * @param string $snapshot_id
	 * @return bool
	 */
	public function delete( string $snapshot_id ): bool;

	/**
	 * Returns all snapshots ordered newest-first. Limit 0 means no limit.
	 *
	 * @param int $limit
	 * @return array<int, Profile_Snapshot_Data>
	 */
	public function get_all( int $limit = 0 ): array;
}
