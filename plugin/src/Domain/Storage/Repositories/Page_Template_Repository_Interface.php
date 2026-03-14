<?php
/**
 * Contract for page template definition listing (spec §10.2).
 * Allows regeneration/repair and tests to depend on a stable interface.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Lists page template definitions for scope resolution in regeneration.
 */
interface Page_Template_Repository_Interface {

	/**
	 * Returns all page template definitions (normalized), optionally paginated.
	 *
	 * @param int $limit  Max items; 0 = default.
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>> List of definitions with ordered_sections etc.
	 */
	public function list_all_definitions( int $limit = 0, int $offset = 0 ): array;
}
