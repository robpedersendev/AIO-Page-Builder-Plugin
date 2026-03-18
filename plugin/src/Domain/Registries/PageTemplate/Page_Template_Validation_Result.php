<?php
/**
 * Result of page template validation (spec §13, page-template-registry-schema §12).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

/**
 * Valid/incomplete flag, error codes, and optional normalized definition.
 */
final class Page_Template_Validation_Result {

	/** @var bool */
	public readonly bool $valid;

	/** @var list<string> */
	public readonly array $errors;

	/** @var array<string, mixed>|null */
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
