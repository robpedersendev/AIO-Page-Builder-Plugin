<?php
/**
 * Step 2 bulk and single build-intent state handling (spec §33.5–33.7).
 *
 * Applies individual or bulk approve (build-intent) or deny (reject) to new-page items; persists via repository.
 * Does not execute page creation. Build-all and build-selected set status to APPROVED for later execution.
 * Denied items are set to REJECTED and are excluded from execution and from the unresolved queue.
 *
 * Implementation brief (master spec, docs/specs/aio-page-builder-master-spec.md):
 * - §30.4–30.5: Plan status model and step-based UI (new page creation is a core step).
 * - §30.7: Remaining-changes logic must include denied actions and unresolved filtering.
 * - §30.8–30.9: Resume preserves decisions; bulk actions require clear scope (see §32.8 mutual exclusivity on other steps).
 * - §32.5–32.7: Existing-page step analog — per-item deny, bulk deny all; logging/history expectations (§32.5) apply to review actions.
 * - §33.1–33.7: New page step — list/metadata (§33.2–33.4), Build action (§33.5), Build All (§33.6), Build Selected (§33.7), dependencies (§33.8), post-build status (§33.9–33.10).
 * Shipped wiring: {@see New_Page_Creation_Bulk_Action_Service}, admin {@see \AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen::maybe_handle_step2_action()},
 * persistence {@see \AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository}.
 * Current transitions (pending → approved|rejected): per-row approve/deny; bulk approve all/selected; bulk deny all; bulk deny selected.
 * Gap addressed vs prior backlog: deny-selected bulk path aligned with §33.7 phased selection. Item-level status history beyond final `status` field is not yet modeled (§33.9 / §32.5 audit trail — future work).
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

	/** Minimum confidence for item to be eligible for bulk actions (matches UI filter). */
	private const MIN_CONFIDENCE_EXCLUDE = 'low';

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
		return $this->bulk_set_all_eligible_status( $plan_post_id, Build_Plan_Item_Statuses::APPROVED );
	}

	/**
	 * Bulk approve selected item IDs in Step 2 (Build Selected Pages, spec §33.7).
	 *
	 * @param int   $plan_post_id Plan post ID.
	 * @param array $item_ids     Item ids to set to approved (only pending are updated).
	 * @return int Number of items updated.
	 */
	public function bulk_approve_selected( int $plan_post_id, array $item_ids ): int {
		if ( empty( $item_ids ) ) {
			return 0;
		}
		$item_id_set = array_fill_keys( array_map( 'strval', $item_ids ), true );
		return $this->bulk_set_selected_eligible_status( $plan_post_id, $item_id_set, Build_Plan_Item_Statuses::APPROVED );
	}

	/**
	 * Bulk deny selected pending new-page items in Step 2 (spec §33.7 symmetry with Build Selected Pages).
	 *
	 * @param int   $plan_post_id Plan post ID.
	 * @param array $item_ids     Item ids to set to rejected (only pending eligible rows are updated).
	 * @return int Number of items updated.
	 */
	public function bulk_deny_selected( int $plan_post_id, array $item_ids ): int {
		if ( empty( $item_ids ) ) {
			return 0;
		}
		$item_id_set = array_fill_keys( array_map( 'strval', $item_ids ), true );
		return $this->bulk_set_selected_eligible_status( $plan_post_id, $item_id_set, Build_Plan_Item_Statuses::REJECTED );
	}

	/**
	 * Bulk deny all pending new-page items in Step 2 (Deny All). Preserves state in plan record.
	 * Denied items are excluded from execution and from the unresolved queue.
	 *
	 * @param int $plan_post_id Plan post ID.
	 * @return int Number of items updated.
	 */
	public function bulk_deny_all_eligible( int $plan_post_id ): int {
		return $this->bulk_set_all_eligible_status( $plan_post_id, Build_Plan_Item_Statuses::REJECTED );
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
			$payload    = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$confidence = (string) ( $payload['confidence'] ?? 'medium' );
			if ( $confidence === self::MIN_CONFIDENCE_EXCLUDE ) {
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
			$item_id_value = (string) ( $item['item_id'] ?? '' );
			if ( $item_id_value !== $item_id ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
				continue;
			}
			$payload    = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$confidence = (string) ( $payload['confidence'] ?? 'medium' );
			if ( $confidence === self::MIN_CONFIDENCE_EXCLUDE ) {
				return false;
			}
			return $this->repository->update_plan_item_status( $plan_post_id, self::STEP_INDEX_NEW_PAGES, $item_id, $new_status );
		}
		return false;
	}

	/**
	 * Bulk-set status for all eligible (pending + non-low confidence) items in Step 2.
	 *
	 * @param int    $plan_post_id Plan post ID.
	 * @param string $new_status   New status (approved or rejected).
	 * @return int Number of items updated.
	 */
	private function bulk_set_all_eligible_status( int $plan_post_id, string $new_status ): int {
		$definition = $this->repository->get_plan_definition( $plan_post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ self::STEP_INDEX_NEW_PAGES ] ?? null;
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
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			$payload    = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$confidence = (string) ( $payload['confidence'] ?? 'medium' );
			if ( $confidence === self::MIN_CONFIDENCE_EXCLUDE ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_NEW_PAGES ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->repository->save_plan_definition( $plan_post_id, $definition );
		}
		return $count;
	}

	/**
	 * Bulk-set status for a selected set of eligible pending items.
	 *
	 * @param int                $plan_post_id Plan post ID.
	 * @param array<string,bool> $item_id_set Item IDs to consider.
	 * @param string             $new_status  New status to set.
	 * @return int Number of items updated.
	 */
	private function bulk_set_selected_eligible_status( int $plan_post_id, array $item_id_set, string $new_status ): int {
		$definition = $this->repository->get_plan_definition( $plan_post_id );
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ self::STEP_INDEX_NEW_PAGES ] ?? null;
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
			$id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $id === '' || ! isset( $item_id_set[ $id ] ) ) {
				continue;
			}
			if ( (string) ( $item['status'] ?? '' ) !== Build_Plan_Item_Statuses::PENDING ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
				continue;
			}
			$payload    = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$confidence = (string) ( $payload['confidence'] ?? 'medium' );
			if ( $confidence === self::MIN_CONFIDENCE_EXCLUDE ) {
				continue;
			}
			$items[ $i ]['status'] = $new_status;
			++$count;
		}
		if ( $count > 0 ) {
			$definition[ Build_Plan_Schema::KEY_STEPS ][ self::STEP_INDEX_NEW_PAGES ][ Build_Plan_Item_Schema::KEY_ITEMS ] = $items;
			$this->repository->save_plan_definition( $plan_post_id, $definition );
		}
		return $count;
	}
}
