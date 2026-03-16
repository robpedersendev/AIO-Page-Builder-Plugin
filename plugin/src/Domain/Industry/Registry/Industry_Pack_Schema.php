<?php
/**
 * Industry Pack definition schema (industry-pack-extension-contract, industry-pack-schema.md).
 * Required/optional fields, validation, and version rules for industry pack objects.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Schema and validation for Industry Pack objects. Portable, versioned, export-safe.
 */
final class Industry_Pack_Schema {

	/** Stable unique key for the pack (e.g. legal, healthcare). */
	public const FIELD_INDUSTRY_KEY = 'industry_key';

	/** Human-readable pack name. */
	public const FIELD_NAME = 'name';

	/** Short description of the industry vertical. */
	public const FIELD_SUMMARY = 'summary';

	/** Lifecycle status: active | draft | deprecated. */
	public const FIELD_STATUS = 'status';

	/** Schema version (e.g. 1) for load/validate. */
	public const FIELD_VERSION_MARKER = 'version_marker';

	/** Optional: page template families this pack supports. */
	public const FIELD_SUPPORTED_PAGE_FAMILIES = 'supported_page_families';

	/** Optional: preferred section template internal_keys. */
	public const FIELD_PREFERRED_SECTION_KEYS = 'preferred_section_keys';

	/** Optional: discouraged section template internal_keys. */
	public const FIELD_DISCOURAGED_SECTION_KEYS = 'discouraged_section_keys';

	/** Optional: default CTA pattern keys (preferred alias); see industry-cta-pattern-contract. */
	public const FIELD_DEFAULT_CTA_PATTERNS = 'default_cta_patterns';

	/** Optional: preferred CTA pattern keys (Industry_CTA_Pattern_Registry). */
	public const FIELD_PREFERRED_CTA_PATTERNS = 'preferred_cta_patterns';

	/** Optional: discouraged CTA pattern keys. */
	public const FIELD_DISCOURAGED_CTA_PATTERNS = 'discouraged_cta_patterns';

	/** Optional: required CTA pattern keys. */
	public const FIELD_REQUIRED_CTA_PATTERNS = 'required_cta_patterns';

	/** Optional: reference to SEO guidance. */
	public const FIELD_SEO_GUIDANCE_REF = 'seo_guidance_ref';

	/** Optional: helper doc refs overlay. */
	public const FIELD_HELPER_OVERLAY_REFS = 'helper_overlay_refs';

	/** Optional: one-pager doc refs overlay. */
	public const FIELD_ONE_PAGER_OVERLAY_REFS = 'one_pager_overlay_refs';

	/** Optional: LPagery/token preset reference. */
	public const FIELD_TOKEN_PRESET_REF = 'token_preset_ref';

	/** Optional: LPagery rule set reference. */
	public const FIELD_LPAGERY_RULE_REF = 'lpagery_rule_ref';

	/** Optional: AI planning rule reference. */
	public const FIELD_AI_RULE_REF = 'ai_rule_ref';

	/** Optional: arbitrary metadata (no secrets). */
	public const FIELD_METADATA = 'metadata';

	/** Optional: when deprecated/superseded, replacement pack or bundle key (industry-pack-deprecation-contract.md). */
	public const FIELD_REPLACEMENT_REF = 'replacement_ref';

	/** Optional: when deprecated, ISO 8601 or version marker for audit. */
	public const FIELD_DEPRECATED_AT = 'deprecated_at';

	/** Optional: short operator note when deprecated. */
	public const FIELD_DEPRECATION_NOTE = 'deprecation_note';

	/** Status: pack is active and used for overlays/ranking. */
	public const STATUS_ACTIVE = 'active';

	/** Status: pack is draft, not used. */
	public const STATUS_DRAFT = 'draft';

	/** Status: pack is deprecated. */
	public const STATUS_DEPRECATED = 'deprecated';

	/** Supported schema version for version_marker. */
	public const SUPPORTED_SCHEMA_VERSION = '1';

	/** Pattern for industry_key (slug-like). */
	public const INDUSTRY_KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for industry_key. */
	public const INDUSTRY_KEY_MAX_LENGTH = 64;

	/** Max length for name. */
	public const NAME_MAX_LENGTH = 256;

	/** Max length for summary. */
	public const SUMMARY_MAX_LENGTH = 1024;

	/** @var list<string>|null */
	private static ?array $required_fields = null;

	/** @var list<string>|null */
	private static ?array $optional_fields = null;

	/** @var list<string>|null */
	private static ?array $allowed_statuses = null;

	/**
	 * Required field names for industry pack object.
	 *
	 * @return list<string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_INDUSTRY_KEY,
			self::FIELD_NAME,
			self::FIELD_SUMMARY,
			self::FIELD_STATUS,
			self::FIELD_VERSION_MARKER,
		);
		return self::$required_fields;
	}

	/**
	 * Optional field names for industry pack object.
	 *
	 * @return list<string>
	 */
	public static function get_optional_fields(): array {
		if ( self::$optional_fields !== null ) {
			return self::$optional_fields;
		}
		self::$optional_fields = array(
			self::FIELD_SUPPORTED_PAGE_FAMILIES,
			self::FIELD_PREFERRED_SECTION_KEYS,
			self::FIELD_DISCOURAGED_SECTION_KEYS,
			self::FIELD_DEFAULT_CTA_PATTERNS,
			self::FIELD_PREFERRED_CTA_PATTERNS,
			self::FIELD_DISCOURAGED_CTA_PATTERNS,
			self::FIELD_REQUIRED_CTA_PATTERNS,
			self::FIELD_SEO_GUIDANCE_REF,
			self::FIELD_HELPER_OVERLAY_REFS,
			self::FIELD_ONE_PAGER_OVERLAY_REFS,
			self::FIELD_TOKEN_PRESET_REF,
			self::FIELD_LPAGERY_RULE_REF,
			self::FIELD_AI_RULE_REF,
			self::FIELD_METADATA,
			self::FIELD_REPLACEMENT_REF,
			self::FIELD_DEPRECATED_AT,
			self::FIELD_DEPRECATION_NOTE,
		);
		return self::$optional_fields;
	}

	/**
	 * Allowed status values.
	 *
	 * @return list<string>
	 */
	public static function get_allowed_statuses(): array {
		if ( self::$allowed_statuses !== null ) {
			return self::$allowed_statuses;
		}
		self::$allowed_statuses = array(
			self::STATUS_ACTIVE,
			self::STATUS_DRAFT,
			self::STATUS_DEPRECATED,
		);
		return self::$allowed_statuses;
	}

	/**
	 * Whether the given status is allowed.
	 */
	public static function is_allowed_status( string $status ): bool {
		return in_array( $status, self::get_allowed_statuses(), true );
	}

	/**
	 * Whether the given version_marker is a supported schema version.
	 */
	public static function is_supported_version( string $version_marker ): bool {
		return $version_marker === self::SUPPORTED_SCHEMA_VERSION;
	}

	/**
	 * Validates industry pack object. Returns list of errors; empty array means valid.
	 *
	 * @param array<string, mixed> $pack Pack definition.
	 * @return array<int, array{code: string, field?: string}> Empty if valid.
	 */
	public static function validate_pack( array $pack ): array {
		$errors = array();

		foreach ( self::get_required_fields() as $field ) {
			$val = $pack[ $field ] ?? null;
			if ( $val === null || ( is_string( $val ) && trim( $val ) === '' ) ) {
				$errors[] = array( 'code' => 'missing_required', 'field' => $field );
			}
		}

		$key = isset( $pack[ self::FIELD_INDUSTRY_KEY ] ) && is_string( $pack[ self::FIELD_INDUSTRY_KEY ] )
			? trim( $pack[ self::FIELD_INDUSTRY_KEY ] )
			: '';
		if ( $key !== '' ) {
			if ( strlen( $key ) > self::INDUSTRY_KEY_MAX_LENGTH ) {
				$errors[] = array( 'code' => 'industry_key_too_long', 'field' => self::FIELD_INDUSTRY_KEY );
			}
			if ( ! preg_match( self::INDUSTRY_KEY_PATTERN, $key ) ) {
				$errors[] = array( 'code' => 'industry_key_invalid_pattern', 'field' => self::FIELD_INDUSTRY_KEY );
			}
		}

		$name = isset( $pack[ self::FIELD_NAME ] ) && is_string( $pack[ self::FIELD_NAME ] )
			? trim( $pack[ self::FIELD_NAME ] )
			: '';
		if ( $name !== '' && strlen( $name ) > self::NAME_MAX_LENGTH ) {
			$errors[] = array( 'code' => 'name_too_long', 'field' => self::FIELD_NAME );
		}

		$summary = isset( $pack[ self::FIELD_SUMMARY ] ) && is_string( $pack[ self::FIELD_SUMMARY ] )
			? trim( $pack[ self::FIELD_SUMMARY ] )
			: '';
		if ( $summary !== '' && strlen( $summary ) > self::SUMMARY_MAX_LENGTH ) {
			$errors[] = array( 'code' => 'summary_too_long', 'field' => self::FIELD_SUMMARY );
		}

		$status = isset( $pack[ self::FIELD_STATUS ] ) && is_string( $pack[ self::FIELD_STATUS ] )
			? $pack[ self::FIELD_STATUS ]
			: '';
		if ( $status !== '' && ! self::is_allowed_status( $status ) ) {
			$errors[] = array( 'code' => 'invalid_status', 'field' => self::FIELD_STATUS );
		}

		$version = isset( $pack[ self::FIELD_VERSION_MARKER ] ) && is_string( $pack[ self::FIELD_VERSION_MARKER ] )
			? trim( $pack[ self::FIELD_VERSION_MARKER ] )
			: '';
		if ( $version !== '' && ! self::is_supported_version( $version ) ) {
			$errors[] = array( 'code' => 'unsupported_version', 'field' => self::FIELD_VERSION_MARKER );
		}

		return $errors;
	}
}
