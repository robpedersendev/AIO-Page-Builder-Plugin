<?php
/**
 * Canonical section template schema definition (spec §12, section-registry-schema.md).
 * Consumed by future registry validation and persistence; no CRUD or admin logic here.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

/**
 * Required/optional field names, allowed categories, render modes, and completeness list.
 * A section template missing any required field is incomplete and not eligible for normal use.
 */
final class Section_Schema {

	/** Required top-level field: stable internal section key. */
	public const FIELD_INTERNAL_KEY = 'internal_key';

	/** Required: human-readable section name. */
	public const FIELD_NAME = 'name';

	/** Required: purpose summary. */
	public const FIELD_PURPOSE_SUMMARY = 'purpose_summary';

	/** Required: section category (one of allowed categories). */
	public const FIELD_CATEGORY = 'category';

	/** Required: structural blueprint reference. */
	public const FIELD_STRUCTURAL_BLUEPRINT_REF = 'structural_blueprint_ref';

	/** Required: field-group blueprint reference. */
	public const FIELD_FIELD_BLUEPRINT_REF = 'field_blueprint_ref';

	/** Required: helper paragraph/block reference. */
	public const FIELD_HELPER_REF = 'helper_ref';

	/** Required: CSS contract manifest reference. */
	public const FIELD_CSS_CONTRACT_REF = 'css_contract_ref';

	/** Required: default variant key (must exist in variants). */
	public const FIELD_DEFAULT_VARIANT = 'default_variant';

	/** Required: variants map (variant_key => descriptor). */
	public const FIELD_VARIANTS = 'variants';

	/** Required: compatibility metadata object. */
	public const FIELD_COMPATIBILITY = 'compatibility';

	/** Required: version metadata object. */
	public const FIELD_VERSION = 'version';

	/** Required: status (draft|active|inactive|deprecated). */
	public const FIELD_STATUS = 'status';

	/** Required: render mode classification. */
	public const FIELD_RENDER_MODE = 'render_mode';

	/** Required: asset dependency declaration object. */
	public const FIELD_ASSET_DECLARATION = 'asset_declaration';

	/** @var array<int, string> Required field names for completeness check (spec §12.2). */
	private static ?array $required_fields = null;

	/** @var array<int, string> Optional field names (spec §12.3). */
	private static ?array $optional_fields = null;

	/** @var array<string, string> Allowed category slug => description. */
	private static ?array $allowed_categories = null;

	/** @var array<int, string> Allowed render mode values. */
	private static ?array $allowed_render_modes = null;

	/**
	 * Returns required field names. A section missing any of these is incomplete.
	 *
	 * @return array<int, string>
	 */
	public static function get_required_fields(): array {
		if ( self::$required_fields !== null ) {
			return self::$required_fields;
		}
		self::$required_fields = array(
			self::FIELD_INTERNAL_KEY,
			self::FIELD_NAME,
			self::FIELD_PURPOSE_SUMMARY,
			self::FIELD_CATEGORY,
			self::FIELD_STRUCTURAL_BLUEPRINT_REF,
			self::FIELD_FIELD_BLUEPRINT_REF,
			self::FIELD_HELPER_REF,
			self::FIELD_CSS_CONTRACT_REF,
			self::FIELD_DEFAULT_VARIANT,
			self::FIELD_VARIANTS,
			self::FIELD_COMPATIBILITY,
			self::FIELD_VERSION,
			self::FIELD_STATUS,
			self::FIELD_RENDER_MODE,
			self::FIELD_ASSET_DECLARATION,
		);
		return self::$required_fields;
	}

	/**
	 * Returns optional field names (spec §12.3).
	 *
	 * @return array<int, string>
	 */
	public static function get_optional_fields(): array {
		if ( self::$optional_fields !== null ) {
			return self::$optional_fields;
		}
		self::$optional_fields = array(
			'short_label',
			'preview_description',
			'preview_image_ref',
			'section_purpose_family',
			'cta_classification',
			'variation_family_key',
			'animation_tier',
			'animation_families',
			'preview_defaults',
			'suggested_use_cases',
			'prohibited_use_cases',
			'notes_for_ai_planning',
			'hierarchy_role_hints',
			'seo_relevance_notes',
			'token_affinity_notes',
			'lpagery_mapping_notes',
			'accessibility_warnings_or_enhancements',
			'migration_notes',
			'deprecation_notes',
			'replacement_section_suggestions',
			'dependencies_sections_or_context',
			'accessibility_contract_ref',
			'deprecation',
			self::FIELD_INDUSTRY_AFFINITY,
			self::FIELD_INDUSTRY_DISCOURAGED,
			self::FIELD_INDUSTRY_CTA_FIT,
			self::FIELD_INDUSTRY_NOTES,
		);
		return self::$optional_fields;
	}

	/**
	 * Returns allowed category slugs (spec §12.6, section-registry-schema §2.1).
	 *
	 * @return array<string, string> Slug => short description.
	 */
	public static function get_allowed_categories(): array {
		if ( self::$allowed_categories !== null ) {
			return self::$allowed_categories;
		}
		self::$allowed_categories = array(
			'hero_intro'          => 'Hero / intro',
			'trust_proof'         => 'Trust / proof',
			'feature_benefit'     => 'Feature / benefit',
			'process_steps'       => 'Process / steps',
			'pricing_packages'    => 'Pricing / packages',
			'faq'                 => 'FAQ',
			'media_gallery'       => 'Media / gallery',
			'comparison'          => 'Comparison',
			'cta_conversion'      => 'CTA / conversion',
			'form_embed'          => 'Form embed (form provider shortcode/block)',
			'directory_listing'   => 'Directory / listing',
			'profile_bio'         => 'Profile / bio',
			'stats_highlights'    => 'Stats / highlights',
			'timeline'            => 'Timeline',
			'navigation_jump'     => 'Navigation / jump links',
			'related_recommended' => 'Related / recommended content',
			'legal_disclaimer'    => 'Legal / disclaimer support',
			'utility_structural'  => 'Utility / structural support',
		);
		return self::$allowed_categories;
	}

	/**
	 * Returns whether the given category slug is allowed.
	 *
	 * @param string $category Category slug.
	 * @return bool
	 */
	public static function is_allowed_category( string $category ): bool {
		$categories = self::get_allowed_categories();
		return isset( $categories[ $category ] );
	}

	/**
	 * Returns allowed render mode values (section-registry-schema §2.2).
	 *
	 * @return array<int, string>
	 */
	public static function get_allowed_render_modes(): array {
		if ( self::$allowed_render_modes !== null ) {
			return self::$allowed_render_modes;
		}
		self::$allowed_render_modes = array(
			'block',
			'full_width',
			'contained',
			'inline',
			'nested',
		);
		return self::$allowed_render_modes;
	}

	/**
	 * Returns whether the given render mode is allowed.
	 *
	 * @param string $render_mode Render mode value.
	 * @return bool
	 */
	public static function is_allowed_render_mode( string $render_mode ): bool {
		return in_array( $render_mode, self::get_allowed_render_modes(), true );
	}

	/**
	 * Internal key pattern: alphanumeric and underscore only; deterministic, non-AI-generated.
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

	/** Optional: industry keys where this section is a strong/good fit (section-industry-affinity-contract). */
	public const FIELD_INDUSTRY_AFFINITY = 'industry_affinity';

	/** Optional: industry keys where this section is discouraged. */
	public const FIELD_INDUSTRY_DISCOURAGED = 'industry_discouraged';

	/** Optional: per-industry CTA fit note. */
	public const FIELD_INDUSTRY_CTA_FIT = 'industry_cta_fit';

	/** Optional: per-industry usage notes. */
	public const FIELD_INDUSTRY_NOTES = 'industry_notes';

	/** Pattern for industry_key (aligned with Industry_Pack_Schema). */
	public const INDUSTRY_KEY_PATTERN = '#^[a-z0-9_-]+$#';

	/** Max length for industry_key. */
	public const INDUSTRY_KEY_MAX_LENGTH = 64;

	/**
	 * Validates optional industry-affinity metadata on a section definition. Returns errors; empty when valid or when no industry fields present.
	 *
	 * @param array<string, mixed> $section Section definition.
	 * @return array<int, array{code: string, field?: string}> Empty if valid.
	 */
	public static function validate_industry_affinity_metadata( array $section ): array {
		$errors    = array();
		$has_any   = false;
		$check_key = function ( string $key ): bool {
			if ( $key === '' || strlen( $key ) > self::INDUSTRY_KEY_MAX_LENGTH ) {
				return false;
			}
			return (bool) preg_match( self::INDUSTRY_KEY_PATTERN, $key );
		};
		if ( array_key_exists( self::FIELD_INDUSTRY_AFFINITY, $section ) ) {
			$has_any = true;
			$val     = $section[ self::FIELD_INDUSTRY_AFFINITY ];
			if ( is_array( $val ) ) {
				foreach ( $val as $k => $v ) {
					$key_str = is_string( $k ) ? $k : (string) $v;
					if ( $key_str === '' && is_string( $v ) ) {
						$key_str = $v;
					}
					if ( $key_str !== '' && ! $check_key( $key_str ) ) {
						$errors[] = array(
							'code'  => 'invalid_industry_key',
							'field' => self::FIELD_INDUSTRY_AFFINITY,
						);
						break;
					}
				}
			}
		}
		if ( array_key_exists( self::FIELD_INDUSTRY_DISCOURAGED, $section ) ) {
			$has_any = true;
			$val     = $section[ self::FIELD_INDUSTRY_DISCOURAGED ];
			if ( is_array( $val ) ) {
				foreach ( $val as $v ) {
					$key_str = is_string( $v ) ? trim( $v ) : '';
					if ( $key_str !== '' && ! $check_key( $key_str ) ) {
						$errors[] = array(
							'code'  => 'invalid_industry_key',
							'field' => self::FIELD_INDUSTRY_DISCOURAGED,
						);
						break;
					}
				}
			}
		}
		if ( array_key_exists( self::FIELD_INDUSTRY_CTA_FIT, $section ) ) {
			$has_any = true;
			$val     = $section[ self::FIELD_INDUSTRY_CTA_FIT ];
			if ( is_array( $val ) ) {
				foreach ( array_keys( $val ) as $k ) {
					if ( is_string( $k ) && $k !== '' && ! $check_key( $k ) ) {
						$errors[] = array(
							'code'  => 'invalid_industry_key',
							'field' => self::FIELD_INDUSTRY_CTA_FIT,
						);
						break;
					}
				}
			}
		}
		if ( array_key_exists( self::FIELD_INDUSTRY_NOTES, $section ) ) {
			$has_any = true;
			$val     = $section[ self::FIELD_INDUSTRY_NOTES ];
			if ( is_array( $val ) ) {
				foreach ( array_keys( $val ) as $k ) {
					if ( is_string( $k ) && $k !== '' && ! $check_key( $k ) ) {
						$errors[] = array(
							'code'  => 'invalid_industry_key',
							'field' => self::FIELD_INDUSTRY_NOTES,
						);
						break;
					}
				}
			}
		}
		return $errors;
	}
}
