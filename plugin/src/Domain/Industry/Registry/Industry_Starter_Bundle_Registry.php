<?php
/**
 * Read-only registry of industry starter bundles (industry-starter-bundle-schema.md).
 * Loads bundle definitions; exposes get by key, get_for_industry, and list_all. Invalid definitions are skipped at load.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Registry of industry starter bundle definitions. Read-only after load. Bundles are overlays; core registries remain authoritative.
 */
final class Industry_Starter_Bundle_Registry {

	public const FIELD_BUNDLE_KEY                   = 'bundle_key';
	public const FIELD_INDUSTRY_KEY                 = 'industry_key';
	public const FIELD_LABEL                        = 'label';
	public const FIELD_SUMMARY                      = 'summary';
	public const FIELD_STATUS                       = 'status';
	public const FIELD_VERSION_MARKER               = 'version_marker';
	public const FIELD_RECOMMENDED_PAGE_FAMILIES   = 'recommended_page_families';
	public const FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS = 'recommended_page_template_refs';
	public const FIELD_RECOMMENDED_SECTION_REFS     = 'recommended_section_refs';
	public const FIELD_TOKEN_PRESET_REF             = 'token_preset_ref';
	public const FIELD_CTA_GUIDANCE_REF             = 'cta_guidance_ref';
	public const FIELD_LPAGERY_GUIDANCE_REF         = 'lpagery_guidance_ref';
	public const FIELD_METADATA                     = 'metadata';

	public const STATUS_ACTIVE    = 'active';
	public const STATUS_DRAFT     = 'draft';
	public const STATUS_DEPRECATED = 'deprecated';

	public const SUPPORTED_SCHEMA_VERSION = '1';
	private const KEY_PATTERN = '#^[a-z0-9_-]+$#';
	private const KEY_MAX_LENGTH = 64;

	/** @var array<string, array<string, mixed>> Map of bundle_key => bundle definition. */
	private array $by_key = array();

	/** @var list<array<string, mixed>> All valid bundles in load order. */
	private array $all = array();

	/**
	 * Loads bundle definitions. Validates each; skips invalid and duplicate keys (first wins). Safe: no throw.
	 *
	 * @param array<int, array<string, mixed>> $definitions List of bundle definitions.
	 * @return void
	 */
	public function load( array $definitions ): void {
		$this->by_key = array();
		$this->all    = array();
		foreach ( $definitions as $bundle ) {
			if ( ! \is_array( $bundle ) ) {
				continue;
			}
			$errors = $this->validate_bundle( $bundle );
			if ( $errors !== array() ) {
				continue;
			}
			$key = \trim( (string) ( $bundle[ self::FIELD_BUNDLE_KEY ] ?? '' ) );
			if ( $key !== '' && ! isset( $this->by_key[ $key ] ) ) {
				$normalized = $this->normalize_bundle( $bundle );
				$this->by_key[ $key ] = $normalized;
				$this->all[]          = $normalized;
			}
		}
	}

	/**
	 * Returns bundle definition by bundle_key, or null if not found.
	 *
	 * @param string $bundle_key Bundle key.
	 * @return array<string, mixed>|null
	 */
	public function get( string $bundle_key ): ?array {
		$key = \trim( $bundle_key );
		return $this->by_key[ $key ] ?? null;
	}

	/**
	 * Returns bundles for the given industry_key. Empty array if none.
	 *
	 * @param string $industry_key Industry pack key.
	 * @return list<array<string, mixed>>
	 */
	public function get_for_industry( string $industry_key ): array {
		$want = \trim( $industry_key );
		if ( $want === '' ) {
			return array();
		}
		$out = array();
		foreach ( $this->all as $bundle ) {
			$ik = isset( $bundle[ self::FIELD_INDUSTRY_KEY ] ) && \is_string( $bundle[ self::FIELD_INDUSTRY_KEY ] )
				? \trim( $bundle[ self::FIELD_INDUSTRY_KEY ] )
				: '';
			if ( $ik === $want ) {
				$out[] = $bundle;
			}
		}
		return $out;
	}

	/**
	 * Returns all loaded bundles.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function list_all(): array {
		return $this->all;
	}

	/**
	 * Validates a bundle definition. Returns list of error codes; empty array when valid.
	 *
	 * @param array<string, mixed> $bundle Raw bundle definition.
	 * @return list<string>
	 */
	public function validate_bundle( array $bundle ): array {
		$errors = array();

		$bundle_key = isset( $bundle[ self::FIELD_BUNDLE_KEY ] ) && \is_string( $bundle[ self::FIELD_BUNDLE_KEY ] )
			? \trim( $bundle[ self::FIELD_BUNDLE_KEY ] )
			: '';
		if ( $bundle_key === '' ) {
			$errors[] = 'missing_bundle_key';
		} elseif ( \strlen( $bundle_key ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $bundle_key ) ) {
			$errors[] = 'invalid_bundle_key';
		}

		$industry_key = isset( $bundle[ self::FIELD_INDUSTRY_KEY ] ) && \is_string( $bundle[ self::FIELD_INDUSTRY_KEY ] )
			? \trim( $bundle[ self::FIELD_INDUSTRY_KEY ] )
			: '';
		if ( $industry_key === '' ) {
			$errors[] = 'missing_industry_key';
		} elseif ( \strlen( $industry_key ) > self::KEY_MAX_LENGTH || ! \preg_match( self::KEY_PATTERN, $industry_key ) ) {
			$errors[] = 'invalid_industry_key';
		}

		$label = isset( $bundle[ self::FIELD_LABEL ] ) && \is_string( $bundle[ self::FIELD_LABEL ] )
			? \trim( $bundle[ self::FIELD_LABEL ] )
			: '';
		if ( $label === '' ) {
			$errors[] = 'missing_label';
		}

		$summary = isset( $bundle[ self::FIELD_SUMMARY ] ) && \is_string( $bundle[ self::FIELD_SUMMARY ] )
			? \trim( $bundle[ self::FIELD_SUMMARY ] )
			: '';
		if ( $summary === '' ) {
			$errors[] = 'missing_summary';
		}

		$status = isset( $bundle[ self::FIELD_STATUS ] ) && \is_string( $bundle[ self::FIELD_STATUS ] )
			? $bundle[ self::FIELD_STATUS ]
			: '';
		if ( $status !== self::STATUS_ACTIVE && $status !== self::STATUS_DRAFT && $status !== self::STATUS_DEPRECATED ) {
			$errors[] = 'invalid_status';
		}

		$version = isset( $bundle[ self::FIELD_VERSION_MARKER ] ) && \is_string( $bundle[ self::FIELD_VERSION_MARKER ] )
			? \trim( $bundle[ self::FIELD_VERSION_MARKER ] )
			: '';
		if ( $version !== self::SUPPORTED_SCHEMA_VERSION ) {
			$errors[] = 'unsupported_version';
		}

		return $errors;
	}

	/**
	 * Normalizes a valid bundle to a canonical shape (required + optional keys).
	 *
	 * @param array<string, mixed> $bundle Validated bundle.
	 * @return array<string, mixed>
	 */
	private function normalize_bundle( array $bundle ): array {
		$out = array(
			self::FIELD_BUNDLE_KEY     => \trim( (string) ( $bundle[ self::FIELD_BUNDLE_KEY ] ?? '' ) ),
			self::FIELD_INDUSTRY_KEY   => \trim( (string) ( $bundle[ self::FIELD_INDUSTRY_KEY ] ?? '' ) ),
			self::FIELD_LABEL          => \trim( (string) ( $bundle[ self::FIELD_LABEL ] ?? '' ) ),
			self::FIELD_SUMMARY        => \trim( (string) ( $bundle[ self::FIELD_SUMMARY ] ?? '' ) ),
			self::FIELD_STATUS         => (string) ( $bundle[ self::FIELD_STATUS ] ?? self::STATUS_ACTIVE ),
			self::FIELD_VERSION_MARKER => \trim( (string) ( $bundle[ self::FIELD_VERSION_MARKER ] ?? self::SUPPORTED_SCHEMA_VERSION ) ),
		);

		if ( isset( $bundle[ self::FIELD_RECOMMENDED_PAGE_FAMILIES ] ) && \is_array( $bundle[ self::FIELD_RECOMMENDED_PAGE_FAMILIES ] ) ) {
			$out[ self::FIELD_RECOMMENDED_PAGE_FAMILIES ] = array_values( array_filter( array_map( 'strval', $bundle[ self::FIELD_RECOMMENDED_PAGE_FAMILIES ] ) ) );
		} else {
			$out[ self::FIELD_RECOMMENDED_PAGE_FAMILIES ] = array();
		}

		if ( isset( $bundle[ self::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) && \is_array( $bundle[ self::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) ) {
			$out[ self::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] = array_values( array_filter( array_map( 'strval', $bundle[ self::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] ) ) );
		} else {
			$out[ self::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] = array();
		}

		if ( isset( $bundle[ self::FIELD_RECOMMENDED_SECTION_REFS ] ) && \is_array( $bundle[ self::FIELD_RECOMMENDED_SECTION_REFS ] ) ) {
			$out[ self::FIELD_RECOMMENDED_SECTION_REFS ] = array_values( array_filter( array_map( 'strval', $bundle[ self::FIELD_RECOMMENDED_SECTION_REFS ] ) ) );
		} else {
			$out[ self::FIELD_RECOMMENDED_SECTION_REFS ] = array();
		}

		$out[ self::FIELD_TOKEN_PRESET_REF ] = isset( $bundle[ self::FIELD_TOKEN_PRESET_REF ] ) && \is_string( $bundle[ self::FIELD_TOKEN_PRESET_REF ] )
			? \trim( $bundle[ self::FIELD_TOKEN_PRESET_REF ] )
			: '';
		$out[ self::FIELD_CTA_GUIDANCE_REF ] = isset( $bundle[ self::FIELD_CTA_GUIDANCE_REF ] ) && \is_string( $bundle[ self::FIELD_CTA_GUIDANCE_REF ] )
			? \trim( $bundle[ self::FIELD_CTA_GUIDANCE_REF ] )
			: '';
		$out[ self::FIELD_LPAGERY_GUIDANCE_REF ] = isset( $bundle[ self::FIELD_LPAGERY_GUIDANCE_REF ] ) && \is_string( $bundle[ self::FIELD_LPAGERY_GUIDANCE_REF ] )
			? \trim( $bundle[ self::FIELD_LPAGERY_GUIDANCE_REF ] )
			: '';

		$out[ self::FIELD_METADATA ] = isset( $bundle[ self::FIELD_METADATA ] ) && \is_array( $bundle[ self::FIELD_METADATA ] )
			? $bundle[ self::FIELD_METADATA ]
			: array();

		return $out;
	}
}
