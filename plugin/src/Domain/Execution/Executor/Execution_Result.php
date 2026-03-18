<?php
/**
 * Structured execution result (spec §40.2, execution-action-contract.md §10; Prompt 079).
 *
 * Immutable result shape: execution_status, handler_result, snapshot_reference, warnings,
 * error_code, build_plan_updates, log_reference. Used by Single_Action_Executor.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Executor;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;

/**
 * Immutable execution result DTO. Convertible to array for logging and API.
 */
final class Execution_Result {

	/** @var string */
	private $action_id;

	/** @var string */
	private $action_type;

	/** @var string */
	private $execution_status;

	/** @var string */
	private $completed_at;

	/** @var array<string, mixed> */
	private $handler_result;

	/** @var string */
	private $snapshot_reference;

	/** @var array<int, string> */
	private $warnings;

	/** @var string */
	private $error_code;

	/** @var string */
	private $error_message;

	/** @var bool */
	private $refusable;

	/** @var array<string, mixed> */
	private $build_plan_updates;

	/** @var string */
	private $log_reference;

	/**
	 * Constructor. Prefer named factory methods.
	 *
	 * @param string               $action_id
	 * @param string               $action_type
	 * @param string               $execution_status
	 * @param string               $completed_at
	 * @param array<string, mixed> $handler_result
	 * @param string               $snapshot_reference
	 * @param array<int, string>   $warnings
	 * @param string               $error_code
	 * @param string               $error_message
	 * @param bool                 $refusable
	 * @param array<string, mixed> $build_plan_updates
	 * @param string               $log_reference
	 */
	public function __construct(
		string $action_id,
		string $action_type,
		string $execution_status,
		string $completed_at = '',
		array $handler_result = array(),
		string $snapshot_reference = '',
		array $warnings = array(),
		string $error_code = '',
		string $error_message = '',
		bool $refusable = false,
		array $build_plan_updates = array(),
		string $log_reference = ''
	) {
		$this->action_id          = $action_id;
		$this->action_type        = $action_type;
		$this->execution_status   = $execution_status;
		$this->completed_at       = $completed_at !== '' ? $completed_at : gmdate( 'c' );
		$this->handler_result     = $handler_result;
		$this->snapshot_reference = $snapshot_reference;
		$this->warnings           = $warnings;
		$this->error_code         = $error_code;
		$this->error_message      = $error_message;
		$this->refusable          = $refusable;
		$this->build_plan_updates = $build_plan_updates;
		$this->log_reference      = $log_reference;
	}

	/** @return string */
	public function get_action_id(): string {
		return $this->action_id;
	}

	/** @return string */
	public function get_action_type(): string {
		return $this->action_type;
	}

	/** @return string */
	public function get_execution_status(): string {
		return $this->execution_status;
	}

	/** @return string */
	public function get_completed_at(): string {
		return $this->completed_at;
	}

	/** @return array<string, mixed> */
	public function get_handler_result(): array {
		return $this->handler_result;
	}

	/** @return string */
	public function get_snapshot_reference(): string {
		return $this->snapshot_reference;
	}

	/** @return array<int, string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return string */
	public function get_error_code(): string {
		return $this->error_code;
	}

	/** @return string */
	public function get_error_message(): string {
		return $this->error_message;
	}

	/** @return bool */
	public function is_refusable(): bool {
		return $this->refusable;
	}

	/** @return array<string, mixed> */
	public function get_build_plan_updates(): array {
		return $this->build_plan_updates;
	}

	/** @return string */
	public function get_log_reference(): string {
		return $this->log_reference;
	}

	/**
	 * Converts result to array for logging and API (contract §10).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$out = array(
			Execution_Action_Contract::RESULT_ACTION_ID    => $this->action_id,
			'action_type'                                  => $this->action_type,
			Execution_Action_Contract::RESULT_STATUS       => $this->execution_status,
			Execution_Action_Contract::RESULT_COMPLETED_AT => $this->completed_at,
			'handler_result'                               => $this->handler_result,
			'snapshot_reference'                           => $this->snapshot_reference,
			'warnings'                                     => $this->warnings,
			'build_plan_updates'                           => $this->build_plan_updates,
			'log_reference'                                => $this->log_reference,
		);
		if ( $this->error_code !== '' || $this->error_message !== '' ) {
			$out[ Execution_Action_Contract::RESULT_ERROR ] = array(
				Execution_Action_Contract::ERROR_CODE      => $this->error_code,
				Execution_Action_Contract::ERROR_MESSAGE   => $this->error_message,
				Execution_Action_Contract::ERROR_REFUSABLE => $this->refusable,
			);
		}
		return $out;
	}

	/**
	 * Builds a refused result (no mutation performed).
	 *
	 * @param string $action_id
	 * @param string $action_type
	 * @param string $error_code
	 * @param string $message
	 * @param string $log_reference
	 * @return self
	 */
	public static function refused( string $action_id, string $action_type, string $error_code, string $message, string $log_reference = '' ): self {
		return new self(
			$action_id,
			$action_type,
			Execution_Action_Contract::STATUS_REFUSED,
			gmdate( 'c' ),
			array(),
			'',
			array(),
			$error_code,
			$message,
			true,
			array(),
			$log_reference
		);
	}

	/**
	 * Builds a completed result (handler succeeded).
	 *
	 * @param string               $action_id
	 * @param string               $action_type
	 * @param array<string, mixed> $handler_result
	 * @param string               $snapshot_reference
	 * @param array<string, mixed> $build_plan_updates
	 * @param array<int, string>   $warnings
	 * @param string               $log_reference
	 * @return self
	 */
	public static function completed(
		string $action_id,
		string $action_type,
		array $handler_result = array(),
		string $snapshot_reference = '',
		array $build_plan_updates = array(),
		array $warnings = array(),
		string $log_reference = ''
	): self {
		return new self(
			$action_id,
			$action_type,
			Execution_Action_Contract::STATUS_COMPLETED,
			gmdate( 'c' ),
			$handler_result,
			$snapshot_reference,
			$warnings,
			'',
			'',
			false,
			$build_plan_updates,
			$log_reference
		);
	}

	/**
	 * Builds a failed result (handler or system failure after validation).
	 *
	 * @param string               $action_id
	 * @param string               $action_type
	 * @param string               $error_code
	 * @param string               $message
	 * @param array<string, mixed> $handler_result
	 * @param array<string, mixed> $build_plan_updates
	 * @param string               $log_reference
	 * @return self
	 */
	public static function failed(
		string $action_id,
		string $action_type,
		string $error_code,
		string $message,
		array $handler_result = array(),
		array $build_plan_updates = array(),
		string $log_reference = ''
	): self {
		return new self(
			$action_id,
			$action_type,
			Execution_Action_Contract::STATUS_FAILED,
			gmdate( 'c' ),
			$handler_result,
			'',
			array(),
			$error_code,
			$message,
			false,
			$build_plan_updates,
			$log_reference
		);
	}
}
