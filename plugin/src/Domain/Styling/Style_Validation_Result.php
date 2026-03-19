<?php
/**
 * Structured result of style payload validation (Prompt 252). Bounded, safe for admin display and logging.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable validation result: valid flag, bounded error messages, and sanitized payload when valid.
 */
final class Style_Validation_Result {

	/** Max length per error message for safe display. */
	public const MAX_ERROR_MESSAGE_LENGTH = 256;

	/** Max number of errors to retain. */
	public const MAX_ERRORS = 50;

	/** @var bool */
	private bool $valid;

	/** @var list<string> */
	private array $errors;

	/** @var array<string, mixed> Sanitized payload (shape depends on context: global_tokens, global_component_overrides, or entity payload). */
	private array $sanitized;

	/**
	 * @param bool               $valid     Whether validation passed.
	 * @param array<int, string> $errors    Bounded list of error messages (safe for admin).
	 * @param array              $sanitized Sanitized payload when valid; empty array when invalid.
	 */
	public function __construct( bool $valid, array $errors = array(), array $sanitized = array() ) {
		$this->valid     = $valid;
		$this->errors    = $this->bound_errors( $errors );
		$this->sanitized = $valid ? $sanitized : array();
	}

	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * Returns error messages (bounded length and count).
	 *
	 * @return list<string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Returns the sanitized payload when valid; empty array otherwise.
	 *
	 * @return array<string, mixed>
	 */
	public function get_sanitized(): array {
		return $this->sanitized;
	}

	/**
	 * @param array<int, string> $errors Raw error messages to bound.
	 * @return list<string>
	 */
	private function bound_errors( array $errors ): array {
		$out   = array();
		$count = 0;
		foreach ( $errors as $msg ) {
			if ( $count >= self::MAX_ERRORS ) {
				break;
			}
			if ( ! is_string( $msg ) ) {
				continue;
			}
			$trimmed = substr( $msg, 0, self::MAX_ERROR_MESSAGE_LENGTH );
			$out[]   = $trimmed;
			++$count;
		}
		return $out;
	}
}
