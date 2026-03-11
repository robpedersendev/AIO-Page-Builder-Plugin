<?php
/**
 * Result of Build Plan generation (spec §30.3). Success, plan payload, omitted report, errors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Generation;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of a single generation run. Caller checks success and uses plan_id or errors.
 */
final class Plan_Generation_Result {

	/** @var bool */
	private $success;

	/** @var string|null Plan ID (internal_key) when success. */
	private $plan_id;

	/** @var int Post ID when success. */
	private $plan_post_id;

	/** @var array<string, mixed> Full plan root payload when success (for example/audit). */
	private $plan_payload;

	/** @var array{omitted: array<int, array<string, mixed>>, count: int} Omitted recommendation report. */
	private $omitted_report;

	/** @var array<int, string> Error messages when ! success. */
	private $errors;

	/**
	 * @param bool                  $success       Whether generation succeeded.
	 * @param string|null           $plan_id       Plan ID when success.
	 * @param int                   $plan_post_id  Plan post ID when success (0 when failed).
	 * @param array<string, mixed>  $plan_payload  Full plan root when success.
	 * @param array{omitted: array<int, array<string, mixed>>, count: int} $omitted_report Omitted report.
	 * @param array<int, string>    $errors        Error messages when failed.
	 */
	public function __construct(
		bool $success,
		?string $plan_id,
		int $plan_post_id,
		array $plan_payload,
		array $omitted_report,
		array $errors = array()
	) {
		$this->success        = $success;
		$this->plan_id        = $plan_id;
		$this->plan_post_id   = $plan_post_id;
		$this->plan_payload   = $plan_payload;
		$this->omitted_report = $omitted_report;
		$this->errors         = $errors;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_plan_id(): ?string {
		return $this->plan_id;
	}

	public function get_plan_post_id(): int {
		return $this->plan_post_id;
	}

	/** @return array<string, mixed> */
	public function get_plan_payload(): array {
		return $this->plan_payload;
	}

	/** @return array{omitted: array<int, array<string, mixed>>, count: int} */
	public function get_omitted_report(): array {
		return $this->omitted_report;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Creates a failed result.
	 *
	 * @param array<int, string> $errors Error messages.
	 * @return self
	 */
	public static function failure( array $errors ): self {
		return new self( false, null, 0, array(), array( 'omitted' => array(), 'count' => 0 ), $errors );
	}
}
