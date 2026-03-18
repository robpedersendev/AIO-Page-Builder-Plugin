<?php
/**
 * Step 3 (navigation) bulk and single approve/deny state handling (spec §34.8–34.9).
 *
 * Applies individual or bulk approve/deny to menu_change items; persists via repository.
 * Does not apply menu or theme-location changes. Caller must verify capability and nonce.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Navigation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Single and bulk approve/deny for Step 3 (navigation). Review-state only.
 */
final class Navigation_Bulk_Action_Service {

	/** @var Build_Plan_Repository */
	private $repository;

	public function __construct( Build_Plan_Repository $repository ) {
		$this->repository = $repository;
	}

	/** Step index for navigation in canonical step order (overview=0, existing=1, new_pages=2, hierarchy=3, navigation=4). */
	public const STEP_INDEX_NAVIGATION = 4;

	/**
	 * Approves a single item. No-op if item not found or not pending.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Item id.
	 * @return bool True if updated.
	 */
	public function approve_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending( $plan_post_id, $item_id, Build_Plan_Item_Statuses::APPROVED );
	}

	/**
	 * Denies a single item. No-op if item not found or not pending.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Item id.
	 * @return bool True if updated.
	 */
	public function deny_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending( $plan_post_id, $item_id, Build_Plan_Item_Statuses::REJECTED );
	}

	/**
	 * Bulk approve all pending menu_change items in Step 3 (spec §34.9).
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_approve_all_eligible( int $plan_post_id ): int {
		return $this->repository->update_plan_step_items_by_status(
			$plan_post_id,
			self::STEP_INDEX_NAVIGATION,
			Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Statuses::APPROVED
		);
	}

	/**
	 * Bulk deny all pending menu_change items in Step 3.
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_deny_all_eligible( int $plan_post_id ): int {
		return $this->repository->update_plan_step_items_by_status(
			$plan_post_id,
			self::STEP_INDEX_NAVIGATION,
			Build_Plan_Item_Statuses::PENDING,
			Build_Plan_Item_Statuses::REJECTED
		);
	}

	/**
	 * Returns eligibility for bulk actions: count of pending menu_change items in Step 3.
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @return array{approve_all_eligible: int, deny_all_eligible: int}
	 */
	public function get_bulk_eligibility( array $plan_definition ): array {
		$steps = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step  = $steps[ self::STEP_INDEX_NAVIGATION ] ?? null;
		if ( ! is_array( $step ) ) {
			return array(
				'approve_all_eligible' => 0,
				'deny_all_eligible'    => 0,
			);
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_NAVIGATION ) {
			return array(
				'approve_all_eligible' => 0,
				'deny_all_eligible'    => 0,
			);
		}
		$items   = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) ? $step[ Build_Plan_Item_Schema::KEY_ITEMS ] : array();
		$pending = 0;
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) === Build_Plan_Item_Statuses::PENDING ) {
				++$pending;
			}
		}
		return array(
			'approve_all_eligible' => $pending,
			'deny_all_eligible'    => $pending,
		);
	}

	private function set_item_status_if_pending( int $plan_post_id, string $item_id, string $new_status ): bool {
		$definition = $this->repository->get_plan_definition( $plan_post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ self::STEP_INDEX_NAVIGATION ] ?? null;
		if ( ! is_array( $step ) ) {
			return false;
		}
		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item['item_id'] ?? '' ) === $item_id && (string) ( $item['status'] ?? '' ) === Build_Plan_Item_Statuses::PENDING ) {
				$items[ $i ]['status'] = $new_status;
				$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_NAVIGATION ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
				return $this->repository->save_plan_definition( $plan_post_id, $definition );
			}
		}
		return false;
	}
}
