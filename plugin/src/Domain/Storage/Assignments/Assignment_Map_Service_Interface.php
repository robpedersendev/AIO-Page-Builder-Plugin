<?php
/**
 * Contract for assignment map query (spec §11.7).
 * Allows regeneration/repair and tests to depend on a stable interface.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Assignments;

defined( 'ABSPATH' ) || exit;

/**
 * Lists assignment rows by map type for repair and diagnostics.
 */
interface Assignment_Map_Service_Interface {

	/**
	 * Returns assignment rows for the given map type.
	 *
	 * @param string $map_type One of Assignment_Types constants.
	 * @param int    $limit    Max rows; 0 means default.
	 * @param int    $offset   Offset for pagination.
	 * @return list<array<string, mixed>> Rows with source_ref, target_ref, etc.
	 */
	public function list_by_type( string $map_type, int $limit = 0, int $offset = 0 ): array;
}
