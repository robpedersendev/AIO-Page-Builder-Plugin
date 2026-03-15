<?php
/**
 * Read/write repository for global styling settings (Prompt 246).
 * Structured, versioned; invalid keys/values fail closed. Separate from aio_applied_design_tokens.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Styling;

defined( 'ABSPATH' ) || exit;

/**
 * Persists and retrieves global style settings. Filters all writes through token and component registries.
 */
final class Global_Style_Settings_Repository {

	/** Max length for any single token value when registry has no max_length. */
	private const FALLBACK_MAX_LENGTH = 512;

	/** @var Style_Token_Registry|null */
	private ?Style_Token_Registry $token_registry;

	/** @var Component_Override_Registry|null */
	private ?Component_Override_Registry $component_registry;

	public function __construct(
		?Style_Token_Registry $token_registry = null,
		?Component_Override_Registry $component_registry = null
	) {
		$this->token_registry     = $token_registry;
		$this->component_registry = $component_registry;
	}

	/**
	 * Returns the full settings array, normalized to schema. Missing/corrupt data returns defaults.
	 *
	 * @return array{version: string, global_tokens: array, global_component_overrides: array}
	 */
	public function get_full(): array {
		$raw = \get_option( Global_Style_Settings_Schema::OPTION_KEY, array() );
		if ( ! is_array( $raw ) ) {
			return Global_Style_Settings_Schema::get_defaults();
		}
		$version = isset( $raw[ Global_Style_Settings_Schema::KEY_VERSION ] ) && is_string( $raw[ Global_Style_Settings_Schema::KEY_VERSION ] )
			? $raw[ Global_Style_Settings_Schema::KEY_VERSION ]
			: Global_Style_Settings_Schema::SCHEMA_VERSION;
		$tokens = isset( $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] ) && is_array( $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] )
			? $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ]
			: array();
		$overrides = isset( $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] ) && is_array( $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] )
			? $raw[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ]
			: array();
		return array(
			Global_Style_Settings_Schema::KEY_VERSION                   => $version,
			Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS             => $tokens,
			Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES => $overrides,
		);
	}

	/**
	 * Returns the stored schema version string.
	 *
	 * @return string
	 */
	public function get_version(): string {
		$full = $this->get_full();
		$v    = $full[ Global_Style_Settings_Schema::KEY_VERSION ] ?? '';
		return is_string( $v ) ? $v : Global_Style_Settings_Schema::SCHEMA_VERSION;
	}

	/**
	 * Returns global token values [ group => [ name => value ] ]. Only allowed keys are returned (filtered on read if registry available).
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_global_tokens(): array {
		$full   = $this->get_full();
		$tokens = $full[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] ?? array();
		if ( ! is_array( $tokens ) ) {
			return array();
		}
		if ( $this->token_registry === null || ! $this->token_registry->is_loaded() ) {
			return $tokens;
		}
		$out = array();
		foreach ( $tokens as $group => $names ) {
			if ( ! is_string( $group ) || ! is_array( $names ) ) {
				continue;
			}
			$allowed_names = $this->token_registry->get_allowed_names_for_group( $group );
			if ( empty( $allowed_names ) ) {
				continue;
			}
			$out[ $group ] = array();
			foreach ( $names as $name => $value ) {
				if ( is_string( $name ) && in_array( $name, $allowed_names, true ) && is_string( $value ) ) {
					$out[ $group ][ $name ] = $value;
				}
			}
		}
		return $out;
	}

	/**
	 * Persists global token values. Invalid groups/names are stripped; values are length-capped.
	 *
	 * @param array<string, array<string, string>> $tokens
	 * @return bool True if option was updated.
	 */
	public function set_global_tokens( array $tokens ): bool {
		$filtered = $this->filter_tokens_for_write( $tokens );
		$full     = $this->get_full();
		$full[ Global_Style_Settings_Schema::KEY_GLOBAL_TOKENS ] = $filtered;
		return \update_option( Global_Style_Settings_Schema::OPTION_KEY, $full );
	}

	/**
	 * Returns global component overrides [ component_id => [ token_var_name => value ] ].
	 *
	 * @return array<string, array<string, string>>
	 */
	public function get_global_component_overrides(): array {
		$full     = $this->get_full();
		$overrides = $full[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] ?? array();
		if ( ! is_array( $overrides ) ) {
			return array();
		}
		if ( $this->component_registry === null || ! $this->component_registry->is_loaded() ) {
			return $overrides;
		}
		$out = array();
		foreach ( $overrides as $component_id => $pairs ) {
			if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
				continue;
			}
			$allowed = $this->component_registry->get_allowed_token_overrides( $component_id );
			if ( empty( $allowed ) ) {
				continue;
			}
			$out[ $component_id ] = array();
			foreach ( $pairs as $var_name => $value ) {
				if ( is_string( $var_name ) && in_array( $var_name, $allowed, true ) && is_string( $value ) ) {
					$out[ $component_id ][ $var_name ] = $this->cap_value_length( $value, self::FALLBACK_MAX_LENGTH );
				}
			}
		}
		return $out;
	}

	/**
	 * Persists global component overrides. Invalid component ids or token names are stripped.
	 *
	 * @param array<string, array<string, string>> $overrides
	 * @return bool
	 */
	public function set_global_component_overrides( array $overrides ): bool {
		$filtered = $this->filter_component_overrides_for_write( $overrides );
		$full     = $this->get_full();
		$full[ Global_Style_Settings_Schema::KEY_GLOBAL_COMPONENT_OVERRIDES ] = $filtered;
		return \update_option( Global_Style_Settings_Schema::OPTION_KEY, $full );
	}

	/**
	 * Resets global tokens and component overrides to schema defaults. Version is preserved.
	 *
	 * @return bool
	 */
	public function reset_to_defaults(): bool {
		$defaults = Global_Style_Settings_Schema::get_defaults();
		return \update_option( Global_Style_Settings_Schema::OPTION_KEY, $defaults );
	}

	/**
	 * Filters token array: only allowed group/name; values capped by spec max_length.
	 *
	 * @param array<string, array<string, string>> $tokens
	 * @return array<string, array<string, string>>
	 */
	private function filter_tokens_for_write( array $tokens ): array {
		$out = array();
		if ( $this->token_registry !== null && $this->token_registry->is_loaded() ) {
			$max_len = self::FALLBACK_MAX_LENGTH;
			foreach ( $tokens as $group => $names ) {
				if ( ! is_string( $group ) || ! is_array( $names ) ) {
					continue;
				}
				$allowed = $this->token_registry->get_allowed_names_for_group( $group );
				$san     = $this->token_registry->get_sanitization_for_group( $group );
				$cap     = isset( $san['max_length'] ) && is_numeric( $san['max_length'] ) ? (int) $san['max_length'] : $max_len;
				$out[ $group ] = array();
				foreach ( $names as $name => $value ) {
					if ( is_string( $name ) && in_array( $name, $allowed, true ) && is_string( $value ) ) {
						$out[ $group ][ $name ] = $this->cap_value_length( $value, $cap );
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Filters component overrides for write: only allowed component_id and token names.
	 *
	 * @param array<string, array<string, string>> $overrides
	 * @return array<string, array<string, string>>
	 */
	private function filter_component_overrides_for_write( array $overrides ): array {
		$out = array();
		if ( $this->component_registry !== null && $this->component_registry->is_loaded() ) {
			foreach ( $overrides as $component_id => $pairs ) {
				if ( ! is_string( $component_id ) || ! is_array( $pairs ) ) {
					continue;
				}
				$allowed_tokens = $this->component_registry->get_allowed_token_overrides( $component_id );
				if ( empty( $allowed_tokens ) ) {
					continue;
				}
				$out[ $component_id ] = array();
				foreach ( $pairs as $var_name => $value ) {
					if ( is_string( $var_name ) && in_array( $var_name, $allowed_tokens, true ) && is_string( $value ) ) {
						$out[ $component_id ][ $var_name ] = $this->cap_value_length( $value, self::FALLBACK_MAX_LENGTH );
					}
				}
			}
		}
		return $out;
	}

	private function cap_value_length( string $value, int $max ): string {
		if ( $max <= 0 ) {
			return '';
		}
		return \strlen( $value ) > $max ? \substr( $value, 0, $max ) : $value;
	}
}
