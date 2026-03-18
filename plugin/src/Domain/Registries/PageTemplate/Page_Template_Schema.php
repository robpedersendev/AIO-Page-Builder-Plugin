<?php
/**
 * Canonical page template schema definition (spec §13, §10.2, page-template-registry-schema.md).
 * Consumed by future registry validation and persistence; no CRUD or one-pager generation here.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

/**
 * Required/optional field names, ordered section item structure, allowed archetypes.
 * A page template missing any required field is incomplete and not eligible for normal use.
 */
final class Page_Template_Schema {

	/** Required: stable internal page-template key. */
	public const FIELD_INTERNAL_KEY = 'internal_key';

	/** Required: human-readable page-template name. */
	public const FIELD_NAME = 'name';

	/** Required: page purpose summary. */
	public const FIELD_PURPOSE_SUMMARY = 'purpose_summary';

	/** Required: category or template archetype. */
	public const FIELD_ARCHETYPE = 'archetype';

	/** Required: ordered section list (array of section reference items). */
	public const FIELD_ORDERED_SECTIONS = 'ordered_sections';

	/** Required: required vs optional section designations (map section_key => { required: bool }). */
	public const FIELD_SECTION_REQUIREMENTS = 'section_requirements';

	/** Required: compatibility metadata object. */
	public const FIELD_COMPATIBILITY = 'compatibility';

	/** Required: one-pager generation metadata object. */
	public const FIELD_ONE_PAGER = 'one_pager';

	/** Required: version metadata object. */
	public const FIELD_VERSION = 'version';

	/** Required: status (draft|active|inactive|deprecated). */
	public const FIELD_STATUS = 'status';

	/** Required: default structural assumptions. */
	public const FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS = 'default_structural_assumptions';

	/** Required: endpoint or usage notes (may be empty). */
	public const FIELD_ENDPOINT_OR_USAGE_NOTES = 'endpoint_or_usage_notes';

	/** Ordered section item: section template internal_key reference. */
	public const SECTION_ITEM_KEY = 'section_key';

	/** Ordered section item: zero-based position. */
	public const SECTION_ITEM_POSITION = 'position';

	/** Ordered section item: required (true) or optional (false). */
	public const SECTION_ITEM_REQUIRED = 'required';

	/** One-pager block: page-purpose summary (required within one_pager). */
	public const ONE_PAGER_PURPOSE_SUMMARY = 'page_purpose_summary';

	/** @var list<string> Required field names for completeness check (spec §13.2). */
	private static ?array $required_fields = null;

	/** @var list<string> Optional field names (spec §13.3). */
	private static ?array $optional_fields = null;

	/** @var array<string, string> Allowed archetype slug => description. */
	private static ?array $allowed_archetypes = null;

	/**
	 * Returns required field names. A page template missing any of these is incomplete.
	 *
	 * @return list<string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_INTERNAL_KEY,
			self::FIELD_NAME,
			self::FIELD_PURPOSE_SUMMARY,
			self::FIELD_ARCHETYPE,
			self::FIELD_ORDERED_SECTIONS,
			self::FIELD_SECTION_REQUIREMENTS,
			self::FIELD_COMPATIBILITY,
			self::FIELD_ONE_PAGER,
			self::FIELD_VERSION,
			self::FIELD_STATUS,
			self::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS,
			self::FIELD_ENDPOINT_OR_USAGE_NOTES,
		);
		return self::$required_fields;
	}

	/**
	 * Returns optional field names (spec §13.3).
	 *
	 * @return list<string>
	 */
	public static function get_optional_fields(): array {
		if ( self::$optional_fields !== null ) {
			return self::$optional_fields;
		}
		self::$optional_fields = array(
			'template_category_class',
			'template_family',
			'display_description',
			'recommended_industries',
			'recommended_audience_types',
			'suggested_page_title_patterns',
			'suggested_slug_patterns',
			'hierarchy_hints',
			'internal_linking_hints',
			'default_token_affinity_notes',
			'notes_for_ai_planning',
			'seo_notes',
			'documentation_notes',
			'preview_metadata',
			'migration_notes',
			'replacement_template_refs',
			'seo_defaults',
			'deprecation',
			self::FIELD_INDUSTRY_AFFINITY,
			self::FIELD_INDUSTRY_REQUIRED,
			self::FIELD_INDUSTRY_DISCOURAGED,
			self::FIELD_INDUSTRY_HIERARCHY_FIT,
			self::FIELD_INDUSTRY_LPAGERY_FIT,
			self::FIELD_INDUSTRY_NOTES,
		);
		return self::$optional_fields;
	}

	/**
	 * Returns allowed archetype slugs (spec §13.6, page-template-registry-schema §2.1).
	 *
	 * @return array<string, string> Slug => short description.
	 */
	public static function get_allowed_archetypes(): array {
		if ( self::$allowed_archetypes !== null ) {
			return self::$allowed_archetypes;
		}
		self::$allowed_archetypes = array(
			'service_page'         => 'Service page',
			'offer_page'           => 'Offer page',
			'pricing_page'         => 'Pricing page',
			'faq_page'             => 'FAQ page',
			'hub_page'             => 'Hub page',
			'sub_hub_page'         => 'Sub-hub page',
			'landing_page'         => 'Landing page',
			'about_page'           => 'About page',
			'location_page'        => 'Location page',
			'event_page'           => 'Event page',
			'request_page'         => 'Request page',
			'profile_page'         => 'Profile page',
			'directory_page'       => 'Directory page',
			'comparison_page'      => 'Comparison page',
			'informational_detail' => 'Informational detail page',
		);
		return self::$allowed_archetypes;
	}

	/**
	 * Returns whether the given archetype slug is allowed.
	 *
	 * @param string $archetype Archetype slug.
	 * @return bool
	 */
	public static function is_allowed_archetype( string $archetype ): bool {
		$archetypes = self::get_allowed_archetypes();
		return isset( $archetypes[ $archetype ] );
	}

	/**
	 * Returns required keys for an ordered section reference item.
	 *
	 * @return list<string>
	 */
	public static function get_ordered_section_item_keys(): array {
		return array(
			self::SECTION_ITEM_KEY,
			self::SECTION_ITEM_POSITION,
			self::SECTION_ITEM_REQUIRED,
		);
	}

	/**
	 * Internal key pattern: alphanumeric and underscore only.
	 *
	 * @var string
	 */
	public const INTERNAL_KEY_PATTERN = '#^[a-z0-9_]+$#';

	/** Max length for internal_key. */
	public const INTERNAL_KEY_MAX_LENGTH = 64;

	/** Max length for name. */
	public const NAME_MAX_LENGTH = 255;

	/** Max length for purpose_summary. */
	public const PURPOSE_SUMMARY_MAX_LENGTH = 1024;

	/** Optional: industry keys where this page is a strong/good fit (page-template-industry-affinity-contract). */
	public const FIELD_INDUSTRY_AFFINITY = 'industry_affinity';

	/** Optional: industry keys where this template is required or strongly recommended. */
	public const FIELD_INDUSTRY_REQUIRED = 'industry_required';

	/** Optional: industry keys where this template is discouraged. */
	public const FIELD_INDUSTRY_DISCOURAGED = 'industry_discouraged';

	/** Optional: per-industry hierarchy fit note. */
	public const FIELD_INDUSTRY_HIERARCHY_FIT = 'industry_hierarchy_fit';

	/** Optional: per-industry LPagery/token fit note. */
	public const FIELD_INDUSTRY_LPAGERY_FIT = 'industry_lpagery_fit';

	/** Optional: per-industry usage notes. */
	public const FIELD_INDUSTRY_NOTES = 'industry_notes';

	/** Pattern for industry_key (aligned with Industry_Pack_Schema). */
	public const INDUSTRY_KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for industry_key. */
	public const INDUSTRY_KEY_MAX_LENGTH = 64;

	/**
	 * Validates optional industry-affinity metadata on a page template definition. Returns errors; empty when valid or when no industry fields present.
	 *
	 * @param array<string, mixed> $page_template Page template definition.
	 * @return array<int, array{code: string, field?: string}> Empty if valid.
	 */
	public static function validate_industry_affinity_metadata( array $page_template ): array {
		$errors                   = array();
		$check_key                = function ( string $key ): bool {
			if ( $key === '' || strlen( $key ) > self::INDUSTRY_KEY_MAX_LENGTH ) {
				return false;
			}
			return (bool) preg_match( self::INDUSTRY_KEY_PATTERN, $key );
		};
		$fields_with_array_values = array(
			self::FIELD_INDUSTRY_AFFINITY,
			self::FIELD_INDUSTRY_REQUIRED,
			self::FIELD_INDUSTRY_DISCOURAGED,
		);
		foreach ( $fields_with_array_values as $field ) {
			if ( ! array_key_exists( $field, $page_template ) ) {
				continue;
			}
			$val = $page_template[ $field ];
			if ( ! is_array( $val ) ) {
				continue;
			}
			foreach ( $val as $k => $v ) {
				$key_str = is_string( $k ) ? $k : (string) $v;
				if ( $key_str === '' && is_string( $v ) ) {
					$key_str = trim( $v );
				}
				if ( $key_str !== '' && ! $check_key( $key_str ) ) {
					$errors[] = array(
						'code'  => 'invalid_industry_key',
						'field' => $field,
					);
					break;
				}
			}
		}
		$fields_with_map_keys = array(
			self::FIELD_INDUSTRY_HIERARCHY_FIT,
			self::FIELD_INDUSTRY_LPAGERY_FIT,
			self::FIELD_INDUSTRY_NOTES,
		);
		foreach ( $fields_with_map_keys as $field ) {
			if ( ! array_key_exists( $field, $page_template ) ) {
				continue;
			}
			$val = $page_template[ $field ];
			if ( is_string( $val ) ) {
				continue;
			}
			if ( is_array( $val ) ) {
				foreach ( array_keys( $val ) as $k ) {
					if ( is_string( $k ) && $k !== '' && ! $check_key( $k ) ) {
						$errors[] = array(
							'code'  => 'invalid_industry_key',
							'field' => $field,
						);
						break;
					}
				}
			}
		}
		return $errors;
	}
}
