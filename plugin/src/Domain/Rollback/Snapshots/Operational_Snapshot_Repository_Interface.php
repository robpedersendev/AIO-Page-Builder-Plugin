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

	/**
	 * Returns snapshot_id => created_at (unix timestamp) for all snapshots with the given target_ref.
	 * Used to detect newer-change conflicts (spec §38.4, §41.9).
	 *
	 * @param string $target_ref Target ref (e.g. post_id, menu term_id, token_set id).
	 * @return array<string, int> Map of snapshot_id to created_at timestamp.
	 */
	public function list_snapshot_created_times_for_target( string $target_ref ): array;

	/**
	 * Returns rollback-capable history entries for a build plan (v1: page replacement + token only).
	 * Each entry has post_snapshot_id, pre_snapshot_id, action_type, target_ref, created_at.
	 *
	 * @param string $plan_id Build plan internal key.
	 * @return array<int, array{post_snapshot_id: string, pre_snapshot_id: string, action_type: string, target_ref: string, created_at: string}>
	 */
	public function list_rollback_entries_for_plan( string $plan_id ): array;
}
