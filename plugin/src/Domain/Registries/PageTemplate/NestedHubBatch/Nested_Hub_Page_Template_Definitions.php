<?php
/**
 * Nested hub (sub-hub) page template definitions for subcategories beneath hub pages (spec §13, §14.3, §16, §17.7, Prompt 159).
 * template_category_class = nested_hub; archetype = sub_hub_page. ~10 non-CTA + ≥4 CTA sections, last CTA, no adjacent CTA.
 * Supports service, product, directory, and geographic subcategory families with parent-family compatibility metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\NestedHubBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the nested hub batch (PT-06 scope).
 * Subcategory pages beneath hubs: Haircuts under Services, Laptop Hard Drives under Computer Parts,
 * Gel Manicures under Manicures, city subregions under location hubs. Category specificity with drill-down capacity.
 */
final class Nested_Hub_Page_Template_Definitions {

	/** Batch ID for nested hub pages (template-library-inventory-manifest PT-06). */
	public const BATCH_ID = 'PT-06';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Allowed parent hub template_family values for parent_family_compatibility (spec §13.8).
	 *
	 * @var list<string>
	 */
	public const ALLOWED_PARENT_FAMILIES = array(
		'services',
		'products',
		'offerings',
		'directories',
		'locations',
	);

	/**
	 * Returns all nested hub page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::nested_hub_services_intro_01(),
			self::nested_hub_services_intro_02(),
			self::nested_hub_services_listing_01(),
			self::nested_hub_services_comparison_01(),
			self::nested_hub_services_educational_01(),
			self::nested_hub_products_intro_01(),
			self::nested_hub_products_comparison_01(),
			self::nested_hub_products_filtered_01(),
			self::nested_hub_offerings_overview_01(),
			self::nested_hub_offerings_educational_01(),
			self::nested_hub_directories_filtered_01(),
			self::nested_hub_directories_category_01(),
			self::nested_hub_locations_subarea_01(),
			self::nested_hub_locations_subarea_02(),
			self::nested_hub_locations_subregion_01(),
			self::nested_hub_services_specialized_01(),
			self::nested_hub_products_value_01(),
			self::nested_hub_directories_listing_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'nested_hub_services_intro_01',
			'nested_hub_services_intro_02',
			'nested_hub_services_listing_01',
			'nested_hub_services_comparison_01',
			'nested_hub_services_educational_01',
			'nested_hub_products_intro_01',
			'nested_hub_products_comparison_01',
			'nested_hub_products_filtered_01',
			'nested_hub_offerings_overview_01',
			'nested_hub_offerings_educational_01',
			'nested_hub_directories_filtered_01',
			'nested_hub_directories_category_01',
			'nested_hub_locations_subarea_01',
			'nested_hub_locations_subarea_02',
			'nested_hub_locations_subregion_01',
			'nested_hub_services_specialized_01',
			'nested_hub_products_value_01',
			'nested_hub_directories_listing_01',
		);
	}

	/**
	 * Builds ordered_sections and section_requirements from a list of section keys.
	 *
	 * @param list<string> $section_keys Section internal keys in order (no adjacent CTA; last must be CTA).
	 * @return array{ ordered: list<array<string, mixed>>, requirements: array<string, array{required: bool}> }
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
	 * Base page template shape for nested hub batch (template_category_class = nested_hub, archetype = sub_hub_page).
	 *
	 * @param string       $internal_key
	 * @param string       $name
	 * @param string       $purpose_summary
	 * @param string       $template_family
	 * @param list<string> $parent_family_compatibility
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
			Page_Template_Schema::FIELD_ARCHETYPE        => 'sub_hub_page',
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
			'template_category_class'                    => 'nested_hub',
			'template_family'                            => $template_family,
			'parent_family_compatibility'                => $parent_family_compatibility,
			'hierarchy_hints'                            => array(
				'common_parent_page_types' => 'hub',
				'hierarchy_role'           => 'nested_hub',
			),
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function nested_hub_services_intro_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'cta_service_detail_01',
			'mlp_card_grid_01',
			'ptf_how_it_works_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_testimonial_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
			'mlp_listing_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_intro_01',
			'Service subcategory (intro)',
			'Nested hub for service subcategory intro: credibility hero, service offering, trust band, service CTA, card grid, how-it-works, consultation CTA, why choose, testimonial, contact panel, booking CTA, listing, contact CTA. Sits under Services hub.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory intro. Offering and trust; service CTA; cards and how-it-works; consultation CTA; why choose and testimonial; contact panel; booking CTA; listing; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Subcategory-specific intro; drill-down to detail or sibling subcategories. Semantic headings (spec §51.6).',
				'drill_down_intent'     => 'Sits beneath Services hub; supports drill-down to child/detail service pages. Categorical specificity with continued comparison capacity.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_services_intro_02(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_listing_01',
			'fb_service_offering_01',
			'cta_service_detail_02',
			'tp_testimonial_01',
			'ptf_service_flow_01',
			'cta_consultation_01',
			'mlp_card_grid_01',
			'fb_why_choose_01',
			'tp_trust_band_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_intro_02',
			'Service subcategory (intro, listing-led)',
			'Nested hub service subcategory intro listing-led: directory hero, listing, service offering, service CTA, testimonial, service flow, consultation CTA, card grid, why choose, trust band, booking CTA, contact panel, contact CTA.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory intro listing-led. Listing and offering; service CTA; testimonial and flow; consultation CTA; cards and why choose; trust band and booking CTA; contact panel; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Listing-led subcategory intro; drill-down and category navigation.',
				'drill_down_intent'     => 'Beneath Services hub; listing supports filtered subcategory browsing and detail drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_services_listing_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_card_grid_01',
			'mlp_listing_01',
			'cta_service_detail_01',
			'fb_service_offering_01',
			'tp_testimonial_02',
			'ptf_how_it_works_01',
			'cta_directory_nav_01',
			'fb_benefit_band_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_listing_01',
			'Service subcategory (filtered listing)',
			'Nested hub filtered-listing variant: directory hero, card grid, listing, service CTA, offering, testimonial, how-it-works, directory nav CTA, benefit band, trust band, consultation CTA, contact panel, booking CTA.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory filtered listing. Cards and listing; service CTA; offering and testimonial; how-it-works and directory nav CTA; benefit and trust; consultation CTA; contact panel; booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Filtered-listing emphasis; directory nav supports sibling subcategory navigation.',
				'drill_down_intent'     => 'Subcategory listing beneath Services hub; drill-down to detail or sibling subcategories.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_services_comparison_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_value_prop_01',
			'ptf_service_flow_01',
			'cta_consultation_01',
			'fb_benefit_band_01',
			'tp_testimonial_01',
			'mlp_card_grid_01',
			'cta_service_detail_01',
			'ptf_expectations_01',
			'tp_guarantee_01',
			'fb_differentiator_01',
			'cta_quote_request_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_comparison_01',
			'Service subcategory (comparison)',
			'Nested hub subcategory comparison: credibility hero, value prop, service flow, consultation CTA, benefit band, testimonial, card grid, service CTA, expectations, guarantee, differentiator, quote CTA, contact panel, booking CTA.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory comparison. Value prop and flow; consultation CTA; benefits and testimonial; cards and service CTA; expectations and guarantee; differentiator and quote CTA; contact panel; booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Subcategory comparison; continued drill-down or comparison capacity without collapsing to singular detail.',
				'drill_down_intent'     => 'Comparison within subcategory; supports drill-down to detail pages.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_services_educational_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'ptf_how_it_works_01',
			'cta_service_detail_01',
			'tp_testimonial_01',
			'fb_why_choose_01',
			'ptf_expectations_01',
			'cta_consultation_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'lpu_contact_panel_01',
			'cta_booking_01',
			'mlp_listing_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_educational_01',
			'Service subcategory (educational)',
			'Nested hub educational subcategory: credibility hero, offering, how-it-works, service CTA, testimonial, why choose, expectations, consultation CTA, card grid, trust band, contact panel, booking CTA, listing, contact CTA.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory educational. Offering and how-it-works; service CTA; testimonial and why choose; expectations and consultation CTA; cards and trust; contact panel; booking CTA; listing; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational vs conversion balance; subcategory-specific learning then drill-down.',
				'drill_down_intent'     => 'Educational emphasis beneath Services hub; drill-down to detail or booking.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_services_specialized_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_service_offering_01',
			'tp_trust_band_01',
			'cta_service_detail_02',
			'ptf_service_flow_01',
			'fb_why_choose_01',
			'mlp_card_grid_01',
			'cta_consultation_01',
			'tp_testimonial_02',
			'ptf_expectations_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'mlp_listing_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_services_specialized_01',
			'Service subcategory (specialized offering)',
			'Nested hub specialized offering drill-down: credibility hero, offering, trust band, service CTA, service flow, why choose, card grid, consultation CTA, testimonial, expectations, booking CTA, contact panel, listing, contact CTA.',
			'services',
			array( 'services' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Service subcategory specialized offering. Offering and trust; service CTA; flow and why choose; cards and consultation CTA; testimonial and expectations; booking CTA; contact panel; listing; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Specialized offering subcategory; drill-down to detail without becoming singular detail page.',
				'drill_down_intent'     => 'Specialized offering (e.g. Gel Manicures under Manicures); continued drill-down capacity.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_products_intro_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_offer_compare_01',
			'mlp_product_cards_01',
			'cta_product_detail_01',
			'tp_rating_01',
			'ptf_buying_process_01',
			'cta_purchase_01',
			'fb_benefit_band_01',
			'tp_testimonial_02',
			'mlp_card_grid_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_products_intro_01',
			'Product subcategory (intro)',
			'Nested hub product subcategory intro: product hero, offer compare, product cards, product CTA, rating, buying process, purchase CTA, benefit band, testimonial, card grid, compare CTA, consent note, contact CTA.',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Product subcategory intro. Offer compare and product cards; product CTA; rating and buying process; purchase CTA; benefit band and testimonial; card grid and compare CTA; consent; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Product subcategory (e.g. Laptop Hard Drives under Computer Parts); drill-down to product detail.',
				'drill_down_intent'     => 'Beneath Products hub; subcategory specificity with product-detail and compare drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_products_comparison_01(): array {
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
			'fb_offer_highlight_01',
			'mlp_product_cards_01',
			'cta_purchase_01',
			'lpu_contact_panel_01',
			'cta_product_detail_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_products_comparison_01',
			'Product subcategory (comparison)',
			'Nested hub product subcategory comparison: product hero, offer compare, comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, offer highlight, product cards, purchase CTA, contact panel, product CTA.',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Product subcategory comparison. Offer compare and comparison steps; compare CTA; comparison cards and differentiator; rating and product CTA; buying process and highlight; product cards and purchase CTA; contact panel; product CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Subcategory comparison; continued comparison capacity and product-detail drill-down.',
				'drill_down_intent'     => 'Comparison within product subcategory; drill-down to product detail.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_products_filtered_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_listing_01',
			'mlp_product_cards_01',
			'cta_product_detail_01',
			'fb_offer_compare_01',
			'tp_rating_01',
			'ptf_buying_process_01',
			'cta_purchase_01',
			'fb_benefit_band_01',
			'mlp_card_grid_01',
			'cta_compare_next_01',
			'lpu_consent_note_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_products_filtered_01',
			'Product subcategory (filtered)',
			'Nested hub product subcategory filtered: directory hero, listing, product cards, product CTA, offer compare, rating, buying process, purchase CTA, benefit band, card grid, compare CTA, consent, contact CTA.',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Product subcategory filtered. Listing and product cards; product CTA; offer compare and rating; buying process and purchase CTA; benefit band and card grid; compare CTA; consent; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Filtered product subcategory listing; drill-down to product detail.',
				'drill_down_intent'     => 'Filtered-listing emphasis beneath Products hub; subcategory specificity.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_products_value_01(): array {
		$keys = array(
			'hero_cred_01',
			'fb_why_choose_01',
			'fb_value_prop_01',
			'cta_product_detail_01',
			'mlp_listing_01',
			'tp_testimonial_01',
			'fb_benefit_band_01',
			'cta_quote_request_01',
			'ptf_buying_process_01',
			'tp_client_logo_01',
			'fb_offer_highlight_01',
			'cta_purchase_01',
			'lpu_trust_disclosure_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_products_value_01',
			'Product subcategory (value-led)',
			'Nested hub product subcategory value-led: credibility hero, why choose, value prop, product CTA, listing, testimonial, benefit band, quote CTA, buying process, client logos, offer highlight, purchase CTA, trust disclosure, contact CTA.',
			'products',
			array( 'products' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Product subcategory value-led. Why choose and value prop; product CTA; listing and testimonial; benefit band and quote CTA; buying process and logos; offer highlight and purchase CTA; trust disclosure; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value and proof balance; product and quote/purchase/contact drill-down.',
				'drill_down_intent'     => 'Value-led subcategory beneath Products hub; drill-down to detail.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_offerings_overview_01(): array {
		$keys = array(
			'hero_prod_01',
			'fb_package_summary_01',
			'fb_offer_compare_01',
			'cta_purchase_01',
			'mlp_product_cards_01',
			'tp_testimonial_02',
			'fb_benefit_detail_01',
			'cta_product_detail_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'lpu_consent_note_01',
			'cta_quote_request_01',
			'mlp_card_grid_01',
			'cta_purchase_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_offerings_overview_01',
			'Offerings subcategory (overview)',
			'Nested hub offerings subcategory overview: product hero, package summary, offer compare, purchase CTA, product cards, testimonial, benefit detail, product CTA, buying process, guarantee, consent, quote CTA, card grid, purchase CTA.',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings subcategory overview. Package summary and offer compare; purchase CTA; product cards and testimonial; benefit detail and product CTA; buying process and guarantee; consent and quote CTA; card grid; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Offerings subcategory beneath Offerings hub; drill-down to offering detail.',
				'drill_down_intent'     => 'Subcategory-wide offerings; purchase and product-detail drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_offerings_educational_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'fb_differentiator_01',
			'tp_rating_01',
			'mlp_listing_01',
			'cta_product_detail_02',
			'ptf_buying_process_01',
			'fb_offer_highlight_01',
			'cta_quote_request_02',
			'lpu_contact_panel_01',
			'mlp_card_grid_01',
			'cta_purchase_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_offerings_educational_01',
			'Offerings subcategory (educational)',
			'Nested hub offerings subcategory educational: compact hero, offer compare, comparison steps, compare CTA, differentiator, rating, listing, product CTA, buying process, offer highlight, quote CTA, contact panel, card grid, purchase CTA.',
			'offerings',
			array( 'offerings' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings subcategory educational. Offer compare and comparison steps; compare CTA; differentiator and rating; listing and product CTA; buying process and highlight; quote CTA; contact panel; card grid; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Educational subcategory; comparison and quote/purchase drill-down.',
				'drill_down_intent'     => 'Educational emphasis beneath Offerings hub; drill-down to detail.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_directories_filtered_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_listing_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'fb_benefit_band_01',
			'tp_testimonial_02',
			'ptf_how_it_works_01',
			'cta_service_detail_01',
			'fb_why_choose_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_directories_filtered_01',
			'Directory subcategory (filtered)',
			'Nested hub directory subcategory filtered: directory hero, listing, card grid, directory nav CTA, benefit band, testimonial, how-it-works, service CTA, why choose, trust band, consultation CTA, contact panel, contact CTA.',
			'directories',
			array( 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory subcategory filtered. Listing and card grid; directory nav CTA; benefit band and testimonial; how-it-works and service CTA; why choose and trust band; consultation CTA; contact panel; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Filtered directory subcategory beneath Directories hub; category navigation.',
				'drill_down_intent'     => 'Subcategory directory listing; drill-down to detail or sibling categories.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_directories_category_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_card_grid_01',
			'fb_service_offering_01',
			'cta_directory_nav_01',
			'mlp_listing_01',
			'tp_testimonial_01',
			'ptf_how_it_works_01',
			'cta_service_detail_02',
			'fb_benefit_band_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'ptf_expectations_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_directories_category_01',
			'Directory subcategory (category)',
			'Nested hub directory subcategory category: directory hero, card grid, offering, directory nav CTA, listing, testimonial, how-it-works, service CTA, benefit band, trust band, consultation CTA, contact panel, expectations, contact CTA.',
			'directories',
			array( 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory subcategory category. Card grid and offering; directory nav CTA; listing and testimonial; how-it-works and service CTA; benefit and trust; consultation CTA; contact panel; expectations; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Category-specific directory subcategory; drill-down and sibling navigation.',
				'drill_down_intent'     => 'Category intro beneath Directories hub; continued drill-down capacity.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_directories_listing_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_listing_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'fb_service_offering_01',
			'tp_testimonial_02',
			'ptf_how_it_works_01',
			'cta_service_detail_01',
			'fb_benefit_band_01',
			'tp_trust_band_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_directories_listing_01',
			'Directory subcategory (listing)',
			'Nested hub directory subcategory listing-led: directory hero, listing, card grid, directory nav CTA, offering, testimonial, how-it-works, service CTA, benefit band, trust band, consultation CTA, contact panel, contact CTA.',
			'directories',
			array( 'directories' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory subcategory listing. Listing and card grid; directory nav CTA; offering and testimonial; how-it-works and service CTA; benefit and trust; consultation CTA; contact panel; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Listing-led directory subcategory beneath Directories hub; drill-down and category nav.',
				'drill_down_intent'     => 'Subcategory listing; drill-down to detail or sibling categories.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_locations_subarea_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'lpu_contact_panel_01',
			'cta_local_action_02',
			'mlp_listing_01',
			'cta_directory_nav_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_locations_subarea_01',
			'Location subarea (local)',
			'Nested hub local subarea beneath location hub: local hero, local value, location info, local CTA, place highlight, card grid, trust band, contact CTA, contact detail, expectations, contact panel, local CTA, listing, directory nav CTA.',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Location subarea local. Local value and location info; local CTA; place highlight and card grid; trust band and contact CTA; contact detail and expectations; contact panel and local CTA; listing; directory nav CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Local subarea (e.g. city subregion beneath regional hub); drill-down to child locations.',
				'drill_down_intent'     => 'Sits beneath location/geographic hub; supports drill-down to neighborhoods, campuses, or detail location pages. Synthetic preview only.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_locations_subarea_02(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'tp_reassurance_01',
			'fb_local_value_01',
			'mlp_listing_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'mlp_card_grid_01',
			'tp_trust_band_01',
			'cta_contact_02',
			'lpu_contact_panel_01',
			'cta_local_action_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_locations_subarea_02',
			'Location subarea (listing-led)',
			'Nested hub location subarea listing-led: local hero, location info, place highlight, local CTA, reassurance, local value, listing, directory nav CTA, contact detail, card grid, trust band, contact CTA, contact panel, local CTA.',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Location subarea listing-led. Location info and place highlight; local CTA; reassurance and local value; listing and directory nav CTA; contact detail and card grid; trust band and contact CTA; contact panel; local CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Listing-led subarea beneath location hub; area-specific listing and drill-down.',
				'drill_down_intent'     => 'Local subarea with listing density; drill-down to child locations or sibling subareas.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function nested_hub_locations_subregion_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_directory_nav_01',
			'lpu_contact_detail_01',
			'ptf_expectations_01',
			'tp_trust_band_01',
			'cta_contact_01',
			'mlp_listing_01',
			'cta_local_action_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'nested_hub_locations_subregion_01',
			'Location subregion',
			'Nested hub location subregion: local hero, local value, location info, local CTA, place highlight, card grid, reassurance, directory nav CTA, contact detail, expectations, trust band, contact CTA, listing, local CTA.',
			'locations',
			array( 'locations' ),
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Location subregion. Local value and location info; local CTA; place highlight and card grid; reassurance and directory nav CTA; contact detail and expectations; trust band and contact CTA; listing; local CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Subregion beneath broader location hub (e.g. city subregions); coverage navigation.',
				'drill_down_intent'     => 'City subregions or geographic subregions beneath hub; drill-down to neighborhoods or detail pages.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}
}
