<?php
/**
 * UI-safe result of an onboarding planning request (spec §49.8, §59.8).
 * No secrets; safe for redirect params and display.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Result of submit_planning_request: success, validation_failed, or provider_failed.
 * Stable payload for onboarding screen and audit.
 */
final class Planning_Request_Result {

	public const STATUS_SUCCESS          = 'success';
	public const STATUS_VALIDATION_FAILED = 'validation_failed';
	public const STATUS_PROVIDER_FAILED   = 'provider_failed';
	public const STATUS_BLOCKED          = 'blocked';

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $status;

	/** @var string */
	private string $run_id;

	/** @var int */
	private int $run_post_id;

	/** @var string User-safe message for display. */
	private string $user_message;

	/** @var array<string, mixed>|null Validation report (to_array) when validation failed; redacted. */
	private ?array $validation_report;

	/** @var array<string, mixed>|null Normalized error when provider failed; redacted. */
	private ?array $normalized_error;

	/** @var string|null Blocking reason when status is blocked. */
	private ?string $blocking_reason;

	/**
	 * @param bool   $success           Whether the run completed with valid output.
	 * @param string $status            One of STATUS_*.
	 * @param string $run_id            Run internal key (empty when blocked).
	 * @param int    $run_post_id       Run post ID (0 when blocked).
	 * @param string $user_message      User-safe message.
	 * @param array<string, mixed>|null $validation_report When validation failed.
	 * @param array<string, mixed>|null $normalized_error  When provider failed.
	 * @param string|null               $blocking_reason   When blocked.
	 */
	public function __construct(
		bool $success,
		string $status,
		string $run_id,
		int $run_post_id,
		string $user_message,
		?array $validation_report = null,
		?array $normalized_error = null,
		?string $blocking_reason = null
	) {
		$this->success           = $success;
		$this->status            = $status;
		$this->run_id            = $run_id;
		$this->run_post_id       = $run_post_id;
		$this->user_message      = $user_message;
		$this->validation_report = $validation_report;
		$this->normalized_error  = $normalized_error;
		$this->blocking_reason   = $blocking_reason;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_run_id(): string {
		return $this->run_id;
	}

	public function get_run_post_id(): int {
		return $this->run_post_id;
	}

	public function get_user_message(): string {
		return $this->user_message;
	}

	/** @return array<string, mixed>|null */
	public function get_validation_report(): ?array {
		return $this->validation_report;
	}

	/** @return array<string, mixed>|null */
	public function get_normalized_error(): ?array {
		return $this->normalized_error;
	}

	public function get_blocking_reason(): ?string {
		return $this->blocking_reason;
	}

	/**
	 * Array shape for redirect params or API; no secrets.
	 *
	 * @return array{success: bool, status: string, run_id: string, run_post_id: int, user_message: string, validation_report: array|null, normalized_error: array|null, blocking_reason: string|null}
	 */
	public function to_array(): array {
		return array(
			'success'           => $this->success,
			'status'            => $this->status,
			'run_id'            => $this->run_id,
			'run_post_id'       => $this->run_post_id,
			'user_message'      => $this->user_message,
			'validation_report' => $this->validation_report,
			'normalized_error'   => $this->normalized_error,
			'blocking_reason'    => $this->blocking_reason,
		);
	}
}
