<?php
/**
 * Machine-readable validation report for AI output (spec §28.11–28.14, ai-output-validation-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object. No secrets; server-side only. Redact sensitive values from user-facing summaries.
 */
final class Validation_Report {

	public const RAW_CAPTURE_OK    = 'ok';
	public const RAW_CAPTURE_EMPTY = 'empty';
	public const RAW_CAPTURE_ERROR = 'error';

	public const PARSE_OK    = 'ok';
	public const PARSE_FAILED = 'failed';

	public const STATE_PASSED  = 'passed';
	public const STATE_PARTIAL = 'partial';
	public const STATE_FAILED  = 'failed';

	/** @var string */
	private string $raw_capture_status;

	/** @var string */
	private string $parse_status;

	/** @var bool */
	private bool $top_level_valid;

	/** @var string */
	private string $schema_ref;

	/** @var array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}> */
	private array $record_validation_results;

	/** @var array<int, Dropped_Record_Report> */
	private array $dropped_records;

	/** @var array<string, mixed>|null */
	private ?array $normalized_output;

	/** @var string */
	private string $final_validation_state;

	/** @var string|null */
	private ?string $blocking_failure_stage;

	/** @var bool */
	private bool $repair_attempted;

	/** @var bool */
	private bool $repair_succeeded;

	/**
	 * @param string                                                                 $raw_capture_status   ok|empty|error.
	 * @param string                                                                 $parse_status         ok|failed.
	 * @param bool                                                                   $top_level_valid     True if all required top-level sections exist and correct type.
	 * @param string                                                                 $schema_ref           Schema reference used.
	 * @param array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}> $record_validation_results Per-section/item results.
	 * @param array<int, Dropped_Record_Report>                                      $dropped_records      When partial: dropped record reports.
	 * @param array<string, mixed>|null                                              $normalized_output    Populated only when state allows handoff; else null.
	 * @param string                                                                 $final_validation_state passed|partial|failed.
	 * @param string|null                                                            $blocking_failure_stage When failed: stage at which validation failed.
	 * @param bool                                                                   $repair_attempted     Whether a repair attempt was invoked.
	 * @param bool                                                                   $repair_succeeded     If repair attempted, whether it produced valid output.
	 */
	public function __construct(
		string $raw_capture_status,
		string $parse_status,
		bool $top_level_valid,
		string $schema_ref,
		array $record_validation_results,
		array $dropped_records,
		?array $normalized_output,
		string $final_validation_state,
		?string $blocking_failure_stage,
		bool $repair_attempted,
		bool $repair_succeeded
	) {
		$this->raw_capture_status         = $raw_capture_status;
		$this->parse_status               = $parse_status;
		$this->top_level_valid            = $top_level_valid;
		$this->schema_ref                 = $schema_ref;
		$this->record_validation_results  = $record_validation_results;
		$this->dropped_records            = $dropped_records;
		$this->normalized_output          = $normalized_output;
		$this->final_validation_state     = $final_validation_state;
		$this->blocking_failure_stage     = $blocking_failure_stage;
		$this->repair_attempted           = $repair_attempted;
		$this->repair_succeeded           = $repair_succeeded;
	}

	public function get_raw_capture_status(): string {
		return $this->raw_capture_status;
	}

	public function get_parse_status(): string {
		return $this->parse_status;
	}

	public function is_top_level_valid(): bool {
		return $this->top_level_valid;
	}

	public function get_schema_ref(): string {
		return $this->schema_ref;
	}

	/**
	 * @return array<int, array{section: string, index?: int, valid: bool, errors: array<int, string>}>
	 */
	public function get_record_validation_results(): array {
		return $this->record_validation_results;
	}

	/**
	 * @return array<int, Dropped_Record_Report>
	 */
	public function get_dropped_records(): array {
		return $this->dropped_records;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function get_normalized_output(): ?array {
		return $this->normalized_output;
	}

	public function get_final_validation_state(): string {
		return $this->final_validation_state;
	}

	public function get_blocking_failure_stage(): ?string {
		return $this->blocking_failure_stage;
	}

	public function is_repair_attempted(): bool {
		return $this->repair_attempted;
	}

	public function is_repair_succeeded(): bool {
		return $this->repair_succeeded;
	}

	/** Whether this report allows Build Plan handoff (passed or partial with normalized output). */
	public function allows_build_plan_handoff(): bool {
		return ( $this->final_validation_state === self::STATE_PASSED || $this->final_validation_state === self::STATE_PARTIAL )
			&& $this->normalized_output !== null;
	}

	/**
	 * Export for logging or API (no secrets).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$dropped = array();
		foreach ( $this->dropped_records as $d ) {
			$dropped[] = $d->to_array();
		}
		return array(
			'raw_capture_status'         => $this->raw_capture_status,
			'parse_status'               => $this->parse_status,
			'top_level_valid'            => $this->top_level_valid,
			'schema_ref'                 => $this->schema_ref,
			'record_validation_results'  => $this->record_validation_results,
			'dropped_records'            => $dropped,
			'normalized_output'          => $this->normalized_output,
			'final_validation_state'     => $this->final_validation_state,
			'blocking_failure_stage'     => $this->blocking_failure_stage,
			'repair_attempted'           => $this->repair_attempted,
			'repair_succeeded'           => $this->repair_succeeded,
		);
	}
}
