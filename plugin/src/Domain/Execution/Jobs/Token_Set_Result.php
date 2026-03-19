<?php
/**
 * Result DTO for token-set apply job (spec §35, §40.2, §41.7; Prompt 083).
 *
 * Immutable: success, message, errors, artifacts (token_group, token_name, previous_value_ref, snapshot_ref).
 * Token application alters values only; selector/structural contracts unchanged.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable token-set apply job result. Preserves previous value ref for revert/rollback.
 */
final class Token_Set_Result {

	/** @var bool */
	private $success;

	/** @var string */
	private $message;

	/** @var array<int, string> */
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

	/** @return array<int, string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array<string, mixed> */
	public function get_artifacts(): array {
		return $this->artifacts;
	}

	/**
	 * Handler result shape (success, message, artifacts).
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
	 * @param mixed $applied_value
	 * @param mixed $previous_value
	 */
	public static function success( string $token_group, string $token_name, $applied_value, $previous_value = null, string $snapshot_ref = '', array $extra = array() ): self {
		$artifacts = array(
			'token_group'   => $token_group,
			'token_name'    => $token_name,
			'applied_value' => $applied_value,
		);
		if ( $previous_value !== null ) {
			$artifacts['previous_value_ref'] = array( 'value' => $previous_value );
		}
		if ( $snapshot_ref !== '' ) {
			$artifacts['snapshot_ref'] = $snapshot_ref;
		}
		return new self(
			true,
			__( 'Token value applied.', 'aio-page-builder' ),
			array(),
			array_merge( $artifacts, $extra )
		);
	}

	public static function failure( string $message, array $errors = array() ): self {
		return new self( false, $message, $errors, array() );
	}
}
