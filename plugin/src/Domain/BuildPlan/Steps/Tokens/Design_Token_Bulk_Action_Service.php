<?php
/**
 * Build Plan Step 4 (design tokens) approve/deny state handling (spec §35; build-plan-state-machine.md).
 *
 * Mutates Build Plan item statuses only. It does not execute token application; execution is handled
 * by the execution queue for approved items (Execution_Action_Types::APPLY_TOKEN_SET).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Single and bulk approve/deny for Step 4 (design tokens).
 *
 * Caller must verify capability and nonce at the controller/screen layer.
 */
final class Design_Token_Bulk_Action_Service {

	/** Step index for design tokens in canonical step order (spec §35). */
	public const STEP_INDEX_DESIGN_TOKENS = 5;

	/** @var Build_Plan_Repository */
	private $repository;

	public function __construct( Build_Plan_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Approves a single design-token item only when it is currently pending.
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
	 * Denies a single design-token item only when it is currently pending.
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
	 * Bulk approves all eligible pending design-token items in Step 4.
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
	 * Bulk denies all eligible pending design-token items in Step 4.
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
	 * Bulk approves selected pending design-token items in Step 4.
	 *
	 * @param int                $plan_post_id Plan post ID.
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
		$step  = $steps[ self::STEP_INDEX_DESIGN_TOKENS ] ?? null;
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
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN || $status !== Build_Plan_Item_Statuses::PENDING ) {
				return false;
			}

			$items[ $i ]['status'] = $new_status;
			$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

			$this->audit(
				$plan_post_id,
				'design_token_single_review',
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
	 * Bulk sets status for all eligible pending items (for design-token step only).
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
		$step  = $steps[ self::STEP_INDEX_DESIGN_TOKENS ] ?? null;
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
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$status    = (string) ( $item['status'] ?? '' );
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN || $status !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}

		if ( $count <= 0 ) {
			return 0;
		}

		$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

		$this->audit(
			$plan_post_id,
			'design_token_bulk_review',
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
	 * @param int                $plan_post_id Plan post ID.
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
		$step  = $steps[ self::STEP_INDEX_DESIGN_TOKENS ] ?? null;
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
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN || $status !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}

		if ( $count <= 0 ) {
			return 0;
		}

		$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_DESIGN_TOKENS ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
		$saved = $this->repository->save_plan_definition( $plan_post_id, $definition );

		$this->audit(
			$plan_post_id,
			'design_token_bulk_selected_review',
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
	 * @param int                  $plan_post_id
	 * @param string               $event_key
	 * @param array<string, mixed> $details
	 * @param bool                 $success
	 * @return void
	 */
	private function audit( int $plan_post_id, string $event_key, array $details, bool $success ): void {
		$entry = array(
			'event'     => $event_key,
			'plan_post' => $plan_post_id,
			'success'   => $success,
			'details'   => $details,
			'actor'     => 'admin',
			'timestamp' => gmdate( 'c' ),
		);
		$line  = \wp_json_encode( $entry );
		Named_Debug_Log::event( Named_Debug_Log_Event::BUILD_PLAN_DESIGN_TOKEN_BULK_DEBUG, false !== $line ? $line : 'json_encode_failed' );
	}
}
