<?php
/**
 * Result of mapping an AI draft array to a canonical registry shape (translator boundary).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Translation;

defined( 'ABSPATH' ) || exit;

final class Canonical_Translation_Result {

	/**
	 * @param list<string>         $errors
	 * @param array<string, mixed> $definition
	 */
	public function __construct(
		private bool $ok,
		private array $definition = array(),
		private array $errors = array()
	) {
	}

	/**
	 * @param list<string> $errors
	 */
	public static function failure( array $errors ): self {
		return new self( false, array(), $errors );
	}

	/**
	 * @param array<string, mixed> $definition
	 */
	public static function success( array $definition ): self {
		return new self( true, $definition, array() );
	}

	public function is_ok(): bool {
		return $this->ok;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_definition(): array {
		return $this->definition;
	}

	/**
	 * @return list<string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
