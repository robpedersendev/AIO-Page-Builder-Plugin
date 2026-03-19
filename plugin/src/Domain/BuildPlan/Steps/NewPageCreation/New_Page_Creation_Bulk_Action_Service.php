<?php
/**
 * Step 2 bulk and single build-intent state handling (spec §33.5–33.7).
 *
 * Applies individual or bulk approve (build-intent) or deny (reject) to new-page items; persists via repository.
 * Does not execute page creation. Build-all and build-selected set status to APPROVED for later execution.
 * Denied items are set to REJECTED and are excluded from execution and from the unresolved queue.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Single and bulk build-intent (approve) and deny for Step 2 (new_pages). Caller must verify capability and nonce.
 */
final class New_Page_Creation_Bulk_Action_Service {

	/** @var Build_Plan_Repository */
	private $repository;

	public function __construct( Build_Plan_Repository $repository ) {
		$this->repository = $repository;
	}

	/** Step index for new page creation in canonical step order. */
	public const STEP_INDEX_NEW_PAGES = 2;

	/**
	 * Sets build-intent for a single item (approve). No-op if item not found or not pending.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Item id.
	 * @return bool True if updated.
	 */
	public function approve_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending( $plan_post_id, $item_id, Build_Plan_Item_Statuses::APPROVED );
	}

	/**
	 * Denies a single new-page item (sets to rejected). No-op if item not found or not pending.
	 * Denied items are excluded from execution and from the unresolved queue.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Item id.
	 * @return bool True if updated.
	 */
	public function deny_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending( $plan_post_id, $item_id, Build_Plan_Item_Statuses::REJECTED );
	}

	/**
	 * Bulk approve all pending new-page items in Step 2 (Build All Pages, spec §33.6).
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_approve_all_eligible( int $plan_post_id ): int {
		return $this->repository->update_plan_step_items_by_status(
			$plan_post_id,
			self::STEP_INDEX_NEW_PAGES,
			Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Statuses::APPROVED
		);
	}

	/**
	 * Bulk approve selected item IDs in Step 2 (Build Selected Pages, spec §33.7).
	 *
	 * @param int   $plan_post_id Plan post ID.
	 * @param array $item_ids     Item ids to set to approved (only pending are updated).
	 * @return int Number of items updated.
	 */
	public function bulk_approve_selected( int $plan_post_id, array $item_ids ): int {
		return $this->repository->update_plan_items_by_ids(
			$plan_post_id,
			self::STEP_INDEX_NEW_PAGES,
			$item_ids,
			Build_Plan_Item_Statuses::APPROVED,
			Build_Plan_Item_Statuses::PENDING
		);
	}

	/**
	 * Bulk deny all pending new-page items in Step 2 (Deny All). Preserves state in plan record.
	 * Denied items are excluded from execution and from the unresolved queue.
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_deny_all_eligible( int $plan_post_id ): int {
		return $this->repository->update_plan_step_items_by_status(
			$plan_post_id,
			self::STEP_INDEX_NEW_PAGES,
			Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Statuses::REJECTED
		);
	}

	/**
	 * Returns eligibility for bulk actions: count of pending new-page items in Step 2.
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @return array{build_all_eligible: int, build_selected_eligible: int, deny_all_eligible: int}
	 */
	public function get_bulk_eligibility( array $plan_definition ): array {
		$steps = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step  = $steps[ self::STEP_INDEX_NEW_PAGES ] ?? null;
		if ( ! is_array( $step ) ) {
			return array(
				'build_all_eligible'      => 0,
				'build_selected_eligible' => 0,
				'deny_all_eligible'       => 0,
			);
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_NEW_PAGES ) {
			return array(
				'build_all_eligible'      => 0,
				'build_selected_eligible' => 0,
				'deny_all_eligible'       => 0,
			);
		}
		$items   = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) ? $step[ Build_Plan_Item_Schema::KEY_ITEMS ] : array();
		$pending = 0;
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) === Build_Plan_Item_Statuses::PENDING ) {
				++$pending;
			}
		}
		return array(
			'build_all_eligible'      => $pending,
			'build_selected_eligible' => $pending,
			'deny_all_eligible'       => $pending,
		);
	}

	private function set_item_status_if_pending( int $plan_post_id, string $item_id, string $new_status ): bool {
		$definition = $this->repository->get_plan_definition( $plan_post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ self::STEP_INDEX_NEW_PAGES ] ?? null;
		if ( ! is_array( $step ) ) {
			return false;
		}
		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item['item_id'] ?? '' ) === $item_id && (string) ( $item['status'] ?? '' ) === Build_Plan_Item_Statuses::PENDING ) {
				return $this->repository->update_plan_item_status( $plan_post_id, self::STEP_INDEX_NEW_PAGES, $item_id, $new_status );
			}
		}
		return false;
	}
}
