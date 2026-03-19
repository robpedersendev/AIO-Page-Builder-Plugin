<?php
/**
 * Result DTO for new-page creation job (spec §33.5, §40.2; Prompt 081).
 *
 * Immutable: success, post_id, message, errors, artifacts (post_id, template_key,
 * assignment_count, log_ref). Used by Create_Page_Handler and for execution logs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable create-page job result. Convertible to array for logging and API.
 */
final class Create_Page_Result {

	/** @var bool */
	private $success;

	/** @var int */
	private $post_id;

	/** @var string */
	private $message;

	/** @var array<int, string> */
	private $errors;

	/** @var array<string, mixed> */
	private $artifacts;

	/** @var string */
	private $log_ref;

	/**
	 * @param bool                 $success
	 * @param int                  $post_id    Created page ID; 0 on failure.
	 * @param string               $message
	 * @param array<int, string>         $errors
	 * @param array<string, mixed> $artifacts  post_id, template_key, assignment_count, log_ref, etc.
	 * @param string               $log_ref
	 */
	public function __construct(
		bool $success,
		int $post_id,
		string $message = '',
		array $errors = array(),
		array $artifacts = array(),
		string $log_ref = ''
	) {
		$this->success   = $success;
		$this->post_id   = $post_id;
		$this->message   = $message;
		$this->errors    = $errors;
		$this->artifacts = $artifacts;
		$this->log_ref   = $log_ref;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_post_id(): int {
		return $this->post_id;
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

	public function get_log_ref(): string {
		return $this->log_ref;
	}

	/**
	 * Converts to array for handler result and logging.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'success'   => $this->success,
			'post_id'   => $this->post_id,
			'message'   => $this->message,
			'errors'    => $this->errors,
			'artifacts' => $this->artifacts,
			'log_ref'   => $this->log_ref,
		);
	}

	/**
	 * Handler result shape for Execution_Handler_Interface (success, message, artifacts).
	 *
	 * @return array<string, mixed>
	 */
	public function to_handler_result(): array {
		$out = array(
			'success'   => $this->success,
			'message'   => $this->message,
			'artifacts' => array_merge( array( 'post_id' => $this->post_id ), $this->artifacts ),
		);
		if ( ! empty( $this->errors ) ) {
			$out['errors'] = $this->errors;
		}
		return $out;
	}

	public static function success( int $post_id, string $template_key, int $assignment_count = 0, string $log_ref = '' ): self {
		return new self(
			true,
			$post_id,
			__( 'Page created.', 'aio-page-builder' ),
			array(),
			array(
				'template_key'     => $template_key,
				'assignment_count' => $assignment_count,
			),
			$log_ref
		);
	}

	public static function failure( string $message, array $errors = array(), string $log_ref = '' ): self {
		return new self( false, 0, $message, $errors, array(), $log_ref );
	}
}
