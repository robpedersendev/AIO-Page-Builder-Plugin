<?php
/**
 * Builds UI state for the Dashboard screen (spec §49.5). Aggregates readiness, activity summaries, queue/errors, quick actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Admin\Dashboard;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Bootstrap\Environment_Validator;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Statuses;
use AIOPageBuilder\Domain\Reporting\UI\Logs_Monitoring_State_Builder;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Assembles dashboard payload: readiness_cards, last_activity_cards, queue_warning_summary, critical_error_summary, quick_actions, welcome_state.
 * Permission filtering of quick_actions is applied when build() is called in a user context (current_user_can).
 */
final class Dashboard_State_Builder {

	private const LAST_CRAWL_LIMIT = 1;
	private const LAST_AI_RUN_LIMIT = 1;
	private const ACTIVE_PLANS_LIMIT = 5;
	private const CRITICAL_ERRORS_DASHBOARD_CAP = 5;
	private const QUEUE_WARNING_ITEMS = 5;

	/** @var object|null Crawl_Snapshot_Service (list_sessions). */
	private $crawl_snapshot_service;

	/** @var object|null AI run repository (list_recent). */
	private $ai_run_repository;

	/** @var object|null Build plan repository (list_recent). */
	private $build_plan_repository;

	/** @var object|null Job queue repository (list_by_status). */
	private $job_queue_repository;

	/** @var Settings_Service */
	private Settings_Service $settings;

	public function __construct(
		Settings_Service $settings,
		?object $crawl_snapshot_service = null,
		?object $ai_run_repository = null,
		?object $build_plan_repository = null,
		?object $job_queue_repository = null
	) {
		$this->settings               = $settings;
		$this->crawl_snapshot_service  = $crawl_snapshot_service;
		$this->ai_run_repository       = $ai_run_repository;
		$this->build_plan_repository   = $build_plan_repository;
		$this->job_queue_repository    = $job_queue_repository;
	}

	/**
	 * Builds full dashboard state. Quick actions are filtered by current user capability.
	 *
	 * @return array{
	 *   readiness_cards: array{environment: array, dependency: array, provider: array},
	 *   last_activity_cards: array{last_crawl: array|null, last_ai_run: array|null, active_build_plans: array},
	 *   queue_warning_summary: array{has_warnings: bool, pending_count: int, failed_count: int, message: string, queue_logs_url: string},
	 *   critical_error_summary: array{count: int, items: list<array>, logs_url: string},
	 *   quick_actions: list<array{label: string, url: string, capability: string}>,
	 *   welcome_state: array{is_first_run: bool, is_resume: bool, onboarding_url: string}
	 * }
	 */
	public function build(): array {
		$readiness_cards   = $this->build_readiness_cards();
		$last_activity     = $this->build_last_activity_cards();
		$queue_summary     = $this->build_queue_warning_summary();
		$critical_summary  = $this->build_critical_error_summary();
		$quick_actions     = $this->build_quick_actions_filtered();
		$welcome_state     = $this->build_welcome_state();
		return array(
			'readiness_cards'       => $readiness_cards,
			'last_activity_cards'   => $last_activity,
			'queue_warning_summary' => $queue_summary,
			'critical_error_summary' => $critical_summary,
			'quick_actions'         => $quick_actions,
			'welcome_state'         => $welcome_state,
		);
	}

	/**
	 * @return array{environment: array{ready: bool, message: string, blocking_count: int, warning_count: int}, dependency: array{ready: bool, message: string}, provider: array{ready: bool, message: string}}
	 */
	private function build_readiness_cards(): array {
		$validator = new Environment_Validator();
		$validator->validate();
		$results = $validator->get_results();

		$blocking = 0;
		$warnings = 0;
		$env_blocking = 0;
		$dep_blocking = 0;
		foreach ( $results as $r ) {
			if ( $r->is_blocking ) {
				++$blocking;
				if ( $r->category === Environment_Validator::CATEGORY_PLATFORM || $r->category === Environment_Validator::CATEGORY_RUNTIME_READINESS ) {
					++$env_blocking;
				}
				if ( $r->category === Environment_Validator::CATEGORY_REQUIRED_DEPENDENCY ) {
					++$dep_blocking;
				}
			} else {
				++$warnings;
			}
		}

		$environment_ready = $env_blocking === 0;
		$dependency_ready  = $dep_blocking === 0;
		$env_message = $blocking > 0
			? ( $validator->get_first_blocking_message() ?: __( 'One or more checks failed.', 'aio-page-builder' ) )
			: ( $warnings > 0 ? sprintf( __( '%d warning(s).', 'aio-page-builder' ), $warnings ) : __( 'Ready.', 'aio-page-builder' ) );
		$dep_message = $dep_blocking > 0
			? sprintf( __( '%d required dependency issue(s).', 'aio-page-builder' ), $dep_blocking )
			: ( $dependency_ready ? __( 'Dependencies met.', 'aio-page-builder' ) : __( 'Check diagnostics.', 'aio-page-builder' ) );

		$provider_ready = $this->has_provider_configured();
		$provider_message = $provider_ready
			? __( 'At least one provider configured.', 'aio-page-builder' )
			: __( 'No AI provider configured.', 'aio-page-builder' );

		return array(
			'environment' => array(
				'ready'          => $environment_ready,
				'message'        => $env_message,
				'blocking_count' => $env_blocking,
				'warning_count'  => $warnings,
			),
			'dependency'  => array(
				'ready'   => $dependency_ready,
				'message' => $dep_message,
			),
			'provider'    => array(
				'ready'   => $provider_ready,
				'message' => $provider_message,
			),
		);
	}

	private function has_provider_configured(): bool {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		if ( ! isset( $config['providers'] ) || ! is_array( $config['providers'] ) ) {
			return false;
		}
		foreach ( $config['providers'] as $p ) {
			if ( is_array( $p ) && isset( $p['credential_state'] ) && (string) $p['credential_state'] === 'configured' ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array{last_crawl: array{run_id: string, started_at: string|null, final_status: string, total_discovered: int}|null, last_ai_run: array{run_id: string, status: string, created_at: string}|null, active_build_plans: list<array{plan_id: string, status: string, title: string}>}
	 */
	private function build_last_activity_cards(): array {
		$last_crawl = $this->build_last_crawl_summary();
		$last_ai_run = $this->build_last_ai_run_summary();
		$active_plans = $this->build_active_plans_summary();
		return array(
			'last_crawl'          => $last_crawl,
			'last_ai_run'         => $last_ai_run,
			'active_build_plans'  => $active_plans,
		);
	}

	/**
	 * @return array{run_id: string, started_at: string|null, final_status: string, total_discovered: int}|null
	 */
	private function build_last_crawl_summary(): ?array {
		if ( $this->crawl_snapshot_service === null || ! method_exists( $this->crawl_snapshot_service, 'list_sessions' ) ) {
			return null;
		}
		$sessions = $this->crawl_snapshot_service->list_sessions( self::LAST_CRAWL_LIMIT );
		$session = $sessions[0] ?? null;
		if ( $session === null || ! is_array( $session ) ) {
			return null;
		}
		return array(
			'run_id'           => (string) ( $session['crawl_run_id'] ?? '' ),
			'started_at'       => isset( $session['started_at'] ) ? (string) $session['started_at'] : null,
			'final_status'     => (string) ( $session['final_status'] ?? 'unknown' ),
			'total_discovered' => (int) ( $session['total_discovered'] ?? 0 ),
		);
	}

	/**
	 * @return array{run_id: string, status: string, created_at: string}|null
	 */
	private function build_last_ai_run_summary(): ?array {
		if ( $this->ai_run_repository === null || ! method_exists( $this->ai_run_repository, 'list_recent' ) ) {
			return null;
		}
		$runs = $this->ai_run_repository->list_recent( self::LAST_AI_RUN_LIMIT, 0 );
		$run = $runs[0] ?? null;
		if ( $run === null || ! is_array( $run ) ) {
			return null;
		}
		$meta = $run['run_metadata'] ?? array();
		return array(
			'run_id'     => (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' ),
			'status'     => (string) ( $run['status'] ?? '' ),
			'created_at' => (string) ( $meta['created_at'] ?? '' ),
		);
	}

	/**
	 * @return list<array{plan_id: string, status: string, title: string}>
	 */
	private function build_active_plans_summary(): array {
		if ( $this->build_plan_repository === null || ! method_exists( $this->build_plan_repository, 'list_recent' ) ) {
			return array();
		}
		$plans = $this->build_plan_repository->list_recent( self::ACTIVE_PLANS_LIMIT, 0 );
		$active_statuses = array( 'pending_review', 'approved', 'in_progress' );
		$out = array();
		foreach ( $plans as $plan ) {
			if ( ! is_array( $plan ) ) {
				continue;
			}
			$status = (string) ( $plan['status'] ?? '' );
			if ( ! in_array( $status, $active_statuses, true ) ) {
				continue;
			}
			$out[] = array(
				'plan_id' => (string) ( $plan['internal_key'] ?? $plan['post_title'] ?? '' ),
				'status'  => $status,
				'title'   => (string) ( $plan['post_title'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * @return array{has_warnings: bool, pending_count: int, failed_count: int, message: string, queue_logs_url: string}
	 */
	private function build_queue_warning_summary(): array {
		$pending_count = 0;
		$failed_count  = 0;
		if ( $this->job_queue_repository !== null && method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			$pending = $this->job_queue_repository->list_by_status( 'pending', self::QUEUE_WARNING_ITEMS, 0 );
			$failed  = $this->job_queue_repository->list_by_status( 'failed', self::QUEUE_WARNING_ITEMS, 0 );
			$pending_count = count( $pending );
			$failed_count  = count( $failed );
		}
		$has_warnings = $pending_count > 0 || $failed_count > 0;
		$message = $has_warnings
			? sprintf(
				/* translators: 1: pending count, 2: failed count */
				__( '%1$d pending, %2$d failed job(s).', 'aio-page-builder' ),
				$pending_count,
				$failed_count
			)
			: __( 'No queue warnings.', 'aio-page-builder' );
		$queue_logs_url = \add_query_arg( array( 'page' => 'aio-page-builder-queue-logs' ), \admin_url( 'admin.php' ) );
		return array(
			'has_warnings'    => $has_warnings,
			'pending_count'  => $pending_count,
			'failed_count'    => $failed_count,
			'message'         => $message,
			'queue_logs_url'  => $queue_logs_url,
		);
	}

	/**
	 * @return array{count: int, items: list<array{event_type: string, attempted_at: string, failure_reason: string}>, logs_url: string}
	 */
	private function build_critical_error_summary(): array {
		$logs_builder = new Logs_Monitoring_State_Builder( $this->job_queue_repository, $this->ai_run_repository );
		$all = $logs_builder->build_critical_errors();
		$items = array_slice( $all, 0, self::CRITICAL_ERRORS_DASHBOARD_CAP );
		$out = array();
		foreach ( $items as $entry ) {
			$out[] = array(
				'event_type'    => (string) ( $entry['event_type'] ?? '' ),
				'attempted_at'  => (string) ( $entry['attempted_at'] ?? '' ),
				'failure_reason' => (string) ( $entry['failure_reason'] ?? '' ),
			);
		}
		$logs_url = \add_query_arg( array( 'page' => 'aio-page-builder-queue-logs', 'tab' => 'critical' ), \admin_url( 'admin.php' ) );
		return array(
			'count'    => count( $all ),
			'items'    => $out,
			'logs_url' => $logs_url,
		);
	}

	/**
	 * Quick actions with capability check. Only includes actions the current user can perform.
	 *
	 * @return list<array{label: string, url: string, capability: string}>
	 */
	private function build_quick_actions_filtered(): array {
		$all = $this->get_quick_actions_definitions();
		$out = array();
		foreach ( $all as $action ) {
			if ( \current_user_can( $action['capability'] ) ) {
				$out[] = $action;
			}
		}
		return $out;
	}

	/**
	 * @return list<array{label: string, url: string, capability: string}>
	 */
	private function get_quick_actions_definitions(): array {
		$base = \admin_url( 'admin.php' );
		return array(
			array(
				'label'      => __( 'Start / Resume Onboarding', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-onboarding' ), $base ),
				'capability' => Capabilities::RUN_ONBOARDING,
			),
			array(
				'label'      => __( 'Diagnostics', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-diagnostics' ), $base ),
				'capability' => Capabilities::VIEW_SENSITIVE_DIAGNOSTICS,
			),
			array(
				'label'      => __( 'Crawl Sessions', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-crawler-sessions' ), $base ),
				'capability' => Capabilities::VIEW_SENSITIVE_DIAGNOSTICS,
			),
			array(
				'label'      => __( 'AI Runs', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-ai-runs' ), $base ),
				'capability' => Capabilities::VIEW_AI_RUNS,
			),
			array(
				'label'      => __( 'Build Plans', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-build-plans' ), $base ),
				'capability' => Capabilities::VIEW_BUILD_PLANS,
			),
			array(
				'label'      => __( 'Queue & Logs', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-queue-logs' ), $base ),
				'capability' => Capabilities::VIEW_LOGS,
			),
			array(
				'label'      => __( 'AI Providers', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-ai-providers' ), $base ),
				'capability' => Capabilities::MANAGE_AI_PROVIDERS,
			),
			array(
				'label'      => __( 'Import / Export', 'aio-page-builder' ),
				'url'        => \add_query_arg( array( 'page' => 'aio-page-builder-export-restore' ), $base ),
				'capability' => Capabilities::EXPORT_DATA,
			),
		);
	}

	/**
	 * @return array{is_first_run: bool, is_resume: bool, onboarding_url: string}
	 */
	private function build_welcome_state(): array {
		$draft_service = new Onboarding_Draft_Service( $this->settings );
		$draft = $draft_service->get_draft();
		$overall = (string) ( $draft['overall_status'] ?? Onboarding_Statuses::NOT_STARTED );
		$is_first_run = $overall === Onboarding_Statuses::NOT_STARTED || $overall === '';
		$resume_statuses = array( Onboarding_Statuses::IN_PROGRESS, Onboarding_Statuses::DRAFT_SAVED, Onboarding_Statuses::BLOCKED, Onboarding_Statuses::READY_FOR_SUBMISSION );
		$is_resume = ! $is_first_run && in_array( $overall, $resume_statuses, true );
		$onboarding_url = \add_query_arg( array( 'page' => 'aio-page-builder-onboarding' ), \admin_url( 'admin.php' ) );
		return array(
			'is_first_run'    => $is_first_run,
			'is_resume'       => $is_resume,
			'onboarding_url' => $onboarding_url,
		);
	}
}
