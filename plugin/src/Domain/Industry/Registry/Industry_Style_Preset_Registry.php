<?php
/**
 * Read-only registry of industry style presets (industry-style-preset-schema.md).
 * Loads preset definitions; exposes lookup by key and list by industry. Invalid definitions are skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry style preset definitions. Read-only after load. Compatible with styling subsystem: no raw CSS.
 */
final class Industry_Style_Preset_Registry {

	public const FIELD_STYLE_PRESET_KEY        = 'style_preset_key';
	public const FIELD_LABEL                   = 'label';
	public const FIELD_VERSION_MARKER          = 'version_marker';
	public const FIELD_STATUS                  = 'status';
	public const FIELD_INDUSTRY_KEY            = 'industry_key';
	public const FIELD_TOKEN_VALUES            = 'token_values';
	public const FIELD_TOKEN_SET_REF           = 'token_set_ref';
	public const FIELD_COMPONENT_OVERRIDE_REFS = 'component_override_refs';
	public const FIELD_DESCRIPTION             = 'description';
	public const FIELD_PREVIEW_METADATA        = 'preview_metadata';

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_DRAFT      = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN             = '#^[a-z0-9_-]+$#';
	private const INDUSTRY_KEY_PATTERN    = '#^[a-z0-9_-]+$#';
	private const TOKEN_NAME_PATTERN      = '#^--aio-[a-z0-9_-]+$#';
	/** Prohibited value substrings per styling-sanitization-rules (no script injection, no raw CSS). */
	private const PROHIBITED_VALUE_PATTERNS = array( 'url(', 'expression(', 'javascript:', 'vbscript:', 'data:', '<', '>', '{', '}' );

	/** @var array<string, array<string, mixed>> Map of style_preset_key => preset definition. */
	private array $by_key = array();

	/** @var array<int, array<string, mixed>> All valid presets in load order. */
	private array $all = array();

	/**
	 * Loads preset definitions. Validates each; skips invalid and duplicate keys (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of preset definitions.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $definitions as $preset ) {
			if ( ! is_array( $preset ) ) {
				continue;
			}
			$errors = $this->validate_preset( $preset );
			if ( $errors !== array() ) {
				continue;
			}
			$key = trim( (string) ( $preset[ self::FIELD_STYLE_PRESET_KEY ] ?? '' ) );
			if ( $key !== '' && ! isset( $this->by_key[ $key ] ) ) {
				$normalized           = $this->normalize_preset( $preset );
				$this->by_key[ $key ] = $normalized;
				$this->all[]          = $normalized;
			}
		}
	}

	/**
	 * Returns preset definition by style_preset_key, or null if not found.
	 *
	 * @param string $style_preset_key Preset key (e.g. referenced by industry pack token_preset_ref).
	 * @return array<string, mixed>|null
	 */
	public function get( string $style_preset_key ): ?array {
		$key = trim( $style_preset_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded presets.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all(): array {
		return $this->all;
	}

	/**
	 * Returns presets associated with the given industry_key. Empty array if none.
	 *
	 * @param string $industry_key Industry key (Industry_Pack_Schema::FIELD_INDUSTRY_KEY).
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_industry( string $industry_key ): array {
		$want = trim( $industry_key );
		if ( $want === '' ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $preset ) {
			$ik = isset( $preset[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $preset[ self::FIELD_INDUSTRY_KEY ] )
				? trim( $preset[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $ik === $want ) {
				$out[] = $preset;
			}
		}
		return $out;
	}

	/**
	 * Returns presets with the given status (e.g. active). Empty array if none.
	 *
	 * @param string $status One of STATUS_ACTIVE, STATUS_DRAFT, STATUS_DEPRECATED.
	 * @return array<int, array<string, mixed>>
	 */
	public function list_by_status( string $status ): array {
		$out = array();
		foreach ( $this->all as $preset ) {
			$s = isset( $preset[ self::FIELD_STATUS ] ) && is_string( $preset[ self::FIELD_STATUS ] )
				? $preset[ self::FIELD_STATUS ]
				: '';
			if ( $s === $status ) {
				$out[] = $preset;
			}
		}
		return $out;
	}

	/**
	 * Validates a single preset. Returns list of error codes; empty array when valid.
	 *
	 * @param array<string, mixed> $preset Raw preset definition.
	 * @return array<int, string>
	 */
	public function validate_preset( array $preset ): array {
		$errors = array();
		$key    = isset( $preset[ self::FIELD_STYLE_PRESET_KEY ] ) && is_string( $preset[ self::FIELD_STYLE_PRESET_KEY ] )
			? trim( $preset[ self::FIELD_STYLE_PRESET_KEY ] )
			: '';
		if ( $key === '' ) {
			$errors[] = 'missing_style_preset_key';
		} elseif ( strlen( $key ) > 64 || ! preg_match( self::KEY_PATTERN, $key ) ) {
			$errors[] = 'invalid_style_preset_key';
		}
		if ( ! isset( $preset[ self::FIELD_LABEL ] ) || ! is_string( $preset[ self::FIELD_LABEL ] ) || trim( $preset[ self::FIELD_LABEL ] ) === '' ) {
			$errors[] = 'missing_label';
		}
		$version = isset( $preset[ self::FIELD_VERSION_MARKER ] ) && is_string( $preset[ self::FIELD_VERSION_MARKER ] )
			? trim( $preset[ self::FIELD_VERSION_MARKER ] )
			: '';
		if ( $version !== self::SUPPORTED_SCHEMA_VERSION ) {
			$errors[] = 'unsupported_version';
		}
		$status = isset( $preset[ self::FIELD_STATUS ] ) && is_string( $preset[ self::FIELD_STATUS ] )
			? $preset[ self::FIELD_STATUS ]
			: '';
		if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_DRAFT, self::STATUS_DEPRECATED ), true ) ) {
			$errors[] = 'invalid_status';
		}
		$industry_key = $preset[ self::FIELD_INDUSTRY_KEY ] ?? null;
		if ( $industry_key !== null && is_string( $industry_key ) && trim( $industry_key ) !== '' ) {
			if ( strlen( trim( $industry_key ) ) > 64 || ! preg_match( self::INDUSTRY_KEY_PATTERN, trim( $industry_key ) ) ) {
				$errors[] = 'invalid_industry_key';
			}
		}
		$token_values = $preset[ self::FIELD_TOKEN_VALUES ] ?? null;
		if ( $token_values !== null && is_array( $token_values ) ) {
			foreach ( $token_values as $token_name => $value ) {
				if ( ! is_string( $token_name ) || ! preg_match( self::TOKEN_NAME_PATTERN, $token_name ) ) {
					$errors[] = 'invalid_token_name';
					break;
				}
				if ( ! is_string( $value ) ) {
					$errors[] = 'invalid_token_value_type';
					break;
				}
				foreach ( self::PROHIBITED_VALUE_PATTERNS as $forbidden ) {
					if ( strpos( $value, $forbidden ) !== false ) {
						$errors[] = 'prohibited_token_value';
						break 2;
					}
				}
			}
		}
		return $errors;
	}

	/**
	 * Normalizes preset for storage (trim strings, ensure types). Call only after validate_preset passed.
	 *
	 * @param array<string, mixed> $preset Valid preset.
	 * @return array<string, mixed>
	 */
	private function normalize_preset( array $preset ): array {
		$out = array(
			self::FIELD_STYLE_PRESET_KEY => trim( (string) ( $preset[ self::FIELD_STYLE_PRESET_KEY ] ?? '' ) ),
			self::FIELD_LABEL            => trim( (string) ( $preset[ self::FIELD_LABEL ] ?? '' ) ),
			self::FIELD_VERSION_MARKER   => trim( (string) ( $preset[ self::FIELD_VERSION_MARKER ] ?? '' ) ),
			self::FIELD_STATUS           => (string) ( $preset[ self::FIELD_STATUS ] ?? '' ),
		);
		if ( isset( $preset[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $preset[ self::FIELD_INDUSTRY_KEY ] ) && trim( $preset[ self::FIELD_INDUSTRY_KEY ] ) !== '' ) {
			$out[ self::FIELD_INDUSTRY_KEY ] = trim( $preset[ self::FIELD_INDUSTRY_KEY ] );
		}
		if ( isset( $preset[ self::FIELD_TOKEN_VALUES ] ) && is_array( $preset[ self::FIELD_TOKEN_VALUES ] ) ) {
			$filtered = array();
			foreach ( $preset[ self::FIELD_TOKEN_VALUES ] as $k => $v ) {
				if ( is_string( $k ) && preg_match( self::TOKEN_NAME_PATTERN, $k ) && is_string( $v ) ) {
					$safe = true;
					foreach ( self::PROHIBITED_VALUE_PATTERNS as $forbidden ) {
						if ( strpos( $v, $forbidden ) !== false ) {
							$safe = false;
							break;
						}
					}
					if ( $safe ) {
						$filtered[ $k ] = $v;
					}
				}
			}
			$out[ self::FIELD_TOKEN_VALUES ] = $filtered;
		}
		if ( isset( $preset[ self::FIELD_TOKEN_SET_REF ] ) && is_string( $preset[ self::FIELD_TOKEN_SET_REF ] ) && trim( $preset[ self::FIELD_TOKEN_SET_REF ] ) !== '' ) {
			$out[ self::FIELD_TOKEN_SET_REF ] = trim( $preset[ self::FIELD_TOKEN_SET_REF ] );
		}
		if ( isset( $preset[ self::FIELD_COMPONENT_OVERRIDE_REFS ] ) && is_array( $preset[ self::FIELD_COMPONENT_OVERRIDE_REFS ] ) ) {
			$refs = array();
			foreach ( $preset[ self::FIELD_COMPONENT_OVERRIDE_REFS ] as $ref ) {
				if ( is_string( $ref ) && trim( $ref ) !== '' ) {
					$refs[] = trim( $ref );
				}
			}
			$out[ self::FIELD_COMPONENT_OVERRIDE_REFS ] = array_values( array_unique( $refs ) );
		}
		if ( isset( $preset[ self::FIELD_DESCRIPTION ] ) && is_string( $preset[ self::FIELD_DESCRIPTION ] ) ) {
			$out[ self::FIELD_DESCRIPTION ] = trim( $preset[ self::FIELD_DESCRIPTION ] );
		}
		if ( isset( $preset[ self::FIELD_PREVIEW_METADATA ] ) && is_array( $preset[ self::FIELD_PREVIEW_METADATA ] ) ) {
			$out[ self::FIELD_PREVIEW_METADATA ] = $preset[ self::FIELD_PREVIEW_METADATA ];
		}
		return $out;
	}
}
