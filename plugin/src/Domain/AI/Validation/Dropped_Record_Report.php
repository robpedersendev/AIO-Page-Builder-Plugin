<?php
/**
 * Report for a single dropped record when partial output handling is applied (spec §28.13, §28.14, ai-output-validation-contract.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object: one dropped item-level record. No secrets; errors are redacted/short codes.
 */
final class Dropped_Record_Report {

	/** @var string */
	private string $section;

	/** @var int */
	private int $index;

	/** @var string */
	private string $reason;

	/** @var array<int, string> */
	private array $errors;

	/**
	 * @param string         $section Top-level section key (e.g. existing_page_changes).
	 * @param int            $index   Index of the dropped record in the array.
	 * @param string         $reason  Short reason (e.g. invalid_enum, missing_required_field).
	 * @param array<int, string> $errors  List of error codes or redacted messages.
	 */
	public function __construct( string $section, int $index, string $reason, array $errors = array() ) {
		$this->section = $section;
		$this->index   = $index;
		$this->reason  = $reason;
		$this->errors  = $errors;
	}

	public function get_section(): string {
		return $this->section;
	}

	public function get_index(): int {
		return $this->index;
	}

	public function get_reason(): string {
		return $this->reason;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Export for logging or API (no sensitive data).
	 *
	 * @return array{section: string, index: int, reason: string, errors: array<int, string>}
	 */
	public function to_array(): array {
		return array(
			'section' => $this->section,
			'index'   => $this->index,
			'reason'  => $this->reason,
			'errors'  => $this->errors,
		);
	}
}
