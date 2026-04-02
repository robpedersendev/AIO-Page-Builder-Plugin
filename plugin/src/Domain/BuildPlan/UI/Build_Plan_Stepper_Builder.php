<?php
/**
 * Builds stepper display data from plan definition (spec §31.3, build-plan-admin-ia-contract.md §4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;

/**
 * Produces step list with number, title, status badge, unresolved count, and blocked state.
 * Does not mutate plan data.
 */
final class Build_Plan_Stepper_Builder {

	/** Step status badge values (IA contract §4). */
	public const BADGE_NOT_STARTED = 'not_started';
	public const BADGE_IN_PROGRESS = 'in_progress';
	public const BADGE_BLOCKED     = 'blocked';
	public const BADGE_COMPLETE    = 'complete';
	public const BADGE_ERROR       = 'error';

	/**
	 * Builds stepper payload for the shell. One entry per step in schema order.
	 *
	 * @param array<string, mixed> $plan_definition Plan root (steps array, plan_id, status).
	 * @return array<int, array<string, mixed>> Each step: step_id, step_type, title, order, step_number, status_badge, unresolved_count, is_blocked.
	 */
	public function build( array $plan_definition ): array {
		$steps_raw           = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$unresolved_by_index = $this->compute_unresolved_counts( $steps_raw );
		$out                 = array();
		foreach ( $steps_raw as $idx => $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$step_type  = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
			$step_id    = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_ID ] ?? '' );
			$title      = (string) ( $step[ Build_Plan_Item_Schema::KEY_TITLE ] ?? $step_type );
			$order      = (int) ( $step[ Build_Plan_Item_Schema::KEY_ORDER ] ?? $idx );
			$unresolved = $unresolved_by_index[ $idx ] ?? 0;
			$is_blocked = $this->is_step_blocked( $idx, $unresolved_by_index );
			$has_error  = $this->step_has_failed_item( $step );
			$badge      = $this->derive_badge( $unresolved, $is_blocked, $has_error, $step_type );
			$out[]      = array(
				'step_id'          => $step_id,
				'step_type'        => $step_type,
				'title'            => $title,
				'order'            => $order,
				'step_number'      => $idx + 1,
				'status_badge'     => $badge,
				'unresolved_count' => $unresolved,
				'is_blocked'       => $is_blocked,
			);
		}
		return $out;
	}

	/**
	 * Computes per-step counts for stepper badges and forward-blocking (pending, in_progress, failed).
	 *
	 * @param array<int, array<string, mixed>> $steps_raw
	 * @return array<int, int>
	 */
	private function compute_unresolved_counts( array $steps_raw ): array {
		$counts = array();
		foreach ( $steps_raw as $idx => $step ) {
			$counts[ $idx ] = 0;
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
				if ( in_array( $item_type, array( Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE, Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE, Build_Plan_Item_Schema::ITEM_TYPE_CONFIRMATION ), true ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
				if ( Build_Plan_Item_Statuses::counts_toward_stepper_unresolved( $status ) ) {
					++$counts[ $idx ];
				}
			}
		}
		return $counts;
	}

	/**
	 * Step is blocked if any earlier step has unresolved items.
	 *
	 * @param int             $current_index
	 * @param array<int, int> $unresolved_by_index
	 * @return bool
	 */
	private function is_step_blocked( int $current_index, array $unresolved_by_index ): bool {
		for ( $i = 0; $i < $current_index; $i++ ) {
			if ( ( $unresolved_by_index[ $i ] ?? 0 ) > 0 ) {
				return true;
			}
		}
		return false;
	}

	private function step_has_failed_item( array $step ): bool {
		$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		foreach ( $items as $item ) {
			if ( is_array( $item ) && ( (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' ) ) === Build_Plan_Item_Statuses::FAILED ) {
				return true;
			}
		}
		return false;
	}

	private function derive_badge( int $unresolved, bool $is_blocked, bool $has_error, string $step_type ): string {
		if ( $has_error ) {
			return self::BADGE_ERROR;
		}
		if ( $is_blocked ) {
			return self::BADGE_BLOCKED;
		}
		if ( $unresolved > 0 ) {
			return self::BADGE_IN_PROGRESS;
		}
		return self::BADGE_COMPLETE;
	}
}
