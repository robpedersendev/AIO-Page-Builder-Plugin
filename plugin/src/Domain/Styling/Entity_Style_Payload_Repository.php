<?php
/**
 * Persistence for per-entity style payloads (Prompt 251). Keyed by entity_type and entity_key; no raw CSS.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes per-entity style payloads. Unsupported entity types or invalid shape fail safely.
 */
final class Entity_Style_Payload_Repository {

	/** Max length for entity_key. */
	private const MAX_ENTITY_KEY_LENGTH = 128;

	/**
	 * Returns the full option array, normalized to schema. Missing/corrupt returns defaults.
	 *
	 * @return array{version: string, payloads: array<string, array<string, array>>}
	 */
	public function get_full(): array {
		$raw = \get_option( Entity_Style_Payload_Schema::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			return Entity_Style_Payload_Schema::get_default_option();
		}
		$version      = isset( $raw[ Entity_Style_Payload_Schema::KEY_VERSION ] ) && is_string( $raw[ Entity_Style_Payload_Schema::KEY_VERSION ] )
			? $raw[ Entity_Style_Payload_Schema::KEY_VERSION ]
			: Entity_Style_Payload_Schema::SCHEMA_VERSION;
		$payloads     = isset( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOADS ] ) && is_array( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOADS ] )
			? $raw[ Entity_Style_Payload_Schema::KEY_PAYLOADS ]
			: array();
		$out_payloads = array();
		foreach ( Entity_Style_Payload_Schema::ENTITY_TYPES as $type ) {
			$out_payloads[ $type ] = isset( $payloads[ $type ] ) && is_array( $payloads[ $type ] ) ? $payloads[ $type ] : array();
		}
		return array(
			Entity_Style_Payload_Schema::KEY_VERSION  => $version,
			Entity_Style_Payload_Schema::KEY_PAYLOADS => $out_payloads,
		);
	}

	/**
	 * Returns payload for an entity. Returns default payload when missing or corrupt.
	 *
	 * @param string $entity_type One of Entity_Style_Payload_Schema::ENTITY_TYPES.
	 * @param string $entity_key  Stable key (e.g. section_key, template_key).
	 * @return array{version: string, token_overrides: array, component_overrides: array}
	 */
	public function get_payload( string $entity_type, string $entity_key ): array {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) ) {
			return Entity_Style_Payload_Schema::get_default_payload();
		}
		$key = $this->normalize_entity_key( $entity_key );
		if ( $key === '' ) {
			return Entity_Style_Payload_Schema::get_default_payload();
		}
		$full     = $this->get_full();
		$payloads = $full[ Entity_Style_Payload_Schema::KEY_PAYLOADS ][ $entity_type ] ?? array();
		$raw      = isset( $payloads[ $key ] ) && is_array( $payloads[ $key ] ) ? $payloads[ $key ] : array();
		return $this->normalize_payload( $raw );
	}

	/**
	 * Persists payload for an entity. Invalid type/key or invalid payload shape are rejected (no write).
	 * For full security (prohibited-pattern checks), use persist_entity_payload_result with a Style_Validation_Result from the sanitizer.
	 *
	 * @param string $entity_type One of Entity_Style_Payload_Schema::ENTITY_TYPES.
	 * @param string $entity_key  Stable key.
	 * @param array  $payload     Must contain token_overrides and component_overrides (arrays); no raw CSS.
	 * @return bool True if option was updated.
	 */
	public function set_payload( string $entity_type, string $entity_key, array $payload ): bool {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) ) {
			return false;
		}
		$key = $this->normalize_entity_key( $entity_key );
		if ( $key === '' ) {
			return false;
		}
		$normalized = $this->normalize_payload( $payload );
		$full       = $this->get_full();
		$full[ Entity_Style_Payload_Schema::KEY_PAYLOADS ][ $entity_type ][ $key ] = $normalized;
		return \update_option( Entity_Style_Payload_Schema::OPTION_KEY, $full );
	}

	/**
	 * Persists entity payload only when the validation result is valid (sanitizer-approved). Use after Styles_JSON_Normalizer + Styles_JSON_Sanitizer.
	 *
	 * @param string                  $entity_type
	 * @param string                  $entity_key
	 * @param Style_Validation_Result $result
	 * @return bool True if result was valid and option was updated.
	 */
	public function persist_entity_payload_result( string $entity_type, string $entity_key, Style_Validation_Result $result ): bool {
		if ( ! $result->is_valid() ) {
			return false;
		}
		return $this->set_payload( $entity_type, $entity_key, $result->get_sanitized() );
	}

	/**
	 * Removes payload for an entity.
	 *
	 * @param string $entity_type
	 * @param string $entity_key
	 * @return bool True if option was updated.
	 */
	public function delete_payload( string $entity_type, string $entity_key ): bool {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) ) {
			return false;
		}
		$key = $this->normalize_entity_key( $entity_key );
		if ( $key === '' ) {
			return false;
		}
		$full = $this->get_full();
		unset( $full[ Entity_Style_Payload_Schema::KEY_PAYLOADS ][ $entity_type ][ $key ] );
		return \update_option( Entity_Style_Payload_Schema::OPTION_KEY, $full );
	}

	/**
	 * Returns all payloads for an entity type (key => normalized payload).
	 *
	 * @param string $entity_type
	 * @return array<string, array{version: string, token_overrides: array, component_overrides: array}>
	 */
	public function get_all_payloads_for_type( string $entity_type ): array {
		if ( ! Entity_Style_Payload_Schema::is_allowed_entity_type( $entity_type ) ) {
			return array();
		}
		$full     = $this->get_full();
		$payloads = $full[ Entity_Style_Payload_Schema::KEY_PAYLOADS ][ $entity_type ] ?? array();
		$out      = array();
		foreach ( $payloads as $k => $raw ) {
			if ( is_string( $k ) && is_array( $raw ) ) {
				$out[ $k ] = $this->normalize_payload( $raw );
			}
		}
		return $out;
	}

	/**
	 * Normalizes a payload to schema shape. Only version and two branches; branches must be array of string key => string value (nested). No raw CSS.
	 *
	 * @param array $raw
	 * @return array{version: string, token_overrides: array, component_overrides: array}
	 */
	private function normalize_payload( array $raw ): array {
		$version = isset( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] ) && is_string( $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ] )
			? $raw[ Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION ]
			: Entity_Style_Payload_Schema::PAYLOAD_VERSION;
		$tokens  = $this->normalize_token_overrides( isset( $raw[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] ) ? $raw[ Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES ] : array() );
		$comps   = $this->normalize_component_overrides( isset( $raw[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] ) ? $raw[ Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES ] : array() );
		return array(
			Entity_Style_Payload_Schema::KEY_PAYLOAD_VERSION     => $version,
			Entity_Style_Payload_Schema::KEY_TOKEN_OVERRIDES     => $tokens,
			Entity_Style_Payload_Schema::KEY_COMPONENT_OVERRIDES => $comps,
		);
	}

	/**
	 * Keeps only structure group => [ name => value ] with string keys and string values.
	 *
	 * @param mixed $raw
	 * @return array<string, array<string, string>>
	 */
	private function normalize_token_overrides( $raw ): array {
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
	 * Keeps only structure component_id => [ token_var_name => value ] with string keys and string values.
	 *
	 * @param mixed $raw
	 * @return array<string, array<string, string>>
	 */
	private function normalize_component_overrides( $raw ): array {
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

	private function normalize_entity_key( string $entity_key ): string {
		$key = \sanitize_key( $entity_key );
		if ( strlen( $key ) > self::MAX_ENTITY_KEY_LENGTH ) {
			$key = substr( $key, 0, self::MAX_ENTITY_KEY_LENGTH );
		}
		return $key;
	}
}
