<?php
/**
 * Emits per-section style payload for section wrappers (Prompt 254). Token overrides as inline style; component overrides as scoped block.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Reads per-section entity style payload. Returns inline declarations for wrapper and optional style block for component overrides.
 */
final class Section_Style_Emitter {

	/** @var Entity_Style_Payload_Repository */
	private Entity_Style_Payload_Repository $payload_repository;

	/** @var Style_Token_Registry|null */
	private ?Style_Token_Registry $token_registry;

	/** @var Component_Override_Registry|null */
	private ?Component_Override_Registry $component_registry;

	public function __construct(
		Entity_Style_Payload_Repository $payload_repository,
		?Style_Token_Registry $token_registry = null,
		?Component_Override_Registry $component_registry = null
	) {
		$this->payload_repository  = $payload_repository;
		$this->token_registry      = $token_registry;
		$this->component_registry = $component_registry;
	}

	/**
	 * Returns inline style string for the section wrapper (token overrides only). Empty when invalid or no overrides.
	 *
	 * @param string $section_key Section template internal_key.
	 * @return string Declarations for style="..." (e.g. "--aio-color-primary: #333;") or empty.
	 */
	public function get_inline_style_for_section( string $section_key ): string {
		if ( $section_key === '' ) {
			return '';
		}
		$payload = $this->payload_repository->get_payload( 'section_template', $section_key );
		$token_overrides = $payload[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] ?? array();
		return $this->emit_token_declarations( $token_overrides );
	}

	/**
	 * Returns a scoped style block for component overrides inside this section, or empty. Safe to inject as first child of section wrapper.
	 *
	 * @param string $section_key Section template internal_key.
	 * @return string HTML <style>...</style> or empty string.
	 */
	public function get_component_override_style_block( string $section_key ): string {
		if ( $section_key === '' || $this->component_registry === null || ! $this->component_registry->is_loaded() ) {
			return '';
		}
		$payload = $this->payload_repository->get_payload( 'section_template', $section_key );
		$component_overrides = $payload[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] ?? array();
		if ( ! is_array( $component_overrides ) || empty( $component_overrides ) ) {
			return '';
		}
		$wrapper_class = 'aio-s-' . preg_replace( '/[^a-z0-9_-]/', '', str_replace( array( ' ', "\t" ), '_', $section_key ) );
		if ( strlen( $wrapper_class ) > 128 ) {
			$wrapper_class = substr( $wrapper_class, 0, 128 );
		}
		$blocks = array();
		foreach ( $component_overrides as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
				continue;
			}
			$allowed = $this->component_registry->get_allowed_token_overrides( $component_id );
			$element_role = $this->component_registry->get_element_role( $component_id );
			if ( $element_role === '' || empty( $allowed ) ) {
				continue;
			}
			$declarations = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( ! is_string( $var_name ) || ! is_string( $value ) || ! in_array( $var_name, $allowed, true ) ) {
					continue;
				}
				$safe = $this->safe_css_value( $value );
				if ( $safe !== '' ) {
					$declarations[] = $var_name . ': ' . $safe . ';';
				}
			}
			if ( empty( $declarations ) ) {
				continue;
			}
			$role = preg_replace( '/[^a-z0-9_-]/', '', $element_role );
			if ( $role !== '' && strlen( $role ) <= 64 ) {
				$selector = '.' . $wrapper_class . ' [class*="aio-s-"][class*="__' . $role . '"]';
				$blocks[] = $selector . '{' . implode( ' ', $declarations ) . '}';
			}
		}
		if ( empty( $blocks ) ) {
			return '';
		}
		return '<style type="text/css">' . implode( ' ', $blocks ) . '</style>';
	}

	/**
	 * Builds declaration string for token overrides (--aio-* only).
	 *
	 * @param array<string, array<string, string>> $token_overrides
	 * @return string
	 */
	private function emit_token_declarations( array $token_overrides ): string {
		$out = array();
		if ( $this->token_registry !== null && $this->token_registry->is_loaded() ) {
			$group_names = $this->token_registry->get_token_group_names();
			foreach ( $token_overrides as $group => $names ) {
				if ( ! is_string( $group ) || ! in_array( $group, $group_names, true ) || $group === 'component' || ! is_array( $names ) ) {
					continue;
				}
				$allowed = $this->token_registry->get_allowed_names_for_group( $group );
				foreach ( $names as $name => $value ) {
					if ( ! is_string( $name ) || ! is_string( $value ) || ! in_array( $name, $allowed, true ) ) {
						continue;
					}
					$var_name = $this->token_registry->get_token_variable_name( $group, $name );
					if ( $var_name === '' ) {
						continue;
					}
					$safe = $this->safe_css_value( $value );
					if ( $safe !== '' ) {
						$out[] = $var_name . ': ' . $safe . ';';
					}
				}
			}
		}
		return implode( ' ', $out );
	}

	private function safe_css_value( string $value ): string {
		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}
		$value = str_replace( array( '<', '>', '\\', "\0", "\n", "\r" ), '', $value );
		$value = preg_replace( '/\s+/', ' ', $value );
		return $value !== null ? trim( $value ) : '';
	}
}
