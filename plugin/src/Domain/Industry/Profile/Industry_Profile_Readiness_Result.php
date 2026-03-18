<?php
/**
 * Immutable readiness/completeness result for Industry Profile (industry-profile-validation-contract.md).
 * State, score, validation errors/warnings, and explainable details for downstream consumers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Readiness result: state, score 0-100, validation errors/warnings, details. Admin-safe; bounded.
 */
final class Industry_Profile_Readiness_Result {

	public const STATE_NONE    = 'none';
	public const STATE_MINIMAL = 'minimal';
	public const STATE_PARTIAL = 'partial';
	public const STATE_READY   = 'ready';

	public const SCORE_NONE    = 0;
	public const SCORE_MINIMAL = 25;
	public const SCORE_PARTIAL = 60;
	public const SCORE_READY   = 100;

	/** @var string */
	private string $state;

	/** @var int */
	private int $score;

	/** @var array<int, string> */
	private array $validation_errors;

	/** @var array<int, string> */
	private array $validation_warnings;

	/** @var array<string, mixed> */
	private array $details;

	/**
	 * @param string               $state              One of STATE_NONE, STATE_MINIMAL, STATE_PARTIAL, STATE_READY.
	 * @param int                  $score              0-100.
	 * @param array<int, string>   $validation_errors  List of error messages.
	 * @param array<int, string>   $validation_warnings List of warning messages.
	 * @param array<string, mixed> $details            Explainable breakdown (e.g. primary_set, question_pack_complete).
	 */
	public function __construct(
		string $state,
		int $score,
		array $validation_errors = array(),
		array $validation_warnings = array(),
		array $details = array()
	) {
		$this->state               = $state;
		$this->score               = max( 0, min( 100, $score ) );
		$this->validation_errors   = $validation_errors;
		$this->validation_warnings = $validation_warnings;
		$this->details             = $details;
	}

	public function get_state(): string {
		return $this->state;
	}

	public function get_score(): int {
		return $this->score;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_validation_errors(): array {
		return $this->validation_errors;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_validation_warnings(): array {
		return $this->validation_warnings;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_details(): array {
		return $this->details;
	}

	public function is_ready(): bool {
		return $this->state === self::STATE_READY;
	}

	public function has_errors(): bool {
		return $this->validation_errors !== array();
	}

	/**
	 * Machine-readable shape for APIs and logging.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'state'               => $this->state,
			'score'               => $this->score,
			'validation_errors'   => $this->validation_errors,
			'validation_warnings' => $this->validation_warnings,
			'details'             => $this->details,
		);
	}
}
