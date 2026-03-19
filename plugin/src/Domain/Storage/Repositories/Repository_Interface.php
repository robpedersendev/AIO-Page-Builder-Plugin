<?php
/**
 * Shared repository contract (spec §5.2, §9, §10). Data access boundary only; no business policy.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Repositories are data access boundaries. Callers must be already authorized.
 * Method naming reflects object schemas and status families; no ad hoc persistence.
 */
interface Repository_Interface {

	/**
	 * Returns a single record by numeric ID (post ID or table primary key).
	 *
	 * @param int $id Post ID or table row id.
	 * @return array<string, mixed>|null Decoded object/row or null if not found.
	 */
	public function get_by_id( int $id ): ?array;

	/**
	 * Returns a single record by stable internal key (slug, plan_id, run_id, etc.).
	 *
	 * @param string $key Internal key (immutable identifier per object schema).
	 * @return array<string, mixed>|null Decoded object/row or null if not found.
	 */
	public function get_by_key( string $key ): ?array;

	/**
	 * Lists records filtered by object status. Status must be in the object's status family.
	 *
	 * @param string $status Status slug (e.g. active, draft, pending_review).
	 * @param int    $limit  Max items to return (0 = use default).
	 * @param int    $offset Offset for pagination.
	 * @return array<int, array<string, mixed>> List of decoded objects/rows.
	 */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array;

	/**
	 * Persists or updates a record. Does not perform permission checks.
	 *
	 * @param array<string, mixed> $data Key-value data; must include identifier for update.
	 * @return int Saved post ID or table row id; 0 on failure.
	 */
	public function save( array $data ): int;

	/**
	 * Returns whether a record exists by internal key or numeric ID.
	 *
	 * @param string|int $key_or_id Internal key (string) or post/row id (int).
	 * @return bool
	 */
	public function exists( $key_or_id ): bool;
}
