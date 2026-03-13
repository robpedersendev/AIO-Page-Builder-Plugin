<?php
/**
 * Minimal contract for listing Build Plans (spec §30; Prompt 129). Used by analytics to read plan history.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Analytics;

defined( 'ABSPATH' ) || exit;

/**
 * Provides plan records for analytics aggregation. Build_Plan_Repository implements this.
 */
interface Build_Plan_List_Provider_Interface {

	/**
	 * Lists plans by most recent first. Each record should include post_date and plan definition (status, steps, items).
	 *
	 * @param int $limit  Max items.
	 * @param int $offset Offset for pagination.
	 * @return list<array<string, mixed>>
	 */
	public function list_recent( int $limit = 50, int $offset = 0 ): array;
}
