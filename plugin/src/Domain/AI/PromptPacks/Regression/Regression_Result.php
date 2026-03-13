<?php
/**
 * Machine-readable result of a prompt-pack regression run (spec §26, §28.11–28.13, §56.2, Prompt 120).
 * Stable payload shapes: regression_run, normalized_output_diff_summary, validator_regression_summary.
 * No secrets; internal QA only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks\Regression;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object: pass/fail/regression outcome and diff summaries.
 */
final class Regression_Result {

	public const OUTCOME_PASS       = 'pass';
	public const OUTCOME_FAIL       = 'fail';
	public const OUTCOME_REGRESSION = 'regression';

	/** @var string */
	private string $outcome;

	/** @var array{run_id: string, prompt_pack_ref: array{internal_key: string, version: string}, schema_ref: string, ran_at: string} */
	private array $regression_run;

	/** @var array{match: bool, added_keys: array, removed_keys: array, value_diffs: array}|null */
	private ?array $normalized_output_diff_summary;

	/** @var array{final_validation_state_match: bool, blocking_stage_match: bool, dropped_count_match: bool, dropped_record_diffs: array} */
	private array $validator_regression_summary;

	/** @var string */
	private string $message;

	/**
	 * @param string                                                                 $outcome    One of OUTCOME_*.
	 * @param array{run_id: string, prompt_pack_ref: array, schema_ref: string, ran_at: string} $regression_run
	 * @param array{match: bool, added_keys: array, removed_keys: array, value_diffs: array}|null $normalized_output_diff_summary
	 * @param array{final_validation_state_match: bool, blocking_stage_match: bool, dropped_count_match: bool, dropped_record_diffs: array} $validator_regression_summary
	 * @param string                                                                 $message    Human-readable summary.
	 */
	public function __construct(
		string $outcome,
		array $regression_run,
		?array $normalized_output_diff_summary,
		array $validator_regression_summary,
		string $message
	) {
		$this->outcome                          = $outcome;
		$this->regression_run                    = $regression_run;
		$this->normalized_output_diff_summary    = $normalized_output_diff_summary;
		$this->validator_regression_summary     = $validator_regression_summary;
		$this->message                           = $message;
	}

	public function get_outcome(): string {
		return $this->outcome;
	}

	public function is_pass(): bool {
		return $this->outcome === self::OUTCOME_PASS;
	}

	/**
	 * @return array{run_id: string, prompt_pack_ref: array, schema_ref: string, ran_at: string}
	 */
	public function get_regression_run(): array {
		return $this->regression_run;
	}

	/**
	 * @return array{match: bool, added_keys: array, removed_keys: array, value_diffs: array}|null
	 */
	public function get_normalized_output_diff_summary(): ?array {
		return $this->normalized_output_diff_summary;
	}

	/**
	 * @return array{final_validation_state_match: bool, blocking_stage_match: bool, dropped_count_match: bool, dropped_record_diffs: array}
	 */
	public function get_validator_regression_summary(): array {
		return $this->validator_regression_summary;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Full payload for logging or report generation (machine-readable).
	 *
	 * @return array{outcome: string, regression_run: array, normalized_output_diff_summary: array|null, validator_regression_summary: array, message: string}
	 */
	public function to_array(): array {
		return array(
			'outcome'                          => $this->outcome,
			'regression_run'                   => $this->regression_run,
			'normalized_output_diff_summary'   => $this->normalized_output_diff_summary,
			'validator_regression_summary'     => $this->validator_regression_summary,
			'message'                          => $this->message,
		);
	}
}
