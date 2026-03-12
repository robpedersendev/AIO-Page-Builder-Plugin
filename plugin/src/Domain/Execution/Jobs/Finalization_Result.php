<?php
/**
 * Result DTO for finalization job (spec §37.7, §40.10; Prompt 084).
 *
 * Immutable: success, message, errors, artifacts (completion_summary, conflicts,
 * finalized_at, actor_ref). Used by Finalize_Plan_Handler and execution logs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable finalization job result. Completion summary per spec §37.7.
 */
final class Finalization_Result {

	/** @var bool */
	private $success;

	/** @var string */
	private $message;

	/** @var list<string> */
	private $errors;

	/** @var array<string, mixed> */
	private $artifacts;

	public function __construct(
		bool $success,
		string $message = '',
		array $errors = array(),
		array $artifacts = array()
	) {
		$this->success   = $success;
		$this->message   = $message;
		$this->errors    = $errors;
		$this->artifacts = $artifacts;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array<string, mixed> */
	public function get_artifacts(): array {
		return $this->artifacts;
	}

	/**
	 * Handler result shape (success, message, artifacts with completion_summary).
	 *
	 * @return array<string, mixed>
	 */
	public function to_handler_result(): array {
		$out = array(
			'success'   => $this->success,
			'message'   => $this->message,
			'artifacts' => $this->artifacts,
		);
		if ( ! empty( $this->errors ) ) {
			$out['errors'] = $this->errors;
		}
		return $out;
	}

	/**
	 * @param array{published: int, completed_without_publication: int, blocked: int, denied: int, failed: int} $summary
	 * @param array<int, array<string, mixed>> $conflicts
	 */
	public static function success(
		string $finalized_at,
		array $summary,
		array $conflicts = array(),
		string $actor_ref = '',
		array $extra = array()
	): self {
		$artifacts = array_merge(
			array(
				'completion_summary' => $summary,
				'conflicts'         => $conflicts,
				'finalized_at'      => $finalized_at,
				'actor_ref'         => $actor_ref,
			),
			$extra
		);
		return new self(
			true,
			__( 'Plan finalized.', 'aio-page-builder' ),
			array(),
			$artifacts
		);
	}

	public static function failure( string $message, array $errors = array(), array $artifacts = array() ): self {
		return new self( false, $message, $errors, $artifacts );
	}
}
