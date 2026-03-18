<?php
/**
 * Result DTO for template-aware menu apply (spec §59.10, §1.9.9; Prompt 207).
 *
 * Stable payloads: menu_apply_execution_result, navigation_hierarchy_summary,
 * menu_target_validation_result. Per-item status and metadata for diff/rollback.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Menus;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of a template-aware menu apply.
 */
final class Template_Menu_Apply_Result {

	/** @var bool */
	private $success;

	/** @var string */
	private $message;

	/** @var list<string> */
	private $errors;

	/** @var int WordPress nav menu term_id; 0 on failure. */
	private $menu_id;

	/** @var array<string, mixed> menu_target_validation_result. */
	private $validation_result;

	/** @var array<string, mixed> navigation_hierarchy_summary. */
	private $hierarchy_summary;

	/** @var list<array<string, mixed>> per_item_status. */
	private $per_item_status;

	/** @var array<string, mixed> artifacts for handler/snapshot. */
	private $artifacts;

	public function __construct(
		bool $success,
		string $message,
		array $errors,
		int $menu_id,
		array $validation_result,
		array $hierarchy_summary,
		array $per_item_status,
		array $artifacts
	) {
		$this->success           = $success;
		$this->message           = $message;
		$this->errors            = $errors;
		$this->menu_id           = $menu_id;
		$this->validation_result = $validation_result;
		$this->hierarchy_summary = $hierarchy_summary;
		$this->per_item_status   = $per_item_status;
		$this->artifacts         = $artifacts;
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

	public function get_menu_id(): int {
		return $this->menu_id;
	}

	/** @return array<string, mixed> */
	public function get_validation_result(): array {
		return $this->validation_result;
	}

	/** @return array<string, mixed> */
	public function get_hierarchy_summary(): array {
		return $this->hierarchy_summary;
	}

	/** @return list<array<string, mixed>> */
	public function get_per_item_status(): array {
		return $this->per_item_status;
	}

	/** @return array<string, mixed> */
	public function get_artifacts(): array {
		return $this->artifacts;
	}

	/**
	 * Stable payload for execution log and snapshot (menu_apply_execution_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_menu_apply_execution_result(): array {
		return array(
			'success'                       => $this->success,
			'message'                       => $this->message,
			'menu_id'                       => $this->menu_id,
			'menu_target_validation_result' => $this->validation_result,
			'navigation_hierarchy_summary'  => $this->hierarchy_summary,
			'per_item_status'               => $this->per_item_status,
			'errors'                        => $this->errors,
		);
	}

	/**
	 * Handler result shape (success, message, artifacts) for Single_Action_Executor.
	 *
	 * @return array<string, mixed>
	 */
	public function to_handler_result(): array {
		$artifacts = array_merge(
			array(
				'menu_id'                       => $this->menu_id,
				'menu_apply_execution_result'   => $this->to_menu_apply_execution_result(),
				'navigation_hierarchy_summary'  => $this->hierarchy_summary,
				'menu_target_validation_result' => $this->validation_result,
			),
			$this->artifacts
		);
		$out       = array(
			'success'   => $this->success,
			'message'   => $this->message,
			'artifacts' => $artifacts,
		);
		if ( ! empty( $this->errors ) ) {
			$out['errors'] = $this->errors;
		}
		return $out;
	}

	/**
	 * Success factory.
	 *
	 * @param int                        $menu_id
	 * @param array<string, mixed>       $validation_result
	 * @param array<string, mixed>       $hierarchy_summary
	 * @param list<array<string, mixed>> $per_item_status
	 * @param array<string, mixed>       $artifacts
	 * @return self
	 */
	public static function success(
		int $menu_id,
		array $validation_result,
		array $hierarchy_summary,
		array $per_item_status,
		array $artifacts = array()
	): self {
		return new self(
			true,
			__( 'Template-aware menu apply completed.', 'aio-page-builder' ),
			array(),
			$menu_id,
			$validation_result,
			$hierarchy_summary,
			$per_item_status,
			$artifacts
		);
	}

	/**
	 * Failure factory. Missing location must be visible (no silent skip).
	 *
	 * @param string                     $message
	 * @param list<string>               $errors
	 * @param array<string, mixed>       $validation_result
	 * @param array<string, mixed>       $hierarchy_summary
	 * @param list<array<string, mixed>> $per_item_status
	 * @return self
	 */
	public static function failure(
		string $message,
		array $errors = array(),
		array $validation_result = array(),
		array $hierarchy_summary = array(),
		array $per_item_status = array()
	): self {
		return new self(
			false,
			$message,
			$errors,
			0,
			$validation_result,
			$hierarchy_summary,
			$per_item_status,
			array()
		);
	}
}
