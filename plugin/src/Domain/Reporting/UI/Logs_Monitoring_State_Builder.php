<?php
/**
 * Builds view-state for Queue & Logs monitoring screen (spec §49.11).
 *
 * Aggregates queue jobs, execution-style log rows, reporting log, and critical-error view
 * from existing storage. Redacted; stable row shapes for tabs and filtering.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Builds monitoring view state from queue repository, AI run repository, and options.
 */
final class Logs_Monitoring_State_Builder {

	private const QUEUE_LIMIT = 100;
	private const EXECUTION_LIMIT = 50;
	private const AI_RUNS_LIMIT = 20;
	private const REPORTING_LOG_CAP = 50;
	private const CRITICAL_ERRORS_CAP = 50;

	/** @var object|null Job queue repository (list_by_status); e.g. Job_Queue_Repository. */
	private $job_queue_repository;

	/** @var object|null AI run repository (list_recent). */
	private $ai_run_repository;

	public function __construct( ?object $job_queue_repository = null, ?object $ai_run_repository = null ) {
		$this->job_queue_repository = $job_queue_repository;
		$this->ai_run_repository     = $ai_run_repository;
	}

	/**
	 * Builds full monitoring state for all tabs. Permission checks are caller's responsibility.
	 *
	 * @return array{
	 *   queue: list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}>,
	 *   execution_logs: list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}>,
	 *   ai_runs: list<array{run_id: string, status: string, created_at: string}>,
	 *   reporting_logs: list<array{event_type: string, dedupe_key: string, attempted_at: string, delivery_status: string, log_reference: string, failure_reason: string}>,
	 *   import_export_logs: list<array{id: string, type: string, created_at: string, status: string}>,
	 *   critical_errors: list<array{event_type: string, attempted_at: string, delivery_status: string, failure_reason: string, log_reference: string}>,
	 *   log_export: array{exportable_log_types: list<array{value: string, label: string}>}
	 * }
	 */
	public function build(): array {
		return array(
			'queue'              => $this->build_queue_tab(),
			'execution_logs'     => $this->build_execution_logs(),
			'ai_runs'            => $this->build_ai_runs_tab(),
			'reporting_logs'     => $this->build_reporting_logs(),
			'import_export_logs' => $this->build_import_export_logs(),
			'critical_errors'    => $this->build_critical_errors(),
			'log_export'         => $this->build_log_export_options(),
		);
	}

	/**
	 * Options for log export (spec §48.10). Used by Queue & Logs screen for export form.
	 *
	 * @return array{exportable_log_types: list<array{value: string, label: string}>}
	 */
	public function build_log_export_options(): array {
		return array(
			'exportable_log_types' => array(
				array( 'value' => Log_Export_Service::LOG_TYPE_QUEUE, 'label' => __( 'Queue', 'aio-page-builder' ) ),
				array( 'value' => Log_Export_Service::LOG_TYPE_EXECUTION, 'label' => __( 'Execution logs', 'aio-page-builder' ) ),
				array( 'value' => Log_Export_Service::LOG_TYPE_REPORTING, 'label' => __( 'Reporting logs', 'aio-page-builder' ) ),
				array( 'value' => Log_Export_Service::LOG_TYPE_CRITICAL, 'label' => __( 'Critical errors', 'aio-page-builder' ) ),
				array( 'value' => Log_Export_Service::LOG_TYPE_AI_RUNS, 'label' => __( 'AI runs', 'aio-page-builder' ) ),
			),
		);
	}

	/**
	 * Queue tab payload: recent jobs by status (pending, running, failed, completed). Row-to-plan via related_object_refs.
	 *
	 * @return list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}>
	 */
	public function build_queue_tab(): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$statuses = array( 'pending', 'running', 'retrying', 'failed', 'completed', 'cancelled' );
		$all      = array();
		$per      = (int) ceil( self::QUEUE_LIMIT / count( $statuses ) );
		foreach ( $statuses as $status ) {
			$rows = $this->job_queue_repository->list_by_status( $status, $per, 0 );
			foreach ( $rows as $row ) {
				$all[] = $this->normalize_queue_row( $row );
			}
		}
		usort( $all, function ( $a, $b ) {
			return strcmp( (string) ( $b['created_at'] ?? '' ), (string) ( $a['created_at'] ?? '' ) );
		} );
		return array_slice( $all, 0, self::QUEUE_LIMIT );
	}

	/**
	 * Execution logs: completed/failed jobs with timestamps (redacted; no raw payloads).
	 *
	 * @return list<array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}>
	 */
	public function build_execution_logs(): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::EXECUTION_LIMIT, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::EXECUTION_LIMIT, 0 );
		$rows      = array_merge( $completed, $failed );
		$out       = array();
		foreach ( $rows as $row ) {
			$out[] = $this->normalize_queue_row( $row );
		}
		usort( $out, function ( $a, $b ) {
			return strcmp( (string) ( $b['completed_at'] ?? $b['created_at'] ?? '' ), (string) ( $a['completed_at'] ?? $a['created_at'] ?? '' ) );
		} );
		return array_slice( $out, 0, self::EXECUTION_LIMIT );
	}

	/**
	 * AI Runs tab: recent runs for linking (run_id, status, created_at). No raw prompts.
	 *
	 * @return list<array{run_id: string, status: string, created_at: string}>
	 */
	public function build_ai_runs_tab(): array {
		if ( $this->ai_run_repository === null || ! method_exists( $this->ai_run_repository, 'list_recent' ) ) {
			return array();
		}
		$runs = $this->ai_run_repository->list_recent( self::AI_RUNS_LIMIT, 0 );
		$out  = array();
		foreach ( $runs as $run ) {
			$meta   = $run['run_metadata'] ?? array();
			$run_id = (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' );
			$out[]  = array(
				'run_id'     => $run_id,
				'status'     => (string) ( $run['status'] ?? '' ),
				'created_at' => (string) ( $meta['created_at'] ?? '' ),
			);
		}
		return $out;
	}

	/**
	 * Reporting logs: REPORTING_LOG option entries (event_type, attempted_at, delivery_status, log_reference, failure_reason).
	 *
	 * @return list<array{event_type: string, dedupe_key: string, attempted_at: string, delivery_status: string, log_reference: string, failure_reason: string}>
	 */
	public function build_reporting_logs(): array {
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			return array();
		}
		$log   = array_slice( $log, -self::REPORTING_LOG_CAP );
		$out   = array();
		foreach ( $log as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$out[] = array(
				'event_type'      => (string) ( $entry['event_type'] ?? '' ),
				'dedupe_key'      => (string) ( $entry['dedupe_key'] ?? '' ),
				'attempted_at'    => (string) ( $entry['attempted_at'] ?? '' ),
				'delivery_status' => (string) ( $entry['delivery_status'] ?? '' ),
				'log_reference'   => (string) ( $entry['log_reference'] ?? '' ),
				'failure_reason'  => (string) ( $entry['failure_reason'] ?? '' ),
			);
		}
		return array_reverse( $out );
	}

	/**
	 * Import/Export logs: placeholder (no storage yet). Stable shape for future.
	 *
	 * @return list<array{id: string, type: string, created_at: string, status: string}>
	 */
	public function build_import_export_logs(): array {
		return array();
	}

	/**
	 * Critical errors: reporting log entries that are developer_error_report and failed.
	 *
	 * @return list<array{event_type: string, attempted_at: string, delivery_status: string, failure_reason: string, log_reference: string}>
	 */
	public function build_critical_errors(): array {
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
				'event_type'      => (string) ( $entry['event_type'] ?? '' ),
				'attempted_at'    => (string) ( $entry['attempted_at'] ?? '' ),
				'delivery_status' => (string) ( $entry['delivery_status'] ?? '' ),
				'failure_reason'  => (string) ( $entry['failure_reason'] ?? '' ),
				'log_reference'   => (string) ( $entry['log_reference'] ?? '' ),
			);
			if ( count( $out ) >= self::CRITICAL_ERRORS_CAP ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string}
	 */
	private function normalize_queue_row( array $row ): array {
		$related = (string) ( $row['related_object_refs'] ?? '' );
		$plan_id = '';
		if ( $related !== '' && preg_match( '/plan[_\s]?id[=:]\s*([a-zA-Z0-9_-]+)/i', $related, $m ) ) {
			$plan_id = $m[1];
		} elseif ( $related !== '' ) {
			$plan_id = trim( substr( $related, 0, 64 ) );
		}
		return array(
			'job_ref'        => (string) ( $row['job_ref'] ?? '' ),
			'job_type'       => (string) ( $row['job_type'] ?? '' ),
			'queue_status'   => (string) ( $row['queue_status'] ?? '' ),
			'created_at'     => (string) ( $row['created_at'] ?? '' ),
			'completed_at'   => (string) ( $row['completed_at'] ?? '' ),
			'failure_reason' => (string) ( $row['failure_reason'] ?? '' ),
			'related_plan_id' => $plan_id,
		);
	}
}
