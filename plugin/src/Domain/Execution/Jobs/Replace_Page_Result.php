<?php
/**
 * Result DTO for existing-page replace/rebuild job (spec §32.9, §40.2, §41.2; Prompt 082).
 *
 * Immutable: success, target_post_id, superseded_post_id (when replace), snapshot_ref,
 * message, errors, artifacts. Used by Replace_Page_Handler and execution logs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable replace-page job result. Includes snapshot and superseded refs for traceability.
 */
final class Replace_Page_Result {

	/** @var bool */
	private $success;

	/** @var int Target page ID (updated in place or newly created in replace flow). */
	private $target_post_id;

	/** @var int Superseded (archived/private) page ID when replacement created a new page; 0 otherwise. */
	private $superseded_post_id;

	/** @var string Pre-change snapshot reference (spec §32.9, §41.2). */
	private $snapshot_ref;

	/** @var string */
	private $message;

	/** @var array<int, string> */
	private $errors;

	/** @var array<string, mixed> */
	private $artifacts;

	/**
	 * @param bool                 $success
	 * @param int                  $target_post_id   Updated or new page ID; 0 on failure.
	 * @param int                  $superseded_post_id Archived page ID when replace; 0 for rebuild.
	 * @param string               $snapshot_ref     Pre-change snapshot ref for logging/rollback.
	 * @param string               $message
	 * @param array<int, string>         $errors
	 * @param array<string, mixed> $artifacts        template_key, assignment_count, superseded_page_ref, etc.
	 */
	public function __construct(
		bool $success,
		int $target_post_id,
		int $superseded_post_id,
		string $snapshot_ref,
		string $message = '',
		array $errors = array(),
		array $artifacts = array()
	) {
		$this->success            = $success;
		$this->target_post_id     = $target_post_id;
		$this->superseded_post_id = $superseded_post_id;
		$this->snapshot_ref       = $snapshot_ref;
		$this->message            = $message;
		$this->errors             = $errors;
		$this->artifacts          = $artifacts;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_target_post_id(): int {
		return $this->target_post_id;
	}

	public function get_superseded_post_id(): int {
		return $this->superseded_post_id;
	}

	public function get_snapshot_ref(): string {
		return $this->snapshot_ref;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array<string, mixed> */
	public function get_artifacts(): array {
		return $this->artifacts;
	}

	/**
	 * Converts to array for handler result and logging (includes snapshot_ref, superseded_page_ref).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'success'            => $this->success,
			'target_post_id'     => $this->target_post_id,
			'superseded_post_id' => $this->superseded_post_id,
			'snapshot_ref'       => $this->snapshot_ref,
			'message'            => $this->message,
			'errors'             => $this->errors,
			'artifacts'          => $this->artifacts,
		);
	}

	/**
	 * Handler result shape for Execution_Handler_Interface (success, message, artifacts with snapshot_ref).
	 *
	 * @return array<string, mixed>
	 */
	public function to_handler_result(): array {
		$artifacts = array_merge(
			array(
				'target_post_id' => $this->target_post_id,
				'snapshot_ref'   => $this->snapshot_ref,
			),
			$this->artifacts
		);
		if ( $this->superseded_post_id > 0 ) {
			$artifacts['superseded_post_id']  = $this->superseded_post_id;
			$artifacts['superseded_page_ref'] = array(
				'type'  => 'post_id',
				'value' => (string) $this->superseded_post_id,
			);
		}
		$out = array(
			'success'   => $this->success,
			'message'   => $this->message,
			'artifacts' => $artifacts,
		);
		if ( ! empty( $this->errors ) ) {
			$out['errors'] = $this->errors;
		}
		return $out;
	}

	public static function success(
		int $target_post_id,
		string $template_key,
		int $assignment_count,
		string $snapshot_ref,
		int $superseded_post_id = 0,
		array $extra_artifacts = array()
	): self {
		$artifacts = array(
			'template_key'     => $template_key,
			'assignment_count' => $assignment_count,
		);
		if ( $superseded_post_id > 0 ) {
			$artifacts['superseded_page_ref'] = array(
				'type'  => 'post_id',
				'value' => (string) $superseded_post_id,
			);
		}
		$artifacts = array_merge( $artifacts, $extra_artifacts );
		return new self(
			true,
			$target_post_id,
			$superseded_post_id,
			$snapshot_ref,
			__( 'Page updated or replaced.', 'aio-page-builder' ),
			array(),
			$artifacts
		);
	}

	public static function failure( string $message, array $errors = array(), string $snapshot_ref = '' ): self {
		return new self( false, 0, 0, $snapshot_ref, $message, $errors, array() );
	}
}
