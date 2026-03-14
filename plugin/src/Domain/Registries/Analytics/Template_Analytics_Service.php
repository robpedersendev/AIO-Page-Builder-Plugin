<?php
/**
 * Observational analytics over template usage, Build Plan recommendation success, execution outcomes, and rollback (spec §49.11, §56.1, §59.12, §59.15, §61.9; Prompt 199).
 * Read-only; uses Build Plan records, job queue, and composition registry as authorities. No mutation of planner or executor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Analytics;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_List_Provider_Interface;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Aggregates template-related signals into stable analytics payloads for the Template Analytics dashboard.
 *
 * Example template analytics summary payload (get_analytics_summary):
 * <code>
 * [
 *   'template_usage_trends' => [
 *     'by_family'   => [ 'landing' => 12, 'blog' => 8 ],
 *     'by_class'    => [ 'marketing' => 10, 'content' => 10 ],
 *     'total_items' => 20,
 *   ],
 *   'recommendation_acceptance' => [
 *     'by_family' => [ 'landing' => [ 'proposed' => 12, 'approved' => 10, 'rejected' => 1, 'failed' => 0, 'completed' => 9 ] ],
 *     'by_class'  => [ 'marketing' => [ 'proposed' => 10, 'approved' => 8, 'rejected' => 1, 'failed' => 0, 'completed' => 7 ] ],
 *   ],
 *   'rejection_reasons' => [
 *     'reasons' => [ [ 'reason' => 'User preferred different layout', 'count' => 3 ], [ 'reason' => 'Timing', 'count' => 1 ] ],
 *     'total'   => 4,
 *   ],
 *   'template_family_outcome_summary' => [
 *     'by_family' => [ 'landing' => [ 'completed' => 9, 'failed' => 0 ], 'blog' => [ 'completed' => 5, 'failed' => 1 ] ],
 *     'total_completed' => 14,
 *     'total_failed'    => 1,
 *   ],
 *   'rollback_frequency' => [ 'total_rollbacks' => 2, 'by_month' => [ '2025-03' => 2 ] ],
 *   'composition_usage' => [ 'by_status' => [ 'draft' => 3, 'published' => 7 ], 'total' => 10 ],
 *   'date_from' => '2025-03-01',
 *   'date_to'   => '2025-03-14',
 * ]
 * </code>
 */
final class Template_Analytics_Service {

	private const MAX_PLANS = 500;
	private const MAX_JOBS   = 500;
	private const MAX_COMPOSITIONS = 200;
	private const TOP_REJECTION_REASONS = 15;

	/** @var Build_Plan_List_Provider_Interface */
	private $plan_list_provider;

	/** @var object|null Job queue repository (list_by_status). */
	private $job_queue_repository;

	/** @var object|null Composition repository (list_all_definitions). */
	private $composition_repository;

	public function __construct(
		Build_Plan_List_Provider_Interface $plan_list_provider,
		$job_queue_repository = null,
		$composition_repository = null
	) {
		$this->plan_list_provider     = $plan_list_provider;
		$this->job_queue_repository   = $job_queue_repository;
		$this->composition_repository = $composition_repository;
	}

	/**
	 * Full template analytics summary for the dashboard (with optional filters).
	 *
	 * @param string|null $date_from     Y-m-d.
	 * @param string|null $date_to       Y-m-d.
	 * @param string|null $template_family Optional filter by template_family.
	 * @param string|null $page_class    Optional filter by template_category_class (page class).
	 * @return array{template_usage_trends: array, recommendation_acceptance: array, rejection_reasons: array, template_family_outcome_summary: array, rollback_frequency: array, composition_usage: array, date_from: string|null, date_to: string|null}
	 */
	public function get_analytics_summary(
		?string $date_from = null,
		?string $date_to = null,
		?string $template_family = null,
		?string $page_class = null
	): array {
		$plans = $this->get_plans_for_period( $date_from, $date_to );
		$item_signals = $this->collect_plan_item_signals( $plans, $template_family, $page_class );

		return array(
			'template_usage_trends'           => $this->build_usage_trends( $item_signals ),
			'recommendation_acceptance'        => $this->build_recommendation_acceptance( $item_signals ),
			'rejection_reasons'               => $this->build_rejection_reasons( $item_signals ),
			'template_family_outcome_summary'  => $this->build_family_outcome_summary( $date_from, $date_to, $template_family ),
			'rollback_frequency'              => $this->build_rollback_frequency( $date_from, $date_to ),
			'composition_usage'               => $this->build_composition_usage(),
			'date_from'                       => $date_from,
			'date_to'                         => $date_to,
		);
	}

	/**
	 * @param string|null $date_from
	 * @param string|null $date_to
	 * @return list<array<string, mixed>>
	 */
	private function get_plans_for_period( ?string $date_from = null, ?string $date_to = null ): array {
		$all = $this->plan_list_provider->list_recent( self::MAX_PLANS, 0 );
		if ( $date_from === null && $date_to === null ) {
			return $all;
		}
		$from_ts = $date_from !== null && $date_from !== '' ? strtotime( $date_from . ' 00:00:00' ) : false;
		$to_ts   = $date_to !== null && $date_to !== '' ? strtotime( $date_to . ' 23:59:59' ) : false;
		$out = array();
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
	 * Collects template-related signals from plan items (new_page, existing_page_change).
	 *
	 * @param list<array<string, mixed>> $plans
	 * @param string|null                $filter_family
	 * @param string|null                $filter_class
	 * @return list<array{template_key: string, template_family: string, template_class: string, status: string, rejection_reason: string}>
	 */
	private function collect_plan_item_signals( array $plans, ?string $filter_family, ?string $filter_class ): array {
		$signals = array();
		$item_types_with_template = array(
			Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
			Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
		);
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
					$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
					if ( ! in_array( $item_type, $item_types_with_template, true ) ) {
						continue;
					}
					$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
						? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
						: array();
					$template_key   = (string) ( $payload['template_key'] ?? $payload['target_template'] ?? '' );
					$summary        = $payload['proposed_template_summary'] ?? $payload['existing_page_template_change_summary'] ?? array();
					$template_family = (string) ( is_array( $summary ) ? ( $summary['template_family'] ?? '' ) : '' );
					$template_class  = (string) ( is_array( $summary ) ? ( $summary['template_category_class'] ?? '' ) : '' );
					if ( $template_class === '' && $template_key !== '' ) {
						$template_class = (string) ( $payload['template_category_class'] ?? $payload['page_class'] ?? '' );
					}
					if ( $filter_family !== null && $filter_family !== '' && $template_family !== $filter_family ) {
						continue;
					}
					if ( $filter_class !== null && $filter_class !== '' && $template_class !== $filter_class ) {
						continue;
					}
					$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? 'pending' );
					$rejection_reason = (string) ( $payload['rejection_reason'] ?? $payload['failure_reason'] ?? $item['failure_reason'] ?? '' );
					if ( $rejection_reason === '' && ( $status === Build_Plan_Item_Statuses::REJECTED || $status === Build_Plan_Item_Statuses::FAILED ) ) {
						$rejection_reason = 'no_reason_recorded';
					}
					$signals[] = array(
						'template_key'      => $template_key,
						'template_family'   => $template_family !== '' ? $template_family : 'unknown',
						'template_class'    => $template_class !== '' ? $template_class : 'unknown',
						'status'            => $status,
						'rejection_reason'  => $rejection_reason,
					);
				}
			}
		}
		return $signals;
	}

	/**
	 * @param list<array{template_key: string, template_family: string, template_class: string, status: string, rejection_reason: string}> $signals
	 * @return array{by_family: array<string, int>, by_class: array<string, int>, total_items: int}
	 */
	private function build_usage_trends( array $signals ): array {
		$by_family = array();
		$by_class  = array();
		foreach ( $signals as $s ) {
			$fam = $s['template_family'];
			$cls = $s['template_class'];
			$by_family[ $fam ] = ( $by_family[ $fam ] ?? 0 ) + 1;
			$by_class[ $cls ]  = ( $by_class[ $cls ] ?? 0 ) + 1;
		}
		arsort( $by_family, SORT_NUMERIC );
		arsort( $by_class, SORT_NUMERIC );
		return array(
			'by_family'    => $by_family,
			'by_class'     => $by_class,
			'total_items'  => count( $signals ),
		);
	}

	/**
	 * @param list<array{template_key: string, template_family: string, template_class: string, status: string, rejection_reason: string}> $signals
	 * @return array{by_family: array<string, array{proposed: int, approved: int, rejected: int, failed: int, completed: int}>, by_class: array<string, array{proposed: int, approved: int, rejected: int, failed: int, completed: int}>}
	 */
	private function build_recommendation_acceptance( array $signals ): array {
		$by_family = array();
		$by_class  = array();
		foreach ( $signals as $s ) {
			$fam = $s['template_family'];
			$cls = $s['template_class'];
			if ( ! isset( $by_family[ $fam ] ) ) {
				$by_family[ $fam ] = array( 'proposed' => 0, 'approved' => 0, 'rejected' => 0, 'failed' => 0, 'completed' => 0 );
			}
			if ( ! isset( $by_class[ $cls ] ) ) {
				$by_class[ $cls ] = array( 'proposed' => 0, 'approved' => 0, 'rejected' => 0, 'failed' => 0, 'completed' => 0 );
			}
			$by_family[ $fam ]['proposed']++;
			$by_class[ $cls ]['proposed']++;
			$status = $s['status'];
			if ( $status === Build_Plan_Item_Statuses::APPROVED ) {
				$by_family[ $fam ]['approved']++;
				$by_class[ $cls ]['approved']++;
			} elseif ( $status === Build_Plan_Item_Statuses::REJECTED ) {
				$by_family[ $fam ]['rejected']++;
				$by_class[ $cls ]['rejected']++;
			} elseif ( $status === Build_Plan_Item_Statuses::FAILED ) {
				$by_family[ $fam ]['failed']++;
				$by_class[ $cls ]['failed']++;
			} elseif ( $status === Build_Plan_Item_Statuses::COMPLETED ) {
				$by_family[ $fam ]['completed']++;
				$by_class[ $cls ]['completed']++;
			}
		}
		return array( 'by_family' => $by_family, 'by_class' => $by_class );
	}

	/**
	 * @param list<array{template_key: string, template_family: string, template_class: string, status: string, rejection_reason: string}> $signals
	 * @return array{reasons: list<array{reason: string, count: int}>, total: int}
	 */
	private function build_rejection_reasons( array $signals ): array {
		$counts = array();
		foreach ( $signals as $s ) {
			if ( $s['rejection_reason'] === '' ) {
				continue;
			}
			$reason = \sanitize_text_field( substr( $s['rejection_reason'], 0, 200 ) );
			if ( $reason === '' ) {
				$reason = 'no_reason_recorded';
			}
			$counts[ $reason ] = ( $counts[ $reason ] ?? 0 ) + 1;
		}
		arsort( $counts, SORT_NUMERIC );
		$reasons = array();
		$n = 0;
		foreach ( $counts as $reason => $count ) {
			if ( $n >= self::TOP_REJECTION_REASONS ) {
				break;
			}
			$reasons[] = array( 'reason' => $reason, 'count' => (int) $count );
			$n++;
		}
		return array(
			'reasons' => $reasons,
			'total'   => array_sum( $counts ),
		);
	}

	/**
	 * Execution outcomes by template family from job queue (create_page, replace_page).
	 *
	 * @param string|null $date_from
	 * @param string|null $date_to
	 * @param string|null $filter_family
	 * @return array{by_family: array<string, array{completed: int, failed: int}>, total_completed: int, total_failed: int}
	 */
	private function build_family_outcome_summary( ?string $date_from, ?string $date_to, ?string $filter_family ): array {
		$by_family = array();
		$total_completed = 0;
		$total_failed    = 0;
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array( 'by_family' => $by_family, 'total_completed' => 0, 'total_failed' => 0 );
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::MAX_JOBS, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::MAX_JOBS, 0 );
		$from_ts = $date_from !== null && $date_from !== '' ? strtotime( $date_from . ' 00:00:00' ) : false;
		$to_ts   = $date_to !== null && $date_to !== '' ? strtotime( $date_to . ' 23:59:59' ) : false;
		foreach ( array_merge( $completed, $failed ) as $row ) {
			$job_type = (string) ( $row['job_type'] ?? '' );
			if ( $job_type !== Execution_Action_Types::CREATE_PAGE && $job_type !== Execution_Action_Types::REPLACE_PAGE ) {
				continue;
			}
			$ts = strtotime( (string) ( $row['completed_at'] ?? $row['created_at'] ?? '' ) );
			if ( $from_ts !== false && $ts < $from_ts ) {
				continue;
			}
			if ( $to_ts !== false && $ts > $to_ts ) {
				continue;
			}
			$related = (string) ( $row['related_object_refs'] ?? '' );
			$family = '';
			if ( $related !== '' ) {
				$decoded = json_decode( $related, true );
				if ( is_array( $decoded ) ) {
					$target = $decoded['target_reference'] ?? $decoded['target'] ?? $decoded;
					if ( is_array( $target ) ) {
						$family = (string) ( $target['template_family'] ?? '' );
					}
				}
			}
			if ( $family === '' ) {
				$family = 'unknown';
			}
			if ( $filter_family !== null && $filter_family !== '' && $family !== $filter_family ) {
				continue;
			}
			$status = (string) ( $row['queue_status'] ?? '' );
			if ( ! isset( $by_family[ $family ] ) ) {
				$by_family[ $family ] = array( 'completed' => 0, 'failed' => 0 );
			}
			if ( $status === 'completed' ) {
				$by_family[ $family ]['completed']++;
				$total_completed++;
			} else {
				$by_family[ $family ]['failed']++;
				$total_failed++;
			}
		}
		ksort( $by_family );
		return array( 'by_family' => $by_family, 'total_completed' => $total_completed, 'total_failed' => $total_failed );
	}

	/**
	 * Rollback job frequency (by month).
	 *
	 * @param string|null $date_from
	 * @param string|null $date_to
	 * @return array{total_rollbacks: int, by_month: list<array{month: string, count: int}>}
	 */
	private function build_rollback_frequency( ?string $date_from, ?string $date_to ): array {
		$by_month = array();
		$total = 0;
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array( 'total_rollbacks' => 0, 'by_month' => array() );
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::MAX_JOBS, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::MAX_JOBS, 0 );
		$from_ts = $date_from !== null && $date_from !== '' ? strtotime( $date_from . ' 00:00:00' ) : false;
		$to_ts   = $date_to !== null && $date_to !== '' ? strtotime( $date_to . ' 23:59:59' ) : false;
		foreach ( array_merge( $completed, $failed ) as $row ) {
			$job_type = (string) ( $row['job_type'] ?? '' );
			if ( $job_type !== Execution_Action_Types::ROLLBACK_ACTION ) {
				continue;
			}
			$created = (string) ( $row['created_at'] ?? '' );
			if ( $created === '' ) {
				continue;
			}
			$ts = strtotime( $created );
			if ( $from_ts !== false && $ts < $from_ts ) {
				continue;
			}
			if ( $to_ts !== false && $ts > $to_ts ) {
				continue;
			}
			$month = gmdate( 'Y-m', $ts );
			$by_month[ $month ] = ( $by_month[ $month ] ?? 0 ) + 1;
			$total++;
		}
		ksort( $by_month );
		$list = array();
		foreach ( $by_month as $month => $count ) {
			$list[] = array( 'month' => $month, 'count' => (int) $count );
		}
		return array( 'total_rollbacks' => $total, 'by_month' => $list );
	}

	/**
	 * Composition usage: counts by status (observational inventory).
	 *
	 * @return array{by_status: array<string, int>, total: int}
	 */
	private function build_composition_usage(): array {
		$by_status = array();
		$total = 0;
		if ( $this->composition_repository === null || ! method_exists( $this->composition_repository, 'list_all_definitions' ) ) {
			return array( 'by_status' => array(), 'total' => 0 );
		}
		$defs = $this->composition_repository->list_all_definitions( self::MAX_COMPOSITIONS, 0 );
		foreach ( $defs as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$status = (string) ( $def['status'] ?? 'unknown' );
			$by_status[ $status ] = ( $by_status[ $status ] ?? 0 ) + 1;
			$total++;
		}
		return array( 'by_status' => $by_status, 'total' => $total );
	}
}
