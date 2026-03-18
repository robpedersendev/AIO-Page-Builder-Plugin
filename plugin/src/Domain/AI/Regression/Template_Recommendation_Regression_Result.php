<?php
/**
 * Machine-readable result of a template-recommendation regression run (spec §58.3, §60.5, Prompt 211).
 * Stable payload: template_recommendation_regression_result. Internal QA only; no secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Regression;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result: pass/fail/regression with class fit, family fit, CTA-law alignment, explanation fit.
 */
final class Template_Recommendation_Regression_Result {

	public const OUTCOME_PASS       = 'pass';
	public const OUTCOME_FAIL       = 'fail';
	public const OUTCOME_REGRESSION = 'regression';

	/** @var string */
	private string $outcome;

	/** @var array{case_id: string, scenario: string, fixture_version: string, ran_at: string} */
	private array $regression_run;

	/** @var bool */
	private bool $class_fit;

	/** @var bool */
	private bool $family_fit;

	/** @var bool|null True when CTA-law aligned, false when not, null when not checked. */
	private ?bool $cta_law_aligned;

	/** @var bool */
	private bool $explanation_fit;

	/** @var string */
	private string $message;

	/** @var array<string, mixed> Optional details (diffs, mismatches). */
	private array $details;

	/**
	 * @param string    $outcome
	 * @param array     $regression_run
	 * @param bool      $class_fit
	 * @param bool      $family_fit
	 * @param bool|null $cta_law_aligned
	 * @param bool      $explanation_fit
	 * @param string    $message
	 * @param array     $details
	 */
	public function __construct(
		string $outcome,
		array $regression_run,
		bool $class_fit,
		bool $family_fit,
		?bool $cta_law_aligned,
		bool $explanation_fit,
		string $message,
		array $details = array()
	) {
		$this->outcome         = $outcome;
		$this->regression_run  = $regression_run;
		$this->class_fit       = $class_fit;
		$this->family_fit      = $family_fit;
		$this->cta_law_aligned = $cta_law_aligned;
		$this->explanation_fit = $explanation_fit;
		$this->message         = $message;
		$this->details         = $details;
	}

	public function get_outcome(): string {
		return $this->outcome;
	}

	public function is_pass(): bool {
		return $this->outcome === self::OUTCOME_PASS;
	}

	/** @return array{case_id: string, scenario: string, fixture_version: string, ran_at: string} */
	public function get_regression_run(): array {
		return $this->regression_run;
	}

	public function get_class_fit(): bool {
		return $this->class_fit;
	}

	public function get_family_fit(): bool {
		return $this->family_fit;
	}

	public function get_cta_law_aligned(): ?bool {
		return $this->cta_law_aligned;
	}

	public function get_explanation_fit(): bool {
		return $this->explanation_fit;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return array<string, mixed> */
	public function get_details(): array {
		return $this->details;
	}

	/**
	 * Full payload for logging or report (template_recommendation_regression_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'outcome'         => $this->outcome,
			'regression_run'  => $this->regression_run,
			'class_fit'       => $this->class_fit,
			'family_fit'      => $this->family_fit,
			'cta_law_aligned' => $this->cta_law_aligned,
			'explanation_fit' => $this->explanation_fit,
			'message'         => $this->message,
			'details'         => $this->details,
		);
	}
}
