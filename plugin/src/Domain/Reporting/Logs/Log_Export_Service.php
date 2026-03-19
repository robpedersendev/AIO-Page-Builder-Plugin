<?php
/**
 * Exports authorized log categories in structured format with filtering and redaction (spec §48.10, §45.5, §45.9).
 *
 * Permission checks are caller's responsibility. No secrets in output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Logs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Reporting\Contracts\Reporting_Event_Types;
use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Logger_Interface;
use AIOPageBuilder\Support\Logging\Log_Severities;

/**
 * Structured log export: approved categories, date/plan/run/job filters, redaction, labeled JSON to controlled path.
 */
final class Log_Export_Service {

	/** Approved log type keys for export. */
	public const LOG_TYPE_QUEUE              = 'queue';
	public const LOG_TYPE_EXECUTION          = 'execution';
	public const LOG_TYPE_REPORTING          = 'reporting';
	public const LOG_TYPE_CRITICAL           = 'critical';
	public const LOG_TYPE_AI_RUNS            = 'ai_runs';
	public const LOG_TYPE_TEMPLATE_FAMILY    = 'template_family';
	public const LOG_TYPE_TEMPLATE_OPERATION = 'template_operation';

	/** @var array<int, string> */
	public const ALLOWED_LOG_TYPES = array(
		self::LOG_TYPE_QUEUE,
		self::LOG_TYPE_EXECUTION,
		self::LOG_TYPE_REPORTING,
		self::LOG_TYPE_CRITICAL,
		self::LOG_TYPE_AI_RUNS,
		self::LOG_TYPE_TEMPLATE_FAMILY,
		self::LOG_TYPE_TEMPLATE_OPERATION,
	);

	/** Job types considered template-related for template_operation / template_family export (spec §48.10, Prompt 198). */
	private const TEMPLATE_RELATED_JOB_TYPES = array( 'create_page', 'replace_page' );

	private const EXPORT_CAP = 500;

	/** @var Plugin_Path_Manager */
	private Plugin_Path_Manager $path_manager;

	/** @var Reporting_Redaction_Service */
	private Reporting_Redaction_Service $redaction;

	/** @var Logger_Interface|null */
	private ?Logger_Interface $logger;

	/** @var object|null Job queue repository (list_by_status). */
	private $job_queue_repository;

	/** @var object|null AI run repository (list_recent). */
	private $ai_run_repository;

	public function __construct(
		Plugin_Path_Manager $path_manager,
		Reporting_Redaction_Service $redaction,
		?Logger_Interface $logger = null,
		?object $job_queue_repository = null,
		?object $ai_run_repository = null
	) {
		$this->path_manager         = $path_manager;
		$this->redaction            = $redaction;
		$this->logger               = $logger;
		$this->job_queue_repository = $job_queue_repository;
		$this->ai_run_repository    = $ai_run_repository;
	}

	/**
	 * Exports requested log types with filters. Redacts before writing. Writes to plugin exports path.
	 *
	 * @param array<int, string>   $log_types Allowed keys from ALLOWED_LOG_TYPES.
	 * @param array<string, mixed> $filters Optional: date_from, date_to (Y-m-d), plan_id, run_id, job_ref.
	 * @return Log_Export_Result
	 */
	public function export( array $log_types, array $filters = array() ): Log_Export_Result {
		$log_ref   = 'log-export-' . gmdate( 'Y-m-d\TH:i:s\Z' );
		$requested = array_values( array_intersect( $log_types, self::ALLOWED_LOG_TYPES ) );
		if ( $requested === array() ) {
			$this->log( 'Log export skipped: no valid log types requested.', array( 'log_ref' => $log_ref ), Log_Severities::WARNING );
			return Log_Export_Result::failure( __( 'No valid log types selected.', 'aio-page-builder' ), $log_ref );
		}

		$exports_dir = $this->path_manager->get_exports_dir();
		if ( $exports_dir === '' ) {
			$this->log( 'Log export failed: exports directory unavailable.', array( 'log_ref' => $log_ref ), Log_Severities::ERROR );
			return Log_Export_Result::failure( __( 'Exports directory unavailable.', 'aio-page-builder' ), $log_ref );
		}
		if ( ! $this->path_manager->ensure_child( Plugin_Path_Manager::CHILD_EXPORTS ) ) {
			$this->log( 'Log export failed: could not ensure exports directory.', array( 'log_ref' => $log_ref ), Log_Severities::ERROR );
			return Log_Export_Result::failure( __( 'Could not create exports directory.', 'aio-page-builder' ), $log_ref );
		}

		$filter_summary = $this->normalize_filter_summary( $filters );
		$payload        = array(
			'export_metadata'    => array(
				'export_timestamp'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'exported_log_types' => $requested,
				'filter_summary'     => $filter_summary,
				'redaction_applied'  => true,
				'label'              => 'AIO Page Builder log export',
			),
			'queue'              => array(),
			'execution'          => array(),
			'reporting'          => array(),
			'critical'           => array(),
			'ai_runs'            => array(),
			'template_family'    => array(),
			'template_operation' => array(),
		);

		if ( in_array( self::LOG_TYPE_QUEUE, $requested, true ) ) {
			$payload['queue'] = $this->collect_queue_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_EXECUTION, $requested, true ) ) {
			$payload['execution'] = $this->collect_execution_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_REPORTING, $requested, true ) ) {
			$payload['reporting'] = $this->collect_reporting_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_CRITICAL, $requested, true ) ) {
			$payload['critical'] = $this->collect_critical_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_AI_RUNS, $requested, true ) ) {
			$payload['ai_runs'] = $this->collect_ai_runs_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_TEMPLATE_FAMILY, $requested, true ) ) {
			$payload['template_family'] = $this->collect_template_family_rows( $filter_summary );
		}
		if ( in_array( self::LOG_TYPE_TEMPLATE_OPERATION, $requested, true ) ) {
			$payload['template_operation'] = $this->collect_template_operation_rows( $filter_summary );
		}

		$filename = 'aio-log-export-' . gmdate( 'Ymd-His' ) . '.json';
		$path     = rtrim( $exports_dir, '/\\' ) . '/' . $filename;
		$json     = \wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		if ( $json === false || file_put_contents( $path, $json ) === false ) {
			$this->log( 'Log export failed: could not write file.', array( 'log_ref' => $log_ref ), Log_Severities::ERROR );
			return Log_Export_Result::failure( __( 'Could not write export file.', 'aio-page-builder' ), $log_ref );
		}

		$this->log(
			'Log export completed.',
			array(
				'log_ref'  => $log_ref,
				'filename' => $filename,
				'types'    => $requested,
			)
		);
		return Log_Export_Result::success( $requested, $filter_summary, $filename, $log_ref );
	}

	/**
	 * @param array<string, mixed> $filters
	 * @return array<string, mixed>
	 */
	private function normalize_filter_summary( array $filters ): array {
		$out = array();
		if ( ! empty( $filters['date_from'] ) && is_string( $filters['date_from'] ) ) {
			$out['date_from'] = \sanitize_text_field( $filters['date_from'] );
		}
		if ( ! empty( $filters['date_to'] ) && is_string( $filters['date_to'] ) ) {
			$out['date_to'] = \sanitize_text_field( $filters['date_to'] );
		}
		if ( ! empty( $filters['plan_id'] ) && is_string( $filters['plan_id'] ) ) {
			$out['plan_id'] = \sanitize_text_field( $filters['plan_id'] );
		}
		if ( ! empty( $filters['run_id'] ) && is_string( $filters['run_id'] ) ) {
			$out['run_id'] = \sanitize_text_field( $filters['run_id'] );
		}
		if ( ! empty( $filters['job_ref'] ) && is_string( $filters['job_ref'] ) ) {
			$out['job_ref'] = \sanitize_text_field( $filters['job_ref'] );
		}
		if ( ! empty( $filters['template_family'] ) && is_string( $filters['template_family'] ) ) {
			$out['template_family'] = \sanitize_text_field( $filters['template_family'] );
		}
		if ( ! empty( $filters['template_operation'] ) && is_string( $filters['template_operation'] ) ) {
			$out['template_operation'] = \sanitize_text_field( $filters['template_operation'] );
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_queue_rows( array $filter_summary ): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$statuses = array( 'pending', 'running', 'retrying', 'failed', 'completed', 'cancelled' );
		$all      = array();
		$per      = (int) ceil( self::EXPORT_CAP / count( $statuses ) );
		foreach ( $statuses as $status ) {
			$rows = $this->job_queue_repository->list_by_status( $status, $per, 0 );
			foreach ( $rows as $row ) {
				$normalized = $this->normalize_queue_row( $row );
				if ( $this->row_passes_filters( $normalized, $filter_summary, 'queue' ) ) {
					$all[] = $this->redact_queue_row( $normalized );
				}
			}
		}
		usort(
			$all,
			function ( $a, $b ) {
				return strcmp( (string) ( $b['created_at'] ?? '' ), (string) ( $a['created_at'] ?? '' ) );
			}
		);
		return array_slice( $all, 0, self::EXPORT_CAP );
	}

	/**
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_execution_rows( array $filter_summary ): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::EXPORT_CAP, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::EXPORT_CAP, 0 );
		$out       = array();
		foreach ( array_merge( $completed, $failed ) as $row ) {
			$normalized = $this->normalize_queue_row( $row );
			if ( $this->row_passes_filters( $normalized, $filter_summary, 'execution' ) ) {
				$out[] = $this->redact_queue_row( $normalized );
			}
		}
		usort(
			$out,
			function ( $a, $b ) {
				return strcmp( (string) ( $b['completed_at'] ?? $b['created_at'] ?? '' ), (string) ( $a['completed_at'] ?? $a['created_at'] ?? '' ) );
			}
		);
		return array_slice( $out, 0, self::EXPORT_CAP );
	}

	/**
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_reporting_rows( array $filter_summary ): array {
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			return array();
		}
		$log = array_slice( $log, -self::EXPORT_CAP );
		$out = array();
		foreach ( array_reverse( $log ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$row = array(
				'event_type'      => (string) ( $entry['event_type'] ?? '' ),
				'dedupe_key'      => (string) ( $entry['dedupe_key'] ?? '' ),
				'attempted_at'    => (string) ( $entry['attempted_at'] ?? '' ),
				'delivery_status' => (string) ( $entry['delivery_status'] ?? '' ),
				'log_reference'   => (string) ( $entry['log_reference'] ?? '' ),
				'failure_reason'  => (string) ( $entry['failure_reason'] ?? '' ),
			);
			if ( $this->row_passes_filters( $row, $filter_summary, 'reporting' ) ) {
				$row['failure_reason'] = $this->redaction->redact_message( $row['failure_reason'] );
				$out[]                 = $row;
			}
		}
		return array_slice( $out, 0, self::EXPORT_CAP );
	}

	/**
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_critical_rows( array $filter_summary ): array {
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
			$row = array(
				'event_type'      => (string) ( $entry['event_type'] ?? '' ),
				'attempted_at'    => (string) ( $entry['attempted_at'] ?? '' ),
				'delivery_status' => (string) ( $entry['delivery_status'] ?? '' ),
				'failure_reason'  => (string) ( $entry['failure_reason'] ?? '' ),
				'log_reference'   => (string) ( $entry['log_reference'] ?? '' ),
			);
			if ( $this->row_passes_filters( $row, $filter_summary, 'critical' ) ) {
				$row['failure_reason'] = $this->redaction->redact_message( $row['failure_reason'] );
				$out[]                 = $row;
			}
			if ( count( $out ) >= self::EXPORT_CAP ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Template-operation rows: execution-style rows for create_page/replace_page only (spec §48.10, Prompt 198).
	 *
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_template_operation_rows( array $filter_summary ): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::EXPORT_CAP, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::EXPORT_CAP, 0 );
		$out       = array();
		foreach ( array_merge( $completed, $failed ) as $row ) {
			$normalized = $this->normalize_queue_row( $row );
			if ( ! in_array( $normalized['job_type'], self::TEMPLATE_RELATED_JOB_TYPES, true ) ) {
				continue;
			}
			if ( $this->row_passes_filters( $normalized, $filter_summary, 'template_operation' ) ) {
				$redacted                    = $this->redact_queue_row( $normalized );
				$redacted['template_key']    = $normalized['template_key'];
				$redacted['template_family'] = $normalized['template_family'];
				$out[]                       = $redacted;
			}
		}
		usort(
			$out,
			function ( $a, $b ) {
				return strcmp( (string) ( $b['completed_at'] ?? $b['created_at'] ?? '' ), (string) ( $a['completed_at'] ?? $a['created_at'] ?? '' ) );
			}
		);
		return array_slice( $out, 0, self::EXPORT_CAP );
	}

	/**
	 * Template-family rows: same as template_operation but optionally filtered by template_family (spec §48.10, Prompt 198).
	 *
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_template_family_rows( array $filter_summary ): array {
		if ( $this->job_queue_repository === null || ! method_exists( $this->job_queue_repository, 'list_by_status' ) ) {
			return array();
		}
		$completed = $this->job_queue_repository->list_by_status( 'completed', self::EXPORT_CAP, 0 );
		$failed    = $this->job_queue_repository->list_by_status( 'failed', self::EXPORT_CAP, 0 );
		$out       = array();
		foreach ( array_merge( $completed, $failed ) as $row ) {
			$normalized = $this->normalize_queue_row( $row );
			if ( ! in_array( $normalized['job_type'], self::TEMPLATE_RELATED_JOB_TYPES, true ) ) {
				continue;
			}
			if ( $this->row_passes_filters( $normalized, $filter_summary, 'template_family' ) ) {
				$redacted                    = $this->redact_queue_row( $normalized );
				$redacted['template_key']    = $normalized['template_key'];
				$redacted['template_family'] = $normalized['template_family'];
				$out[]                       = $redacted;
			}
		}
		usort(
			$out,
			function ( $a, $b ) {
				return strcmp( (string) ( $b['completed_at'] ?? $b['created_at'] ?? '' ), (string) ( $a['completed_at'] ?? $a['created_at'] ?? '' ) );
			}
		);
		return array_slice( $out, 0, self::EXPORT_CAP );
	}

	/**
	 * @param array<string, mixed> $filter_summary
	 * @return array<int, array<string, string>>
	 */
	private function collect_ai_runs_rows( array $filter_summary ): array {
		if ( $this->ai_run_repository === null || ! method_exists( $this->ai_run_repository, 'list_recent' ) ) {
			return array();
		}
		$runs = $this->ai_run_repository->list_recent( self::EXPORT_CAP, 0 );
		$out  = array();
		foreach ( $runs as $run ) {
			$meta   = $run['run_metadata'] ?? array();
			$run_id = (string) ( $run['internal_key'] ?? $run['post_title'] ?? '' );
			$row    = array(
				'run_id'     => $run_id,
				'status'     => (string) ( $run['status'] ?? '' ),
				'created_at' => (string) ( $meta['created_at'] ?? '' ),
			);
			if ( $this->row_passes_filters( $row, $filter_summary, 'ai_runs' ) ) {
				$out[] = $row;
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array{job_ref: string, job_type: string, queue_status: string, created_at: string, completed_at: string, failure_reason: string, related_plan_id: string, template_key: string, template_family: string}
	 */
	private function normalize_queue_row( array $row ): array {
		$related = (string) ( $row['related_object_refs'] ?? '' );
		$plan_id = '';
		if ( $related !== '' && preg_match( '/plan[_\s]?id[=:]\s*([a-zA-Z0-9_-]+)/i', $related, $m ) ) {
			$plan_id = $m[1];
		} elseif ( $related !== '' ) {
			$plan_id = trim( substr( $related, 0, 64 ) );
		}
		$template_key    = '';
		$template_family = '';
		if ( $related !== '' ) {
			$decoded = json_decode( $related, true );
			if ( is_array( $decoded ) ) {
				$target = $decoded['target_reference'] ?? $decoded['target'] ?? $decoded;
				if ( is_array( $target ) ) {
					$template_key    = (string) ( $target['template_key'] ?? $target['template_ref'] ?? '' );
					$template_family = (string) ( $target['template_family'] ?? '' );
				}
			}
		}
		return array(
			'job_ref'         => (string) ( $row['job_ref'] ?? '' ),
			'job_type'        => (string) ( $row['job_type'] ?? '' ),
			'queue_status'    => (string) ( $row['queue_status'] ?? '' ),
			'created_at'      => (string) ( $row['created_at'] ?? '' ),
			'completed_at'    => (string) ( $row['completed_at'] ?? '' ),
			'failure_reason'  => (string) ( $row['failure_reason'] ?? '' ),
			'related_plan_id' => $plan_id,
			'template_key'    => $template_key,
			'template_family' => $template_family,
		);
	}

	/**
	 * @param array<string, string> $row
	 * @return array<string, string>
	 */
	private function redact_queue_row( array $row ): array {
		if ( isset( $row['failure_reason'] ) && $row['failure_reason'] !== '' ) {
			$row['failure_reason'] = $this->redaction->redact_message( $row['failure_reason'] );
		}
		return $row;
	}

	/**
	 * @param array<string, string> $row
	 * @param array<string, mixed>  $filter_summary
	 * @param string                $family queue|execution|reporting|critical|ai_runs|template_operation|template_family
	 * @return bool
	 */
	private function row_passes_filters( array $row, array $filter_summary, string $family ): bool {
		if ( isset( $filter_summary['date_from'] ) && $filter_summary['date_from'] !== '' ) {
			$ts = $row['created_at'] ?? $row['attempted_at'] ?? '';
			if ( $ts !== '' && strcmp( substr( $ts, 0, 10 ), $filter_summary['date_from'] ) < 0 ) {
				return false;
			}
		}
		if ( isset( $filter_summary['date_to'] ) && $filter_summary['date_to'] !== '' ) {
			$ts = $row['created_at'] ?? $row['attempted_at'] ?? '';
			if ( $ts !== '' && strcmp( substr( $ts, 0, 10 ), $filter_summary['date_to'] ) > 0 ) {
				return false;
			}
		}
		if ( isset( $filter_summary['plan_id'] ) && $filter_summary['plan_id'] !== '' && ( $family === 'queue' || $family === 'execution' || $family === 'template_operation' || $family === 'template_family' ) ) {
			if ( (string) ( $row['related_plan_id'] ?? '' ) !== $filter_summary['plan_id'] ) {
				return false;
			}
		}
		if ( isset( $filter_summary['job_ref'] ) && $filter_summary['job_ref'] !== '' && ( $family === 'queue' || $family === 'execution' || $family === 'template_operation' || $family === 'template_family' ) ) {
			if ( (string) ( $row['job_ref'] ?? '' ) !== $filter_summary['job_ref'] ) {
				return false;
			}
		}
		if ( isset( $filter_summary['run_id'] ) && $filter_summary['run_id'] !== '' && $family === 'ai_runs' ) {
			if ( (string) ( $row['run_id'] ?? '' ) !== $filter_summary['run_id'] ) {
				return false;
			}
		}
		if ( isset( $filter_summary['template_family'] ) && $filter_summary['template_family'] !== '' && $family === 'template_family' ) {
			if ( (string) ( $row['template_family'] ?? '' ) !== $filter_summary['template_family'] ) {
				return false;
			}
		}
		if ( isset( $filter_summary['template_operation'] ) && $filter_summary['template_operation'] !== '' && ( $family === 'template_operation' || $family === 'template_family' ) ) {
			if ( (string) ( $row['job_type'] ?? '' ) !== $filter_summary['template_operation'] ) {
				return false;
			}
		}
		return true;
	}

	private function log( string $message, array $context, string $severity = Log_Severities::INFO ): void {
		if ( $this->logger === null ) {
			return;
		}
		$ref = $context !== array() ? \wp_json_encode( $context ) : '';
		if ( $ref === false ) {
			$ref = '';
		}
		$record = new Error_Record(
			$context['log_ref'] ?? 'log-export-' . uniqid( '', true ),
			Log_Categories::IMPORT_EXPORT,
			$severity,
			$message,
			gmdate( 'c' ),
			'',
			'',
			'',
			$ref
		);
		$this->logger->log( $record );
	}
}
