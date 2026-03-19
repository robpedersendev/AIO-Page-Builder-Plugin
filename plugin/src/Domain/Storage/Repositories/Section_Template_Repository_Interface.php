<?php
/**
 * Contract for section template definition listing (spec §10).
 * Allows regeneration/repair and tests to depend on a stable interface.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Lists section template definitions for scope resolution in regeneration.
 */
interface Section_Template_Repository_Interface {

	/**
	 * Returns all section template definitions (normalized), optionally paginated.
	 *
	 * @param int $limit  Max items; 0 = default.
	 * @param int $offset Offset for pagination.
	 * @return array<int, array<string, mixed>> List of definitions keyed by internal_key etc.
	 */
	public function list_all_definitions( int $limit = 0, int $offset = 0 ): array;

	/**
	 * Returns all unique internal keys in the registry (for uniqueness checks and inventory).
	 *
	 * @return array<int, string>
	 */
	public function get_all_internal_keys(): array;
}
