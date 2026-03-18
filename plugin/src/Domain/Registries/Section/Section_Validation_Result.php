<?php
/**
 * Result of section definition validation (spec §12, section-registry-schema §12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

/**
 * Valid/incomplete flag, error codes, and optional normalized definition.
 * Callers use this to decide whether to persist; reject on invalid.
 */
final class Section_Validation_Result {

	/** @var bool */
	public readonly bool $valid;

	/** @var list<string> Error codes or messages; no secrets. */
	public readonly array $errors;

	/** @var array<string, mixed>|null Normalized definition when available. */
	public readonly ?array $normalized;

	public function __construct( bool $valid, array $errors = array(), ?array $normalized = null ) {
		$this->valid      = $valid;
		$this->errors     = $errors;
		$this->normalized = $normalized;
	}

	public static function success( array $normalized ): self {
		return new self( true, array(), $normalized );
	}

	public static function failure( array $errors, ?array $normalized = null ): self {
		return new self( false, $errors, $normalized );
	}
}
