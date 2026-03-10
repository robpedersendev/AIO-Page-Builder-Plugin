<?php
/**
 * Result of profile validation: valid flag, errors, and optional sanitized payload (spec §22.12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

/**
 * Callers use this to decide whether to persist; sanitized_payload is safe to store when valid or when using sanitize-only mode.
 */
final class Profile_Validation_Result {

	/** @var bool */
	public readonly bool $valid;

	/** @var list<string> Error or warning messages; sanitized, no secrets. */
	public readonly array $errors;

	/** @var array<string, mixed>|null Normalized/sanitized payload when available. */
	public readonly ?array $sanitized_payload;

	public function __construct( bool $valid, array $errors = array(), ?array $sanitized_payload = null ) {
		$this->valid            = $valid;
		$this->errors           = $errors;
		$this->sanitized_payload = $sanitized_payload;
	}

	public static function success( array $sanitized_payload ): self {
		return new self( true, array(), $sanitized_payload );
	}

	public static function failure( array $errors, ?array $sanitized_payload = null ): self {
		return new self( false, $errors, $sanitized_payload );
	}
}
