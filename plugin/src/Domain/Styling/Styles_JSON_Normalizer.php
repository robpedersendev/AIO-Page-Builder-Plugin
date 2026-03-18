<?php
/**
 * Normalizes structured style input into deterministic internal form (Prompt 252).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Produces canonical array shapes for tokens, component overrides, and entity payloads.
 */
final class Styles_JSON_Normalizer {

	/**
	 * Normalizes raw input to global token shape [ group => [ name => value ] ]. Only string keys and string values retained.
	 *
	 * @param mixed $raw
	 * @return array<string, array<string, string>>
	 */
	public function normalize_global_tokens( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $group => $names ) {
			if ( ! is_string( $group ) || ! is_array( $names ) ) {
				continue;
			}
			$out[ $group ] = array();
			foreach ( $names as $name => $value ) {
				if ( is_string( $name ) && is_string( $value ) ) {
					$out[ $group ][ $name ] = $value;
				}
			}
		}
		return $out;
	}

	/**
	 * Normalizes raw input to global component override shape [ component_id => [ token_var_name => value ] ].
	 *
	 * @param mixed $raw
	 * @return array<string, array<string, string>>
	 */
	public function normalize_global_component_overrides( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$out = array();
		foreach ( $raw as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
				continue;
			}
			$out[ $component_id ] = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( is_string( $var_name ) && is_string( $value ) ) {
					$out[ $component_id ][ $var_name ] = $value;
				}
			}
		}
		return $out;
	}

	/**
	 * Normalizes raw input to entity payload shape (version, token_overrides, component_overrides).
	 *
	 * @param mixed $raw
	 * @return array{version: string, token_overrides: array<string, array<string, string>>, component_overrides: array<string, array<string, string>>}
	 */
	public function normalize_entity_payload( $raw ): array {
		$default = Entity_Style_Payload_Schema::get_default_payload();
		if ( ! is_array( $raw ) ) {
			return $default;
		}
		$token_overrides     = $this->normalize_global_tokens( $raw[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] ?? array() );
		$component_overrides = $this->normalize_global_component_overrides( $raw[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] ?? array() );
		$version             = isset( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] ) && is_string( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] )
			? $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ]
			: Entity_Style_Payload_Schema::PAYLOAD_VERSION;
		return array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => $version,
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => $token_overrides,
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => $component_overrides,
		);
	}
}
