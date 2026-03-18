<?php
/**
 * Result of a restore run (spec §52.8, §53.8). No page content rewrite.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Import;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable restore result: success, restored categories, resolved actions, log reference.
 */
final class Restore_Result {

	/** @var bool */
	private bool $success;

	/** @var string */
	private string $message;

	/** @var list<string> Categories that were restored. */
	private array $restored_categories;

	/** @var list<array{category: string, action: string, key?: string}> Summary of resolved actions (overwrite, keep, duplicate). */
	private array $resolved_actions;

	/** @var bool Whether validation had passed before restore ran. */
	private bool $validation_passed;

	/** @var list<string> Blocking failures if restore was not run or failed. */
	private array $blocking_failures;

	/** @var string Log reference for this restore. */
	private string $log_reference;

	/** @var array<string, mixed> Template-library restore validation summary (Prompt 185). */
	private array $template_library_restore_summary;

	/**
	 * @param bool                        $success
	 * @param string                      $message
	 * @param list<string>                $restored_categories
	 * @param list<array<string, string>> $resolved_actions
	 * @param bool                        $validation_passed
	 * @param list<string>                $blocking_failures
	 * @param string                      $log_reference
	 * @param array<string, mixed>        $template_library_restore_summary Optional template-library validation summary.
	 */
	public function __construct(
		bool $success,
		string $message,
		array $restored_categories,
		array $resolved_actions,
		bool $validation_passed,
		array $blocking_failures,
		string $log_reference,
		array $template_library_restore_summary = array()
	) {
		$this->success                          = $success;
		$this->message                          = $message;
		$this->restored_categories              = $restored_categories;
		$this->resolved_actions                 = $resolved_actions;
		$this->validation_passed                = $validation_passed;
		$this->blocking_failures                = $blocking_failures;
		$this->log_reference                    = $log_reference;
		$this->template_library_restore_summary = $template_library_restore_summary;
	}

	public function is_success(): bool {
		return $this->success;
	}

	public function get_message(): string {
		return $this->message;
	}

	/** @return list<string> */
	public function get_restored_categories(): array {
		return $this->restored_categories;
	}

	/** @return list<array{category: string, action: string, key?: string}> */
	public function get_resolved_actions(): array {
		return $this->resolved_actions;
	}

	public function validation_passed(): bool {
		return $this->validation_passed;
	}

	/** @return list<string> */
	public function get_blocking_failures(): array {
		return $this->blocking_failures;
	}

	public function get_log_reference(): string {
		return $this->log_reference;
	}

	/** @return array<string, mixed> */
	public function get_template_library_restore_summary(): array {
		return $this->template_library_restore_summary;
	}

	/**
	 * Payload for UI/API (no secrets).
	 *
	 * @return array{success: bool, message: string, restored_categories: list<string>, resolved_actions: list<array>, validation_passed: bool, blocking_failures: list<string>, log_reference: string}
	 */
	public function to_payload(): array {
		return array(
			'success'                          => $this->success,
			'message'                          => $this->message,
			'restored_categories'              => $this->restored_categories,
			'resolved_actions'                 => $this->resolved_actions,
			'validation_passed'                => $this->validation_passed,
			'blocking_failures'                => $this->blocking_failures,
			'log_reference'                    => $this->log_reference,
			'template_library_restore_summary' => $this->template_library_restore_summary,
		);
	}

	public static function success(
		array $restored_categories,
		array $resolved_actions,
		string $log_reference,
		string $message = 'Restore completed.',
		array $template_library_restore_summary = array()
	): self {
		return new self(
			true,
			$message,
			$restored_categories,
			$resolved_actions,
			true,
			array(),
			$log_reference,
			$template_library_restore_summary
		);
	}

	public static function failure(
		string $message,
		array $blocking_failures = array(),
		bool $validation_passed = false,
		string $log_reference = ''
	): self {
		return new self(
			false,
			$message,
			array(),
			array(),
			$validation_passed,
			$blocking_failures,
			$log_reference,
			array()
		);
	}
}
