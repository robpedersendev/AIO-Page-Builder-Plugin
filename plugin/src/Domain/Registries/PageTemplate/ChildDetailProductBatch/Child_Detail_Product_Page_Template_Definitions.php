<?php
/**
 * Child/detail page template definitions for products, catalog entities, spec-heavy and item-level pages (spec §13, §14.3, §16, §17.7, Prompt 161).
 * template_category_class = child_detail; archetype = offer_page | comparison_page. Products/catalog family.
 * ~10 non-CTA (8–14) + ≥5 CTA sections, mandatory bottom CTA, no adjacent CTA. Spec-rich, comparison and recommendation support.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailProductBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the product/catalog child/detail batch (PT-08 scope).
 * Singular product, catalog entity, equipment/spec, or item-level pages (e.g. 1TB WD Laptop Hard Drive, Desktop Variant, Furniture Piece, Toy Product).
 */
final class Child_Detail_Product_Page_Template_Definitions {

	/** Batch ID for product/catalog child/detail pages (template-library-inventory-manifest PT-08). */
	public const BATCH_ID = 'PT-08';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Allowed template families for this batch (page-template-category-taxonomy-contract).
	 *
	 * @var array<int, string>
	 */
	public const ALLOWED_FAMILIES = array(
		'products',
	);

	/**
	 * Returns all product/catalog child/detail page template definitions (order preserved for seeding).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::child_detail_product_spec_01(),
			self::child_detail_product_spec_02(),
			self::child_detail_product_comparison_01(),
			self::child_detail_product_comparison_02(),
			self::child_detail_product_media_01(),
			self::child_detail_product_recommendation_01(),
			self::child_detail_product_proof_01(),
			self::child_detail_product_cta_intense_01(),
			self::child_detail_product_catalog_item_01(),
			self::child_detail_product_equipment_01(),
			self::child_detail_product_buying_guide_01(),
			self::child_detail_product_value_01(),
			self::child_detail_product_differentiator_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return array<int, string>
	 */
	public static function template_keys(): array {
		return array(
			'child_detail_product_spec_01',
			'child_detail_product_spec_02',
			'child_detail_product_comparison_01',
			'child_detail_product_comparison_02',
			'child_detail_product_media_01',
			'child_detail_product_recommendation_01',
			'child_detail_product_proof_01',
			'child_detail_product_cta_intense_01',
			'child_detail_product_catalog_item_01',
			'child_detail_product_equipment_01',
			'child_detail_product_buying_guide_01',
			'child_detail_product_value_01',
			'child_detail_product_differentiator_01',
		);
	}

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 *
	 * @param array<int, string> $section_keys Section internal keys in order (no adjacent CTA; last must be CTA).
	 * @return array{ ordered: array<int, array<string, mixed>>, requirements: array<string, array{required: bool}> }
	 */
	private static function ordered_and_requirements( array $section_keys ): array {
		$ordered      = array();
		$requirements = array();
		foreach ( $section_keys as $pos => $key ) {
			$ordered[]            = array(
				Page_Template_Schema::SECTION_ITEM_KEY => $key,
				Page_Template_Schema::SECTION_ITEM_POSITION => $pos,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			);
			$requirements[ $key ] = array( 'required' => true );
		}
		return array(
			'ordered'      => $ordered,
			'requirements' => $requirements,
		);
	}

	/**
	 * Base page template shape for product/catalog child/detail batch.
	 *
	 * @param string       $internal_key
	 * @param string       $name
	 * @param string       $purpose_summary
	 * @param string       $archetype
	 * @param string       $template_family
	 * @param array<int, string> $parent_family_compatibility
	 * @param array        $ordered
	 * @param array        $section_requirements
	 * @param array        $one_pager
	 * @param string       $endpoint_notes
	 * @param array        $extra
	 * @return array<string, mixed>
	 */
	private static function base_template(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $archetype,
		string $template_family,
		array $parent_family_compatibility,
		array $ordered,
		array $section_requirements,
		array $one_pager,
		string $endpoint_notes,
		array $extra = array()
	): array {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $internal_key,
			Page_Template_Schema::FIELD_NAME             => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE        => $archetype,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY    => array(),
			Page_Template_Schema::FIELD_ONE_PAGER        => $one_pager,
			Page_Template_Schema::FIELD_VERSION          => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS           => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => $endpoint_notes,
			'template_category_class'                    => 'child_detail',
			'template_family'                            => $template_family,
			'parent_family_compatibility'                => $parent_family_compatibility,
			'hierarchy_hints'                            => array(
				'common_parent_page_types' => 'hub, nested_hub',
				'hierarchy_role'           => 'leaf',
			),
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function child_detail_product_spec_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'fb_benefit_detail_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'tp_rating_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'tp_guarantee_01',
			'fb_why_choose_01',
			'cta_quote_request_01',
			'mlp_product_cards_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_spec_01',
			'Product detail (spec-heavy)',
			'Child/detail page for a single product with specification density: product hero, value prop, benefit detail, product CTA, buying process, rating, offer compare, purchase CTA, guarantee, why choose, quote CTA, product cards, trust band, consultation CTA, consent note, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product detail with spec/benefit density. Benefit detail and offer compare; purchase, quote, consultation, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Spec-heavy; technical and benefit detail support decision; synthetic preview only.',
				'cta_direction_summary' => 'Product detail, purchase, quote, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Spec/benefit density; no commerce logic.',
			)
		);
	}

	public static function child_detail_product_spec_02(): array {
		$keys = array(
			'hero_prod_01',
			'fb_offer_compare_01',
			'fb_benefit_detail_01',
			'cta_purchase_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'fb_differentiator_01',
			'cta_product_detail_02',
			'tp_rating_01',
			'fb_why_choose_01',
			'cta_quote_request_01',
			'mlp_product_cards_01',
			'tp_trust_band_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_spec_02',
			'Product detail (technical/spec-led)',
			'Child/detail page for a single product with technical/spec emphasis: product hero, offer compare, benefit detail, purchase CTA, buying process, guarantee, differentiator, product CTA, rating, why choose, quote CTA, product cards, trust band, compare CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product technical/spec-led. Differentiator and benefit detail; purchase, product, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Technical detail and differentiator; comparison-adjacent with compare CTA.',
				'cta_direction_summary' => 'Purchase, product detail, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Technical/spec-led; differentiator prominent.',
			)
		);
	}

	public static function child_detail_product_comparison_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'mlp_comparison_cards_01',
			'fb_differentiator_01',
			'tp_rating_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'fb_benefit_band_01',
			'cta_purchase_01',
			'tp_guarantee_01',
			'mlp_product_cards_01',
			'cta_quote_request_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_comparison_01',
			'Product detail (comparison-adjacent)',
			'Child/detail page for a single product with comparison depth: product hero, offer compare, comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, benefit band, purchase CTA, guarantee, product cards, quote CTA, consent, contact CTA.',
			'comparison_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product with comparison depth. Comparison steps and cards; compare, product, purchase, quote, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Comparison-adjacent; supports decision vs alternatives; no checkout.',
				'cta_direction_summary' => 'Compare, product detail, purchase, quote, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Comparison depth; comparison steps and cards.',
			)
		);
	}

	public static function child_detail_product_comparison_02(): array {
		$keys = array(
			'hero_prod_01',
			'fb_differentiator_01',
			'fb_offer_compare_01',
			'cta_product_detail_02',
			'mlp_comparison_cards_01',
			'ptf_comparison_steps_01',
			'tp_rating_01',
			'cta_compare_next_01',
			'fb_benefit_band_01',
			'ptf_buying_process_01',
			'cta_purchase_01',
			'tp_guarantee_01',
			'fb_offer_highlight_01',
			'cta_quote_request_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_comparison_02',
			'Product detail (comparison + highlight)',
			'Child/detail page for a single product with comparison and offer highlight: product hero, differentiator, offer compare, product CTA, comparison cards, comparison steps, rating, compare CTA, benefit band, buying process, purchase CTA, guarantee, offer highlight, quote CTA, contact panel, contact CTA.',
			'comparison_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product comparison plus offer highlight. Compare, purchase, quote, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Comparison and highlight; decision-support flow.',
				'cta_direction_summary' => 'Product detail, compare, purchase, quote, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Comparison + offer highlight.',
			)
		);
	}

	public static function child_detail_product_media_01(): array {
		$keys = array(
			'hero_prod_01',
			'mlp_product_cards_01',
			'fb_value_prop_01',
			'cta_product_detail_01',
			'mlp_card_grid_01',
			'tp_testimonial_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'tp_rating_01',
			'ptf_buying_process_01',
			'cta_quote_request_01',
			'fb_benefit_band_01',
			'mlp_listing_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_media_01',
			'Product detail (media emphasis)',
			'Child/detail page for a single product with media emphasis: product hero, product cards, value prop, product CTA, card grid, testimonial, offer compare, purchase CTA, rating, buying process, quote CTA, benefit band, listing, compare CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product with media emphasis. Product cards, card grid, listing; purchase, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Media-rich; cards and grid support catalog-style presentation.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Media emphasis; cards and grid.',
			)
		);
	}

	public static function child_detail_product_recommendation_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'fb_why_choose_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'tp_testimonial_01',
			'fb_benefit_band_01',
			'cta_purchase_01',
			'tp_rating_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'fb_offer_highlight_01',
			'mlp_product_cards_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_recommendation_01',
			'Product detail (recommendation posture)',
			'Child/detail page for a single product with recommendation posture: product hero, value prop, why choose, product CTA, buying process, testimonial, benefit band, purchase CTA, rating, guarantee, quote CTA, offer highlight, product cards, consultation CTA, contact panel, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product recommendation posture. Why choose and benefit band; purchase, quote, consultation, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Recommendation-oriented; buying process and highlight support guidance.',
				'cta_direction_summary' => 'Product detail, purchase, quote, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Recommendation posture; why choose and highlight.',
			)
		);
	}

	public static function child_detail_product_proof_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'tp_trust_band_01',
			'tp_rating_01',
			'cta_product_detail_01',
			'tp_testimonial_01',
			'tp_guarantee_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'tp_client_logo_01',
			'fb_why_choose_01',
			'cta_quote_request_01',
			'ptf_buying_process_01',
			'tp_testimonial_02',
			'cta_consultation_01',
			'lpu_trust_disclosure_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_proof_01',
			'Product detail (proof-dense)',
			'Child/detail page for a single product with proof density: product hero, value prop, trust band, rating, product CTA, testimonials, guarantee, offer compare, purchase CTA, client logos, why choose, quote CTA, buying process, testimonial, consultation CTA, trust disclosure, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product proof-dense. Trust band, rating, testimonials, guarantee, logos; purchase, quote, consultation, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Proof-heavy; trust and testimonials support conversion without commerce engine.',
				'cta_direction_summary' => 'Product detail, purchase, quote, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Proof-dense; rating, testimonials, guarantee, logos.',
			)
		);
	}

	public static function child_detail_product_cta_intense_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'cta_product_detail_01',
			'fb_offer_compare_01',
			'tp_rating_01',
			'cta_purchase_01',
			'ptf_buying_process_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'tp_guarantee_01',
			'mlp_product_cards_01',
			'cta_compare_next_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_cta_intense_01',
			'Product detail (CTA intensity)',
			'Child/detail page for a single product with high CTA intensity: product hero, value prop, product CTA, offer compare, rating, purchase CTA, buying process, benefit band, quote CTA, guarantee, product cards, compare CTA, testimonial, consultation CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product with CTA intensity. Multiple CTAs spaced by content; product, purchase, quote, compare, consultation, contact.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'CTA-heavy; direct guidance without transactional integration.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'CTA intensity; six CTAs.',
			)
		);
	}

	public static function child_detail_product_catalog_item_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_offer_compare_01',
			'mlp_product_cards_01',
			'cta_product_detail_02',
			'fb_benefit_detail_01',
			'tp_rating_01',
			'ptf_buying_process_01',
			'cta_purchase_01',
			'fb_why_choose_01',
			'mlp_card_grid_01',
			'cta_quote_request_01',
			'tp_guarantee_01',
			'fb_offer_highlight_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_catalog_item_01',
			'Catalog item detail',
			'Child/detail page for a singular catalog entity (e.g. furniture piece, toy product): product hero, offer compare, product cards, product CTA, benefit detail, rating, buying process, purchase CTA, why choose, card grid, quote CTA, guarantee, offer highlight, compare CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single catalog item. Product cards and card grid; purchase, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Catalog-entity style; item-level detail with comparison support.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Catalog item; cards and grid.',
			)
		);
	}

	public static function child_detail_product_equipment_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_benefit_detail_01',
			'fb_differentiator_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'tp_rating_01',
			'fb_value_prop_01',
			'cta_purchase_01',
			'tp_guarantee_01',
			'fb_offer_compare_01',
			'cta_quote_request_01',
			'tp_trust_band_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_equipment_01',
			'Equipment / specification-heavy product detail',
			'Child/detail page for equipment or spec-heavy product (e.g. 1TB WD Laptop Hard Drive): product hero, benefit detail, differentiator, product CTA, buying process, rating, value prop, purchase CTA, guarantee, offer compare, quote CTA, trust band, comparison steps, compare CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Equipment/spec-heavy product. Benefit detail and differentiator; purchase, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Specification-heavy; equipment and hardware style; no inventory or price engine.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Equipment/spec-heavy; benefit detail and differentiator.',
			)
		);
	}

	public static function child_detail_product_buying_guide_01(): array {
		$keys = array(
			'hero_prod_01',
			'ptf_buying_process_01',
			'fb_value_prop_01',
			'cta_product_detail_02',
			'ptf_comparison_steps_01',
			'fb_offer_compare_01',
			'tp_rating_01',
			'cta_purchase_01',
			'fb_why_choose_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'tp_guarantee_01',
			'mlp_product_cards_01',
			'cta_compare_next_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_buying_guide_01',
			'Product detail (buying-guide style)',
			'Child/detail page for a single product with buying-guide flow: product hero, buying process, value prop, product CTA, comparison steps, offer compare, rating, purchase CTA, why choose, benefit band, quote CTA, guarantee, product cards, compare CTA, contact panel, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product buying-guide style. Buying process and comparison steps lead; purchase, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Buying-guide structure; process and comparison support decision.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Buying-guide style; process and steps.',
			)
		);
	}

	public static function child_detail_product_value_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_value_prop_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'fb_benefit_band_01',
			'tp_testimonial_01',
			'fb_offer_highlight_01',
			'cta_product_detail_01',
			'tp_guarantee_01',
			'ptf_buying_process_01',
			'cta_quote_request_01',
			'tp_trust_band_01',
			'fb_why_choose_01',
			'cta_consultation_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_value_01',
			'Product detail (value-led)',
			'Child/detail page for a single product value-led: product hero, value prop, offer compare, purchase CTA, benefit band, testimonial, offer highlight, product CTA, guarantee, buying process, quote CTA, trust band, why choose, consultation CTA, consent, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product value-led. Value prop, offer compare, benefit band, highlight; purchase, product, quote, consultation, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value-first; offer and benefit support conversion.',
				'cta_direction_summary' => 'Purchase, product detail, quote, consultation, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Value-led; offer compare and highlight.',
			)
		);
	}

	public static function child_detail_product_differentiator_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_differentiator_01',
			'fb_value_prop_01',
			'cta_product_detail_01',
			'fb_offer_compare_01',
			'tp_rating_01',
			'ptf_buying_process_01',
			'cta_purchase_01',
			'fb_why_choose_01',
			'tp_guarantee_01',
			'cta_quote_request_01',
			'fb_benefit_band_01',
			'mlp_product_cards_01',
			'cta_compare_next_01',
			'tp_trust_band_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'child_detail_product_differentiator_01',
			'Product detail (differentiator-led)',
			'Child/detail page for a single product with differentiator-led structure: product hero, differentiator, value prop, product CTA, offer compare, rating, buying process, purchase CTA, why choose, guarantee, quote CTA, benefit band, product cards, compare CTA, trust band, contact panel, contact CTA.',
			'offer_page',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Single product differentiator-led. Differentiator and why choose; purchase, quote, compare, contact CTAs.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Differentiator-first; decision-support vs alternatives.',
				'cta_direction_summary' => 'Product detail, purchase, quote, compare, contact; last CTA contact.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array(
				'preview_metadata'      => array( 'synthetic' => true ),
				'differentiation_notes' => 'Differentiator-led; vs-alternatives emphasis.',
			)
		);
	}
}
