<?php
/**
 * Feature, benefit, offer, and value-proposition section template definitions for SEC-03 library batch (spec §12, §15, §20, §51, Prompt 149).
 * Production-grade feature/benefit/value sections with full metadata, field blueprints, preview and accessibility metadata.
 * Does not persist; callers save via Section_Template_Repository or Feature_Benefit_Value_Library_Batch_Seeder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Returns section definitions for the feature/benefit/value library batch (SEC-03).
 * Each definition is schema-compliant with embedded field_blueprint, taxonomy, preview and animation metadata.
 */
final class Feature_Benefit_Value_Library_Batch_Definitions {

	/** Batch ID per template-library-inventory-manifest §3.1. */
	public const BATCH_ID = 'SEC-03';

	/** Section purpose family for all in this batch. */
	public const PURPOSE_FAMILY = 'feature_benefit';

	/** Industry keys for first launch verticals (section-industry-affinity-contract; Prompt 363). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all feature/benefit/value batch section definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::fb_feature_grid_01(),
			self::fb_benefit_band_01(),
			self::fb_offer_compare_01(),
			self::fb_package_summary_01(),
			self::fb_differentiator_01(),
			self::fb_before_after_01(),
			self::fb_why_choose_01(),
			self::fb_product_spec_01(),
			self::fb_service_offering_01(),
			self::fb_value_prop_01(),
			self::fb_feature_compact_01(),
			self::fb_benefit_detail_01(),
			self::fb_offer_highlight_01(),
			self::fb_local_value_01(),
			self::fb_directory_value_01(),
			self::fb_resource_explainer_01(),
		);
	}

	/**
	 * Returns section keys in this batch (for listing and tests).
	 *
	 * @return list<string>
	 */
	public static function section_keys(): array {
		return array(
			'fb_feature_grid_01',
			'fb_benefit_band_01',
			'fb_offer_compare_01',
			'fb_package_summary_01',
			'fb_differentiator_01',
			'fb_before_after_01',
			'fb_why_choose_01',
			'fb_product_spec_01',
			'fb_service_offering_01',
			'fb_value_prop_01',
			'fb_feature_compact_01',
			'fb_benefit_detail_01',
			'fb_offer_highlight_01',
			'fb_local_value_01',
			'fb_directory_value_01',
			'fb_resource_explainer_01',
		);
	}

	/**
	 * Builds a feature/benefit/value section definition with common structure.
	 *
	 * @param string $key Internal key.
	 * @param string $name Display name.
	 * @param string $purpose_summary Purpose summary.
	 * @param string $variation_family_key Variation family (e.g. value_proposition, feature_grid).
	 * @param string $preview_desc Preview description.
	 * @param array<string, mixed> $blueprint_fields Field definitions for embedded blueprint.
	 * @param array<string, mixed> $preview_defaults Synthetic ACF defaults for preview.
	 * @param array<string, mixed> $extra Optional extra keys (short_label, cta_classification, suggested_use_cases, variants, etc.).
	 * @return array<string, mixed>
	 */
	private static function fb_definition(
		string $key,
		string $name,
		string $purpose_summary,
		string $variation_family_key,
		string $preview_desc,
		array $blueprint_fields,
		array $preview_defaults,
		array $extra = array()
	): array {
		$bp_id = 'acf_blueprint_' . $key;
		$base = array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_NAME                    => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY         => $purpose_summary,
			Section_Schema::FIELD_CATEGORY                => 'feature_benefit',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_' . $key,
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => $bp_id,
			Section_Schema::FIELD_HELPER_REF              => 'helper_' . $key,
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css_' . $key,
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array(
				'default' => array( 'label' => 'Default', 'description' => '', 'css_modifiers' => array() ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(
				'may_precede'          => array(),
				'may_follow'           => array(),
				'avoid_adjacent'       => array(),
				'duplicate_purpose_of' => array(),
			),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
			'section_purpose_family'                     => self::PURPOSE_FAMILY,
			'variation_family_key'                       => $variation_family_key,
			'preview_description'                        => $preview_desc,
			'preview_image_ref'                          => '',
			'animation_tier'                             => 'subtle',
			'animation_families'                         => array( 'entrance', 'hover' ),
			'preview_defaults'                           => $preview_defaults,
			'accessibility_warnings_or_enhancements'     => 'Use semantic headings and list or grid for repeated items. Do not rely on color alone for meaning (spec §51.3, §51.8). Ensure sufficient contrast. Optional nodes (icons, images) must be omit-safe and field-driven.',
			'seo_relevance_notes'                       => 'Feature and benefit content supports entity and offering signals; keep headings descriptive and benefit-focused (spec §15.9).',
		);
		$base['field_blueprint'] = array(
			'blueprint_id'    => $bp_id,
			'section_key'     => $key,
			'section_version' => '1',
			'label'           => $name . ' fields',
			'description'     => 'Feature/benefit content fields.',
			'fields'          => $blueprint_fields,
		);
		if ( ! isset( $extra[ Section_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Section_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $base, $extra );
	}

	/** Feature grid: headline + repeatable (title, description, optional icon_ref). */
	public static function fb_feature_grid_01(): array {
		$key = 'fb_feature_grid_01';
		$fields = array(
			array( 'key' => 'field_fb_fg_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_fg_items',
				'name'        => 'features',
				'label'       => 'Features',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_fg_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_fg_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_fb_fg_icon', 'name' => 'icon_ref', 'label' => 'Icon reference', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Feature grid',
			'Grid of features with title, description, and optional icon. Use for product or service capability lists.',
			'feature_grid',
			'Grid of feature items with title and description.',
			$fields,
			array( 'headline' => 'What we offer', 'features' => array( array( 'title' => 'Feature A', 'description' => 'Synthetic description.', 'icon_ref' => '' ) ) ),
			array( 'short_label' => 'Feature grid', 'suggested_use_cases' => array( 'Product page', 'Service page', 'Landing' ) )
		);
	}

	/** Benefit band: headline + repeatable benefit text. */
	public static function fb_benefit_band_01(): array {
		$key = 'fb_benefit_band_01';
		$fields = array(
			array( 'key' => 'field_fb_bb_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_bb_items',
				'name'        => 'benefits',
				'label'       => 'Benefits',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_bb_text', 'name' => 'text', 'label' => 'Benefit text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Benefit band',
			'Band of benefit statements with optional headline. Use for value highlights on service or product pages.',
			'benefit_band',
			'Band of benefit items.',
			$fields,
			array( 'headline' => 'Why it matters', 'benefits' => array( array( 'text' => 'Synthetic benefit one' ), array( 'text' => 'Synthetic benefit two' ) ) ),
			array( 'short_label' => 'Benefit band', 'suggested_use_cases' => array( 'Service page', 'Product page', 'Landing' ) )
		);
	}

	/** Offer comparison: headline + repeatable offers (name, features, cta). */
	public static function fb_offer_compare_01(): array {
		$key = 'fb_offer_compare_01';
		$fields = array(
			array( 'key' => 'field_fb_oc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_oc_offers',
				'name'        => 'offers',
				'label'       => 'Offers',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_oc_name', 'name' => 'name', 'label' => 'Offer name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_oc_features', 'name' => 'features', 'label' => 'Features (one per line or list)', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_fb_oc_cta', 'name' => 'cta', 'label' => 'CTA link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Offer comparison',
			'Comparison of offers with name, features, and optional CTA per offer. Use for plan or option comparison.',
			'offer_comparison',
			'Comparison of offers with features and CTA.',
			$fields,
			array( 'headline' => 'Compare options', 'offers' => array( array( 'name' => 'Option A', 'features' => 'Synthetic feature list.', 'cta' => array() ) ) ),
			array( 'short_label' => 'Offer compare', 'cta_classification' => 'primary_cta', 'suggested_use_cases' => array( 'Pricing page', 'Service tiers', 'Product variants' ) )
		);
	}

	/** Package summary: headline + repeatable packages (name, highlights, optional price/cta). */
	public static function fb_package_summary_01(): array {
		$key = 'fb_package_summary_01';
		$fields = array(
			array( 'key' => 'field_fb_ps_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_ps_packages',
				'name'        => 'packages',
				'label'       => 'Packages',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_ps_name', 'name' => 'name', 'label' => 'Package name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_ps_highlights', 'name' => 'highlights', 'label' => 'Highlights', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_fb_ps_price', 'name' => 'price_label', 'label' => 'Price or label', 'type' => 'text', 'required' => false ),
					array( 'key' => 'field_fb_ps_cta', 'name' => 'cta', 'label' => 'CTA link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Package summary',
			'Summary of packages with name, highlights, optional price label, and CTA. Use for tier or bundle presentation.',
			'package_summary',
			'Package summaries with highlights and CTA.',
			$fields,
			array( 'headline' => 'Packages', 'packages' => array( array( 'name' => 'Basic', 'highlights' => 'Synthetic highlights.', 'price_label' => '', 'cta' => array() ) ) ),
			array(
				'short_label'        => 'Packages',
				'cta_classification' => 'primary_cta',
				'suggested_use_cases' => array( 'Pricing', 'Bundles', 'Tiers' ),
				Section_Schema::FIELD_INDUSTRY_NOTES => array(
					'cosmetology_nail' => 'Strong fit for service packages and pricing; one CTA per tier.',
					'realtor'          => 'Good for service tiers; avoid misleading pricing claims.',
					'plumber'          => 'Strong fit for service tiers and financing; disclose clearly.',
					'disaster_recovery' => 'Good for response or service tiers; avoid overclaiming.',
				),
			)
		);
	}

	/** Differentiator list: headline + repeatable (title, description). */
	public static function fb_differentiator_01(): array {
		$key = 'fb_differentiator_01';
		$fields = array(
			array( 'key' => 'field_fb_diff_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_diff_items',
				'name'        => 'differentiators',
				'label'       => 'Differentiators',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_diff_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_diff_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Differentiator list',
			'List of differentiators with title and description. Use for "what sets us apart" on service or product pages.',
			'differentiator',
			'List of differentiator items.',
			$fields,
			array( 'headline' => 'What sets us apart', 'differentiators' => array( array( 'title' => 'Differentiator A', 'description' => 'Synthetic description.' ) ) ),
			array( 'short_label' => 'Differentiators', 'suggested_use_cases' => array( 'Service page', 'Product page', 'Competitive' ) )
		);
	}

	/** Before/after value framing: headline, before label, after label, repeatable points. */
	public static function fb_before_after_01(): array {
		$key = 'fb_before_after_01';
		$fields = array(
			array( 'key' => 'field_fb_ba_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_fb_ba_before', 'name' => 'before_label', 'label' => 'Before label', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_fb_ba_after', 'name' => 'after_label', 'label' => 'After label', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_ba_points',
				'name'        => 'points',
				'label'       => 'Value points',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_ba_text', 'name' => 'text', 'label' => 'Point text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Before/after value framing',
			'Before/after value framing with optional labels and repeatable points. Use for transformation or outcome messaging.',
			'value_framing',
			'Before/after framing with value points.',
			$fields,
			array( 'headline' => 'Before and after', 'before_label' => 'Before', 'after_label' => 'After', 'points' => array( array( 'text' => 'Synthetic point' ) ) ),
			array( 'short_label' => 'Before/after', 'suggested_use_cases' => array( 'Service page', 'Outcome focus', 'Transformation' ) )
		);
	}

	/** Why choose us: headline + repeatable reasons. */
	public static function fb_why_choose_01(): array {
		$key = 'fb_why_choose_01';
		$fields = array(
			array( 'key' => 'field_fb_wc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_wc_reasons',
				'name'        => 'reasons',
				'label'       => 'Reasons',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_wc_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_wc_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Why choose us',
			'Why choose us block with headline and repeatable reasons (title, description). Use for service or provider differentiation.',
			'why_choose',
			'Why choose us with reasons list.',
			$fields,
			array( 'headline' => 'Why choose us', 'reasons' => array( array( 'title' => 'Reason one', 'description' => 'Synthetic description.' ) ) ),
			array( 'short_label' => 'Why choose', 'suggested_use_cases' => array( 'Service page', 'Local page', 'Provider' ) )
		);
	}

	/** Product spec/value hybrid: headline, specs repeater, value copy. */
	public static function fb_product_spec_01(): array {
		$key = 'fb_product_spec_01';
		$fields = array(
			array( 'key' => 'field_fb_pspec_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_pspec_specs',
				'name'        => 'specs',
				'label'       => 'Specifications',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => false,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_pspec_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_pspec_value', 'name' => 'value', 'label' => 'Value', 'type' => 'text', 'required' => true ),
				),
			),
			array( 'key' => 'field_fb_pspec_value_copy', 'name' => 'value_copy', 'label' => 'Value proposition copy', 'type' => 'textarea', 'required' => false ),
		);
		return self::fb_definition(
			$key,
			'Product spec / value hybrid',
			'Product specifications with optional value proposition copy. Use for product or offering detail pages.',
			'product_spec',
			'Specs and value copy.',
			$fields,
			array( 'headline' => 'Specifications', 'specs' => array( array( 'label' => 'Spec A', 'value' => 'Value' ) ), 'value_copy' => 'Synthetic value copy.' ),
			array( 'short_label' => 'Product spec', 'suggested_use_cases' => array( 'Product page', 'Offering detail', 'Spec sheet' ) )
		);
	}

	/** Service offering layout: headline + repeatable services (name, description, optional link). */
	public static function fb_service_offering_01(): array {
		$key = 'fb_service_offering_01';
		$fields = array(
			array( 'key' => 'field_fb_so_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_so_services',
				'name'        => 'services',
				'label'       => 'Services',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_so_name', 'name' => 'name', 'label' => 'Service name', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_so_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_fb_so_link', 'name' => 'link', 'label' => 'Link', 'type' => 'link', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Service offering layout',
			'Service offerings with name, description, and optional link. Use for service hub or location pages.',
			'service_offering',
			'Service offerings with name and description.',
			$fields,
			array( 'headline' => 'Our services', 'services' => array( array( 'name' => 'Service A', 'description' => 'Synthetic description.', 'link' => array() ) ) ),
			array( 'short_label' => 'Service offering', 'cta_classification' => 'navigation_cta', 'suggested_use_cases' => array( 'Service hub', 'Location page', 'Offerings' ) )
		);
	}

	/** Value proposition block: headline, value statement, supporting points. */
	public static function fb_value_prop_01(): array {
		$key = 'fb_value_prop_01';
		$fields = array(
			array( 'key' => 'field_fb_vp_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_fb_vp_statement', 'name' => 'value_statement', 'label' => 'Value statement', 'type' => 'textarea', 'required' => true ),
			array(
				'key'         => 'field_fb_vp_points',
				'name'        => 'supporting_points',
				'label'       => 'Supporting points',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => false,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_vp_text', 'name' => 'text', 'label' => 'Point text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Value proposition block',
			'Value proposition with headline, main statement, and optional supporting points. Use for core value messaging.',
			'value_proposition',
			'Value proposition with statement and points.',
			$fields,
			array( 'headline' => 'Our value', 'value_statement' => 'Synthetic value statement.', 'supporting_points' => array( array( 'text' => 'Point one' ) ) ),
			array( 'short_label' => 'Value prop', 'suggested_use_cases' => array( 'Landing', 'Service page', 'Product page' ) )
		);
	}

	/** Compact feature list: headline + repeatable short feature text. */
	public static function fb_feature_compact_01(): array {
		$key = 'fb_feature_compact_01';
		$fields = array(
			array( 'key' => 'field_fb_fc_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_fc_items',
				'name'        => 'features',
				'label'       => 'Features',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_fc_text', 'name' => 'text', 'label' => 'Feature text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Compact feature list',
			'Compact list of short feature statements. Use for dense feature display or sidebar.',
			'feature_compact',
			'Compact feature list.',
			$fields,
			array( 'headline' => 'Features', 'features' => array( array( 'text' => 'Feature one' ), array( 'text' => 'Feature two' ) ) ),
			array( 'short_label' => 'Feature compact', 'suggested_use_cases' => array( 'Product page', 'Dense layout', 'Sidebar' ) )
		);
	}

	/** Detailed benefit: headline + repeatable (title, description, optional image). */
	public static function fb_benefit_detail_01(): array {
		$key = 'fb_benefit_detail_01';
		$fields = array(
			array( 'key' => 'field_fb_bd_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_bd_items',
				'name'        => 'benefits',
				'label'       => 'Benefits',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_bd_title', 'name' => 'title', 'label' => 'Title', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_bd_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
					array( 'key' => 'field_fb_bd_image', 'name' => 'image', 'label' => 'Optional image', 'type' => 'image', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Detailed benefit',
			'Detailed benefits with title, description, and optional image. Use for media-assisted benefit blocks.',
			'benefit_detail',
			'Detailed benefits with optional images.',
			$fields,
			array( 'headline' => 'Benefits', 'benefits' => array( array( 'title' => 'Benefit A', 'description' => 'Synthetic description.', 'image' => array() ) ) ),
			array( 'short_label' => 'Benefit detail', 'suggested_use_cases' => array( 'Service page', 'Product page', 'Media-assisted' ) )
		);
	}

	/** Single offer highlight: headline, offer name, description, CTA. */
	public static function fb_offer_highlight_01(): array {
		$key = 'fb_offer_highlight_01';
		$fields = array(
			array( 'key' => 'field_fb_oh_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_fb_oh_name', 'name' => 'offer_name', 'label' => 'Offer name', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_fb_oh_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'required' => false ),
			array( 'key' => 'field_fb_oh_cta', 'name' => 'cta', 'label' => 'CTA link', 'type' => 'link', 'required' => false ),
		);
		return self::fb_definition(
			$key,
			'Offer highlight',
			'Single offer highlight with name, description, and optional CTA. Use for featured plan or offer.',
			'offer_highlight',
			'Single offer with CTA.',
			$fields,
			array( 'headline' => 'Featured offer', 'offer_name' => 'Premium', 'description' => 'Synthetic description.', 'cta' => array() ),
			array( 'short_label' => 'Offer highlight', 'cta_classification' => 'primary_cta', 'suggested_use_cases' => array( 'Pricing', 'Featured plan', 'Promo' ) )
		);
	}

	/** Local value: headline + value points for location/service area. */
	public static function fb_local_value_01(): array {
		$key = 'fb_local_value_01';
		$fields = array(
			array( 'key' => 'field_fb_lv_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array(
				'key'         => 'field_fb_lv_points',
				'name'        => 'value_points',
				'label'       => 'Value points',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_lv_text', 'name' => 'text', 'label' => 'Point text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Local / service value',
			'Value points for local or service area pages. Use for location-specific value messaging.',
			'local_value',
			'Local value points.',
			$fields,
			array( 'headline' => 'Value in your area', 'value_points' => array( array( 'text' => 'Synthetic local value point' ) ) ),
			array( 'short_label' => 'Local value', 'suggested_use_cases' => array( 'Location page', 'Service area', 'Regional' ) )
		);
	}

	/** Directory value: headline + intro + repeatable value items for directory entries. */
	public static function fb_directory_value_01(): array {
		$key = 'fb_directory_value_01';
		$fields = array(
			array( 'key' => 'field_fb_dv_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => false ),
			array( 'key' => 'field_fb_dv_intro', 'name' => 'intro', 'label' => 'Intro', 'type' => 'textarea', 'required' => false ),
			array(
				'key'         => 'field_fb_dv_items',
				'name'        => 'value_items',
				'label'       => 'Value items',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => true,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_dv_label', 'name' => 'label', 'label' => 'Label', 'type' => 'text', 'required' => true ),
					array( 'key' => 'field_fb_dv_desc', 'name' => 'description', 'label' => 'Description', 'type' => 'text', 'required' => false ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Directory value',
			'Value proposition for directory or listing context: headline, intro, repeatable value items. Use for directory entry or category intro.',
			'directory_value',
			'Directory value with intro and items.',
			$fields,
			array( 'headline' => 'What you get', 'intro' => 'Synthetic intro.', 'value_items' => array( array( 'label' => 'Item A', 'description' => 'Synthetic.' ) ) ),
			array( 'short_label' => 'Directory value', 'suggested_use_cases' => array( 'Directory hub', 'Category intro', 'Listing' ) )
		);
	}

	/** Resource explainer: headline, body copy, optional repeatable key points. */
	public static function fb_resource_explainer_01(): array {
		$key = 'fb_resource_explainer_01';
		$fields = array(
			array( 'key' => 'field_fb_re_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text', 'required' => true ),
			array( 'key' => 'field_fb_re_body', 'name' => 'body', 'label' => 'Body copy', 'type' => 'textarea', 'required' => true ),
			array(
				'key'         => 'field_fb_re_points',
				'name'        => 'key_points',
				'label'       => 'Key points',
				'type'        => Field_Blueprint_Schema::TYPE_REPEATER,
				'required'    => false,
				'sub_fields'  => array(
					array( 'key' => 'field_fb_re_text', 'name' => 'text', 'label' => 'Point text', 'type' => 'text', 'required' => true ),
				),
			),
		);
		return self::fb_definition(
			$key,
			'Resource explainer',
			'Explainer block with headline, body copy, and optional key points. Use for resource or informational pages.',
			'resource_explainer',
			'Explainer with body and key points.',
			$fields,
			array( 'headline' => 'How it works', 'body' => 'Synthetic body copy.', 'key_points' => array( array( 'text' => 'Key point one' ) ) ),
			array( 'short_label' => 'Resource explainer', 'suggested_use_cases' => array( 'Resource page', 'How-to', 'Informational' ) )
		);
	}
}
