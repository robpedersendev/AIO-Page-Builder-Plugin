<?php
/**
 * Stable execution result payload for template-driven existing-page replacement (spec §32, §32.9, §59.10, §59.11; Prompt 196).
 *
 * Immutable DTO: template_replacement_execution_result and replacement_trace_record for snapshot-backed
 * traceability between original and replacement pages. Used for logging, rollback input, and status history.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable template page replacement result. Convertible to array for artifacts and logging.
 */
final class Template_Page_Replacement_Result {

	/** @var bool */
	private $success;

	/** @var int */
	private $target_post_id;

	/** @var int */
	private $superseded_post_id;

	/** @var string */
	private $snapshot_ref;

	/** @var string */
	private $template_key;

	/** @var string */
	private $template_family;

	/** @var array<string, mixed> */
	private $replacement_trace_record;

	/** @var int */
	private $field_assignment_count;

	/** @var list<string> */
	private $warnings;

	/** @var list<string> */
	private $errors;

	/** @var string */
	private $message;

	/**
	 * @param bool                 $success
	 * @param int                  $target_post_id
	 * @param int                  $superseded_post_id
	 * @param string               $snapshot_ref
	 * @param string               $template_key
	 * @param string               $template_family
	 * @param array<string, mixed> $replacement_trace_record
	 * @param int                  $field_assignment_count
	 * @param list<string>         $warnings
	 * @param list<string>         $errors
	 * @param string               $message
	 */
	public function __construct(
		bool $success,
		int $target_post_id,
		int $superseded_post_id,
		string $snapshot_ref,
		string $template_key = '',
		string $template_family = '',
		array $replacement_trace_record = array(),
		int $field_assignment_count = 0,
		array $warnings = array(),
		array $errors = array(),
		string $message = ''
	) {
		$this->success                  = $success;
		$this->target_post_id           = $target_post_id;
		$this->superseded_post_id       = $superseded_post_id;
		$this->snapshot_ref             = $snapshot_ref;
		$this->template_key             = $template_key;
		$this->template_family          = $template_family;
		$this->replacement_trace_record = $replacement_trace_record;
		$this->field_assignment_count   = $field_assignment_count;
		$this->warnings                 = $warnings;
		$this->errors                   = $errors;
		$this->message                  = $message;
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

	public function get_template_key(): string {
		return $this->template_key;
	}

	public function get_template_family(): string {
		return $this->template_family;
	}

	/** @return array<string, mixed> */
	public function get_replacement_trace_record(): array {
		return $this->replacement_trace_record;
	}

	public function get_field_assignment_count(): int {
		return $this->field_assignment_count;
	}

	/** @return list<string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Stable payload for artifacts and logging (template_replacement_execution_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'success'                  => $this->success,
			'target_post_id'           => $this->target_post_id,
			'superseded_post_id'       => $this->superseded_post_id,
			'snapshot_ref'             => $this->snapshot_ref,
			'template_key'             => $this->template_key,
			'template_family'          => $this->template_family,
			'replacement_trace_record' => $this->replacement_trace_record,
			'field_assignment_count'   => $this->field_assignment_count,
			'warnings'                 => $this->warnings,
			'errors'                   => $this->errors,
			'message'                  => $this->message,
		);
	}

	/**
	 * Builds a success result with full traceability.
	 */
	public static function success(
		int $target_post_id,
		int $superseded_post_id,
		string $snapshot_ref,
		string $template_key,
		string $template_family = '',
		array $replacement_trace_record = array(),
		int $field_assignment_count = 0,
		array $warnings = array()
	): self {
		return new self(
			true,
			$target_post_id,
			$superseded_post_id,
			$snapshot_ref,
			$template_key,
			$template_family,
			$replacement_trace_record,
			$field_assignment_count,
			$warnings,
			array(),
			__( 'Page updated or replaced.', 'aio-page-builder' )
		);
	}

	/**
	 * Builds a failure result with message and errors.
	 */
	public static function failure( string $message, array $errors = array(), string $snapshot_ref = '', string $template_key = '' ): self {
		return new self(
			false,
			0,
			0,
			$snapshot_ref,
			$template_key,
			'',
			array(),
			0,
			array(),
			$errors,
			$message
		);
	}

	/**
	 * Returns an example template_replacement_execution_result payload for documentation and tests.
	 *
	 * @return array<string, mixed>
	 */
	public static function example_payload(): array {
		return array(
			'success'                  => true,
			'target_post_id'           => 202,
			'superseded_post_id'       => 101,
			'snapshot_ref'             => 'op-snap-pre-exec_item_1_b-20250113T150000-123',
			'template_key'             => 'tpl_services_hub',
			'template_family'          => 'services',
			'replacement_trace_record' => array(
				'original_post_id' => 101,
				'new_post_id'      => 202,
				'archive_status'   => 'private',
				'template_key'     => 'tpl_services_hub',
				'snapshot_pre_id'  => 'op-snap-pre-exec_item_1_b-20250113T150000-123',
			),
			'field_assignment_count'   => 3,
			'warnings'                 => array(),
			'errors'                   => array(),
			'message'                  => __( 'Page updated or replaced.', 'aio-page-builder' ),
		);
	}
}
