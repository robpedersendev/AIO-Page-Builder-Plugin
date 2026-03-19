<?php
/**
 * Observational analytics over Build Plans: review trends, common blockers, execution failures, rollback frequency (spec §30, §45, §49.11, §59.12; Prompt 129).
 * Read-only; uses plan history as authority. No mutation of plans or execution behavior.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Analytics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Statuses;

/**
 * Aggregates plan and item-level data into stable analytics payloads. Redacted; no raw secrets.
 */
final class Build_Plan_Analytics_Service {

	/** Max plans to load for aggregation (bounded). */
	private const MAX_PLANS = 500;

	/** @var Build_Plan_List_Provider_Interface */
	private $plan_list_provider;

	public function __construct( Build_Plan_List_Provider_Interface $plan_list_provider ) {
		$this->plan_list_provider = $plan_list_provider;
	}

	/**
	 * Returns plans in optional date range. Uses post_date when present.
	 *
	 * @param string|null $date_from Y-m-d or empty.
	 * @param string|null $date_to   Y-m-d or empty.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_plans_for_period( ?string $date_from = null, ?string $date_to = null ): array {
		$all = $this->plan_list_provider->list_recent( self::MAX_PLANS, 0 );
		if ( $date_from === null && $date_to === null ) {
			return $all;
		}
		$from_ts = $date_from !== null && $date_from !== '' ? strtotime( $date_from . ' 00:00:00' ) : false;
		$to_ts   = $date_to !== null && $date_to !== '' ? strtotime( $date_to . ' 23:59:59' ) : false;
		$out     = array();
		foreach ( $all as $plan ) {
			$post_date = (string) ( $plan['post_date'] ?? '' );
			if ( $post_date === '' ) {
				$out[] = $plan;
				continue;
			}
			$ts = strtotime( $post_date );
			if ( $from_ts !== false && $ts < $from_ts ) {
				continue;
			}
			if ( $to_ts !== false && $ts > $to_ts ) {
				continue;
			}
			$out[] = $plan;
		}
		return $out;
	}

	/**
	 * Plan review trends: root status counts, approval/denial rates (spec §30, §31).
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m-d.
	 * @return array{total_plans: int, by_status: array<string, int>, approval_count: int, rejection_count: int, approval_rate: float, denial_rate: float, date_from: string|null, date_to: string|null}
	 */
	public function get_plan_review_trends( ?string $date_from = null, ?string $date_to = null ): array {
		$plans     = $this->get_plans_for_period( $date_from, $date_to );
		$by_status = array();
		foreach ( Build_Plan_Statuses::ROOT_STATUSES as $s ) {
			$by_status[ $s ] = 0;
		}
		$approval_count  = 0;
		$rejection_count = 0;
		foreach ( $plans as $plan ) {
			$status = (string) ( $plan[ Build_Plan_Schema::KEY_STATUS ] ?? $plan['status'] ?? '' );
			if ( $status !== '' && isset( $by_status[ $status ] ) ) {
				++$by_status[ $status ];
			} else {
				$by_status[ $status ] = ( $by_status[ $status ] ?? 0 ) + 1;
			}
			if ( $status === Build_Plan_Statuses::ROOT_APPROVED ) {
				++$approval_count;
			} elseif ( $status === Build_Plan_Statuses::ROOT_REJECTED ) {
				++$rejection_count;
			}
		}
		$total         = count( $plans );
		$reviewed      = $approval_count + $rejection_count;
		$approval_rate = $reviewed > 0 ? round( $approval_count / $reviewed, 4 ) : 0.0;
		$denial_rate   = $reviewed > 0 ? round( $rejection_count / $reviewed, 4 ) : 0.0;
		return array(
			'total_plans'     => $total,
			'by_status'       => $by_status,
			'approval_count'  => $approval_count,
			'rejection_count' => $rejection_count,
			'approval_rate'   => $approval_rate,
			'denial_rate'     => $denial_rate,
			'date_from'       => $date_from,
			'date_to'         => $date_to,
		);
	}

	/**
	 * Common blockers: item-level rejected/failed counts grouped by item_type (redacted categories only).
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m-d.
	 * @param int         $top_n     Max categories to return (default 10).
	 * @return array{blockers: array<int, array{category: string, count: int}>, total_rejected: int, total_failed: int, date_from: string|null, date_to: string|null}
	 */
	public function get_common_blockers( ?string $date_from = null, ?string $date_to = null, int $top_n = 10 ): array {
		$plans             = $this->get_plans_for_period( $date_from, $date_to );
		$count_by_category = array();
		$total_rejected    = 0;
		$total_failed      = 0;
		foreach ( $plans as $plan ) {
			$steps = isset( $plan[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan[ Build_Plan_Schema::KEY_STEPS ] )
				? $plan[ Build_Plan_Schema::KEY_STEPS ]
				: array();
			foreach ( $steps as $step ) {
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
					$status    = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? 'pending' );
					$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? 'unknown' );
					if ( $item_type === '' ) {
						$item_type = 'unknown';
					}
					if ( $status === Build_Plan_Item_Statuses::REJECTED ) {
						++$total_rejected;
						$count_by_category[ $item_type ] = ( $count_by_category[ $item_type ] ?? 0 ) + 1;
					} elseif ( $status === Build_Plan_Item_Statuses::FAILED ) {
						++$total_failed;
						$count_by_category[ $item_type ] = ( $count_by_category[ $item_type ] ?? 0 ) + 1;
					}
				}
			}
		}
		arsort( $count_by_category, SORT_NUMERIC );
		$blockers = array();
		$n        = 0;
		foreach ( $count_by_category as $category => $count ) {
			if ( $n >= $top_n ) {
				break;
			}
			$blockers[] = array(
				'category' => $category,
				'count'    => (int) $count,
			);
			++$n;
		}
		return array(
			'blockers'       => $blockers,
			'total_rejected' => $total_rejected,
			'total_failed'   => $total_failed,
			'date_from'      => $date_from,
			'date_to'        => $date_to,
		);
	}

	/**
	 * Execution failure trends: item status failed grouped by item_type (and optionally step_type).
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m_d.
	 * @return array{failures_by_item_type: array<string, int>, total_failed_items: int, date_from: string|null, date_to: string|null}
	 */
	public function get_execution_failure_trends( ?string $date_from = null, ?string $date_to = null ): array {
		$plans        = $this->get_plans_for_period( $date_from, $date_to );
		$by_item_type = array();
		$total        = 0;
		foreach ( $plans as $plan ) {
			$steps = isset( $plan[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan[ Build_Plan_Schema::KEY_STEPS ] )
				? $plan[ Build_Plan_Schema::KEY_STEPS ]
				: array();
			foreach ( $steps as $step ) {
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
					$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
					if ( $status !== Build_Plan_Item_Statuses::FAILED ) {
						continue;
					}
					++$total;
					$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? 'unknown' );
					if ( $item_type === '' ) {
						$item_type = 'unknown';
					}
					$by_item_type[ $item_type ] = ( $by_item_type[ $item_type ] ?? 0 ) + 1;
				}
			}
		}
		return array(
			'failures_by_item_type' => $by_item_type,
			'total_failed_items'    => $total,
			'date_from'             => $date_from,
			'date_to'               => $date_to,
		);
	}

	/**
	 * Rollback frequency summary. Stub: uses plan history only; rollback table not queried (spec §59.12).
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m-d.
	 * @return array{total_rollbacks: int, by_month: array<int, array{month: string, count: int}>, date_from: string|null, date_to: string|null, source: string}
	 */
	public function get_rollback_frequency_summary( ?string $date_from = null, ?string $date_to = null ): array {
		return array(
			'total_rollbacks' => 0,
			'by_month'        => array(),
			'date_from'       => $date_from,
			'date_to'         => $date_to,
			'source'          => 'plan_analytics_only',
		);
	}

	/**
	 * Full analytics summary: all trend payloads in one call (for screen).
	 *
	 * @param string|null $date_from Y-m-d.
	 * @param string|null $date_to   Y-m-d.
	 * @return array{plan_review_trends: array, common_blockers: array, execution_failure_trends: array, rollback_frequency_summary: array}
	 */
	public function get_analytics_summary( ?string $date_from = null, ?string $date_to = null ): array {
		return array(
			'plan_review_trends'         => $this->get_plan_review_trends( $date_from, $date_to ),
			'common_blockers'            => $this->get_common_blockers( $date_from, $date_to ),
			'execution_failure_trends'   => $this->get_execution_failure_trends( $date_from, $date_to ),
			'rollback_frequency_summary' => $this->get_rollback_frequency_summary( $date_from, $date_to ),
		);
	}
}
