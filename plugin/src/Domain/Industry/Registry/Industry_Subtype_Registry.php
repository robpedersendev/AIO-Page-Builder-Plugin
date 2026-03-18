<?php
/**
 * Read-only registry of industry subtypes (industry-subtype-schema.md; Prompt 413/414).
 * Exposes get by subtype_key and get_for_parent(parent_industry_key). Invalid definitions are skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry subtype definitions. Subtypes are overlays on a parent industry pack.
 */
final class Industry_Subtype_Registry {

	public const FIELD_SUBTYPE_KEY         = 'subtype_key';
	public const FIELD_PARENT_INDUSTRY_KEY = 'parent_industry_key';
	public const FIELD_LABEL               = 'label';
	public const FIELD_SUMMARY             = 'summary';
	public const FIELD_STATUS              = 'status';
	public const FIELD_VERSION_MARKER      = 'version_marker';

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_DRAFT      = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_VERSION = '1';
	private const KEY_PATTERN      = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LEN      = 64;

	/** @var array<string, array<string, mixed>> Map of subtype_key => definition. */
	private array $by_key = array();

	/** @var array<string, list<array<string, mixed>>> Map of parent_industry_key => list of subtypes. */
	private array $by_parent = array();

	/**
	 * Returns built-in subtype definitions (Prompt 415). Used by bootstrap to load seed subtypes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_builtin_definitions(): array {
		return \AIOPageBuilder\Domain\Industry\Registry\Subtypes\Builtin_Subtypes::get_definitions();
	}

	/**
	 * Loads subtype definitions. Skips invalid and duplicate keys (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of subtype definitions.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_key    = array();
		$this->by_parent = array();
		foreach ( $definitions as $def ) {
			if ( ! is_array( $def ) ) {
				continue;
			}
			$key    = trim( (string) ( $def[ self::FIELD_SUBTYPE_KEY ] ?? '' ) );
			$parent = trim( (string) ( $def[ self::FIELD_PARENT_INDUSTRY_KEY ] ?? '' ) );
			if ( $key === '' || $parent === '' ) {
				continue;
			}
			if ( strlen( $key ) > self::KEY_MAX_LEN || ! preg_match( self::KEY_PATTERN, $key ) ) {
				continue;
			}
			$status = trim( (string) ( $def[ self::FIELD_STATUS ] ?? '' ) );
			if ( ! in_array( $status, array( self::STATUS_ACTIVE, self::STATUS_DRAFT, self::STATUS_DEPRECATED ), true ) ) {
				$status = self::STATUS_ACTIVE;
			}
			$version = trim( (string) ( $def[ self::FIELD_VERSION_MARKER ] ?? '' ) );
			if ( $version !== '' && $version !== self::SUPPORTED_VERSION ) {
				continue;
			}
			if ( ! isset( $this->by_key[ $key ] ) ) {
				$normalized           = array(
					self::FIELD_SUBTYPE_KEY         => $key,
					self::FIELD_PARENT_INDUSTRY_KEY => $parent,
					self::FIELD_LABEL               => trim( (string) ( $def[ self::FIELD_LABEL ] ?? '' ) ),
					self::FIELD_SUMMARY             => trim( (string) ( $def[ self::FIELD_SUMMARY ] ?? '' ) ),
					self::FIELD_STATUS              => $status,
					self::FIELD_VERSION_MARKER      => $version !== '' ? $version : self::SUPPORTED_VERSION,
				);
				$this->by_key[ $key ] = $normalized;
				if ( ! isset( $this->by_parent[ $parent ] ) ) {
					$this->by_parent[ $parent ] = array();
				}
				$this->by_parent[ $parent ][] = $normalized;
			}
		}
	}

	/**
	 * Returns subtype definition by subtype_key, or null if not found.
	 *
	 * @param string $subtype_key Subtype key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $subtype_key ): ?array {
		$key = trim( $subtype_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns all loaded subtype definitions (for linting and coverage analysis).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all(): array {
		return array_values( $this->by_key );
	}

	/**
	 * Returns subtypes for the given parent industry key. Only active by default.
	 *
	 * @param string $parent_industry_key Parent industry pack key.
	 * @param bool   $active_only If true, only return status = active.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_parent( string $parent_industry_key, bool $active_only = true ): array {
		$parent = trim( $parent_industry_key );
		$list   = $this->by_parent[ $parent ] ?? array();
		if ( $active_only ) {
			return array_values(
				array_filter(
					$list,
					function ( array $def ): bool {
						return ( $def[ self::FIELD_STATUS ] ?? '' ) === self::STATUS_ACTIVE;
					}
				)
			);
		}
		return $list;
	}
}
