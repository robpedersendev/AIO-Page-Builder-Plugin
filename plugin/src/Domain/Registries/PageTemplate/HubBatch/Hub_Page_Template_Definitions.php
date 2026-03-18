<?php
/**
 * Hub page template definitions for Services, Products, Offerings, Directories, Locations (spec §13, §14, §16, Prompt 157).
 * template_category_class = hub: ~10 non-CTA + ≥4 CTA sections, last CTA, no adjacent CTA. Drill-down and category navigation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\HubBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Returns page template definitions for the hub batch (PT-03 scope).
 * template_category_class = hub; template_family = services, products, offerings, directories, locations.
 */
final class Hub_Page_Template_Definitions {

	/** Batch ID for hub pages (template-library-inventory-manifest PT-03). */
	public const BATCH_ID = 'PT-03';

	/** Industry keys for first launch verticals (page-template-industry-affinity-contract; Prompt 364). */
	private const LAUNCH_INDUSTRIES = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

	/**
	 * Returns all hub page template definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			self::hub_services_proof_01(),
			self::hub_services_listing_01(),
			self::hub_services_conversion_01(),
			self::hub_products_comparison_01(),
			self::hub_products_media_01(),
			self::hub_products_value_01(),
			self::hub_offerings_overview_01(),
			self::hub_offerings_compare_01(),
			self::hub_directory_browse_01(),
			self::hub_directory_category_01(),
			self::hub_directory_listing_01(),
			self::hub_locations_overview_01(),
			self::hub_locations_regional_01(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'hub_services_proof_01',
			'hub_services_listing_01',
			'hub_services_conversion_01',
			'hub_products_comparison_01',
			'hub_products_media_01',
			'hub_products_value_01',
			'hub_offerings_overview_01',
			'hub_offerings_compare_01',
			'hub_directory_browse_01',
			'hub_directory_category_01',
			'hub_directory_listing_01',
			'hub_locations_overview_01',
			'hub_locations_regional_01',
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
	 * Base page template shape for hub batch (template_category_class = hub).
	 *
	 * @param string $internal_key
	 * @param string $name
	 * @param string $purpose_summary
	 * @param string $template_family
	 * @param array  $ordered
	 * @param array  $section_requirements
	 * @param array  $one_pager
	 * @param string $endpoint_notes
	 * @param array  $extra
	 * @return array<string, mixed>
	 */
	private static function base_template(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $template_family,
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
			Page_Template_Schema::FIELD_ARCHETYPE        => 'hub_page',
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
			'template_category_class'                    => 'hub',
			'template_family'                            => $template_family,
		);
		if ( ! isset( $extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] ) ) {
			$extra[ Page_Template_Schema::FIELD_INDUSTRY_AFFINITY ] = self::LAUNCH_INDUSTRIES;
		}
		return array_merge( $def, $extra );
	}

	public static function hub_services_proof_01(): array {
		$keys = array(
			'hero_cred_01',
			'tp_trust_band_01',
			'tp_testimonial_01',
			'cta_service_detail_01',
			'fb_service_offering_01',
			'tp_client_logo_01',
			'ptf_service_flow_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'tp_case_teaser_01',
			'mlp_card_grid_01',
			'cta_booking_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_services_proof_01',
			'Services hub (proof-led)',
			'Services hub proof-led: credibility hero, trust band, testimonial, service CTA, service offering, client logos, service flow, consultation CTA, why choose, case teaser, card grid, booking CTA, contact panel, contact CTA. Drill-down to service detail pages.',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services hub with strong proof. Trust and testimonial before first service CTA; offering and logos; flow and consultation CTA; why choose and case; cards and booking CTA; contact panel; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Category-wide value and proof; drill-down CTAs to service detail pages. Semantic headings per section (spec §51.6).',
				'drill_down_intent'     => 'Supports drilling into individual service or offering detail pages via service-detail and consultation CTAs.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_services_listing_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_card_grid_01',
			'fb_service_offering_01',
			'cta_service_detail_02',
			'mlp_listing_01',
			'tp_testimonial_02',
			'ptf_how_it_works_01',
			'cta_directory_nav_01',
			'fb_benefit_band_01',
			'tp_trust_band_01',
			'ptf_steps_01',
			'cta_consultation_02',
			'lpu_contact_panel_01',
			'cta_booking_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_services_listing_01',
			'Services hub (listing-led)',
			'Services hub listing-led: directory hero, card grid, service offering, service CTA, listing, testimonial, how-it-works, directory nav CTA, benefit band, trust band, steps, consultation CTA, contact panel, booking CTA. Category navigation emphasis.',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services hub with listing prominence. Card grid and offering; service CTA; listing and testimonial; how-it-works and directory nav CTA; benefits and trust; steps and consultation CTA; contact panel; booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Listing and category navigation; drill-down via service and directory CTAs.',
				'drill_down_intent'     => 'Cards and listing support drill-down; directory nav CTA for category browsing.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_services_conversion_01(): array {
		$keys = array(
			'hero_conv_01',
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
			'cta_booking_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_services_conversion_01',
			'Services hub (conversion-led)',
			'Services hub conversion-led: conversion hero, value prop, service flow, consultation CTA, benefit band, testimonial, card grid, service CTA, expectations, guarantee, differentiator, quote CTA, contact panel, booking CTA. Higher CTA intensity.',
			'services',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Services hub conversion-led. Value prop and flow; consultation CTA; benefits and testimonial; cards and service CTA; expectations and guarantee; differentiator and quote CTA; contact panel; booking CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Conversion posture with four CTAs; drill-down and booking intent.',
				'drill_down_intent'     => 'Service-detail and consultation/quote/booking CTAs support category-to-detail conversion.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_products_comparison_01(): array {
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
			'lpu_consent_note_01',
			'cta_product_detail_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_products_comparison_01',
			'Products hub (comparison)',
			'Products hub comparison-led: product hero, offer compare, comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, offer highlight, product cards, purchase CTA, consent note, product CTA. Drill-down to product detail.',
			'products',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Products hub with comparison depth. Offer compare and comparison steps; compare CTA; comparison cards and differentiator; rating and product CTA; buying process and highlight; product cards and purchase CTA; consent; product CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Comparison depth; category navigation and product-detail drill-down.',
				'drill_down_intent'     => 'Product-detail and compare CTAs support drilling into product or comparison flows.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_products_media_01(): array {
		$keys = array(
			'hero_media_01',
			'mlp_product_cards_01',
			'mlp_gallery_01',
			'cta_product_detail_01',
			'fb_benefit_detail_01',
			'tp_testimonial_02',
			'mlp_media_band_01',
			'cta_purchase_01',
			'fb_product_spec_01',
			'ptf_buying_process_01',
			'tp_guarantee_01',
			'cta_compare_next_01',
			'lpu_contact_panel_01',
			'cta_purchase_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_products_media_01',
			'Products hub (media-led)',
			'Products hub media-rich: media hero, product cards, gallery, product CTA, benefit detail, testimonial, media band, purchase CTA, product spec, buying process, guarantee, compare CTA, contact panel, purchase CTA. Media richness emphasis.',
			'products',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Products hub media-led. Product cards and gallery; product CTA; benefit detail and testimonial; media band and purchase CTA; spec and buying process; guarantee and compare CTA; contact panel; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Media richness; product and purchase drill-down.',
				'drill_down_intent'     => 'Product-detail and purchase CTAs; gallery and cards support category browsing.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_products_value_01(): array {
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
			'hub_products_value_01',
			'Products hub (value-led)',
			'Products hub value-led: credibility hero, why choose, value prop, product CTA, listing, testimonial, benefit band, quote CTA, buying process, client logos, offer highlight, purchase CTA, trust disclosure, contact CTA. Value and proof balance.',
			'products',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Products hub value-led. Why choose and value prop; product CTA; listing and testimonial; benefit band and quote CTA; buying process and logos; offer highlight and purchase CTA; trust disclosure; contact CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Value and proof; product and quote/purchase/contact CTAs.',
				'drill_down_intent'     => 'Product-detail, quote, purchase, contact CTAs support category-to-detail and conversion.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_offerings_overview_01(): array {
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
			'hub_offerings_overview_01',
			'Offerings hub (overview)',
			'Offerings hub overview: product hero, package summary, offer compare, purchase CTA, product cards, testimonial, benefit detail, product CTA, buying process, guarantee, consent note, quote CTA, card grid, purchase CTA. Category-wide offerings.',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings hub overview. Package summary and offer compare; purchase CTA; product cards and testimonial; benefit detail and product CTA; buying process and guarantee; consent and quote CTA; card grid; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Category-wide offerings; purchase and product-detail drill-down.',
				'drill_down_intent'     => 'Product-detail, quote, purchase CTAs support drill-down into offering detail.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_offerings_compare_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_offer_compare_01',
			'ptf_comparison_steps_01',
			'cta_compare_next_01',
			'mlp_comparison_cards_01',
			'fb_differentiator_01',
			'tp_rating_01',
			'cta_product_detail_02',
			'ptf_buying_process_01',
			'fb_offer_highlight_01',
			'mlp_listing_01',
			'cta_quote_request_02',
			'lpu_contact_panel_01',
			'cta_purchase_02',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_offerings_compare_01',
			'Offerings hub (compare)',
			'Offerings hub compare-led: compact hero, offer compare, comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, offer highlight, listing, quote CTA, contact panel, purchase CTA. Comparison depth.',
			'offerings',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Offerings hub compare-led. Offer compare and comparison steps; compare CTA; comparison cards and differentiator; rating and product CTA; buying process and highlight; listing and quote CTA; contact panel; purchase CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Comparison depth; category navigation and product/quote/purchase CTAs.',
				'drill_down_intent'     => 'Compare-next and product-detail CTAs support comparison and detail drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_directory_browse_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_card_grid_01',
			'mlp_listing_01',
			'cta_directory_nav_01',
			'fb_directory_value_01',
			'mlp_directory_entry_01',
			'ptf_how_it_works_01',
			'cta_compare_next_01',
			'tp_reassurance_01',
			'mlp_related_content_01',
			'fb_feature_compact_01',
			'cta_contact_01',
			'lpu_contact_panel_01',
			'cta_directory_nav_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_directory_browse_01',
			'Directory hub (browse-led)',
			'Directory hub browse-led: directory hero, card grid, listing, directory nav CTA, directory value, directory entry, how-it-works, compare CTA, reassurance, related content, feature compact, contact CTA, contact panel, directory nav CTA. Browse and category navigation.',
			'directories',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory hub browse-led. Card grid and listing; directory nav CTA; directory value and entry; how-it-works and compare CTA; reassurance and related content; feature compact and contact CTA; contact panel; directory nav CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Browse and category navigation; directory nav and contact CTAs. Semantic headings for list/grid (spec §51.6).',
				'drill_down_intent'     => 'Directory nav CTAs support drilling into categories or entries; listing and cards support browse.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_directory_category_01(): array {
		$keys = array(
			'hero_compact_01',
			'fb_directory_value_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'mlp_listing_01',
			'tp_client_logo_01',
			'ptf_steps_01',
			'cta_service_detail_01',
			'mlp_related_content_01',
			'fb_benefit_band_01',
			'lpu_contact_panel_01',
			'cta_contact_02',
			'tp_reassurance_01',
			'cta_directory_nav_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_directory_category_01',
			'Directory hub (category-led)',
			'Directory hub category-led: compact hero, directory value, card grid, directory nav CTA, listing, client logos, steps, service CTA, related content, benefit band, contact panel, contact CTA, reassurance, directory nav CTA. Category structure emphasis.',
			'directories',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory hub category-led. Directory value and card grid; directory nav CTA; listing and logos; steps and service CTA; related content and benefit band; contact panel and contact CTA; reassurance; directory nav CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Category structure; directory nav and service/contact CTAs.',
				'drill_down_intent'     => 'Directory nav and service-detail CTAs support category and entry drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_directory_listing_01(): array {
		$keys = array(
			'hero_dir_01',
			'mlp_listing_01',
			'mlp_card_grid_01',
			'cta_directory_nav_01',
			'fb_feature_grid_01',
			'mlp_detail_spec_01',
			'ptf_how_it_works_01',
			'cta_product_detail_01',
			'mlp_related_content_01',
			'tp_reassurance_01',
			'lpu_contact_panel_01',
			'cta_contact_01',
			'fb_directory_value_01',
			'cta_compare_next_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_directory_listing_01',
			'Directory hub (listing-led)',
			'Directory hub listing-led: directory hero, listing, card grid, directory nav CTA, feature grid, detail spec, how-it-works, product CTA, related content, reassurance, contact panel, contact CTA, directory value, compare CTA. Listing prominence.',
			'directories',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Directory hub listing-led. Listing and card grid; directory nav CTA; feature grid and detail spec; how-it-works and product CTA; related content and reassurance; contact panel and contact CTA; directory value; compare CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Listing prominence; directory nav, product, contact, compare CTAs.',
				'drill_down_intent'     => 'Listing and cards support search/browse; directory nav and product CTAs for drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_locations_overview_01(): array {
		$keys = array(
			'hero_local_01',
			'mlp_location_info_01',
			'mlp_place_highlight_01',
			'cta_local_action_01',
			'fb_local_value_01',
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
			'hub_locations_overview_01',
			'Locations hub (overview)',
			'Locations hub overview: local hero, location info, place highlight, local CTA, local value, card grid, trust band, contact CTA, contact detail, expectations, contact panel, local CTA, listing, directory nav CTA. Locations-overview style.',
			'locations',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Locations hub overview. Location info and place highlight; local CTA; local value and card grid; trust band and contact CTA; contact detail and expectations; contact panel and local CTA; listing; directory nav CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Locations-overview; local and contact and directory CTAs. Semantic headings (spec §51.6).',
				'drill_down_intent'     => 'Local action and directory nav CTAs support drill-down to location or region pages.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}

	public static function hub_locations_regional_01(): array {
		$keys = array(
			'hero_local_01',
			'fb_local_value_01',
			'mlp_location_info_01',
			'cta_local_action_01',
			'mlp_place_highlight_01',
			'mlp_card_grid_01',
			'tp_reassurance_01',
			'cta_local_action_02',
			'lpu_contact_detail_01',
			'lpu_contact_panel_01',
			'ptf_expectations_01',
			'cta_contact_02',
			'mlp_listing_01',
			'cta_directory_nav_01',
		);
		$r    = self::ordered_and_requirements( $keys );
		return self::base_template(
			'hub_locations_regional_01',
			'Locations hub (regional)',
			'Locations hub regional: local hero, local value, location info, local CTA, place highlight, card grid, reassurance, local CTA, contact detail, contact panel, expectations, contact CTA, listing, directory nav CTA. Regional emphasis.',
			'locations',
			$r['ordered'],
			$r['requirements'],
			array(
				'page_purpose_summary'  => 'Locations hub regional. Local value and location info; local CTA; place highlight and card grid; reassurance and local CTA; contact detail and panel; expectations and contact CTA; listing; directory nav CTA.',
				'section_helper_order'  => 'same_as_template',
				'page_flow_explanation' => 'Regional emphasis; local and contact and directory CTAs for drill-down.',
				'drill_down_intent'     => 'Local action, contact, directory nav CTAs support regional and location drill-down.',
			),
			'Requires section library (hero, trust, fb, ptf, mlp, lpu, CTA batches).',
			array( 'preview_metadata' => array( 'synthetic' => true ) )
		);
	}
}
