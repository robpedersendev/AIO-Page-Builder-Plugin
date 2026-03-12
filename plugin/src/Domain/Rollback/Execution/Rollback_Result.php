<?php
/**
 * Result DTO for rollback execution (spec §38.5, §38.6, §41.10).
 *
 * Holds job_id, target_ref, status, partial_rollback, failure_reason, log_ref,
 * pre/post snapshot refs, and next_action_guidance.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Execution;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of a rollback execution attempt.
 */
final class Rollback_Result {

	public const STATUS_SUCCESS   = 'success';
	public const STATUS_FAILED    = 'failed';
	public const STATUS_INELIGIBLE = 'ineligible';

	/** @var string */
	private string $job_id;

	/** @var string */
	private string $target_ref;

	/** @var string One of STATUS_* */
	private string $status;

	/** @var bool True if rollback was partially applied. */
	private bool $partial_rollback;

	/** @var string */
	private string $failure_reason;

	/** @var string Optional log or audit reference. */
	private string $log_ref;

	/** @var string */
	private string $pre_snapshot_id;

	/** @var string */
	private string $post_snapshot_id;

	/** @var string Optional guidance for user (spec §38.6). */
	private string $next_action_guidance;

	/** @var string */
	private string $message;

	/** @var array<string, mixed> */
	private array $result_summary;

	private function __construct(
		string $job_id,
		string $target_ref,
		string $status,
		bool $partial_rollback,
		string $failure_reason,
		string $log_ref,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $next_action_guidance,
		string $message,
		array $result_summary
	) {
		$this->job_id                = $job_id;
		$this->target_ref            = $target_ref;
		$this->status                = $status;
		$this->partial_rollback      = $partial_rollback;
		$this->failure_reason        = $failure_reason;
		$this->log_ref               = $log_ref;
		$this->pre_snapshot_id       = $pre_snapshot_id;
		$this->post_snapshot_id      = $post_snapshot_id;
		$this->next_action_guidance  = $next_action_guidance;
		$this->message               = $message;
		$this->result_summary        = $result_summary;
	}

	/**
	 * Builds a success result.
	 *
	 * @param string               $job_id
	 * @param string               $target_ref
	 * @param string               $pre_snapshot_id
	 * @param string               $post_snapshot_id
	 * @param string               $log_ref
	 * @param array<string, mixed> $result_summary
	 * @return self
	 */
	public static function success(
		string $job_id,
		string $target_ref,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $log_ref = '',
		array $result_summary = array()
	): self {
		return new self(
			$job_id,
			$target_ref,
			self::STATUS_SUCCESS,
			false,
			'',
			$log_ref,
			$pre_snapshot_id,
			$post_snapshot_id,
			'',
			__( 'Rollback completed.', 'aio-page-builder' ),
			$result_summary
		);
	}

	/**
	 * Builds a failed result (handler or runtime error).
	 *
	 * @param string               $job_id
	 * @param string               $target_ref
	 * @param string               $failure_reason
	 * @param bool                 $partial_rollback
	 * @param string               $pre_snapshot_id
	 * @param string               $post_snapshot_id
	 * @param string               $next_action_guidance
	 * @param string               $log_ref
	 * @param array<string, mixed> $result_summary
	 * @return self
	 */
	public static function failed(
		string $job_id,
		string $target_ref,
		string $failure_reason,
		bool $partial_rollback,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $next_action_guidance = '',
		string $log_ref = '',
		array $result_summary = array()
	): self {
		$message = $partial_rollback
			? __( 'Rollback partially applied; see failure reason.', 'aio-page-builder' )
			: __( 'Rollback failed.', 'aio-page-builder' );
		return new self(
			$job_id,
			$target_ref,
			self::STATUS_FAILED,
			$partial_rollback,
			$failure_reason,
			$log_ref,
			$pre_snapshot_id,
			$post_snapshot_id,
			$next_action_guidance,
			$message,
			$result_summary
		);
	}

	/**
	 * Builds an ineligible-at-execution result (revalidation failed).
	 *
	 * @param string               $job_id
	 * @param string               $target_ref
	 * @param string               $failure_reason
	 * @param string               $pre_snapshot_id
	 * @param string               $post_snapshot_id
	 * @param string               $next_action_guidance
	 * @param array<string, mixed> $result_summary
	 * @return self
	 */
	public static function ineligible(
		string $job_id,
		string $target_ref,
		string $failure_reason,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $next_action_guidance = '',
		array $result_summary = array()
	): self {
		return new self(
			$job_id,
			$target_ref,
			self::STATUS_INELIGIBLE,
			false,
			$failure_reason,
			'',
			$pre_snapshot_id,
			$post_snapshot_id,
			$next_action_guidance,
			__( 'Rollback not eligible at execution time.', 'aio-page-builder' ),
			$result_summary
		);
	}

	public function get_job_id(): string {
		return $this->job_id;
	}

	public function get_target_ref(): string {
		return $this->target_ref;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function is_success(): bool {
		return $this->status === self::STATUS_SUCCESS;
	}

	public function is_partial_rollback(): bool {
		return $this->partial_rollback;
	}

	public function get_failure_reason(): string {
		return $this->failure_reason;
	}

	public function get_log_ref(): string {
		return $this->log_ref;
	}

	public function get_pre_snapshot_id(): string {
		return $this->pre_snapshot_id;
	}

	public function get_post_snapshot_id(): string {
		return $this->post_snapshot_id;
	}

	public function get_next_action_guidance(): string {
		return $this->next_action_guidance;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return array<string, mixed> */
	public function get_result_summary(): array {
		return $this->result_summary;
	}

	/**
	 * Returns a machine-readable array for logging and API.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'job_id'                => $this->job_id,
			'target_ref'            => $this->target_ref,
			'status'                => $this->status,
			'partial_rollback'      => $this->partial_rollback,
			'failure_reason'        => $this->failure_reason,
			'log_ref'               => $this->log_ref,
			'pre_snapshot_id'       => $this->pre_snapshot_id,
			'post_snapshot_id'      => $this->post_snapshot_id,
			'next_action_guidance'  => $this->next_action_guidance,
			'message'               => $this->message,
			'result_summary'        => $this->result_summary,
		);
	}
}
