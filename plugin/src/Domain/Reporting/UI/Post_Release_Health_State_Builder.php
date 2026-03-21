<?php
/**
 * Builds post-release health review state (spec §45, §46, §49.11, §59.15, §60.8; Prompt 131).
 *
 * Aggregates reporting health, queue backlog/failures, Build Plan approval/denial trends,
 * AI run validity rates, rollback usage, import/export and support-package context from existing
 * structured records. Observational only; no mutation. Redacted; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Analytics\Build_Plan_Analytics_Service;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Health_Summary_Builder;

/**
 * Produces post_release_health_summary, domain_health_scores, and recommended_investigation_items.
 */
final class Post_Release_Health_State_Builder {

	private const DEFAULT_DAYS = 30;

	private const AI_RUN_LIMIT = 200;

	/** @var object|null Job queue repository (list_by_status). */
	private $job_queue_repository;

	/** @var object|null AI run repository (list_recent). */
	private $ai_run_repository;

	/** @var object|null Build plan repository (list_recent). */
	private $build_plan_repository;

	/** @var Build_Plan_Analytics_Service|null */
	private $plan_analytics_service;

	public function __construct(
		?object $job_queue_repository = null,
		?object $ai_run_repository = null,
		?object $build_plan_repository = null,
		?Build_Plan_Analytics_Service $plan_analytics_service = null
	) {
		$this->job_queue_repository   = $job_queue_repository;
		$this->ai_run_repository      = $ai_run_repository;
		$this->build_plan_repository  = $build_plan_repository;
		$this->plan_analytics_service = $plan_analytics_service;
	}

	/**
	 * Builds full post-release health payload for the given period.
	 *
	 * @param string|null $date_from Y-m-d (default: 30 days ago).
	 * @param string|null $date_to   Y-m-d (default: today).
	 * @return array{
	 *   post_release_health_summary: array{period_start: string, period_end: string, overall_status: string, summary_message: string},
	 *   domain_health_scores: array<string, array{status: string, score_label: string, message: string, link_url: string, link_label: string}>,
	 *   recommended_investigation_items: array<int, array{domain: string, priority: string, title: string, message: string, link_url: string, link_label: string}>
	 * }
	 */
	public function build( ?string $date_from = null, ?string $date_to = null ): array {
		$date_from = $this->normalize_date_from( $date_from );
		$date_to   = $this->normalize_date_to( $date_to );

		$reporting_health = ( new Reporting_Health_Summary_Builder() )->build();
		$queue_health     = ( new Queue_Health_Summary_Builder( $this->job_queue_repository ) )->build();
		$triage           = ( new Support_Triage_State_Builder( $this->job_queue_repository, $this->ai_run_repository, $this->build_plan_repository ) )->build();

		$plan_trends = array();
		$rollback    = array();
		if ( $this->plan_analytics_service !== null ) {
			$analytics   = $this->plan_analytics_service->get_analytics_summary( $date_from, $date_to );
			$plan_trends = $analytics['plan_review_trends'];
			$rollback    = $analytics['rollback_frequency_summary'];
		}

		$ai_validity = $this->build_ai_run_validity( $date_from, $date_to );

		$domain_scores = $this->build_domain_health_scores(
			$reporting_health,
			$queue_health,
			$plan_trends,
			$rollback,
			$ai_validity,
			$triage
		);

		$recommended = $this->build_recommended_investigation_items(
			$reporting_health,
			$queue_health,
			$plan_trends,
			$triage,
			$domain_scores
		);

		$overall_status  = $this->derive_overall_status( $domain_scores, $recommended );
		$summary_message = $this->derive_summary_message( $overall_status, $domain_scores, $recommended );

		return array(
			'post_release_health_summary'     => array(
				'period_start'    => $date_from,
				'period_end'      => $date_to,
				'overall_status'  => $overall_status,
				'summary_message' => $summary_message,
			),
			'domain_health_scores'            => $domain_scores,
			'recommended_investigation_items' => $recommended,
		);
	}

	private function normalize_date_from( ?string $date_from ): string {
		if ( $date_from !== null && $date_from !== '' ) {
			return $date_from;
		}
		return gmdate( 'Y-m-d', strtotime( '-' . self::DEFAULT_DAYS . ' days' ) );
	}

	private function normalize_date_to( ?string $date_to ): string {
		if ( $date_to !== null && $date_to !== '' ) {
			return $date_to;
		}
		return gmdate( 'Y-m-d' );
	}

	/**
	 * AI run validity for period: completed count, failed count, success rate (from list_recent + created_at filter).
	 *
	 * @return array{total: int, completed: int, failed: int, success_rate: float, in_period: int}
	 */
	private function build_ai_run_validity( string $date_from, string $date_to ): array {
		if ( $this->ai_run_repository === null || ! method_exists( $this->ai_run_repository, 'list_recent' ) ) {
			return array(
				'total'        => 0,
				'completed'    => 0,
				'failed'       => 0,
				'success_rate' => 0.0,
				'in_period'    => 0,
			);
		}
		$runs      = $this->ai_run_repository->list_recent( self::AI_RUN_LIMIT, 0 );
		$from_ts   = strtotime( $date_from . ' 00:00:00' );
		$to_ts     = strtotime( $date_to . ' 23:59:59' );
		$in_period = 0;
		$completed = 0;
		$failed    = 0;
		foreach ( $runs as $run ) {
			if ( ! is_array( $run ) ) {
				continue;
			}
			$meta    = $run['run_metadata'] ?? array();
			$created = (string) ( $meta['created_at'] ?? '' );
			if ( $created === '' ) {
				continue;
			}
			$ts = strtotime( $created );
			if ( $ts < $from_ts || $ts > $to_ts ) {
				continue;
			}
			++$in_period;
			$status = (string) ( $run['status'] ?? $meta['status'] ?? '' );
			if ( $status === 'completed' || $status === 'success' ) {
				++$completed;
			} elseif ( $status === 'failed_validation' || $status === 'failed' ) {
				++$failed;
			}
		}
		$validated    = $completed + $failed;
		$success_rate = $validated > 0 ? round( $completed / $validated, 4 ) : 0.0;
		return array(
			'total'        => count( $runs ),
			'completed'    => $completed,
			'failed'       => $failed,
			'success_rate' => $success_rate,
			'in_period'    => $in_period,
		);
	}

	private function build_domain_health_scores(
		array $reporting_health,
		array $queue_health,
		array $plan_trends,
		array $rollback,
		array $ai_validity,
		array $triage
	): array {
		$base   = \admin_url( 'admin.php' );
		$scores = array();

		// Reporting.
		$reporting_ok        = empty( $reporting_health['reporting_degraded'] );
		$scores['reporting'] = array(
			'status'      => $reporting_ok ? 'ok' : 'degraded',
			'score_label' => $reporting_ok ? __( 'OK', 'aio-page-builder' ) : __( 'Degraded', 'aio-page-builder' ),
			'message'     => (string) ( $reporting_health['summary_message'] ?? '' ),
			'link_url'    => \add_query_arg(
				array(
					'page' => 'aio-page-builder-queue-logs',
					'tab'  => 'reporting',
				),
				$base
			),
			'link_label'  => __( 'Queue & Logs → Reporting', 'aio-page-builder' ),
		);

		// Queue.
		$stale           = (int) ( $queue_health['stale_lock_count'] ?? 0 );
		$failed          = (int) ( $queue_health['total_failed'] ?? 0 );
		$bottleneck      = ! empty( $queue_health['bottleneck_warning'] );
		$queue_status    = $stale > 0 ? 'critical' : ( $failed > 0 || $bottleneck ? 'warning' : 'ok' );
		$scores['queue'] = array(
			'status'      => $queue_status,
			'score_label' => $queue_status === 'critical' ? __( 'Critical', 'aio-page-builder' ) : ( $queue_status === 'warning' ? __( 'Warning', 'aio-page-builder' ) : __( 'OK', 'aio-page-builder' ) ),
			'message'     => (string) ( $queue_health['summary_message'] ?? '' ),
			'link_url'    => \add_query_arg(
				array(
					'page' => 'aio-page-builder-queue-logs',
					'tab'  => 'queue',
				),
				$base
			),
			'link_label'  => __( 'Queue & Logs', 'aio-page-builder' ),
		);

		// Build plan review (approval/denial trend).
		$denial_rate                 = (float) ( $plan_trends['denial_rate'] ?? 0 );
		$total_plans                 = (int) ( $plan_trends['total_plans'] ?? 0 );
		$plan_status                 = $total_plans === 0 ? 'ok' : ( $denial_rate >= 0.5 ? 'warning' : ( $denial_rate >= 0.25 ? 'attention' : 'ok' ) );
		$scores['build_plan_review'] = array(
			'status'      => $plan_status,
			'score_label' => $plan_status === 'warning' ? __( 'High denial rate', 'aio-page-builder' ) : ( $plan_status === 'attention' ? __( 'Moderate denial', 'aio-page-builder' ) : __( 'OK', 'aio-page-builder' ) ),
			'message'     => $total_plans === 0 ? __( 'No plans in period.', 'aio-page-builder' ) : sprintf(
				/* translators: 1: approval rate percent, 2: denial rate percent, 3: plan count. */
				__( 'Approval rate %1$.1f%%, denial %2$.1f%% (%3$d plans).', 'aio-page-builder' ),
				( $plan_trends['approval_rate'] ?? 0 ) * 100,
				$denial_rate * 100,
				$total_plans
			),
			'link_url'    => \add_query_arg( array( 'page' => 'aio-page-builder-build-plan-analytics' ), $base ),
			'link_label'  => __( 'Build Plan Analytics', 'aio-page-builder' ),
		);

		// AI run validity.
		$rate                      = (float) ( $ai_validity['success_rate'] ?? 0 );
		$in_period                 = (int) ( $ai_validity['in_period'] ?? 0 );
		$ai_status                 = $in_period === 0 ? 'ok' : ( $rate < 0.5 ? 'warning' : ( $rate < 0.8 ? 'attention' : 'ok' ) );
		$scores['ai_run_validity'] = array(
			'status'      => $ai_status,
			'score_label' => $ai_status === 'warning' ? __( 'Low success rate', 'aio-page-builder' ) : ( $ai_status === 'attention' ? __( 'Moderate', 'aio-page-builder' ) : __( 'OK', 'aio-page-builder' ) ),
			'message'     => $in_period === 0 ? __( 'No AI runs in period.', 'aio-page-builder' ) : sprintf(
				/* translators: 1: success rate percent, 2: number of runs. */
				__( '%1$.1f%% success (%2$d runs in period).', 'aio-page-builder' ),
				$rate * 100,
				$in_period
			),
			'link_url'    => \add_query_arg( array( 'page' => 'aio-page-builder-ai-runs' ), $base ),
			'link_label'  => __( 'AI Runs', 'aio-page-builder' ),
		);

		// Rollback (from analytics stub; link to Support Triage / history).
		$rollback_total     = (int) ( $rollback['total_rollbacks'] ?? 0 );
		$scores['rollback'] = array(
			'status'      => 'ok',
			'score_label' => __( 'OK', 'aio-page-builder' ),
			'message'     => $rollback_total === 0 ? __( 'No rollbacks in period.', 'aio-page-builder' ) : sprintf(
				/* translators: %d: number of rollbacks. */
				__( '%d rollback(s) in period.', 'aio-page-builder' ),
				$rollback_total
			),
			'link_url'    => \add_query_arg( array( 'page' => 'aio-page-builder-support-triage' ), $base ),
			'link_label'  => __( 'Support Triage', 'aio-page-builder' ),
		);

		// Import/export: triage may surface failures when a feed exists; otherwise do not imply a monitored failure log.
		$import_export_failures  = $triage['import_export_failures'] ?? array();
		$ie_ok                   = count( $import_export_failures ) === 0;
		$scores['import_export'] = array(
			'status'      => $ie_ok ? 'ok' : 'attention',
			'score_label' => $ie_ok ? __( 'OK', 'aio-page-builder' ) : __( 'Failures present', 'aio-page-builder' ),
			'message'     => $ie_ok
				? __( 'Import/export outcomes are not aggregated here; use Import / Export for operations and Queue & Logs for reporting history.', 'aio-page-builder' )
				: sprintf(
					/* translators: %d: number of import/export issues. */
					__( '%d import/export issue(s) noted.', 'aio-page-builder' ),
					count( $import_export_failures )
				),
			'link_url'    => \add_query_arg( array( 'page' => 'aio-page-builder-export-restore' ), $base ),
			'link_label'  => __( 'Import / Export', 'aio-page-builder' ),
		);

		$scores['support_package'] = array(
			'status'      => 'ok',
			'score_label' => __( 'OK', 'aio-page-builder' ),
			'message'     => __( 'Support package usage from Support Triage and Export.', 'aio-page-builder' ),
			'link_url'    => \add_query_arg( array( 'page' => 'aio-page-builder-support-triage' ), $base ),
			'link_label'  => __( 'Support Triage', 'aio-page-builder' ),
		);

		return $scores;
	}

	private function build_recommended_investigation_items(
		array $reporting_health,
		array $queue_health,
		array $plan_trends,
		array $triage,
		array $domain_scores
	): array {
		$base  = \admin_url( 'admin.php' );
		$items = array();

		foreach ( $triage['critical_issues'] ?? array() as $issue ) {
			$items[] = array(
				'domain'     => (string) ( $issue['domain'] ?? 'general' ),
				'priority'   => 'high',
				'title'      => (string) ( $issue['title'] ?? '' ),
				'message'    => (string) ( $issue['message'] ?? '' ),
				'link_url'   => (string) ( $issue['link_url'] ?? $base ),
				'link_label' => (string) ( $issue['link_label'] ?? __( 'View', 'aio-page-builder' ) ),
			);
		}

		foreach ( $triage['degraded_systems'] ?? array() as $sys ) {
			$items[] = array(
				'domain'     => (string) ( $sys['domain'] ?? 'general' ),
				'priority'   => 'medium',
				'title'      => (string) ( $sys['title'] ?? '' ),
				'message'    => (string) ( $sys['message'] ?? '' ),
				'link_url'   => (string) ( $sys['link_url'] ?? $base ),
				'link_label' => (string) ( $sys['link_label'] ?? __( 'View', 'aio-page-builder' ) ),
			);
		}

		$denial_rate = (float) ( $plan_trends['denial_rate'] ?? 0 );
		if ( ( (int) ( $plan_trends['total_plans'] ?? 0 ) ) > 0 && $denial_rate >= 0.5 ) {
			$items[] = array(
				'domain'     => 'build_plan_review',
				'priority'   => 'medium',
				'title'      => __( 'High Build Plan denial rate', 'aio-page-builder' ),
				'message'    => sprintf(
					/* translators: %s: denial rate percentage (one decimal). */
					__( '%.1f%% denial in period. Review common blockers and plan friction.', 'aio-page-builder' ),
					$denial_rate * 100
				),
				'link_url'   => \add_query_arg( array( 'page' => 'aio-page-builder-build-plan-analytics' ), $base ),
				'link_label' => __( 'Build Plan Analytics', 'aio-page-builder' ),
			);
		}

		foreach ( $domain_scores as $domain => $score ) {
			if ( ( $score['status'] ?? '' ) === 'critical' && ! $this->already_recommended( $items, $domain ) ) {
				$items[] = array(
					'domain'     => $domain,
					'priority'   => 'high',
					'title'      => (string) ( $score['score_label'] ?? $domain ),
					'message'    => (string) ( $score['message'] ?? '' ),
					'link_url'   => (string) ( $score['link_url'] ?? $base ),
					'link_label' => (string) ( $score['link_label'] ?? __( 'View', 'aio-page-builder' ) ),
				);
			}
		}

		return array_slice( $items, 0, 20 );
	}

	private function already_recommended( array $items, string $domain ): bool {
		foreach ( $items as $item ) {
			if ( ( $item['domain'] ?? '' ) === $domain ) {
				return true;
			}
		}
		return false;
	}

	private function derive_overall_status( array $domain_scores, array $recommended ): string {
		foreach ( $domain_scores as $score ) {
			if ( ( $score['status'] ?? '' ) === 'critical' ) {
				return 'critical';
			}
		}
		foreach ( $recommended as $item ) {
			if ( ( $item['priority'] ?? '' ) === 'high' ) {
				return 'attention';
			}
		}
		foreach ( $domain_scores as $score ) {
			if ( in_array( $score['status'] ?? '', array( 'warning', 'degraded' ), true ) ) {
				return 'attention';
			}
		}
		return 'ok';
	}

	private function derive_summary_message( string $overall_status, array $domain_scores, array $recommended ): string {
		if ( $overall_status === 'critical' ) {
			return __( 'Critical issues detected. Review recommended investigation items and domain scores.', 'aio-page-builder' );
		}
		if ( $overall_status === 'attention' ) {
			return __( 'Some domains need attention. Use the links below for tuning and follow-up.', 'aio-page-builder' );
		}
		if ( count( $recommended ) > 0 ) {
			return __( 'Operational health OK; optional follow-up items listed.', 'aio-page-builder' );
		}
		return __( 'Operational health good across domains for the selected period.', 'aio-page-builder' );
	}
}
