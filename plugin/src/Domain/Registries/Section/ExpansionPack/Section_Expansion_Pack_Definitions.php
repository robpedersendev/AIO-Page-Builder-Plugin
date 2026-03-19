<?php
/**
 * Curated section template definitions for the expansion pack (spec §12, §15, §20, §57.6, Prompt 122).
 * Production-grade sections with full metadata, field blueprints, helper/CSS refs, and accessibility.
 * Does not persist; callers save via Section_Template_Repository or Section_Registry_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\ExpansionPack;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the curated expansion pack.
 * Each definition is schema-compliant and includes embedded field_blueprint per acf-field-blueprint-schema.
 *
 * New section keys and required supporting artifacts:
 *
 * | Section key            | Category         | Field blueprint ref           | Helper ref                   | CSS contract ref           | Structural blueprint ref   |
 * |------------------------|------------------|-------------------------------|------------------------------|----------------------------|----------------------------|
 * | st_stats_highlights    | stats_highlights | acf_blueprint_st_stats_highlights | helper_st_stats_highlights   | css_st_stats_highlights    | bp_st_stats_highlights     |
 * | st_cta_conversion      | cta_conversion   | acf_blueprint_st_cta_conversion   | helper_st_cta_conversion     | css_st_cta_conversion      | bp_st_cta_conversion       |
 * | st_faq                 | faq              | acf_blueprint_st_faq             | helper_st_faq                | css_st_faq                 | bp_st_faq                  |
 *
 * Each section also has: compatibility, version, variants, default_variant, asset_declaration (none),
 * optional short_label, suggested_use_cases, accessibility_warnings_or_enhancements.
 */
final class Section_Expansion_Pack_Definitions {

	/** Section key: stats / highlights. */
	public const KEY_STATS_HIGHLIGHTS = 'st_stats_highlights';

	/** Section key: CTA / conversion. */
	public const KEY_CTA_CONVERSION = 'st_cta_conversion';

	/** Section key: FAQ. */
	public const KEY_FAQ = 'st_faq';

	/** Industry keys for first launch verticals (section-industry-affinity-contract; Prompt 363). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all expansion-pack section definitions (order preserved for seeding).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::stats_highlights_definition(),
			self::cta_conversion_definition(),
			self::faq_definition(),
		);
	}

	/**
	 * Returns section keys in this pack (for listing and tests).
	 *
	 * @return array<int, string>
	 */
	public static function section_keys(): array {
		return array(
			self::KEY_STATS_HIGHLIGHTS,
			self::KEY_CTA_CONVERSION,
			self::KEY_FAQ,
		);
	}

	/**
	 * Stats / highlights section: headline plus repeatable stat items (label, value, optional suffix).
	 *
	 * @return array<string, mixed>
	 */
	public static function stats_highlights_definition(): array {
		$key   = self::KEY_STATS_HIGHLIGHTS;
		$bp_id = 'acf_blueprint_' . $key;
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => 'Stats / highlights',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Displays a headline and a set of key metrics or highlights (e.g. numbers, percentages). Use for social proof, outcomes, or feature counts.',
			Section_Schema::FIELD_CATEGORY                 => 'stats_highlights',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st_stats_highlights',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $bp_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_st_stats_highlights',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_st_stats_highlights',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array(
					'label'       => 'Default',
					'description' => 'Horizontal layout of stat items.',
				),
				'compact' => array(
					'label'         => 'Compact',
					'description'   => 'Tighter spacing for dense layouts.',
					'css_modifiers' => array( 'aio-s-st_stats_highlights--compact' ),
				),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array( 'st_cta_conversion', 'st_faq', 'form_section_ndr' ),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'short_label'                                  => 'Stats',
			'suggested_use_cases'                          => array( 'Landing page metrics', 'Outcome highlights', 'Feature counts', 'Social proof numbers' ),
			Section_Schema::FIELD_INDUSTRY_AFFINITY        => self::LAUNCH_INDUSTRIES,
			'accessibility_warnings_or_enhancements'       => 'Ensure numbers are announced meaningfully (e.g. aria-label or live region for dynamic updates). Prefer semantic list or grid for stat items.',
			'field_blueprint'                              => array(
				'blueprint_id'    => $bp_id,
				'section_key'     => $key,
				'section_version' => '1',
				'label'           => 'Stats / highlights fields',
				'description'     => 'Headline and repeatable stat items (label, value, optional suffix).',
				'fields'          => array(
					array(
						'key'          => 'field_stats_headline',
						'name'         => 'headline',
						'label'        => 'Headline',
						'type'         => 'text',
						'required'     => false,
						'instructions' => 'Optional section headline (e.g. "By the numbers").',
					),
					array(
						'key'          => 'field_stats_items',
						'name'         => 'stat_items',
						'label'        => 'Stat items',
						'type'         => 'repeater',
						'required'     => true,
						'instructions' => 'Add one row per stat (label, value, optional suffix like % or +).',
						'layout'       => 'block',
						'button_label' => 'Add stat',
						'min'          => 1,
						'max'          => 8,
						'sub_fields'   => array(
							array(
								'key'      => 'field_stats_item_label',
								'name'     => 'label',
								'label'    => 'Label',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'field_stats_item_value',
								'name'     => 'value',
								'label'    => 'Value',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'field_stats_item_suffix',
								'name'     => 'suffix',
								'label'    => 'Suffix (e.g. %, +)',
								'type'     => 'text',
								'required' => false,
							),
						),
					),
				),
			),
		);
	}

	/**
	 * CTA / conversion section: headline, subheadline, primary and optional secondary CTA links.
	 *
	 * @return array<string, mixed>
	 */
	public static function cta_conversion_definition(): array {
		$key   = self::KEY_CTA_CONVERSION;
		$bp_id = 'acf_blueprint_' . $key;
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => 'CTA / conversion',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Call-to-action block with headline, subheadline, and primary (and optional secondary) link. Use for sign-up, download, or next-step prompts.',
			Section_Schema::FIELD_CATEGORY                 => 'cta_conversion',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st_cta_conversion',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $bp_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_st_cta_conversion',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_st_cta_conversion',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array(
					'label'       => 'Default',
					'description' => 'Centered CTA block.',
				),
				'minimal' => array(
					'label'         => 'Minimal',
					'description'   => 'Reduced emphasis, inline-style.',
					'css_modifiers' => array( 'aio-s-st_cta_conversion--minimal' ),
				),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array( 'st_faq', 'form_section_ndr' ),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'short_label'                                  => 'CTA',
			'suggested_use_cases'                          => array( 'End-of-page sign-up', 'Download or contact prompt', 'Next-step conversion' ),
			Section_Schema::FIELD_INDUSTRY_AFFINITY        => self::LAUNCH_INDUSTRIES,
			'accessibility_warnings_or_enhancements'       => 'Primary CTA should be a single clear link or button. Ensure link text describes destination (avoid "Click here"). Secondary link must be visually distinct.',
			'field_blueprint'                              => array(
				'blueprint_id'    => $bp_id,
				'section_key'     => $key,
				'section_version' => '1',
				'label'           => 'CTA / conversion fields',
				'description'     => 'Headline, subheadline, primary and optional secondary CTA links.',
				'fields'          => array(
					array(
						'key'          => 'field_cta_headline',
						'name'         => 'headline',
						'label'        => 'Headline',
						'type'         => 'text',
						'required'     => true,
						'instructions' => 'Main CTA headline.',
					),
					array(
						'key'          => 'field_cta_subheadline',
						'name'         => 'subheadline',
						'label'        => 'Subheadline',
						'type'         => 'textarea',
						'required'     => false,
						'instructions' => 'Optional supporting line.',
					),
					array(
						'key'          => 'field_cta_primary',
						'name'         => 'primary_cta',
						'label'        => 'Primary CTA',
						'type'         => 'link',
						'required'     => true,
						'instructions' => 'Main call-to-action link.',
					),
					array(
						'key'          => 'field_cta_secondary',
						'name'         => 'secondary_cta',
						'label'        => 'Secondary link (optional)',
						'type'         => 'link',
						'required'     => false,
						'instructions' => 'Optional secondary link (e.g. "Learn more").',
					),
				),
			),
		);
	}

	/**
	 * FAQ section: headline and repeatable question/answer pairs.
	 *
	 * @return array<string, mixed>
	 */
	public static function faq_definition(): array {
		$key   = self::KEY_FAQ;
		$bp_id = 'acf_blueprint_' . $key;
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => 'FAQ',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Frequently asked questions: a headline and a list of question/answer pairs. Suited for accordion or expanded list presentation.',
			Section_Schema::FIELD_CATEGORY                 => 'faq',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st_faq',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => $bp_id,
			Section_Schema::FIELD_HELPER_REF               => 'helper_st_faq',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_st_faq',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default'   => array(
					'label'       => 'Default',
					'description' => 'List or accordion of Q&A pairs.',
				),
				'accordion' => array(
					'label'         => 'Accordion',
					'description'   => 'Collapsible disclosure pattern.',
					'css_modifiers' => array( 'aio-s-st_faq--accordion' ),
				),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array( 'st_cta_conversion', 'form_section_ndr' ),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                  => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'short_label'                                  => 'FAQ',
			'suggested_use_cases'                          => array( 'Product or service FAQs', 'Support pages', 'Pre-form clarification' ),
			Section_Schema::FIELD_INDUSTRY_AFFINITY        => self::LAUNCH_INDUSTRIES,
			'accessibility_warnings_or_enhancements'       => 'Use heading for section; each Q&A pair should use proper heading level for question and visible answer. For accordion, use disclosure/button pattern with aria-expanded and aria-controls.',
			'field_blueprint'                              => array(
				'blueprint_id'    => $bp_id,
				'section_key'     => $key,
				'section_version' => '1',
				'label'           => 'FAQ fields',
				'description'     => 'Headline and repeatable question/answer pairs.',
				'fields'          => array(
					array(
						'key'          => 'field_faq_headline',
						'name'         => 'headline',
						'label'        => 'Headline',
						'type'         => 'text',
						'required'     => false,
						'instructions' => 'Optional section headline (e.g. "Frequently asked questions").',
					),
					array(
						'key'          => 'field_faq_items',
						'name'         => 'faq_items',
						'label'        => 'FAQ items',
						'type'         => 'repeater',
						'required'     => true,
						'instructions' => 'Add one row per question and answer.',
						'layout'       => 'block',
						'button_label' => 'Add Q&A',
						'min'          => 1,
						'max'          => 20,
						'sub_fields'   => array(
							array(
								'key'      => 'field_faq_item_question',
								'name'     => 'question',
								'label'    => 'Question',
								'type'     => 'text',
								'required' => true,
							),
							array(
								'key'      => 'field_faq_item_answer',
								'name'     => 'answer',
								'label'    => 'Answer',
								'type'     => 'textarea',
								'required' => true,
								'rows'     => 4,
							),
						),
					),
				),
			),
		);
	}
}
