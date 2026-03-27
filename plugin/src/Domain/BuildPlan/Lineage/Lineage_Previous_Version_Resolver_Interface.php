<?php
/**
 * Resolves the prior plan post in a lineage for cross-version comparisons.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Lineage;

defined( 'ABSPATH' ) || exit;

/**
 * Implemented by {@see Build_Plan_Lineage_Service}.
 */
interface Lineage_Previous_Version_Resolver_Interface {

	/**
	 * Post ID of the plan with version_seq = current_version_seq - 1, or null if missing.
	 */
	public function get_previous_version_post_id( string $lineage_id, int $current_version_seq ): ?int;
}
