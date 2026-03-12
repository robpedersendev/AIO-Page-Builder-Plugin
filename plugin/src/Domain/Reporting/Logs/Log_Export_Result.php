<?php
/**
 * Result of a log export run (spec §48.10, §45.5, §45.9).
 *
 * Stable payload shape for UI and audit; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Logs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable log export result: success, exported types, filter summary, redaction flag, file reference, log reference.
 */
final class Log_Export_Result {

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var list<string> Log types included in the export (e.g. queue, execution, reporting, critical, ai_runs). */
	private array $exported_log_types;

	/** @var array<string, mixed> Filter parameters applied (e.g. date_from, date_to, plan_id). */
	private array $filter_summary;

	/** @var bool Whether redaction was applied before export. */
	private bool $redaction_applied;

	/** @var string Safe file reference (filename only; no server path). */
	private string $export_file_reference;

	/** @var string Log reference for this export attempt/result. */
	private string $export_log_reference;

	/**
	 * @param bool         $success
	 * @param string       $message
	 * @param list<string> $exported_log_types
	 * @param array<string, mixed> $filter_summary
	 * @param bool         $redaction_applied
	 * @param string       $export_file_reference
	 * @param string       $export_log_reference
	 */
	public function __construct(
		bool $success,
		string $message,
		array $exported_log_types,
		array $filter_summary,
		bool $redaction_applied,
		string $export_file_reference,
		string $export_log_reference
	) {
		$this->success                = $success;
		$this->message                = $message;
		$this->exported_log_types     = $exported_log_types;
		$this->filter_summary         = $filter_summary;
		$this->redaction_applied      = $redaction_applied;
		$this->export_file_reference  = $export_file_reference;
		$this->export_log_reference   = $export_log_reference;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return list<string> */
	public function get_exported_log_types(): array {
		return $this->exported_log_types;
	}

	/** @return array<string, mixed> */
	public function get_filter_summary(): array {
		return $this->filter_summary;
	}

	public function is_redaction_applied(): bool {
		return $this->redaction_applied;
	}

	public function get_export_file_reference(): string {
		return $this->export_file_reference;
	}

	public function get_export_log_reference(): string {
		return $this->export_log_reference;
	}

	/**
	 * Payload for UI/API (no secrets).
	 *
	 * @return array{success: bool, message: string, exported_log_types: list<string>, filter_summary: array, redaction_applied: bool, export_file_reference: string, export_log_reference: string}
	 */
	public function to_payload(): array {
		return array(
			'success'                => $this->success,
			'message'                => $this->message,
			'exported_log_types'     => $this->exported_log_types,
			'filter_summary'         => $this->filter_summary,
			'redaction_applied'      => $this->redaction_applied,
			'export_file_reference' => $this->export_file_reference,
			'export_log_reference'   => $this->export_log_reference,
		);
	}

	/**
	 * @param list<string> $exported_log_types
	 * @param array<string, mixed> $filter_summary
	 * @param string       $export_file_reference
	 * @param string       $export_log_reference
	 * @return self
	 */
	public static function success(
		array $exported_log_types,
		array $filter_summary,
		string $export_file_reference,
		string $export_log_reference
	): self {
		return new self(
			true,
			__( 'Log export completed successfully.', 'aio-page-builder' ),
			$exported_log_types,
			$filter_summary,
			true,
			$export_file_reference,
			$export_log_reference
		);
	}

	/**
	 * @param string $message
	 * @param string $export_log_reference
	 * @return self
	 */
	public static function failure( string $message, string $export_log_reference = '' ): self {
		return new self(
			false,
			$message,
			array(),
			array(),
			false,
			'',
			$export_log_reference
		);
	}
}
