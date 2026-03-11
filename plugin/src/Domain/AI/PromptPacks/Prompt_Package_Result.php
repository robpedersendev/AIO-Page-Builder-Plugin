<?php
/**
 * Result of building a normalized prompt package (spec §27, §29.1, §29.2). Success or validation failure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\PromptPacks;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result: either a normalized prompt package or validation errors. No secrets.
 */
final class Prompt_Package_Result {

	/** @var bool */
	private bool $success;

	/** @var array<string, mixed>|null Normalized prompt package when success. */
	private ?array $normalized_prompt_package;

	/** @var array<int, string> Validation or assembly errors when not success. */
	private array $validation_errors;

	/** @var array<string, mixed>|null Selected prompt pack metadata when success. */
	private ?array $selected_pack;

	/**
	 * @param bool                     $success                   Whether assembly succeeded.
	 * @param array<string, mixed>|null $normalized_prompt_package Package when success.
	 * @param array<int, string>       $validation_errors         Errors when not success.
	 * @param array<string, mixed>|null $selected_pack             Selected pack when success.
	 */
	public function __construct(
		bool $success,
		?array $normalized_prompt_package,
		array $validation_errors = array(),
		?array $selected_pack = null
	) {
		$this->success                   = $success;
		$this->normalized_prompt_package  = $normalized_prompt_package;
		$this->validation_errors          = $validation_errors;
		$this->selected_pack              = $selected_pack;
	}

	public function is_success(): bool {
		return $this->success;
	}

	/** @return array<string, mixed>|null */
	public function get_normalized_prompt_package(): ?array {
		return $this->normalized_prompt_package;
	}

	/** @return array<int, string> */
	public function get_validation_errors(): array {
		return $this->validation_errors;
	}

	/** @return array<string, mixed>|null */
	public function get_selected_pack(): ?array {
		return $this->selected_pack;
	}

	/**
	 * Machine-readable validation result shape (prompt_package_validation_result).
	 *
	 * @return array{success: bool, validation_errors: array<int, string>, has_package: bool}
	 */
	public function to_validation_result(): array {
		return array(
			'success'           => $this->success,
			'validation_errors' => $this->validation_errors,
			'has_package'       => $this->normalized_prompt_package !== null,
		);
	}
}
