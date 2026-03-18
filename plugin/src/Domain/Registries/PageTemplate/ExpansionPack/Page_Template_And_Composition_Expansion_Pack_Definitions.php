<?php
/**
 * Curated page template and composition definitions for the expansion pack (spec §13, §14, §16, Prompt 123).
 * Uses existing page-template and composition schemas; references section keys from section expansion pack and form.
 * Does not persist; callers save via Page_Template_Repository and Composition_Repository.
 *
 * New template keys and one-pager refs:
 *
 * | Template key              | Archetype     | One-pager ref (page_purpose_summary)                    |
 * |---------------------------|---------------|--------------------------------------------------------|
 * | pt_landing_stats_cta_faq  | landing_page  | Landing with stats, CTA, and FAQ sections.             |
 * | pt_faq_page               | faq_page      | FAQ-focused page with optional CTA.                    |
 *
 * New composition keys and provenance:
 *
 * | Composition id              | Source template ref      | Validation status |
 * |-----------------------------|--------------------------|-------------------|
 * | comp_landing_stats_cta_faq  | pt_landing_stats_cta_faq | valid             |
 * | comp_faq_cta                | pt_faq_page              | valid             |
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ExpansionPack;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Validation_Result;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Definitions;

/**
 * Returns page template and composition definitions for the curated expansion pack.
 * Section keys must exist (section expansion pack and form seed run first).
 */
final class Page_Template_And_Composition_Expansion_Pack_Definitions {

	/** Page template: landing with stats, CTA, FAQ. */
	public const PAGE_TEMPLATE_LANDING_STATS_CTA_FAQ = 'pt_landing_stats_cta_faq';

	/** Page template: FAQ page with optional CTA. */
	public const PAGE_TEMPLATE_FAQ_PAGE = 'pt_faq_page';

	/** Composition: landing stats CTA FAQ example. */
	public const COMPOSITION_LANDING_STATS_CTA_FAQ = 'comp_landing_stats_cta_faq';

	/** Composition: FAQ + CTA example. */
	public const COMPOSITION_FAQ_CTA = 'comp_faq_cta';

	/**
	 * Returns all expansion-pack page template definitions.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function page_template_definitions(): array {
		return array(
			self::landing_stats_cta_faq_template(),
			self::faq_page_template(),
		);
	}

	/**
	 * Returns all expansion-pack composition definitions.
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function composition_definitions(): array {
		return array(
			self::composition_landing_stats_cta_faq(),
			self::composition_faq_cta(),
		);
	}

	/**
	 * Page template: landing with stats, CTA, and FAQ sections.
	 *
	 * @return array<string, mixed>
	 */
	public static function landing_stats_cta_faq_template(): array {
		$ordered      = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION,
				Page_Template_Schema::SECTION_ITEM_POSITION => 1,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => Section_Expansion_Pack_Definitions::KEY_FAQ,
				Page_Template_Schema::SECTION_ITEM_POSITION => 2,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => false,
			),
		);
		$section_reqs = array(
			Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS => array( 'required' => true ),
			Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION => array( 'required' => true ),
			Section_Expansion_Pack_Definitions::KEY_FAQ => array( 'required' => false ),
		);
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => self::PAGE_TEMPLATE_LANDING_STATS_CTA_FAQ,
			Page_Template_Schema::FIELD_NAME             => 'Landing: stats, CTA, FAQ',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Landing page with key metrics, a primary CTA, and an optional FAQ section. Suited for product or service landings.',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'landing_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $section_reqs,
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => array(
				'page_purpose_summary'  => 'Landing with stats, CTA, and FAQ. Lead with metrics, then conversion prompt, then optional FAQ.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Stats establish credibility; CTA drives conversion; FAQ addresses objections.',
			),
			Page_Template_Schema::FIELD_VERSION          => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => 'Requires section expansion pack (st_stats_highlights, st_cta_conversion, st_faq).',
		);
	}

	/**
	 * Page template: FAQ-focused page with optional CTA.
	 *
	 * @return array<string, mixed>
	 */
	public static function faq_page_template(): array {
		$ordered      = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => Section_Expansion_Pack_Definitions::KEY_FAQ,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION,
				Page_Template_Schema::SECTION_ITEM_POSITION => 1,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => false,
			),
		);
		$section_reqs = array(
			Section_Expansion_Pack_Definitions::KEY_FAQ => array( 'required' => true ),
			Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION => array( 'required' => false ),
		);
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => self::PAGE_TEMPLATE_FAQ_PAGE,
			Page_Template_Schema::FIELD_NAME             => 'FAQ page',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'FAQ-focused page with an optional CTA section at the end. For support or product FAQ pages.',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'faq_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $section_reqs,
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => array(
				'page_purpose_summary' => 'FAQ page with optional CTA. Answer common questions then prompt for action.',
				'section_helper_order' => 'same_as_template',
			),
			Page_Template_Schema::FIELD_VERSION          => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => 'Requires section expansion pack (st_faq, st_cta_conversion).',
		);
	}

	/**
	 * Curated composition derived from pt_landing_stats_cta_faq.
	 *
	 * @return array<string, mixed>
	 */
	public static function composition_landing_stats_cta_faq(): array {
		$ordered = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY      => Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS,
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY      => Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION,
				Composition_Schema::SECTION_ITEM_POSITION => 1,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY      => Section_Expansion_Pack_Definitions::KEY_FAQ,
				Composition_Schema::SECTION_ITEM_POSITION => 2,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
		);
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => self::COMPOSITION_LANDING_STATS_CTA_FAQ,
			Composition_Schema::FIELD_NAME                 => 'Landing: stats, CTA, FAQ (example)',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_STATUS               => 'active',
			Composition_Schema::FIELD_VALIDATION_STATUS    => Composition_Validation_Result::VALID,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => self::PAGE_TEMPLATE_LANDING_STATS_CTA_FAQ,
			Composition_Schema::FIELD_VALIDATION_CODES     => array(),
		);
	}

	/**
	 * Curated composition derived from pt_faq_page.
	 *
	 * @return array<string, mixed>
	 */
	public static function composition_faq_cta(): array {
		$ordered = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY      => Section_Expansion_Pack_Definitions::KEY_FAQ,
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY      => Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION,
				Composition_Schema::SECTION_ITEM_POSITION => 1,
				Composition_Schema::SECTION_ITEM_VARIANT  => 'default',
			),
		);
		return array(
			Composition_Schema::FIELD_COMPOSITION_ID       => self::COMPOSITION_FAQ_CTA,
			Composition_Schema::FIELD_NAME                 => 'FAQ + CTA (example)',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered,
			Composition_Schema::FIELD_STATUS               => 'active',
			Composition_Schema::FIELD_VALIDATION_STATUS    => Composition_Validation_Result::VALID,
			Composition_Schema::FIELD_SOURCE_TEMPLATE_REF  => self::PAGE_TEMPLATE_FAQ_PAGE,
			Composition_Schema::FIELD_VALIDATION_CODES     => array(),
		);
	}
}
