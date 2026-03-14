<?php
/**
 * Template-focused demo and showcase fixture generator (spec §56.4, §60.6, §60.7, §59.15; Prompt 201).
 * Produces deterministic synthetic section templates, page templates, compositions, compare sets, and
 * Build Plan recommendation items for QA, demos, and stakeholder review. Uses real schemas and
 * product pathways; no external calls or live provider usage.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Fixtures;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Generates template showcase fixture pack: sections, page templates, compositions, compare sets,
 * and sample Build Plan template recommendation items. All data is synthetic and repeatable.
 */
final class Template_Showcase_Fixture_Generator {

	/** Manifest key for synthetic marker. */
	public const SYNTHETIC_MARKER = '_synthetic';

	/** Manifest version for schema evolution. */
	private const MANIFEST_VERSION = '1.0';

	/** Fixed timestamp for deterministic output. */
	private const FIXTURE_TIMESTAMP = '2025-03-15T10:00:00Z';

	/** Section keys (representative families). */
	private const SECTION_HERO   = 'st_showcase_hero_01';
	private const SECTION_TRUST  = 'st_showcase_trust_01';
	private const SECTION_CTA    = 'st_showcase_cta_01';

	/** Page template keys (top-level, hub, nested_hub, child_detail). */
	private const PAGE_LANDING   = 'pt_showcase_landing_01';
	private const PAGE_HUB       = 'pt_showcase_hub_01';
	private const PAGE_NESTED    = 'pt_showcase_nested_hub_01';
	private const PAGE_CHILD     = 'pt_showcase_child_01';

	/** Composition ids. */
	private const COMP_01 = 'comp_showcase_01';
	private const COMP_02 = 'comp_showcase_02';

	/**
	 * Generates the full template showcase fixture pack.
	 *
	 * @return array{
	 *   manifest: array{version: string, generated_at: string, section_families: list<string>, page_classes: list<string>, compare_sets: array{section_keys: list<string>, page_keys: list<string>}, counts: array{sections: int, page_templates: int, compositions: int, build_plan_recommendation_items: int}, _synthetic: bool},
	 *   sections: list<array>,
	 *   page_templates: list<array>,
	 *   compositions: list<array>,
	 *   build_plan_recommendation_items: list<array>,
	 *   compare_sets: array{section_keys: list<string>, page_keys: list<string>}
	 * }
	 */
	public function generate(): array {
		$sections       = $this->build_sections();
		$page_templates = $this->build_page_templates();
		$compositions   = $this->build_compositions();
		$items          = $this->build_build_plan_recommendation_items();
		$compare_sets   = array(
			'section_keys' => array( self::SECTION_HERO, self::SECTION_TRUST, self::SECTION_CTA ),
			'page_keys'    => array( self::PAGE_LANDING, self::PAGE_HUB, self::PAGE_NESTED ),
		);

		$section_families = array( 'hero_intro', 'trust_proof', 'cta' );
		$page_classes     = array( 'top_level', 'hub', 'nested_hub', 'child_detail' );

		$manifest = array(
			'version'        => self::MANIFEST_VERSION,
			'generated_at'    => self::FIXTURE_TIMESTAMP,
			'section_families' => $section_families,
			'page_classes'    => $page_classes,
			'compare_sets'    => $compare_sets,
			'counts'          => array(
				'sections'                      => count( $sections ),
				'page_templates'                => count( $page_templates ),
				'compositions'                  => count( $compositions ),
				'build_plan_recommendation_items' => count( $items ),
			),
			self::SYNTHETIC_MARKER => true,
		);

		return array(
			'manifest'                       => $manifest,
			'sections'                       => $sections,
			'page_templates'                 => $page_templates,
			'compositions'                   => $compositions,
			'build_plan_recommendation_items' => $items,
			'compare_sets'                   => $compare_sets,
		);
	}

	/**
	 * Returns the template showcase fixture manifest only (counts and metadata).
	 *
	 * @return array<string, mixed>
	 */
	public function get_manifest(): array {
		$out = $this->generate();
		return $out['manifest'];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function build_sections(): array {
		return array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY           => self::SECTION_HERO,
				Section_Schema::FIELD_NAME                  => 'Showcase Hero',
				Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Synthetic hero section for demo and QA.',
				Section_Schema::FIELD_CATEGORY              => 'hero_intro',
				Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_showcase_hero',
				Section_Schema::FIELD_FIELD_BLUEPRINT_REF   => 'acf_showcase_hero',
				Section_Schema::FIELD_HELPER_REF            => 'helper_showcase_hero',
				Section_Schema::FIELD_CSS_CONTRACT_REF      => 'css_showcase_hero',
				Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
				Section_Schema::FIELD_VARIANTS              => array( 'default' => array( 'label' => 'Default' ) ),
				Section_Schema::FIELD_COMPATIBILITY         => array(),
				Section_Schema::FIELD_VERSION               => array( 'version' => '1', 'stable_key_retained' => true ),
				Section_Schema::FIELD_STATUS                => 'active',
				Section_Schema::FIELD_RENDER_MODE           => 'block',
				Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
			),
			array(
				Section_Schema::FIELD_INTERNAL_KEY           => self::SECTION_TRUST,
				Section_Schema::FIELD_NAME                  => 'Showcase Trust',
				Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Synthetic trust/proof section for demo.',
				Section_Schema::FIELD_CATEGORY              => 'trust_proof',
				Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_showcase_trust',
				Section_Schema::FIELD_FIELD_BLUEPRINT_REF   => 'acf_showcase_trust',
				Section_Schema::FIELD_HELPER_REF            => 'helper_showcase_trust',
				Section_Schema::FIELD_CSS_CONTRACT_REF      => 'css_showcase_trust',
				Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
				Section_Schema::FIELD_VARIANTS              => array( 'default' => array( 'label' => 'Default' ) ),
				Section_Schema::FIELD_COMPATIBILITY         => array(),
				Section_Schema::FIELD_VERSION               => array( 'version' => '1', 'stable_key_retained' => true ),
				Section_Schema::FIELD_STATUS                => 'active',
				Section_Schema::FIELD_RENDER_MODE           => 'block',
				Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
			),
			array(
				Section_Schema::FIELD_INTERNAL_KEY           => self::SECTION_CTA,
				Section_Schema::FIELD_NAME                  => 'Showcase CTA',
				Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Synthetic CTA section for demo and CTA-rule QA.',
				Section_Schema::FIELD_CATEGORY              => 'cta',
				Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_showcase_cta',
				Section_Schema::FIELD_FIELD_BLUEPRINT_REF   => 'acf_showcase_cta',
				Section_Schema::FIELD_HELPER_REF            => 'helper_showcase_cta',
				Section_Schema::FIELD_CSS_CONTRACT_REF      => 'css_showcase_cta',
				Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
				Section_Schema::FIELD_VARIANTS              => array( 'default' => array( 'label' => 'Default' ) ),
				Section_Schema::FIELD_COMPATIBILITY         => array(),
				Section_Schema::FIELD_VERSION               => array( 'version' => '1', 'stable_key_retained' => true ),
				Section_Schema::FIELD_STATUS                => 'active',
				Section_Schema::FIELD_RENDER_MODE           => 'block',
				Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
			),
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function build_page_templates(): array {
		$ordered_hero = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY     => self::SECTION_HERO,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
		);
		$ordered_hero_trust = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY     => self::SECTION_HERO,
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
			array(
				Page_Template_Schema::SECTION_ITEM_KEY     => self::SECTION_TRUST,
				Page_Template_Schema::SECTION_ITEM_POSITION => 1,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => false,
			),
		);
		$requirements_landing = array( self::SECTION_HERO => array( 'required' => true ) );
		$requirements_hub     = array( self::SECTION_HERO => array( 'required' => true ), self::SECTION_TRUST => array( 'required' => false ) );

		return array(
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY       => self::PAGE_LANDING,
				Page_Template_Schema::FIELD_NAME               => 'Showcase Landing',
				Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Synthetic top-level landing for demo.',
				Page_Template_Schema::FIELD_ARCHETYPE          => 'landing',
				Page_Template_Schema::FIELD_ORDERED_SECTIONS   => $ordered_hero,
				Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $requirements_landing,
				Page_Template_Schema::FIELD_COMPATIBILITY     => array(),
				Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Showcase landing purpose.' ),
				Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
				Page_Template_Schema::FIELD_STATUS             => 'active',
				Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
				Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
				'template_category_class'                     => 'top_level',
				'template_family'                             => 'showcase_landing',
			),
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY       => self::PAGE_HUB,
				Page_Template_Schema::FIELD_NAME               => 'Showcase Hub',
				Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Synthetic hub page for demo.',
				Page_Template_Schema::FIELD_ARCHETYPE          => 'hub',
				Page_Template_Schema::FIELD_ORDERED_SECTIONS   => $ordered_hero_trust,
				Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $requirements_hub,
				Page_Template_Schema::FIELD_COMPATIBILITY     => array(),
				Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Showcase hub purpose.' ),
				Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
				Page_Template_Schema::FIELD_STATUS             => 'active',
				Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
				Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
				'template_category_class'                     => 'hub',
				'template_family'                             => 'showcase_hub',
			),
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY       => self::PAGE_NESTED,
				Page_Template_Schema::FIELD_NAME               => 'Showcase Nested Hub',
				Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Synthetic nested hub for demo.',
				Page_Template_Schema::FIELD_ARCHETYPE          => 'nested_hub',
				Page_Template_Schema::FIELD_ORDERED_SECTIONS   => $ordered_hero_trust,
				Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $requirements_hub,
				Page_Template_Schema::FIELD_COMPATIBILITY     => array(),
				Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Showcase nested hub purpose.' ),
				Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
				Page_Template_Schema::FIELD_STATUS             => 'active',
				Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
				Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
				'template_category_class'                     => 'nested_hub',
				'template_family'                             => 'showcase_nested_hub',
			),
			array(
				Page_Template_Schema::FIELD_INTERNAL_KEY       => self::PAGE_CHILD,
				Page_Template_Schema::FIELD_NAME               => 'Showcase Child Detail',
				Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Synthetic child/detail page for demo.',
				Page_Template_Schema::FIELD_ARCHETYPE          => 'service_page',
				Page_Template_Schema::FIELD_ORDERED_SECTIONS   => $ordered_hero_trust,
				Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $requirements_hub,
				Page_Template_Schema::FIELD_COMPATIBILITY     => array(),
				Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Showcase child detail purpose.' ),
				Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
				Page_Template_Schema::FIELD_STATUS             => 'active',
				Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
				Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
				'template_category_class'                     => 'child_detail',
				'template_family'                             => 'showcase_child_detail',
			),
		);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function build_compositions(): array {
		$ordered1 = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY     => self::SECTION_HERO,
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY     => self::SECTION_CTA,
				Composition_Schema::SECTION_ITEM_POSITION => 1,
				Composition_Schema::SECTION_ITEM_VARIANT => 'default',
			),
		);
		$ordered2 = array(
			array(
				Composition_Schema::SECTION_ITEM_KEY     => self::SECTION_HERO,
				Composition_Schema::SECTION_ITEM_POSITION => 0,
				Composition_Schema::SECTION_ITEM_VARIANT => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY     => self::SECTION_TRUST,
				Composition_Schema::SECTION_ITEM_POSITION => 1,
				Composition_Schema::SECTION_ITEM_VARIANT => 'default',
			),
			array(
				Composition_Schema::SECTION_ITEM_KEY     => self::SECTION_CTA,
				Composition_Schema::SECTION_ITEM_POSITION => 2,
				Composition_Schema::SECTION_ITEM_VARIANT => 'default',
			),
		);

		return array(
			array(
				Composition_Schema::FIELD_COMPOSITION_ID       => self::COMP_01,
				Composition_Schema::FIELD_NAME                => 'Showcase Composition 1',
				Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered1,
				Composition_Schema::FIELD_STATUS              => 'active',
				Composition_Schema::FIELD_VALIDATION_STATUS   => 'valid',
				Composition_Schema::FIELD_SOURCE_TEMPLATE_REF => self::PAGE_LANDING,
			),
			array(
				Composition_Schema::FIELD_COMPOSITION_ID       => self::COMP_02,
				Composition_Schema::FIELD_NAME                => 'Showcase Composition 2',
				Composition_Schema::FIELD_ORDERED_SECTION_LIST => $ordered2,
				Composition_Schema::FIELD_STATUS              => 'active',
				Composition_Schema::FIELD_VALIDATION_STATUS   => 'valid',
				Composition_Schema::FIELD_SOURCE_TEMPLATE_REF => self::PAGE_HUB,
			),
		);
	}

	/**
	 * Sample Build Plan items with proposed_template_summary / existing_page_template_change_summary for demo.
	 *
	 * @return list<array<string, mixed>>
	 */
	private function build_build_plan_recommendation_items(): array {
		$proposed_new = array(
			'template_key'            => self::PAGE_LANDING,
			'name'                    => 'Showcase Landing',
			'template_category_class' => 'top_level',
			'template_family'          => 'showcase_landing',
			'cta_direction_summary'    => 'Primary CTA only.',
			'section_count'           => 1,
			'deprecation_status'      => 'active',
			'replacement_keys'        => array(),
		);
		$proposed_hub = array(
			'template_key'            => self::PAGE_HUB,
			'name'                    => 'Showcase Hub',
			'template_category_class' => 'hub',
			'template_family'          => 'showcase_hub',
			'cta_direction_summary'   => 'Neutral.',
			'section_count'           => 2,
			'deprecation_status'      => 'active',
			'replacement_keys'        => array(),
		);
		$existing_change = array(
			'template_key'            => self::PAGE_NESTED,
			'name'                    => 'Showcase Nested Hub',
			'template_category_class' => 'nested_hub',
			'template_family'          => 'showcase_nested_hub',
			'cta_direction_summary'   => 'Neutral.',
			'section_count'           => 2,
			'deprecation_status'      => 'active',
		);

		return array(
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'showcase_new_1',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_slug'               => 'demo-home',
					'title'                     => 'Demo Home',
					'proposed_template_summary'  => $proposed_new,
				),
				Build_Plan_Item_Schema::KEY_STATUS    => 'proposed',
			),
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'showcase_new_2',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_slug'               => 'demo-services',
					'title'                     => 'Demo Services',
					'proposed_template_summary'  => $proposed_hub,
				),
				Build_Plan_Item_Schema::KEY_STATUS    => 'proposed',
			),
			array(
				Build_Plan_Item_Schema::KEY_ITEM_ID   => 'showcase_epc_1',
				Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE,
				Build_Plan_Item_Schema::KEY_PAYLOAD   => array(
					'target_page_id'                    => 1001,
					'existing_page_template_change_summary' => $existing_change,
					'replacement_reason_summary'       => 'Synthetic: align with nested hub template for demo.',
				),
				Build_Plan_Item_Schema::KEY_STATUS    => 'proposed',
			),
		);
	}
}
