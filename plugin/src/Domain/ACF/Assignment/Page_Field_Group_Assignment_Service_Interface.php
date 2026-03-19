<?php
/**
 * Contract for page-to-field-group assignment (spec §20.10–20.12).
 * Allows regeneration/repair and tests to depend on a stable interface.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Assignment;

defined( 'ABSPATH' ) || exit;

/**
 * Reassigns ACF field groups for a page from stored template/composition source.
 */
interface Page_Field_Group_Assignment_Service_Interface {

	/**
	 * Rebuilds field-group assignments for a page from its stored template or composition.
	 *
	 * @param int $page_id Post ID of the page.
	 * @return array{assigned: array<int, string>, removed: array<int, string>, errors: array<int, string>} Result summary.
	 */
	public function reassign_from_stored_source( int $page_id ): array;
}
