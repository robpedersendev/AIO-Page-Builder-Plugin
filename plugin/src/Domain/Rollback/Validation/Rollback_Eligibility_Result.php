<?php
/**
 * Result DTO for rollback eligibility evaluation (spec §38.4, §41.9, §59.11).
 *
 * Holds is_eligible, blocking_reasons, warnings, required_permissions, target_resolution_state,
 * rollback_handler_key, and optional snapshot/execution refs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of rollback eligibility validation.
 */
final class Rollback_Eligibility_Result {

	/** Target resolution: target object exists and is usable for rollback. */
	public const TARGET_RESOLVED = 'resolved';

	/** Target no longer exists or cannot be resolved. */
	public const TARGET_MISSING = 'missing';

	/** Target exists but state is invalid or incompatible for rollback. */
	public const TARGET_INVALID = 'invalid';

	/** Target resolution not yet evaluated. */
	public const TARGET_UNKNOWN = 'unknown';

	/** @var bool */
	private bool $is_eligible;

	/** @var list<string> Blocking reason codes (Rollback_Blocking_Reasons). */
	private array $blocking_reasons;

	/** @var list<string> Warning codes or messages (non-blocking). */
	private array $warnings;

	/** @var list<string> Capability names required to execute rollback. */
	private array $required_permissions;

	/** @var string One of TARGET_* constants. */
	private string $target_resolution_state;

	/** @var string Action type or handler key (e.g. replace_page, update_menu, apply_token_set). */
	private string $rollback_handler_key;

	/** @var string */
	private string $pre_snapshot_id;

	/** @var string */
	private string $post_snapshot_id;

	/** @var string */
	private string $execution_ref;

	/** @var string Optional human-readable summary. */
	private string $message;

	private function __construct(
		bool $is_eligible,
		array $blocking_reasons,
		array $warnings,
		array $required_permissions,
		string $target_resolution_state,
		string $rollback_handler_key,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $execution_ref,
		string $message
	) {
		$this->is_eligible            = $is_eligible;
		$this->blocking_reasons       = $blocking_reasons;
		$this->warnings               = $warnings;
		$this->required_permissions   = $required_permissions;
		$this->target_resolution_state = $target_resolution_state;
		$this->rollback_handler_key   = $rollback_handler_key;
		$this->pre_snapshot_id         = $pre_snapshot_id;
		$this->post_snapshot_id       = $post_snapshot_id;
		$this->execution_ref          = $execution_ref;
		$this->message                = $message;
	}

	/**
	 * Builds an eligible result (no blockers).
	 *
	 * @param string $rollback_handler_key
	 * @param string $pre_snapshot_id
	 * @param string $post_snapshot_id
	 * @param string $execution_ref
	 * @param list<string> $warnings Optional warnings (e.g. state_diverged).
	 * @param list<string> $required_permissions
	 * @return self
	 */
	public static function eligible(
		string $rollback_handler_key,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $execution_ref,
		array $warnings = array(),
		array $required_permissions = array()
	): self {
		return new self(
			true,
			array(),
			$warnings,
			$required_permissions,
			self::TARGET_RESOLVED,
			$rollback_handler_key,
			$pre_snapshot_id,
			$post_snapshot_id,
			$execution_ref,
			__( 'Rollback is eligible.', 'aio-page-builder' )
		);
	}

	/**
	 * Builds an ineligible result with blocking reasons.
	 *
	 * @param list<string> $blocking_reasons
	 * @param string       $rollback_handler_key Empty if unknown.
	 * @param string       $pre_snapshot_id
	 * @param string       $post_snapshot_id
	 * @param string       $execution_ref
	 * @param string       $target_resolution_state
	 * @param list<string> $warnings
	 * @param list<string> $required_permissions
	 * @param string       $message
	 * @return self
	 */
	public static function ineligible(
		array $blocking_reasons,
		string $rollback_handler_key,
		string $pre_snapshot_id,
		string $post_snapshot_id,
		string $execution_ref,
		string $target_resolution_state = self::TARGET_UNKNOWN,
		array $warnings = array(),
		array $required_permissions = array(),
		string $message = ''
	): self {
		if ( $message === '' ) {
			$message = __( 'Rollback is not eligible.', 'aio-page-builder' );
		}
		return new self(
			false,
			$blocking_reasons,
			$warnings,
			$required_permissions,
			$target_resolution_state,
			$rollback_handler_key,
			$pre_snapshot_id,
			$post_snapshot_id,
			$execution_ref,
			$message
		);
	}

	public function is_eligible(): bool {
		return $this->is_eligible;
	}

	/** @return list<string> */
	public function get_blocking_reasons(): array {
		return $this->blocking_reasons;
	}

	/** @return list<string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return list<string> */
	public function get_required_permissions(): array {
		return $this->required_permissions;
	}

	public function get_target_resolution_state(): string {
		return $this->target_resolution_state;
	}

	public function get_rollback_handler_key(): string {
		return $this->rollback_handler_key;
	}

	public function get_pre_snapshot_id(): string {
		return $this->pre_snapshot_id;
	}

	public function get_post_snapshot_id(): string {
		return $this->post_snapshot_id;
	}

	public function get_execution_ref(): string {
		return $this->execution_ref;
	}

	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Returns a machine-readable array suitable for API or UI data builders.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'is_eligible'             => $this->is_eligible,
			'blocking_reasons'        => $this->blocking_reasons,
			'warnings'                => $this->warnings,
			'required_permissions'   => $this->required_permissions,
			'target_resolution_state' => $this->target_resolution_state,
			'rollback_handler_key'    => $this->rollback_handler_key,
			'pre_snapshot_id'         => $this->pre_snapshot_id,
			'post_snapshot_id'        => $this->post_snapshot_id,
			'execution_ref'           => $this->execution_ref,
			'message'                 => $this->message,
		);
	}
}
