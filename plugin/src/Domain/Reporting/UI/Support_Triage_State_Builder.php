<?php
/**
 * Builds support triage dashboard state (spec §49.11, §59.12, §60.7).
 *
 * Aggregates reporting health, queue degradation, critical errors, failed AI runs,
 * stale Build Plans, rollback-eligible recent actions, and import/export context.
 * Redacted; no secrets. Reuses existing summary builders; links to authoritative screens.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Queue\Queue_Health_Summary_Builder;
use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Builds a stable support-dashboard payload: critical_issues, degraded_systems,
 * recent_failed_workflows, rollback_candidates, import_export_failures, recommended_links.
 */
final class Support_Triage_State_Builder {

	private const CRITICAL_CAP            = 10;
	private const FAILED_AI_RUNS_CAP      = 5;
	private const STALE_PLANS_CAP         = 10;
	private const ROLLBACK_CANDIDATES_CAP = 10;

	/** Job types that typically have rollback-eligible actions (link to plan for full eligibility). */
	private const ROLLBACK_CAPABLE_JOB_TYPES = array(
		'replace_page',
		'create_page',
		'update_menu',
		'apply_token_set',
	);

	/** @var object|null Job queue repository (list_by_status, get_by_key). */
	private $job_queue_repository;

	/** @var object|null AI run repository (list_recent). */
	private $ai_run_repository;

	/** @var object|null Build plan repository (list_recent). */
	private $build_plan_repository;

	/** @var \AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service|null Optional industry diagnostics (Prompt 356). */
	private $industry_diagnostics;

	public function __construct(
		?object $job_queue_repository = null,
		?object $ai_run_repository = null,
		?object $build_plan_repository = null,
		?\AIOPageBuilder\Domain\Industry\Reporting\Industry_Diagnostics_Service $industry_diagnostics = null
	) {
		$this->job_queue_repository  = $job_queue_repository;
		$this->ai_run_repository     = $ai_run_repository;
		$this->build_plan_repository = $build_plan_repository;
		$this->industry_diagnostics  = $industry_diagnostics;
	}

	/**
	 * Builds full support triage payload. Permission checks are caller's responsibility.
	 *
	 * @return array{
	 *   critical_issues: array<int, array{severity: string, domain: string, title: string, message: string, link_url: string, link_label: string}>,
	 *   degraded_systems: array<int, array{domain: string, title: string, message: string, link_url: string, link_label: string}>,
	 *   recent_failed_workflows: array<int, array{domain: string, identifier: string, summary: string, link_url: string, link_label: string}>,
	 *   rollback_candidates: array<int, array{job_ref: string, job_type: string, plan_id: string, completed_at: string, link_url: string, link_label: string}>,
	 *   import_export_failures: array<int, array{message: string, link_url: string, link_label: string}>,
	 *   recommended_links: array<int, array{label: string, url: string, description: string}>,
	 *   stale_plans: array<int, array{plan_id: string, status: string, title: string}>
	 * }
	 */
	public function build(): array {
		$reporting_health    = ( new Reporting_Health_Summary_Builder() )->build();
		$queue_health        = ( new Queue_Health_Summary_Builder( $this->job_queue_repository ) )->build();
		$critical_errors     = $this->build_critical_errors();
		$failed_ai_runs      = $this->build_failed_ai_runs();
		$stale_plans         = $this->build_stale_plans();
		$rollback_candidates = $this->build_rollback_candidates();
		$import_export       = $this->build_import_export_failures();
		$critical_issues     = $this->aggregate_critical_issues( $reporting_health, $queue_health, $critical_errors );
		$degraded_systems    = $this->aggregate_degraded_systems( $reporting_health, $queue_health );
		$recent_failed       = $this->aggregate_recent_failed_workflows( $critical_errors, $failed_ai_runs, $queue_health );
		$recommended_links   = $this->build_recommended_links( $critical_issues, $degraded_systems, $recent_failed );
		$industry_snapshot   = $this->industry_diagnostics !== null ? $this->industry_diagnostics->get_snapshot() : null;

		$out = array(
			'critical_issues'         => $critical_issues,
			'degraded_systems'        => $degraded_systems,
			'recent_failed_workflows' => $recent_failed,
			'rollback_candidates'     => $rollback_candidates,
			'import_export_failures'  => $import_export,
			'recommended_links'       => $recommended_links,
			'stale_plans'             => $stale_plans,
		);
		if ( $industry_snapshot !== null ) {
			$out['industry_snapshot'] = $industry_snapshot;
		}
		return $out;
	}

	/**
	 * @param array<string, mixed>        $reporting_health
	 * @param array<string, mixed>        $queue_health
	 * @param array<int, array<string, string>> $critical_errors
	 * @return array<int, array{severity: string, domain: string, title: string, message: string, link_url: string, link_label: string}>
	 */
	private function aggregate_critical_issues( array $reporting_health, array $queue_health, array $critical_errors ): array {
		$base              = \admin_url( 'admin.php' );
		$out               = array();
		$logs_critical_url = \add_query_arg(
			array(
				'page' => 'aio-page-builder-queue-logs',
				'tab'  => 'critical',
			),
			$base
		);
		if ( count( $critical_errors ) > 0 ) {
			$out[] = array(
				'severity'   => 'critical',
				'domain'     => 'reporting',
				'title'      => __( 'Critical error delivery failures', 'aio-page-builder' ),
				'message'    => sprintf( __( '%d developer error report(s) failed to deliver.', 'aio-page-builder' ), count( $critical_errors ) ),
				'link_url'   => $logs_critical_url,
				'link_label' => __( 'View critical errors', 'aio-page-builder' ),
			);
		}
		if ( ( (int) ( $queue_health['stale_lock_count'] ?? 0 ) ) > 0 ) {
			$out[] = array(
				'severity'   => 'critical',
				'domain'     => 'queue',
				'title'      => __( 'Stale queue locks', 'aio-page-builder' ),
				'message'    => sprintf( __( '%d job(s) with stale lock detected.', 'aio-page-builder' ), (int) $queue_health['stale_lock_count'] ),
				'link_url'   => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'queue',
					),
					$base
				),
				'link_label' => __( 'Queue & Logs', 'aio-page-builder' ),
			);
		}
		if ( ! empty( $reporting_health['reporting_degraded'] ) && ( (int) ( $reporting_health['recent_failures_count'] ?? 0 ) ) > 0 ) {
			$out[] = array(
				'severity'   => 'high',
				'domain'     => 'reporting',
				'title'      => __( 'Reporting degraded', 'aio-page-builder' ),
				'message'    => (string) ( $reporting_health['summary_message'] ?? '' ),
				'link_url'   => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'reporting',
					),
					$base
				),
				'link_label' => __( 'Reporting logs', 'aio-page-builder' ),
			);
		}
		return array_slice( $out, 0, self::CRITICAL_CAP );
	}

	/**
	 * @param array<string, mixed> $reporting_health
	 * @param array<string, mixed> $queue_health
	 * @return array<int, array{domain: string, title: string, message: string, link_url: string, link_label: string}>
	 */
	private function aggregate_degraded_systems( array $reporting_health, array $queue_health ): array {
		$base = \admin_url( 'admin.php' );
		$out  = array();
		if ( ! empty( $reporting_health['reporting_degraded'] ) ) {
			$out[] = array(
				'domain'     => 'reporting',
				'title'      => __( 'Reporting', 'aio-page-builder' ),
				'message'    => (string) ( $reporting_health['summary_message'] ?? '' ),
				'link_url'   => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'reporting',
					),
					$base
				),
				'link_label' => __( 'Queue & Logs → Reporting', 'aio-page-builder' ),
			);
		}
		if ( ( (int) ( $queue_health['total_failed'] ?? 0 ) ) > 0 || ! empty( $queue_health['bottleneck_warning'] ) ) {
			$out[] = array(
				'domain'     => 'queue',
				'title'      => __( 'Queue', 'aio-page-builder' ),
				'message'    => (string) ( $queue_health['summary_message'] ?? '' ),
				'link_url'   => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'queue',
					),
					$base
				),
				'link_label' => __( 'Queue & Logs', 'aio-page-builder' ),
			);
		}
		return $out;
	}

	/**
	 * @param array<int, array<string, string>> $critical_errors
	 * @param array<int, array<string, string>> $failed_ai_runs
	 * @param array<string, mixed>        $queue_health
	 * @return array<int, array{domain: string, identifier: string, summary: string, link_url: string, link_label: string}>
	 */
	private function aggregate_recent_failed_workflows( array $critical_errors, array $failed_ai_runs, array $queue_health ): array {
		$base = \admin_url( 'admin.php' );
		$out  = array();
		foreach ( array_slice( $failed_ai_runs, 0, 5 ) as $run ) {
			$out[] = array(
				'domain'     => 'ai_runs',
				'identifier' => (string) ( $run['run_id'] ?? '' ),
				'summary'    => (string) ( $run['status'] ?? '' ) . ' — ' . ( (string) ( $run['created_at'] ?? '' ) ),
				'link_url'   => \add_query_arg(
					array(
						'page'   => 'aio-page-builder-ai-runs',
						'run_id' => (string) ( $run['run_id'] ?? '' ),
					),
					$base
				),
				'link_label' => __( 'View AI run', 'aio-page-builder' ),
			);
		}
		$failed_count = (int) ( $queue_health['total_failed'] ?? 0 );
		if ( $failed_count > 0 ) {
			$out[] = array(
				'domain'     => 'queue',
				'identifier' => 'failed_jobs',
				'summary'    => sprintf( __( '%d failed queue job(s).', 'aio-page-builder' ), $failed_count ),
				'link_url'   => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'queue',
					),
					$base
				),
				'link_label' => __( 'Queue & Logs', 'aio-page-builder' ),
			);
		}
		return array_slice( $out, 0, 15 );
	}

	/**
	 * @return array<int, array{event_type: string, attempted_at: string, failure_reason: string}>
	 */
	private function build_critical_errors(): array {
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			return array();
		}
		$out = array();
		foreach ( array_reverse( $log ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			if ( ( (string) ( $entry['event_type'] ?? '' ) ) !== Reporting_Event_Types::DEVELOPER_ERROR_REPORT ) {
				continue;
			}
			if ( ( (string) ( $entry['delivery_status'] ?? '' ) ) !== 'failed' ) {
				continue;
			}
			$out[] = array(
				'event_type'     => (string) ( $entry['event_type'] ?? '' ),
				'attempted_at'   => (string) ( $entry['attempted_at'] ?? '' ),
				'failure_reason' => (string) ( $entry['failure_reason'] ?? '' ),
			);
			if ( count( $out ) >= self::CRITICAL_CAP ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * @return array<int, array{run_id: string, status: string, created_at: string}>
	 */
	private function build_failed_ai_runs(): array {
		if ( $this->ai_run_repository === null || ! method_exists( $this->ai_run_repository, 'list_recent' ) ) {
			return array();
		}
		$runs = $this->ai_run_repository->list_recent( self::FAILED_AI_RUNS_CAP * 3, 0 );
		$out  = array();
		foreach ( $runs as $run ) {
			if ( ! is_array( $run ) ) {
				continue;
			}
			$status = (string) ( $run['status'] ?? '' );
			if ( $status === 'completed' || $status === 'success' || $status === '' ) {
				continue;
			}
			$meta  = $run['run_metadata'] ?? array();
			$out[] = array(
				'run_id'     => (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' ),
				'status'     => $status,
				'created_at' => (string) ( $meta['created_at'] ?? '' ),
			);
			if ( count( $out ) >= self::FAILED_AI_RUNS_CAP ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Plans in pending_review or in_progress (needing attention). No secrets.
	 *
	 * @return array<int, array{plan_id: string, status: string, title: string}>
	 */
	private function build_stale_plans(): array {
		if ( $this->build_plan_repository === null || ! method_exists( $this->build_plan_repository, 'list_recent' ) ) {
			return array();
		}
		$plans    = $this->build_plan_repository->list_recent( self::STALE_PLANS_CAP * 2, 0 );
		$statuses = array( 'pending_review', 'in_progress', 'approved' );
		$out      = array();
		foreach ( $plans as $plan ) {
			if ( ! is_array( $plan ) ) {
				continue;
			}
			$status = (string) ( $plan['status'] ?? '' );
			if ( ! in_array( $status, $statuses, true ) ) {
				continue;
			}
			$out[] = array(
				'plan_id' => (string) ( $plan['internal_key'] ?? $plan['post_title'] ?? '' ),
				'status'  => $status,
				'title'   => (string) ( $plan['post_title'] ?? '' ),
			);
			if ( count( $out ) >= self::STALE_PLANS_CAP ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Recent completed jobs that are typically rollback-capable; link to plan for eligibility.
	 *
	 * @return array<int, array{job_ref: string, job_type: string, plan_id: string, completed_at: string, link_url: string, link_label: string}>
	 */
	private function build_rollback_candidates(): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$rows = $this->job_queue_repository->list_by_status( 'completed', self::ROLLBACK_CANDIDATES_CAP, 0 );
		$base = \admin_url( 'admin.php' );
		$out  = array();
		foreach ( $rows as $row ) {
			$job_type = (string) ( $row['job_type'] ?? '' );
			if ( ! in_array( $job_type, self::ROLLBACK_CAPABLE_JOB_TYPES, true ) ) {
				continue;
			}
			$related = (string) ( $row['related_object_refs'] ?? '' );
			$plan_id = '';
			if ( $related !== '' && preg_match( '/plan[_\s]?id[=:]\s*([a-zA-Z0-9_-]+)/i', $related, $m ) ) {
				$plan_id = $m[1];
			} elseif ( $related !== '' ) {
				$plan_id = trim( substr( $related, 0, 64 ) );
			}
			$plan_id = $plan_id !== '' ? $plan_id : '—';
			$out[]   = array(
				'job_ref'      => (string) ( $row['job_ref'] ?? '' ),
				'job_type'     => $job_type,
				'plan_id'      => $plan_id,
				'completed_at' => (string) ( $row['completed_at'] ?? '' ),
				'link_url'     => $plan_id !== '—' ? \add_query_arg(
					array(
						'page'    => 'aio-page-builder-build-plans',
						'plan_id' => $plan_id,
					),
					$base
				) : \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'queue',
					),
					$base
				),
				'link_label'   => $plan_id !== '—' ? __( 'Open plan', 'aio-page-builder' ) : __( 'Queue & Logs', 'aio-page-builder' ),
			);
		}
		return $out;
	}

	/**
	 * Import/export failures: no persistent failure log in state; empty list. Use recommended_links to reach Import/Export screen.
	 *
	 * @return array<int, array{message: string, link_url: string, link_label: string}>
	 */
	private function build_import_export_failures(): array {
		return array();
	}

	/**
	 * @param array<int, array<string, string>> $critical_issues
	 * @param array<int, array<string, string>> $degraded_systems
	 * @param array<int, array<string, string>> $recent_failed
	 * @return array<int, array{label: string, url: string, description: string}>
	 */
	private function build_recommended_links( array $critical_issues, array $degraded_systems, array $recent_failed ): array {
		$base = \admin_url( 'admin.php' );
		$out  = array();
		if ( count( $critical_issues ) > 0 ) {
			$out[] = array(
				'label'       => __( 'Critical errors', 'aio-page-builder' ),
				'url'         => \add_query_arg(
					array(
						'page' => 'aio-page-builder-queue-logs',
						'tab'  => 'critical',
					),
					$base
				),
				'description' => __( 'View failed developer error report delivery.', 'aio-page-builder' ),
			);
		}
		$out[] = array(
			'label'       => __( 'Queue & Logs', 'aio-page-builder' ),
			'url'         => \add_query_arg( array( 'page' => 'aio-page-builder-queue-logs' ), $base ),
			'description' => __( 'Queue health, execution logs, reporting logs.', 'aio-page-builder' ),
		);
		$out[] = array(
			'label'       => __( 'Build Plans', 'aio-page-builder' ),
			'url'         => \add_query_arg( array( 'page' => 'aio-page-builder-build-plans' ), $base ),
			'description' => __( 'Review and execute plans; rollback from plan.', 'aio-page-builder' ),
		);
		$out[] = array(
			'label'       => __( 'AI Runs', 'aio-page-builder' ),
			'url'         => \add_query_arg( array( 'page' => 'aio-page-builder-ai-runs' ), $base ),
			'description' => __( 'View AI run history and status.', 'aio-page-builder' ),
		);
		$out[] = array(
			'label'       => __( 'Import / Export', 'aio-page-builder' ),
			'url'         => \add_query_arg( array( 'page' => 'aio-page-builder-export-restore' ), $base ),
			'description' => __( 'Validate package, restore, export history.', 'aio-page-builder' ),
		);
		return $out;
	}
}
