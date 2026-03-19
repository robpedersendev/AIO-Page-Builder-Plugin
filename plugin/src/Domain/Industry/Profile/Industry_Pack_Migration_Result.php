<?php
/**
 * Result of an industry pack migration run (Prompt 412).
 * Immutable; contains migrated refs, outcome, and warnings for audit/support.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Result of Industry_Pack_Migration_Executor::run_migration().
 * Does not include Build Plan snapshot changes; historical artifacts are never rewritten.
 */
final class Industry_Pack_Migration_Result {

	public const OBJECT_TYPE_PRIMARY_INDUSTRY   = 'primary_industry_key';
	public const OBJECT_TYPE_SECONDARY_INDUSTRY = 'secondary_industry_keys';
	public const OBJECT_TYPE_STARTER_BUNDLE     = 'selected_starter_bundle_key';

	/** @var bool */
	private $success;

	/** @var array<int, array{object_type: string, old_ref: string, new_ref: string}> */
	private $migrated_refs;

	/** @var array<int, string> */
	private $warnings;

	/** @var array<int, string> */
	private $errors;

	/** @var string Optional audit note (e.g. "Migrated from realtor to realtor_v2"). */
	private $audit_note;

	/**
	 * @param bool                                                               $success       Whether migration completed without fatal errors.
	 * @param array<int, array{object_type: string, old_ref: string, new_ref: string}> $migrated_refs List of refs that were updated.
	 * @param array<int, string>                                                 $warnings      Non-fatal warnings.
	 * @param array<int, string>                                                 $errors        Fatal or validation errors.
	 * @param string                                                             $audit_note    Short note for audit/support.
	 */
	public function __construct(
		bool $success,
		array $migrated_refs,
		array $warnings = array(),
		array $errors = array(),
		string $audit_note = ''
	) {
		$this->success       = $success;
		$this->migrated_refs = $migrated_refs;
		$this->warnings      = $warnings;
		$this->errors        = $errors;
		$this->audit_note    = trim( $audit_note );
	}

	public function is_success(): bool {
		return $this->success;
	}

	/** @return array<int, array{object_type: string, old_ref: string, new_ref: string}> */
	public function get_migrated_refs(): array {
		return $this->migrated_refs;
	}

	/** @return array<int, string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	public function get_audit_note(): string {
		return $this->audit_note;
	}
}
