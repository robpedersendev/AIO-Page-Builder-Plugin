<?php
/**
 * Hub and nested hub variant expansion super-batch (spec §13, §14.3, §16, Prompt 165).
 * Expands PT-03, PT-04, PT-06 with materially distinct variants: listing strategy, comparison depth,
 * local relevance, proof density, drill-down emphasis. ~10 non-CTA + ≥4 CTA, last CTA, no adjacent CTA. Synthetic preview only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\HubNestedHubVariantExpansionBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Hub and nested hub variant expansion (PT-12). Adds variants to PT-03, PT-04, PT-06 families.
 * Hub: template_category_class = hub, hierarchy_role = hub. Nested: template_category_class = nested_hub, hierarchy_role = nested_hub.
 */
final class Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions {

	/** Batch ID for hub/nested hub variant expansion (template-library-inventory-manifest PT-12). */
	public const BATCH_ID = 'PT-12';

	/**
	 * Returns all hub and nested hub variant definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			// Hub variants (services, products, offerings, directories, locations).
			self::hub_services_proof_02(),
			self::hub_services_listing_02(),
			self::hub_services_educational_01(),
			self::hub_products_comparison_02(),
			self::hub_products_value_02(),
			self::hub_offerings_overview_02(),
			self::hub_offerings_compare_02(),
			self::hub_directory_browse_02(),
			self::hub_directory_listing_02(),
			self::hub_locations_overview_02(),
			self::hub_locations_regional_02(),
			// Geographic hub variants.
			self::hub_geo_service_area_03(),
			self::hub_geo_regional_03(),
			self::hub_geo_city_directory_03(),
			self::hub_geo_location_overview_03(),
			self::hub_geo_coverage_listing_03(),
			// Nested hub variants.
			self::nested_hub_services_intro_03(),
			self::nested_hub_services_listing_02(),
			self::nested_hub_services_educational_02(),
			self::nested_hub_services_proof_01(),
			self::nested_hub_products_intro_02(),
			self::nested_hub_products_comparison_02(),
			self::nested_hub_products_value_02(),
			self::nested_hub_offerings_overview_02(),
			self::nested_hub_offerings_compare_01(),
			self::nested_hub_directories_filtered_02(),
			self::nested_hub_directories_category_02(),
			self::nested_hub_directories_listing_02(),
			self::nested_hub_locations_subarea_03(),
			self::nested_hub_locations_subregion_02(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'hub_services_proof_02', 'hub_services_listing_02', 'hub_services_educational_01',
			'hub_products_comparison_02', 'hub_products_value_02', 'hub_offerings_overview_02', 'hub_offerings_compare_02',
			'hub_directory_browse_02', 'hub_directory_listing_02', 'hub_locations_overview_02', 'hub_locations_regional_02',
			'hub_geo_service_area_03', 'hub_geo_regional_03', 'hub_geo_city_directory_03', 'hub_geo_location_overview_03', 'hub_geo_coverage_listing_03',
			'nested_hub_services_intro_03', 'nested_hub_services_listing_02', 'nested_hub_services_educational_02', 'nested_hub_services_proof_01',
			'nested_hub_products_intro_02', 'nested_hub_products_comparison_02', 'nested_hub_products_value_02',
			'nested_hub_offerings_overview_02', 'nested_hub_offerings_compare_01',
			'nested_hub_directories_filtered_02', 'nested_hub_directories_category_02', 'nested_hub_directories_listing_02',
			'nested_hub_locations_subarea_03', 'nested_hub_locations_subregion_02',
		);
	}

	private static function ordered_and_requirements( array $section_keys ): array {
		$ordered      = array();
		$requirements = array();
		foreach ( $section_keys as $pos => $key ) {
			$ordered[] = array(
				Page_Template_Schema::SECTION_ITEM_KEY      => $key,
				Page_Template_Schema::SECTION_ITEM_POSITION => $pos,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			);
			$requirements[ $key ] = array( 'required' => true );
		}
		return array( 'ordered' => $ordered, 'requirements' => $requirements );
	}

	private static function base_hub(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $template_family,
		array $ordered,
		array $section_requirements,
		array $one_pager,
		string $endpoint_notes,
		string $differentiation_notes
	): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY             => $internal_key,
			Page_Template_Schema::FIELD_NAME                     => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                 => 'hub_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS          => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS      => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY             => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                => $one_pager,
			Page_Template_Schema::FIELD_VERSION                  => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                   => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES  => $endpoint_notes,
			'template_category_class'                           => 'hub',
			'template_family'                                    => $template_family,
			'preview_metadata'                                  => array( 'synthetic' => true ),
			'differentiation_notes'                             => $differentiation_notes,
			'variation_family'                                  => $template_family,
			'hierarchy_hints'                                   => array( 'hierarchy_role' => 'hub', 'common_parent_page_types' => '' ),
		);
	}

	private static function base_nested(
		string $internal_key,
		string $name,
		string $purpose_summary,
		string $template_family,
		array $parent_family_compatibility,
		array $ordered,
		array $section_requirements,
		array $one_pager,
		string $endpoint_notes,
		string $differentiation_notes
	): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY             => $internal_key,
			Page_Template_Schema::FIELD_NAME                     => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                 => 'sub_hub_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS          => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS      => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY             => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                => $one_pager,
			Page_Template_Schema::FIELD_VERSION                  => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                   => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES  => $endpoint_notes,
			'template_category_class'                           => 'nested_hub',
			'template_family'                                    => $template_family,
			'parent_family_compatibility'                       => $parent_family_compatibility,
			'preview_metadata'                                  => array( 'synthetic' => true ),
			'differentiation_notes'                             => $differentiation_notes,
			'variation_family'                                  => $template_family,
			'hierarchy_hints'                                   => array( 'hierarchy_role' => 'nested_hub', 'common_parent_page_types' => 'hub' ),
		);
	}

	// ---------- Hub variants ----------

	public static function hub_services_proof_02(): array {
		$keys = array( 'hero_cred_01', 'tp_testimonial_01', 'tp_client_logo_01', 'cta_service_detail_01', 'fb_service_offering_01', 'tp_trust_band_01', 'ptf_service_flow_01', 'cta_consultation_01', 'tp_case_teaser_01', 'fb_why_choose_01', 'mlp_card_grid_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_services_proof_02', 'Services hub (proof v2)', 'Services hub proof variant: cred hero, testimonial and logos before first CTA, offering, trust band, flow, consultation CTA, case teaser, why choose, cards, booking CTA, panel, contact CTA.', 'services', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Services hub proof v2. Triple proof before first CTA; flow and consultation; case and why choose; booking and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Proof cluster first; different CTA spacing than hub_services_proof_01.', 'cta_direction_summary' => 'Service detail, consultation, booking, contact.' ),
			'Requires section library.', 'Testimonial and logos before first CTA; case teaser mid-page; proof density differs from PT-03 proof_01.' );
	}

	public static function hub_services_listing_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_listing_01', 'mlp_card_grid_01', 'cta_service_detail_02', 'fb_service_offering_01', 'ptf_how_it_works_01', 'tp_testimonial_02', 'cta_directory_nav_01', 'fb_benefit_band_01', 'ptf_steps_01', 'tp_trust_band_01', 'cta_consultation_02', 'lpu_contact_panel_01', 'cta_booking_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_services_listing_02', 'Services hub (listing v2)', 'Services hub listing variant: directory hero, listing first then cards, service CTA, offering, how-it-works, testimonial, directory nav CTA, benefit band, steps, trust band, consultation CTA, panel, booking CTA.', 'services', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Services hub listing v2. Listing before card grid; directory nav CTA mid-page; steps and trust before consultation CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Listing-first; directory nav for category browsing.', 'cta_direction_summary' => 'Service detail, directory nav, consultation, booking.' ),
			'Requires section library.', 'Listing before cards; directory nav CTA earlier; different section order than PT-03 services_listing_01.' );
	}

	public static function hub_services_educational_01(): array {
		$keys = array( 'hero_edu_01', 'ptf_how_it_works_01', 'fb_value_prop_01', 'cta_service_detail_01', 'ptf_service_flow_01', 'fb_benefit_band_01', 'tp_reassurance_01', 'cta_consultation_01', 'mlp_card_grid_01', 'ptf_expectations_01', 'tp_testimonial_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_services_educational_01', 'Services hub (education-led)', 'Services hub education-led: edu hero, how-it-works and value prop before CTA, service flow, benefit band, reassurance, consultation CTA, cards, expectations, testimonial, booking CTA, panel, contact CTA.', 'services', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Services hub education-led. Process and value before first CTA; expectations and testimonial; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Educational balance; drill-down CTAs after explainer blocks.', 'cta_direction_summary' => 'Service detail, consultation, booking, contact.' ),
			'Requires section library.', 'Education-first; how-it-works and value prop before first CTA; expectations block.' );
	}

	public static function hub_products_comparison_02(): array {
		$keys = array( 'hero_prod_01', 'mlp_comparison_cards_01', 'fb_offer_compare_01', 'cta_compare_next_01', 'ptf_comparison_steps_01', 'tp_rating_01', 'fb_differentiator_01', 'cta_product_detail_01', 'ptf_buying_process_01', 'fb_offer_highlight_01', 'mlp_product_cards_01', 'cta_purchase_01', 'lpu_consent_note_01', 'cta_product_detail_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_products_comparison_02', 'Products hub (comparison v2)', 'Products hub comparison variant: product hero, comparison cards first, offer compare, compare CTA, comparison steps, rating, differentiator, product CTA, buying process, offer highlight, product cards, purchase CTA, consent, product CTA.', 'products', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Products hub comparison v2. Comparison cards before offer compare; rating and differentiator block; two product-detail CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Comparison depth; different section order than PT-03 products_comparison_01.', 'cta_direction_summary' => 'Compare next, product detail, purchase, product detail.' ),
			'Requires section library.', 'Comparison cards first; rating mid-page; different flow than PT-03 products_comparison_01.' );
	}

	public static function hub_products_value_02(): array {
		$keys = array( 'hero_cred_01', 'fb_value_prop_01', 'fb_why_choose_01', 'cta_product_detail_01', 'tp_testimonial_01', 'mlp_listing_01', 'fb_benefit_band_01', 'cta_quote_request_01', 'ptf_buying_process_01', 'tp_client_logo_01', 'fb_offer_highlight_01', 'cta_purchase_01', 'lpu_trust_disclosure_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_products_value_02', 'Products hub (value v2)', 'Products hub value variant: cred hero, value prop and why-choose before CTA, testimonial, listing, benefit band, quote CTA, buying process, logos, offer highlight, purchase CTA, trust disclosure, contact CTA.', 'products', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Products hub value v2. Value prop and why-choose first; listing and benefit band; quote and purchase CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Value-first; different CTA distribution than PT-03 products_value_01.', 'cta_direction_summary' => 'Product detail, quote request, purchase, contact.' ),
			'Requires section library.', 'Value prop and why-choose before first CTA; different sequence than PT-03 products_value_01.' );
	}

	public static function hub_offerings_overview_02(): array {
		$keys = array( 'hero_prod_01', 'fb_offer_compare_01', 'fb_package_summary_01', 'cta_purchase_01', 'mlp_product_cards_01', 'ptf_buying_process_01', 'tp_testimonial_02', 'cta_product_detail_01', 'fb_benefit_detail_01', 'tp_guarantee_01', 'lpu_consent_note_01', 'cta_quote_request_01', 'mlp_card_grid_01', 'cta_purchase_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_offerings_overview_02', 'Offerings hub (overview v2)', 'Offerings hub overview variant: product hero, offer compare before package summary, purchase CTA, product cards, buying process, testimonial, product CTA, benefit detail, guarantee, consent, quote CTA, card grid, purchase CTA.', 'offerings', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offerings hub overview v2. Offer compare before packages; buying process before product CTA; card grid late.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Compare-first; different order than PT-03 offerings_overview_01.', 'cta_direction_summary' => 'Purchase, product detail, quote request, purchase.' ),
			'Requires section library.', 'Offer compare before package summary; buying process mid-page; different flow than PT-03 offerings_overview_01.' );
	}

	public static function hub_offerings_compare_02(): array {
		$keys = array( 'hero_compact_01', 'ptf_comparison_steps_01', 'fb_offer_compare_01', 'cta_compare_next_01', 'mlp_comparison_cards_01', 'tp_rating_01', 'fb_differentiator_01', 'cta_product_detail_02', 'ptf_buying_process_01', 'fb_offer_highlight_01', 'mlp_listing_01', 'cta_quote_request_02', 'lpu_contact_panel_01', 'cta_purchase_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_offerings_compare_02', 'Offerings hub (compare v2)', 'Offerings hub compare variant: compact hero, comparison steps first, offer compare, compare CTA, comparison cards, rating, differentiator, product CTA, buying process, offer highlight, listing, quote CTA, panel, purchase CTA.', 'offerings', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offerings hub compare v2. Comparison steps before offer compare; rating and differentiator; quote and purchase CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Steps-first comparison; different order than PT-03 offerings_compare_01.', 'cta_direction_summary' => 'Compare next, product detail, quote request, purchase.' ),
			'Requires section library.', 'Comparison steps first; rating block; different sequence than PT-03 offerings_compare_01.' );
	}

	public static function hub_directory_browse_02(): array {
		$keys = array( 'hero_dir_01', 'fb_directory_value_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'mlp_listing_01', 'mlp_directory_entry_01', 'ptf_how_it_works_01', 'cta_compare_next_01', 'tp_reassurance_01', 'fb_feature_compact_01', 'mlp_related_content_01', 'cta_contact_01', 'lpu_contact_panel_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_directory_browse_02', 'Directory hub (browse v2)', 'Directory hub browse variant: directory hero, directory value first, card grid, directory nav CTA, listing, directory entry, how-it-works, compare CTA, reassurance, feature compact, related content, contact CTA, panel, directory nav CTA.', 'directories', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory hub browse v2. Directory value before cards; directory entry and how-it-works; compare and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Value-first browse; different order than PT-03 directory_browse_01.', 'cta_direction_summary' => 'Directory nav, compare next, contact, directory nav.' ),
			'Requires section library.', 'Directory value before card grid; directory entry block; different flow than PT-03 directory_browse_01.' );
	}

	public static function hub_directory_listing_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_card_grid_01', 'mlp_listing_01', 'cta_directory_nav_01', 'fb_directory_value_01', 'ptf_how_it_works_01', 'mlp_detail_spec_01', 'cta_product_detail_01', 'mlp_related_content_01', 'fb_benefit_band_01', 'tp_reassurance_01', 'cta_contact_01', 'lpu_contact_panel_01', 'cta_compare_next_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_directory_listing_02', 'Directory hub (listing v2)', 'Directory hub listing variant: directory hero, card grid and listing before directory nav CTA, directory value, how-it-works, detail spec, product CTA, related content, benefit band, reassurance, contact CTA, panel, compare CTA.', 'directories', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory hub listing v2. Cards and listing first; detail spec and product CTA; contact and compare CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Listing prominence; different order than PT-03 directory_listing_01.', 'cta_direction_summary' => 'Directory nav, product detail, contact, compare next.' ),
			'Requires section library.', 'Cards and listing before directory nav CTA; detail spec block; different sequence than PT-03 directory_listing_01.' );
	}

	public static function hub_locations_overview_02(): array {
		$keys = array( 'hero_local_01', 'fb_local_value_01', 'mlp_place_highlight_01', 'cta_local_action_01', 'mlp_location_info_01', 'mlp_card_grid_01', 'tp_trust_band_01', 'cta_contact_01', 'lpu_contact_detail_01', 'ptf_expectations_01', 'lpu_contact_panel_01', 'cta_local_action_02', 'mlp_listing_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_locations_overview_02', 'Locations hub (overview v2)', 'Locations hub overview variant: local hero, local value and place highlight before CTA, location info, card grid, trust band, contact CTA, contact detail, expectations, panel, local CTA, listing, directory nav CTA.', 'locations', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Locations hub overview v2. Local value and place highlight first; contact CTA before second local CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Local relevance; different order than PT-03 locations_overview_01.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav.' ),
			'Requires section library.', 'Local value and place highlight before first CTA; different sequence than PT-03 locations_overview_01.' );
	}

	public static function hub_locations_regional_02(): array {
		$keys = array( 'hero_local_01', 'mlp_location_info_01', 'fb_local_value_01', 'cta_local_action_01', 'mlp_place_highlight_01', 'tp_reassurance_01', 'mlp_card_grid_01', 'cta_local_action_02', 'lpu_contact_detail_01', 'ptf_expectations_01', 'lpu_contact_panel_01', 'cta_contact_02', 'mlp_listing_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_locations_regional_02', 'Locations hub (regional v2)', 'Locations hub regional variant: local hero, location info before local value, local CTA, place highlight, reassurance, card grid, local CTA, contact detail, expectations, panel, contact CTA, listing, directory nav CTA.', 'locations', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Locations hub regional v2. Location info first; two local CTAs before contact CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Regional emphasis; different order than PT-03 locations_regional_01.', 'cta_direction_summary' => 'Local action, local action, contact, directory nav.' ),
			'Requires section library.', 'Location info before local value; reassurance mid-page; different flow than PT-03 locations_regional_01.' );
	}

	// ---------- Geographic hub variants ----------

	public static function hub_geo_service_area_03(): array {
		$keys = array( 'hero_local_01', 'mlp_place_highlight_01', 'fb_local_value_01', 'cta_local_action_01', 'mlp_location_info_01', 'mlp_card_grid_01', 'tp_trust_band_01', 'cta_contact_01', 'lpu_contact_detail_01', 'mlp_listing_01', 'ptf_expectations_01', 'cta_local_action_02', 'lpu_contact_panel_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_geo_service_area_03', 'Service area hub v3', 'Service area hub variant: local hero, place highlight and local value before CTA, location info, card grid, trust band, contact CTA, contact detail, listing, expectations, local CTA, panel, directory nav CTA.', 'service_area', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service area hub v3. Place highlight first; listing before second local CTA; different order than PT-04.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Place-first local relevance.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav.' ),
			'Requires section library.', 'Place highlight and local value before first CTA; listing mid-page; differs from PT-04 service_area_01/02.' );
	}

	public static function hub_geo_regional_03(): array {
		$keys = array( 'hero_local_01', 'fb_local_value_01', 'mlp_place_highlight_01', 'cta_local_action_01', 'mlp_location_info_01', 'tp_reassurance_01', 'mlp_card_grid_01', 'cta_contact_01', 'lpu_contact_detail_01', 'mlp_listing_01', 'ptf_expectations_01', 'cta_local_action_02', 'lpu_contact_panel_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_geo_regional_03', 'Regional hub v3', 'Regional hub variant: local hero, local value and place highlight, local CTA, location info, reassurance, card grid, contact CTA, contact detail, listing, expectations, local CTA, panel, directory nav CTA.', 'regional', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Regional hub v3. Reassurance before card grid; listing before second local CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Regional drill-down; different order than PT-04 regional_01/02.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav.' ),
			'Requires section library.', 'Reassurance mid-page; listing block; differs from PT-04 regional templates.' );
	}

	public static function hub_geo_city_directory_03(): array {
		$keys = array( 'hero_local_01', 'mlp_location_info_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'fb_local_value_01', 'mlp_place_highlight_01', 'tp_trust_band_01', 'cta_local_action_01', 'mlp_listing_01', 'lpu_contact_detail_01', 'ptf_expectations_01', 'cta_contact_01', 'lpu_contact_panel_01', 'cta_local_action_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_geo_city_directory_03', 'City directory hub v3', 'City directory hub variant: local hero, location info and card grid before directory nav CTA, local value, place highlight, trust band, local CTA, listing, contact detail, expectations, contact CTA, panel, local CTA.', 'city_directory', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'City directory hub v3. Location info and cards before directory nav CTA; contact CTA before final local CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Directory nav early; different order than PT-04 city_directory.', 'cta_direction_summary' => 'Directory nav, local action, contact, local action.' ),
			'Requires section library.', 'Location info and card grid before first CTA; differs from PT-04 city_directory templates.' );
	}

	public static function hub_geo_location_overview_03(): array {
		$keys = array( 'hero_local_01', 'mlp_place_highlight_01', 'mlp_location_info_01', 'cta_local_action_01', 'fb_local_value_01', 'mlp_card_grid_01', 'tp_reassurance_01', 'cta_contact_01', 'lpu_contact_detail_01', 'mlp_listing_01', 'ptf_expectations_01', 'cta_local_action_02', 'lpu_contact_panel_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_geo_location_overview_03', 'Location overview hub v3', 'Location overview hub variant: local hero, place highlight and location info before CTA, local value, card grid, reassurance, contact CTA, contact detail, listing, expectations, local CTA, panel, directory nav CTA.', 'location_overview', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location overview hub v3. Place highlight and location info first; contact CTA before second local CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Overview emphasis; different order than PT-04 location_overview.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav.' ),
			'Requires section library.', 'Place highlight and location info before first CTA; differs from PT-04 location_overview.' );
	}

	public static function hub_geo_coverage_listing_03(): array {
		$keys = array( 'hero_local_01', 'mlp_listing_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'fb_local_value_01', 'mlp_location_info_01', 'mlp_place_highlight_01', 'cta_local_action_01', 'tp_trust_band_01', 'lpu_contact_detail_01', 'ptf_expectations_01', 'cta_contact_01', 'lpu_contact_panel_01', 'cta_local_action_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_hub( 'hub_geo_coverage_listing_03', 'Coverage listing hub v3', 'Coverage listing hub variant: local hero, listing and card grid before directory nav CTA, local value, location info, place highlight, local CTA, trust band, contact detail, expectations, contact CTA, panel, local CTA.', 'coverage_listing', $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Coverage listing hub v3. Listing and cards first; directory nav CTA early; local and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Coverage listing emphasis; different order than PT-04 coverage_listing.', 'cta_direction_summary' => 'Directory nav, local action, contact, local action.' ),
			'Requires section library.', 'Listing and card grid before first CTA; differs from PT-04 coverage_listing templates.' );
	}

	// ---------- Nested hub variants ----------

	public static function nested_hub_services_intro_03(): array {
		$keys = array( 'hero_cred_01', 'ptf_how_it_works_01', 'fb_service_offering_01', 'cta_service_detail_01', 'tp_trust_band_01', 'mlp_card_grid_01', 'fb_why_choose_01', 'cta_consultation_01', 'tp_testimonial_01', 'ptf_service_flow_01', 'lpu_contact_panel_01', 'cta_booking_01', 'mlp_listing_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_services_intro_03', 'Service subcategory (intro v3)', 'Nested hub service intro variant: cred hero, how-it-works and offering before CTA, trust band, cards, why choose, consultation CTA, testimonial, service flow, panel, booking CTA, listing, contact CTA.', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service subcategory intro v3. How-it-works before offering; service flow after testimonial; different order than PT-06 intro_01/02.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Process-first subcategory intro.', 'cta_direction_summary' => 'Service detail, consultation, booking, contact.' ),
			'Requires section library.', 'How-it-works before offering; service flow late; differs from PT-06 services_intro_01/02.' );
	}

	public static function nested_hub_services_listing_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_listing_01', 'mlp_card_grid_01', 'cta_service_detail_01', 'fb_service_offering_01', 'tp_testimonial_02', 'ptf_how_it_works_01', 'cta_directory_nav_01', 'fb_benefit_band_01', 'tp_trust_band_01', 'cta_consultation_01', 'lpu_contact_panel_01', 'cta_booking_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_services_listing_02', 'Service subcategory (listing v2)', 'Nested hub service listing variant: directory hero, listing before cards, service CTA, offering, testimonial, how-it-works, directory nav CTA, benefit band, trust band, consultation CTA, panel, booking CTA.', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service subcategory listing v2. Listing before cards; directory nav CTA mid-page; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Listing-first; differs from PT-06 services_listing_01.', 'cta_direction_summary' => 'Service detail, directory nav, consultation, booking.' ),
			'Requires section library.', 'Listing before card grid; 13 sections; differs from PT-06 services_listing_01.' );
	}

	public static function nested_hub_services_educational_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_service_flow_01', 'fb_value_prop_01', 'cta_service_detail_01', 'ptf_how_it_works_01', 'fb_benefit_band_01', 'tp_reassurance_01', 'cta_consultation_01', 'mlp_card_grid_01', 'ptf_expectations_01', 'tp_testimonial_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_services_educational_02', 'Service subcategory (educational v2)', 'Nested hub service educational variant: edu hero, service flow and value prop before CTA, how-it-works, benefit band, reassurance, consultation CTA, cards, expectations, testimonial, booking CTA, panel, contact CTA.', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service subcategory educational v2. Service flow and value prop first; expectations block; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Educational balance; differs from PT-06 services_educational_01.', 'cta_direction_summary' => 'Service detail, consultation, booking, contact.' ),
			'Requires section library.', 'Service flow and value prop before first CTA; expectations mid-page; differs from PT-06 services_educational_01.' );
	}

	public static function nested_hub_services_proof_01(): array {
		$keys = array( 'hero_cred_01', 'tp_testimonial_01', 'tp_client_logo_01', 'cta_service_detail_01', 'fb_service_offering_01', 'tp_trust_band_01', 'ptf_how_it_works_01', 'cta_consultation_01', 'tp_case_teaser_01', 'fb_why_choose_01', 'mlp_card_grid_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_services_proof_01', 'Service subcategory (proof-led)', 'Nested hub service proof-led: cred hero, testimonial and logos before CTA, offering, trust band, how-it-works, consultation CTA, case teaser, why choose, cards, booking CTA, panel, contact CTA.', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service subcategory proof-led. Triple proof before first CTA; case teaser and why choose; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Proof density for subcategory; drill-down CTAs.', 'cta_direction_summary' => 'Service detail, consultation, booking, contact.' ),
			'Requires section library.', 'Proof cluster first; case teaser mid-page; new variant for services subcategory.' );
	}

	public static function nested_hub_products_intro_02(): array {
		$keys = array( 'hero_prod_01', 'fb_offer_compare_01', 'fb_value_prop_01', 'cta_product_detail_01', 'mlp_product_cards_01', 'ptf_buying_process_01', 'tp_guarantee_01', 'cta_purchase_01', 'fb_benefit_band_01', 'tp_testimonial_02', 'mlp_card_grid_01', 'cta_quote_request_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_products_intro_02', 'Product subcategory (intro v2)', 'Nested hub product intro variant: product hero, offer compare and value prop before CTA, product cards, buying process, guarantee, purchase CTA, benefit band, testimonial, card grid, quote CTA, panel, contact CTA.', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product subcategory intro v2. Offer compare and value prop first; guarantee before purchase CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Compare and value first; differs from PT-06 products_intro_01.', 'cta_direction_summary' => 'Product detail, purchase, quote request, contact.' ),
			'Requires section library.', 'Offer compare and value prop before first CTA; differs from PT-06 products_intro_01.' );
	}

	public static function nested_hub_products_comparison_02(): array {
		$keys = array( 'hero_compact_01', 'mlp_comparison_cards_01', 'fb_offer_compare_01', 'cta_compare_next_01', 'ptf_comparison_steps_01', 'tp_rating_01', 'fb_differentiator_01', 'cta_product_detail_01', 'ptf_buying_process_01', 'fb_offer_highlight_01', 'mlp_product_cards_01', 'cta_purchase_01', 'lpu_consent_note_01', 'cta_product_detail_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_products_comparison_02', 'Product subcategory (comparison v2)', 'Nested hub product comparison variant: compact hero, comparison cards first, offer compare, compare CTA, comparison steps, rating, differentiator, product CTA, buying process, offer highlight, product cards, purchase CTA, consent, product CTA.', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product subcategory comparison v2. Comparison cards first; rating and differentiator; two product CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Comparison depth; differs from PT-06 products_comparison_01.', 'cta_direction_summary' => 'Compare next, product detail, purchase, product detail.' ),
			'Requires section library.', 'Comparison cards first; differs from PT-06 products_comparison_01.' );
	}

	public static function nested_hub_products_value_02(): array {
		$keys = array( 'hero_cred_01', 'fb_why_choose_01', 'fb_value_prop_01', 'cta_product_detail_01', 'tp_testimonial_01', 'mlp_listing_01', 'fb_benefit_band_01', 'cta_quote_request_01', 'ptf_buying_process_01', 'tp_client_logo_01', 'fb_offer_highlight_01', 'cta_purchase_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_products_value_02', 'Product subcategory (value v2)', 'Nested hub product value variant: cred hero, why-choose and value prop before CTA, testimonial, listing, benefit band, quote CTA, buying process, logos, offer highlight, purchase CTA, panel, contact CTA.', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product subcategory value v2. Why-choose and value prop first; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Value-first; differs from PT-06 products_value_01.', 'cta_direction_summary' => 'Product detail, quote request, purchase, contact.' ),
			'Requires section library.', 'Why-choose and value prop before first CTA; differs from PT-06 products_value_01.' );
	}

	public static function nested_hub_offerings_overview_02(): array {
		$keys = array( 'hero_prod_01', 'fb_package_summary_01', 'fb_offer_compare_01', 'cta_purchase_01', 'mlp_product_cards_01', 'tp_testimonial_02', 'ptf_buying_process_01', 'cta_product_detail_01', 'fb_benefit_detail_01', 'tp_guarantee_01', 'lpu_consent_note_01', 'cta_quote_request_01', 'mlp_card_grid_01', 'cta_purchase_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_offerings_overview_02', 'Offerings subcategory (overview v2)', 'Nested hub offerings overview variant: product hero, package summary before offer compare, purchase CTA, product cards, testimonial, buying process, product CTA, benefit detail, guarantee, consent, quote CTA, card grid, purchase CTA.', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offerings subcategory overview v2. Package summary first; buying process before product CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Package-first; differs from PT-06 offerings_overview_01.', 'cta_direction_summary' => 'Purchase, product detail, quote request, purchase.' ),
			'Requires section library.', 'Package summary before offer compare; differs from PT-06 offerings_overview_01.' );
	}

	public static function nested_hub_offerings_compare_01(): array {
		$keys = array( 'hero_compact_01', 'fb_offer_compare_01', 'ptf_comparison_steps_01', 'cta_compare_next_01', 'mlp_comparison_cards_01', 'fb_differentiator_01', 'tp_rating_01', 'cta_product_detail_01', 'ptf_buying_process_01', 'fb_offer_highlight_01', 'mlp_product_cards_01', 'cta_quote_request_01', 'lpu_contact_panel_01', 'cta_purchase_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_offerings_compare_01', 'Offerings subcategory (compare)', 'Nested hub offerings compare: compact hero, offer compare and comparison steps, compare CTA, comparison cards, differentiator, rating, product CTA, buying process, offer highlight, product cards, quote CTA, panel, purchase CTA.', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offerings subcategory compare. Comparison depth; rating and differentiator; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Comparison-led subcategory; drill-down CTAs.', 'cta_direction_summary' => 'Compare next, product detail, quote request, purchase.' ),
			'Requires section library.', 'New compare-led variant for offerings subcategory.' );
	}

	public static function nested_hub_directories_filtered_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_listing_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'fb_directory_value_01', 'ptf_how_it_works_01', 'tp_reassurance_01', 'cta_contact_01', 'mlp_related_content_01', 'fb_benefit_band_01', 'lpu_contact_panel_01', 'cta_compare_next_01', 'mlp_directory_entry_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_directories_filtered_02', 'Directory subcategory (filtered v2)', 'Nested hub directory filtered variant: directory hero, listing and cards before directory nav CTA, directory value, how-it-works, reassurance, contact CTA, related content, benefit band, panel, compare CTA, directory entry, directory nav CTA.', 'directories', array( 'directories' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory subcategory filtered v2. Listing and cards first; directory entry late; four CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Filtered listing emphasis; differs from PT-06 directories_filtered_01.', 'cta_direction_summary' => 'Directory nav, contact, compare next, directory nav.' ),
			'Requires section library.', 'Listing and cards before first CTA; directory entry late; differs from PT-06 directories_filtered_01.' );
	}

	public static function nested_hub_directories_category_02(): array {
		$keys = array( 'hero_compact_01', 'fb_directory_value_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'mlp_listing_01', 'ptf_steps_01', 'tp_client_logo_01', 'cta_service_detail_01', 'mlp_related_content_01', 'fb_benefit_band_01', 'lpu_contact_panel_01', 'cta_contact_02', 'tp_reassurance_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_directories_category_02', 'Directory subcategory (category v2)', 'Nested hub directory category variant: compact hero, directory value and cards, directory nav CTA, listing, steps, logos, service CTA, related content, benefit band, panel, contact CTA, reassurance, directory nav CTA.', 'directories', array( 'directories' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory subcategory category v2. Steps and logos block; service CTA mid-page.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Category structure; differs from PT-06 directories_category_01.', 'cta_direction_summary' => 'Directory nav, service detail, contact, directory nav.' ),
			'Requires section library.', 'Steps and logos block; service CTA; differs from PT-06 directories_category_01.' );
	}

	public static function nested_hub_directories_listing_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_card_grid_01', 'mlp_listing_01', 'cta_directory_nav_01', 'fb_feature_grid_01', 'ptf_how_it_works_01', 'mlp_detail_spec_01', 'cta_product_detail_01', 'mlp_related_content_01', 'tp_reassurance_01', 'lpu_contact_panel_01', 'cta_contact_01', 'fb_directory_value_01', 'cta_compare_next_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_directories_listing_02', 'Directory subcategory (listing v2)', 'Nested hub directory listing variant: directory hero, card grid and listing, directory nav CTA, feature grid, how-it-works, detail spec, product CTA, related content, reassurance, panel, contact CTA, directory value, compare CTA.', 'directories', array( 'directories' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory subcategory listing v2. Feature grid and detail spec; product and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Listing prominence; differs from PT-06 directories_listing_01.', 'cta_direction_summary' => 'Directory nav, product detail, contact, compare next.' ),
			'Requires section library.', 'Feature grid and detail spec block; differs from PT-06 directories_listing_01.' );
	}

	public static function nested_hub_locations_subarea_03(): array {
		$keys = array( 'hero_local_01', 'mlp_location_info_01', 'fb_local_value_01', 'cta_local_action_01', 'mlp_place_highlight_01', 'mlp_card_grid_01', 'tp_trust_band_01', 'cta_contact_01', 'lpu_contact_detail_01', 'ptf_expectations_01', 'mlp_listing_01', 'cta_local_action_02', 'lpu_contact_panel_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_locations_subarea_03', 'Location subarea v3', 'Nested hub location subarea variant: local hero, location info and local value before CTA, place highlight, card grid, trust band, contact CTA, contact detail, expectations, listing, local CTA, panel, directory nav CTA.', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location subarea v3. Location info and local value first; listing before second local CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Local relevance; differs from PT-06 locations_subarea_01/02.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav.' ),
			'Requires section library.', 'Location info and local value before first CTA; differs from PT-06 locations_subarea templates.' );
	}

	public static function nested_hub_locations_subregion_02(): array {
		$keys = array( 'hero_local_01', 'fb_local_value_01', 'mlp_place_highlight_01', 'cta_local_action_01', 'mlp_location_info_01', 'mlp_card_grid_01', 'tp_reassurance_01', 'cta_local_action_02', 'lpu_contact_detail_01', 'ptf_expectations_01', 'lpu_contact_panel_01', 'cta_contact_01', 'mlp_listing_01', 'cta_directory_nav_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_nested( 'nested_hub_locations_subregion_02', 'Location subregion v2', 'Nested hub location subregion variant: local hero, local value and place highlight before CTA, location info, card grid, reassurance, local CTA, contact detail, expectations, panel, contact CTA, listing, directory nav CTA.', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location subregion v2. Local value and place highlight first; two local CTAs before contact CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Subregion drill-down; differs from PT-06 locations_subregion_01.', 'cta_direction_summary' => 'Local action, local action, contact, directory nav.' ),
			'Requires section library.', 'Local value and place highlight before first CTA; differs from PT-06 locations_subregion_01.' );
	}
}
