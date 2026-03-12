<?php
/**
 * Plan state access for single-action executor (spec §40.2; Prompt 079).
 *
 * Allows lookup of plan by id, plan definition, step index for item, and item status updates.
 * Build_Plan_Repository implements this; tests may use a stub.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal contract for executor to resolve plan and update item status.
 */
interface Plan_State_For_Execution_Interface {

	/**
	 * Gets plan record by plan_id (internal_key).
	 *
	 * @param string $plan_id Plan ID.
	 * @return array<string, mixed>|null Record with id, plan_definition, etc., or null.
	 */
	public function get_by_key( string $plan_id ): ?array;

	/**
	 * Gets full plan definition for a plan post.
	 *
	 * @param int $post_id Plan post ID.
	 * @return array<string, mixed> Plan definition (steps, etc.).
	 */
	public function get_plan_definition( int $post_id ): array;

	/**
	 * Finds step index containing the given plan item id.
	 *
	 * @param array<string, mixed> $definition Plan definition.
	 * @param string               $plan_item_id Item id.
	 * @return int|null Step index (0-based) or null.
	 */
	public function find_step_index_for_item( array $definition, string $plan_item_id ): ?int;

	/**
	 * Updates a single plan item's status.
	 *
	 * @param int    $post_id    Plan post ID.
	 * @param int    $step_index Step index.
	 * @param string $item_id    Item id.
	 * @param string $new_status New status.
	 * @return bool Success.
	 */
	public function update_plan_item_status( int $post_id, int $step_index, string $item_id, string $new_status ): bool;
}
