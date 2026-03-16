<?php
/**
 * Builds and validates portable industry pack bundles (Prompt 394, industry-pack-bundle-format-contract).
 * No executable payloads; admin-only use; full site export remains authoritative.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Industry_Page_OnePager_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Section_Helper_Overlay_Registry;
use AIOPageBuilder\Domain\Industry\Onboarding\Industry_Question_Pack_Definitions;
use AIOPageBuilder\Domain\Industry\Registry\Industry_CTA_Pattern_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_SEO_Guidance_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Registry\StylePresets\Builtin_Industry_Style_Presets;
use AIOPageBuilder\Domain\Industry\LPagery\Industry_LPagery_Rule_Registry;

/**
 * Builds portable industry pack bundles from built-in definitions and validates bundle structure.
 */
final class Industry_Pack_Bundle_Service {

	/** Bundle format version (industry-pack-bundle-format-contract). */
	public const BUNDLE_VERSION = '1';

	/** Supported bundle versions for import validation. */
	public const SUPPORTED_BUNDLE_VERSIONS = array( '1' );

	/** Manifest key: bundle format version. */
	public const MANIFEST_BUNDLE_VERSION = 'bundle_version';

	/** Manifest key: schema version. */
	public const MANIFEST_SCHEMA_VERSION = 'schema_version';

	/** Manifest key: created_at (ISO 8601). */
	public const MANIFEST_CREATED_AT = 'created_at';

	/** Manifest key: included categories. */
	public const MANIFEST_INCLUDED_CATEGORIES = 'included_categories';

	/** Manifest key: optional dependency refs. */
	public const MANIFEST_DEPENDENCY_REFS = 'dependency_refs';

	/** Payload key: packs. */
	public const PAYLOAD_PACKS = 'packs';

	/** Payload key: starter_bundles. */
	public const PAYLOAD_STARTER_BUNDLES = 'starter_bundles';

	/** Payload key: style_presets. */
	public const PAYLOAD_STYLE_PRESETS = 'style_presets';

	/** Payload key: cta_patterns. */
	public const PAYLOAD_CTA_PATTERNS = 'cta_patterns';

	/** Payload key: seo_guidance. */
	public const PAYLOAD_SEO_GUIDANCE = 'seo_guidance';

	/** Payload key: lpagery_rules. */
	public const PAYLOAD_LPAGERY_RULES = 'lpagery_rules';

	/** Payload key: section_helper_overlays. */
	public const PAYLOAD_SECTION_HELPER_OVERLAYS = 'section_helper_overlays';

	/** Payload key: page_one_pager_overlays. */
	public const PAYLOAD_PAGE_ONE_PAGER_OVERLAYS = 'page_one_pager_overlays';

	/** Payload key: question_packs. */
	public const PAYLOAD_QUESTION_PACKS = 'question_packs';

	/** Payload key: optional site_profile (industry_profile + applied_preset). */
	public const PAYLOAD_SITE_PROFILE = 'site_profile';

	/** All known payload category keys (excluding site_profile which is optional). */
	private const CATEGORY_KEYS = array(
		self::PAYLOAD_PACKS,
		self::PAYLOAD_STARTER_BUNDLES,
		self::PAYLOAD_STYLE_PRESETS,
		self::PAYLOAD_CTA_PATTERNS,
		self::PAYLOAD_SEO_GUIDANCE,
		self::PAYLOAD_LPAGERY_RULES,
		self::PAYLOAD_SECTION_HELPER_OVERLAYS,
		self::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS,
		self::PAYLOAD_QUESTION_PACKS,
	);

	/**
	 * Builds a portable industry pack bundle from built-in definitions or from provided sources.
	 *
	 * @param array<string, mixed> $options Optional. 'include_site_profile' => true to add site_profile; 'industry_profile' => array, 'applied_preset' => array when including.
	 * @param array<string, array<int, array<string, mixed>>>|null $sources Optional. When provided, use these arrays per payload key (packs, starter_bundles, etc.) instead of built-in; allows testing without loading registries.
	 * @return array<string, mixed> Bundle with manifest and payload keys.
	 */
	public function build_bundle( array $options = [], ?array $sources = null ): array {
		$include_site_profile = ! empty( $options['include_site_profile'] );
		$industry_profile    = $options['industry_profile'] ?? array();
		$applied_preset      = $options['applied_preset'] ?? array();

		if ( $sources !== null && is_array( $sources ) ) {
			$packs    = $sources[ self::PAYLOAD_PACKS ] ?? array();
			$starter  = $sources[ self::PAYLOAD_STARTER_BUNDLES ] ?? array();
			$presets  = $sources[ self::PAYLOAD_STYLE_PRESETS ] ?? array();
			$cta      = $sources[ self::PAYLOAD_CTA_PATTERNS ] ?? array();
			$seo      = $sources[ self::PAYLOAD_SEO_GUIDANCE ] ?? array();
			$lpagery  = $sources[ self::PAYLOAD_LPAGERY_RULES ] ?? array();
			$section  = $sources[ self::PAYLOAD_SECTION_HELPER_OVERLAYS ] ?? array();
			$onepager = $sources[ self::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS ] ?? array();
			$question = $sources[ self::PAYLOAD_QUESTION_PACKS ] ?? array();
		} else {
			$packs    = Industry_Pack_Registry::get_builtin_pack_definitions();
			$starter  = Industry_Starter_Bundle_Registry::get_builtin_definitions();
			$presets  = Builtin_Industry_Style_Presets::get_definitions();
			$cta      = Industry_CTA_Pattern_Registry::get_builtin_definitions();
			$seo      = Industry_SEO_Guidance_Registry::get_builtin_definitions();
			$lpagery  = Industry_LPagery_Rule_Registry::get_builtin_definitions();
			$section  = Industry_Section_Helper_Overlay_Registry::get_builtin_overlay_definitions();
			$onepager = Industry_Page_OnePager_Overlay_Registry::get_builtin_overlay_definitions();
			$question = Industry_Question_Pack_Definitions::default_packs();
		}

		$included = array(
			self::PAYLOAD_PACKS,
			self::PAYLOAD_STARTER_BUNDLES,
			self::PAYLOAD_STYLE_PRESETS,
			self::PAYLOAD_CTA_PATTERNS,
			self::PAYLOAD_SEO_GUIDANCE,
			self::PAYLOAD_LPAGERY_RULES,
			self::PAYLOAD_SECTION_HELPER_OVERLAYS,
			self::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS,
			self::PAYLOAD_QUESTION_PACKS,
		);
		if ( $include_site_profile ) {
			$included[] = self::PAYLOAD_SITE_PROFILE;
		}

		$bundle = array(
			self::MANIFEST_BUNDLE_VERSION       => self::BUNDLE_VERSION,
			self::MANIFEST_SCHEMA_VERSION      => '1',
			self::MANIFEST_CREATED_AT           => gmdate( 'c' ),
			self::MANIFEST_INCLUDED_CATEGORIES  => $included,
			self::MANIFEST_DEPENDENCY_REFS      => null,
			self::PAYLOAD_PACKS                 => $packs,
			self::PAYLOAD_STARTER_BUNDLES       => $starter,
			self::PAYLOAD_STYLE_PRESETS         => $presets,
			self::PAYLOAD_CTA_PATTERNS          => $cta,
			self::PAYLOAD_SEO_GUIDANCE          => $seo,
			self::PAYLOAD_LPAGERY_RULES         => $lpagery,
			self::PAYLOAD_SECTION_HELPER_OVERLAYS => $section,
			self::PAYLOAD_PAGE_ONE_PAGER_OVERLAYS => $onepager,
			self::PAYLOAD_QUESTION_PACKS        => $question,
		);
		if ( $include_site_profile ) {
			$bundle[ self::PAYLOAD_SITE_PROFILE ] = array(
				'industry_profile' => is_array( $industry_profile ) ? $industry_profile : array(),
				'applied_preset'   => is_array( $applied_preset ) ? $applied_preset : array(),
			);
		}
		return $bundle;
	}

	/**
	 * Validates bundle structure and manifest. Does not validate per-object schemas (done at import).
	 *
	 * @param array<string, mixed> $bundle Bundle array (manifest + payload).
	 * @return list<string> List of error messages; empty if valid.
	 */
	public function validate_bundle( array $bundle ): array {
		$errors = array();
		$version = isset( $bundle[ self::MANIFEST_BUNDLE_VERSION ] ) && is_string( $bundle[ self::MANIFEST_BUNDLE_VERSION ] )
			? trim( $bundle[ self::MANIFEST_BUNDLE_VERSION ] )
			: '';
		if ( $version === '' ) {
			$errors[] = 'Missing or invalid bundle_version.';
		} elseif ( ! in_array( $version, self::SUPPORTED_BUNDLE_VERSIONS, true ) ) {
			$errors[] = 'Unsupported bundle_version: ' . $version;
		}
		if ( empty( $bundle[ self::MANIFEST_SCHEMA_VERSION ] ) || ! is_string( $bundle[ self::MANIFEST_SCHEMA_VERSION ] ) ) {
			$errors[] = 'Missing or invalid schema_version.';
		}
		if ( empty( $bundle[ self::MANIFEST_CREATED_AT ] ) || ! is_string( $bundle[ self::MANIFEST_CREATED_AT ] ) ) {
			$errors[] = 'Missing or invalid created_at.';
		}
		$included = $bundle[ self::MANIFEST_INCLUDED_CATEGORIES ] ?? null;
		if ( ! is_array( $included ) ) {
			$errors[] = 'Missing or invalid included_categories.';
		} else {
			$allowed = array_merge( self::CATEGORY_KEYS, array( self::PAYLOAD_SITE_PROFILE ) );
			foreach ( $included as $cat ) {
				if ( ! is_string( $cat ) || $cat === '' ) {
					$errors[] = 'Invalid category in included_categories.';
					break;
				}
				if ( ! in_array( $cat, $allowed, true ) ) {
					$errors[] = 'Unknown category: ' . $cat;
				}
				if ( isset( $bundle[ $cat ] ) && ! is_array( $bundle[ $cat ] ) ) {
					$errors[] = 'Payload for category ' . $cat . ' must be an array.';
				}
			}
		}
		return $errors;
	}
}
