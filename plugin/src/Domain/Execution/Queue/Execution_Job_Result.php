<?php
/**
 * Result DTO for a single queue job execution (spec §40.7, §42.6; Prompt 080).
 *
 * Immutable: job_ref, job_type, status, action_id, plan_item_id, result_summary,
 * retry_count, retry_eligible, failure_reason, completed_at. Used for per-job
 * and aggregate reporting; supports partial-failure and retry metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable job execution result. Convertible to array for logging and API.
 */
final class Execution_Job_Result {

	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED    = 'failed';
	public const STATUS_REFUSED   = 'refused';
	public const STATUS_CANCELLED = 'cancelled';

	/** @var string */
	private $job_ref;

	/** @var string */
	private $job_type;

	/** @var string */
	private $status;

	/** @var string */
	private $action_id;

	/** @var string */
	private $plan_item_id;

	/** @var array<string, mixed> */
	private $result_summary;

	/** @var int */
	private $retry_count;

	/** @var bool */
	private $retry_eligible;

	/** @var string */
	private $failure_reason;

	/** @var string */
	private $completed_at;

	/**
	 * Constructor. Prefer named factory methods.
	 *
	 * @param string               $job_ref
	 * @param string               $job_type
	 * @param string               $status
	 * @param string               $action_id
	 * @param string               $plan_item_id
	 * @param array<string, mixed>  $result_summary
	 * @param int                   $retry_count
	 * @param bool                  $retry_eligible
	 * @param string                $failure_reason
	 * @param string                $completed_at
	 */
	public function __construct(
		string $job_ref,
		string $job_type,
		string $status,
		string $action_id = '',
		string $plan_item_id = '',
		array $result_summary = array(),
		int $retry_count = 0,
		bool $retry_eligible = false,
		string $failure_reason = '',
		string $completed_at = ''
	) {
		$this->job_ref        = $job_ref;
		$this->job_type       = $job_type;
		$this->status         = $status;
		$this->action_id      = $action_id;
		$this->plan_item_id   = $plan_item_id;
		$this->result_summary = $result_summary;
		$this->retry_count    = $retry_count;
		$this->retry_eligible = $retry_eligible;
		$this->failure_reason = $failure_reason;
		$this->completed_at   = $completed_at !== '' ? $completed_at : gmdate( 'c' );
	}

	public function get_job_ref(): string {
		return $this->job_ref;
	}

	public function get_job_type(): string {
		return $this->job_type;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_action_id(): string {
		return $this->action_id;
	}

	public function get_plan_item_id(): string {
		return $this->plan_item_id;
	}

	/** @return array<string, mixed> */
	public function get_result_summary(): array {
		return $this->result_summary;
	}

	public function get_retry_count(): int {
		return $this->retry_count;
	}

	public function is_retry_eligible(): bool {
		return $this->retry_eligible;
	}

	public function get_failure_reason(): string {
		return $this->failure_reason;
	}

	public function get_completed_at(): string {
		return $this->completed_at;
	}

	/**
	 * Converts to array for logging and API.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'job_ref'         => $this->job_ref,
			'job_type'        => $this->job_type,
			'status'          => $this->status,
			'action_id'       => $this->action_id,
			'plan_item_id'    => $this->plan_item_id,
			'result_summary'  => $this->result_summary,
			'retry_count'     => $this->retry_count,
			'retry_eligible'  => $this->retry_eligible,
			'failure_reason'  => $this->failure_reason,
			'completed_at'    => $this->completed_at,
		);
	}

	/**
	 * Builds a completed job result.
	 *
	 * @param string               $job_ref
	 * @param string               $job_type
	 * @param string               $action_id
	 * @param string               $plan_item_id
	 * @param array<string, mixed>  $result_summary
	 * @param int                   $retry_count
	 * @return self
	 */
	public static function completed(
		string $job_ref,
		string $job_type,
		string $action_id,
		string $plan_item_id,
		array $result_summary = array(),
		int $retry_count = 0
	): self {
		return new self(
			$job_ref,
			$job_type,
			self::STATUS_COMPLETED,
			$action_id,
			$plan_item_id,
			$result_summary,
			$retry_count,
			false,
			'',
			gmdate( 'c' )
		);
	}

	/**
	 * Builds a failed job result.
	 *
	 * @param string $job_ref
	 * @param string $job_type
	 * @param string $action_id
	 * @param string $plan_item_id
	 * @param string $failure_reason
	 * @param array<string, mixed> $result_summary
	 * @param int   $retry_count
	 * @param bool  $retry_eligible
	 * @return self
	 */
	public static function failed(
		string $job_ref,
		string $job_type,
		string $action_id,
		string $plan_item_id,
		string $failure_reason,
		array $result_summary = array(),
		int $retry_count = 0,
		bool $retry_eligible = false
	): self {
		return new self(
			$job_ref,
			$job_type,
			self::STATUS_FAILED,
			$action_id,
			$plan_item_id,
			$result_summary,
			$retry_count,
			$retry_eligible,
			$failure_reason,
			gmdate( 'c' )
		);
	}

	/**
	 * Builds a refused job result (validation/approval blocked execution).
	 *
	 * @param string $job_ref
	 * @param string $job_type
	 * @param string $action_id
	 * @param string $plan_item_id
	 * @param string $failure_reason
	 * @param array<string, mixed> $result_summary
	 * @return self
	 */
	public static function refused(
		string $job_ref,
		string $job_type,
		string $action_id,
		string $plan_item_id,
		string $failure_reason,
		array $result_summary = array()
	): self {
		return new self(
			$job_ref,
			$job_type,
			self::STATUS_REFUSED,
			$action_id,
			$plan_item_id,
			$result_summary,
			0,
			false,
			$failure_reason,
			gmdate( 'c' )
		);
	}
}
