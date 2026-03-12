<?php
/**
 * Minimal contract for Build Plan read/save used by finalization and tests (spec §37, §40.10).
 *
 * Build_Plan_Repository implements this; tests may use a stub.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Plan lookup and definition persistence for finalization job service.
 */
interface Build_Plan_Repository_Interface {

	/**
	 * Gets plan record by plan_id (internal key).
	 *
	 * @param string $key Plan ID.
	 * @return array<string, mixed>|null Record with 'id', etc., or null.
	 */
	public function get_by_key( string $key ): ?array;

	/**
	 * Gets full plan definition for a plan post.
	 *
	 * @param int $post_id Plan post ID.
	 * @return array<string, mixed> Plan definition (steps, status, etc.).
	 */
	public function get_plan_definition( int $post_id ): array;

	/**
	 * Saves the full plan definition for a plan post.
	 *
	 * @param int                  $post_id    Plan post ID.
	 * @param array<string, mixed> $definition Plan root payload.
	 * @return bool Success.
	 */
	public function save_plan_definition( int $post_id, array $definition ): bool;
}
