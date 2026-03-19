<?php
/**
 * Build Plan Step 5 (SEO, meta, media) approve/deny state handling (spec §36; build-plan-state-machine.md).
 *
 * Mutates Build Plan item statuses only. It does not execute SEO/meta writes in v1.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\SEO;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;

/**
 * Single and bulk approve/deny for Step 5 SEO items.
 *
 * Caller must verify capability and nonce at the controller/screen layer.
 */
final class SEO_Bulk_Action_Service {

	/** Step index for SEO in canonical step order. */
	public const STEP_INDEX_SEO = 6;

	/** @var Build_Plan_Repository */
	private $repository;

	public function __construct( Build_Plan_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Approves a single SEO item only when it is currently pending.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Plan item id.
	 * @return bool True if updated.
	 */
	public function approve_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending_and_type(
			$plan_post_id,
			$item_id,
			Build_Plan_Item_Statuses::APPROVED
		);
	}

	/**
	 * Denies a single SEO item only when it is currently pending.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $item_id      Plan item id.
	 * @return bool True if updated.
	 */
	public function deny_item( int $plan_post_id, string $item_id ): bool {
		return $this->set_item_status_if_pending_and_type(
			$plan_post_id,
			$item_id,
			Build_Plan_Item_Statuses::REJECTED
		);
	}

	/**
	 * Bulk approves all eligible pending SEO items in Step 5.
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_approve_all_eligible( int $plan_post_id ): int {
		return $this->bulk_set_status_if_pending_and_type(
			$plan_post_id,
			Build_Plan_Item_Statuses::APPROVED
		);
	}

	/**
	 * Bulk denies all eligible pending SEO items in Step 5.
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_deny_all_eligible( int $plan_post_id ): int {
		return $this->bulk_set_status_if_pending_and_type(
			$plan_post_id,
			Build_Plan_Item_Statuses::REJECTED
		);
	}

	/**
	 * Bulk approves selected pending SEO items in Step 5.
	 *
	 * @param int                 $plan_post_id Plan post ID.
	 * @param array<int, string> $item_ids     Selected item ids.
	 * @return int Number of items updated.
	 */
	public function bulk_approve_selected( int $plan_post_id, array $item_ids ): int {
		$item_ids = array_values( array_filter( array_map( 'strval', $item_ids ) ) );
		if ( empty( $item_ids ) ) {
			return 0;
		}
		return $this->bulk_set_selected_status_if_pending_and_type(
			$plan_post_id,
			$item_ids,
			Build_Plan_Item_Statuses::APPROVED
		);
	}

	/**
	 * @param int    $plan_post_id
	 * @param string $item_id
	 * @param string $new_status
	 * @return bool
	 */
	private function set_item_status_if_pending_and_type( int $plan_post_id, string $item_id, string $new_status ): bool {
		$item_id = trim( $item_id );
		if ( $item_id === '' ) {
			return false;
		}

		$definition = $this->repository->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return false;
		}

		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step  = $steps[ self::STEP_INDEX_SEO ] ?? null;
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
			$is_target = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $item_id;
			if ( ! $is_target ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$status    = (string) ( $item['status'] ?? '' );
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_SEO || $status !== Build_Plan_Item_Statuses::PENDING ) {
				return false;
			}

			$items[ $i ]['status'] = $new_status;
			$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_SEO ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

			$this->audit(
				$plan_post_id,
				'seo_single_review',
				array(
					'item_id'    => $item_id,
					'new_status' => $new_status,
				),
				$saved
			);

			return $saved;
		}

		return false;
	}

	/**
	 * Bulk sets status for all eligible pending items (for SEO step only).
	 *
	 * @param int    $plan_post_id
	 * @param string $new_status
	 * @return int
	 */
	private function bulk_set_status_if_pending_and_type( int $plan_post_id, string $new_status ): int {
		$definition = $this->repository->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return 0;
		}

		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step  = $steps[ self::STEP_INDEX_SEO ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}

		$items  = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$count  = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$status    = (string) ( $item['status'] ?? '' );
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_SEO || $status !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}

		if ( $count <= 0 ) {
			return 0;
		}

		$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_SEO ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

		$this->audit(
			$plan_post_id,
			'seo_bulk_review',
			array(
				'new_status' => $new_status,
				'updated'    => $count,
			),
			$saved
		);

		return $saved ? $count : 0;
	}

	/**
	 * Bulk sets status for selected eligible pending items only.
	 *
	 * @param int                 $plan_post_id Plan post ID.
	 * @param array<int, string> $item_ids     Selected item ids.
	 * @param string             $new_status
	 * @return int
	 */
	private function bulk_set_selected_status_if_pending_and_type( int $plan_post_id, array $item_ids, string $new_status ): int {
		$item_set = array_flip( array_map( 'strval', $item_ids ) );

		$definition = $this->repository->get_plan_definition( $plan_post_id );
		if ( empty( $definition ) ) {
			return 0;
		}

		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step  = $steps[ self::STEP_INDEX_SEO ] ?? null;
		if ( ! is_array( $step ) ) {
			return 0;
		}

		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();

		$count = 0;
		foreach ( $items as $i => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $item_id === '' || ! isset( $item_set[ $item_id ] ) ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$status    = (string) ( $item['status'] ?? '' );
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_SEO || $status !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}

		if ( $count <= 0 ) {
			return 0;
		}

		$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_SEO ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

		$this->audit(
			$plan_post_id,
			'seo_bulk_selected_review',
			array(
				'new_status' => $new_status,
				'updated'    => $count,
			),
			$saved
		);

		return $saved ? $count : 0;
	}

	/**
	 * Minimal structured audit; safe for logs (no secrets).
	 *
	 * @param int                   $plan_post_id
	 * @param string                $event_key
	 * @param array<string, mixed> $details
	 * @param bool                  $success
	 * @return void
	 */
	private function audit( int $plan_post_id, string $event_key, array $details, bool $success ): void {
		$entry = array(
			'event'      => $event_key,
			'plan_post'  => $plan_post_id,
			'success'    => $success,
			'details'    => $details,
			'actor'      => 'admin',
			'timestamp'  => gmdate( 'c' ),
		);
		\error_log( '[AIO Page Builder] ' . \wp_json_encode( $entry ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}

