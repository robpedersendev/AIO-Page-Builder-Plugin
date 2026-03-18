<?php
/**
 * Result of deriving section keys from a template or composition for registration (Prompt 296).
 * Read-path only; used by new-page ACF registration to avoid broad registry loading.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of template/composition section-key derivation for ACF registration.
 */
final class Template_Section_Key_Derivation_Result {

	/** @var list<string> Section internal_key values. */
	private array $section_keys;

	/** @var bool True when the template/composition was found and resolved. */
	private bool $resolved;

	public function __construct( array $section_keys, bool $resolved = true ) {
		$this->section_keys = array_values( array_unique( array_filter( $section_keys, 'is_string' ) ) );
		$this->resolved     = $resolved;
	}

	/**
	 * Returns the list of section keys to register.
	 *
	 * @return list<string>
	 */
	public function get_section_keys(): array {
		return $this->section_keys;
	}

	/**
	 * Whether the source (template/composition) was found and section keys were derived.
	 *
	 * @return bool
	 */
	public function is_resolved(): bool {
		return $this->resolved;
	}
}
