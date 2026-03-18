<?php
/**
 * Emits global token values as scoped --aio-* CSS custom properties (Prompt 249).
 * Uses only approved token names; invalid names or values are omitted.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Reads validated global token values and emits CSS variable declarations for approved scope.
 */
final class Global_Token_Variable_Emitter {

	/** Default scope selector when emitting (per render-surfaces spec). */
	private const ROOT_SELECTOR = ':root';

	/** @var Global_Style_Settings_Repository */
	private Global_Style_Settings_Repository $repository;

	/** @var Style_Token_Registry|null When null, no emission (fail closed). */
	private ?Style_Token_Registry $token_registry;

	public function __construct(
		Global_Style_Settings_Repository $repository,
		?Style_Token_Registry $token_registry = null
	) {
		$this->repository     = $repository;
		$this->token_registry = $token_registry;
	}

	/**
	 * Emits CSS block for :root with --aio-* custom properties. Invalid tokens/values omitted.
	 * Usable in front-end and preview contexts.
	 *
	 * @return string CSS block including selector (e.g. ":root { --aio-color-primary: #333; }") or empty string.
	 */
	public function emit_for_root(): string {
		$declarations = $this->get_approved_declarations();
		if ( empty( $declarations ) ) {
			return '';
		}
		$inner = implode( ' ', $declarations );
		return self::ROOT_SELECTOR . ' { ' . $inner . ' }';
	}

	/**
	 * Returns list of "name: value;" declaration strings for approved tokens only.
	 *
	 * @return list<string>
	 */
	public function get_approved_declarations(): array {
		if ( $this->token_registry === null || ! $this->token_registry->is_loaded() ) {
			return array();
		}
		$tokens = $this->repository->get_global_tokens();
		$out    = array();
		foreach ( $tokens as $group => $names ) {
			if ( ! is_string( $group ) || ! is_array( $names ) ) {
				continue;
			}
			foreach ( $names as $name => $value ) {
				if ( ! is_string( $name ) || ! is_string( $value ) ) {
					continue;
				}
				$var_name = $this->token_registry->get_token_variable_name( $group, $name );
				if ( $var_name === '' ) {
					continue;
				}
				$safe_value = $this->safe_css_value( $value );
				if ( $safe_value === '' ) {
					continue;
				}
				$out[] = $var_name . ': ' . $safe_value . ';';
			}
		}
		return $out;
	}

	/**
	 * Sanitizes a token value for safe emission inside a CSS declaration. Removes characters that could break context or close a style tag.
	 *
	 * @param string $value Raw value from repository (already length-capped).
	 * @return string Safe value for CSS, or empty if not safe.
	 */
	private function safe_css_value( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}
		// * Remove characters that could break CSS or close </style>.
		$value = str_replace( array( '<', '>', '\\', "\0", "\n", "\r" ), '', $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		if ( $value === null ) {
			return '';
		}
		return trim( $value );
	}
}
