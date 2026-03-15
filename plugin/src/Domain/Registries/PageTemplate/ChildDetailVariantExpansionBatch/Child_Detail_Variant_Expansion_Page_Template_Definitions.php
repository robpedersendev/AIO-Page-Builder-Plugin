<?php
/**
 * Child/detail variant expansion super-batch (spec §13, §14.3, §16, Prompt 166).
 * Expands PT-07, PT-08, PT-09 with materially distinct variants: conversion, educational, comparison, trust, media-rich.
 * ~10 non-CTA (8–14) + ≥5 CTA sections, mandatory bottom CTA, no adjacent CTA. Synthetic preview only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate\ChildDetailVariantExpansionBatch;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Child/detail variant expansion (PT-13). Adds variants to PT-07, PT-08, PT-09 families.
 * template_category_class = child_detail; hierarchy_role = leaf.
 */
final class Child_Detail_Variant_Expansion_Page_Template_Definitions {

	/** Batch ID for child/detail variant expansion (template-library-inventory-manifest PT-13). */
	public const BATCH_ID = 'PT-13';

	/**
	 * Returns all child/detail variant definitions (order preserved for seeding).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function all_definitions(): array {
		return array(
			// Services.
			self::child_detail_service_conversion_02(),
			self::child_detail_service_educational_02(),
			self::child_detail_service_comparison_01(),
			self::child_detail_service_media_01(),
			self::child_detail_service_urgency_01(),
			// Offerings.
			self::child_detail_offer_value_02(),
			self::child_detail_offer_package_02(),
			self::child_detail_offer_trust_01(),
			self::child_detail_offer_consultation_02(),
			// Locations.
			self::child_detail_location_local_02(),
			self::child_detail_location_visit_02(),
			self::child_detail_location_subregion_01(),
			self::child_detail_location_proof_01(),
			self::child_detail_location_contact_02(),
			// Products / catalog.
			self::child_detail_product_spec_03(),
			self::child_detail_product_comparison_03(),
			self::child_detail_product_media_02(),
			self::child_detail_product_proof_02(),
			self::child_detail_product_catalog_02(),
			self::child_detail_product_urgency_01(),
			// Directories / profile / entity.
			self::child_detail_directory_profile_02(),
			self::child_detail_entity_detail_02(),
			self::child_detail_listing_detail_01(),
			self::child_detail_profile_trust_02(),
			self::child_detail_profile_educational_02(),
			self::child_detail_profile_media_02(),
			// Resource / informational.
			self::child_detail_resource_detail_02(),
			self::child_detail_article_02(),
			self::child_detail_informational_educational_02(),
			self::child_detail_authority_detail_02(),
		);
	}

	/**
	 * Returns page template internal keys in this batch.
	 *
	 * @return list<string>
	 */
	public static function template_keys(): array {
		return array(
			'child_detail_service_conversion_02', 'child_detail_service_educational_02', 'child_detail_service_comparison_01', 'child_detail_service_media_01', 'child_detail_service_urgency_01',
			'child_detail_offer_value_02', 'child_detail_offer_package_02', 'child_detail_offer_trust_01', 'child_detail_offer_consultation_02',
			'child_detail_location_local_02', 'child_detail_location_visit_02', 'child_detail_location_subregion_01', 'child_detail_location_proof_01', 'child_detail_location_contact_02',
			'child_detail_product_spec_03', 'child_detail_product_comparison_03', 'child_detail_product_media_02', 'child_detail_product_proof_02', 'child_detail_product_catalog_02', 'child_detail_product_urgency_01',
			'child_detail_directory_profile_02', 'child_detail_entity_detail_02', 'child_detail_listing_detail_01', 'child_detail_profile_trust_02', 'child_detail_profile_educational_02', 'child_detail_profile_media_02',
			'child_detail_resource_detail_02', 'child_detail_article_02', 'child_detail_informational_educational_02', 'child_detail_authority_detail_02',
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
		string $differentiation_notes
	): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY             => $internal_key,
			Page_Template_Schema::FIELD_NAME                     => $name,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY          => $purpose_summary,
			Page_Template_Schema::FIELD_ARCHETYPE                => $archetype,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS        => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS     => $section_requirements,
			Page_Template_Schema::FIELD_COMPATIBILITY            => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                => $one_pager,
			Page_Template_Schema::FIELD_VERSION                  => array( 'version' => '1', 'stable_key_retained' => true ),
			Page_Template_Schema::FIELD_STATUS                  => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES  => $endpoint_notes,
			'template_category_class'                           => 'child_detail',
			'template_family'                                    => $template_family,
			'parent_family_compatibility'                       => $parent_family_compatibility,
			'preview_metadata'                                  => array( 'synthetic' => true ),
			'differentiation_notes'                             => $differentiation_notes,
			'variation_family'                                  => $template_family,
			'hierarchy_hints'                                   => array(
				'common_parent_page_types' => 'hub, nested_hub',
				'hierarchy_role'           => 'leaf',
			),
			Page_Template_Schema::FIELD_INDUSTRY_AFFINITY        => array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' ),
		);
	}

	// ---------- Services ----------

	public static function child_detail_service_conversion_02(): array {
		$keys = array( 'hero_cred_01', 'fb_service_offering_01', 'cta_service_detail_01', 'tp_testimonial_01', 'tp_trust_band_01', 'fb_why_choose_01', 'cta_consultation_01', 'ptf_how_it_works_01', 'ptf_expectations_01', 'cta_quote_request_01', 'tp_guarantee_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_service_conversion_02', 'Service detail (conversion v2)', 'Child/detail conversion-led variant: hero, offering, service CTA, testimonial, trust band, why choose, consultation CTA, how-it-works, expectations, quote CTA, guarantee, booking CTA, panel, contact CTA.', 'service_page', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service detail conversion v2. Early service and consultation CTAs; proof and process; quote, booking, contact.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Conversion-led; different CTA spacing than PT-07 conversion_01.', 'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Conversion v2; earlier CTAs and guarantee before booking.' );
	}

	public static function child_detail_service_educational_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_how_it_works_01', 'fb_service_offering_01', 'ptf_expectations_01', 'cta_service_detail_02', 'ptf_service_flow_01', 'fb_benefit_band_01', 'tp_testimonial_01', 'cta_consultation_01', 'tp_trust_band_01', 'fb_why_choose_01', 'cta_quote_request_01', 'tp_guarantee_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_service_educational_02', 'Service detail (educational v2)', 'Child/detail educational variant: edu hero, how-it-works, offering, expectations, service CTA, service flow, benefit band, testimonial, consultation CTA, trust band, why choose, quote CTA, guarantee, booking CTA, panel, contact CTA.', 'service_page', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service detail educational v2. Process and expectations before first CTA; benefit band and testimonial.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Education-first; different order than PT-07 educational_01.', 'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Educational v2; edu hero and process blocks lead.' );
	}

	public static function child_detail_service_comparison_01(): array {
		$keys = array( 'hero_cred_01', 'fb_service_offering_01', 'fb_benefit_band_01', 'cta_service_detail_01', 'ptf_how_it_works_01', 'fb_why_choose_01', 'tp_testimonial_01', 'cta_consultation_01', 'ptf_expectations_01', 'tp_trust_band_01', 'cta_quote_request_01', 'ptf_service_flow_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_service_comparison_01', 'Service detail (comparison-led)', 'Child/detail comparison posture: hero, offering, benefit band, service CTA, how-it-works, why choose, testimonial, consultation CTA, expectations, trust band, quote CTA, service flow, booking CTA, panel, contact CTA.', 'service_page', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service detail comparison-led. Benefit band and why-choose support comparison; multiple CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Comparison depth via benefit and why-choose.', 'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Comparison-led; benefit band and why-choose early.' );
	}

	public static function child_detail_service_media_01(): array {
		$keys = array( 'hero_cred_01', 'mlp_gallery_01', 'fb_service_offering_01', 'cta_service_detail_01', 'tp_testimonial_01', 'ptf_how_it_works_01', 'mlp_related_content_01', 'cta_consultation_01', 'fb_why_choose_01', 'tp_trust_band_01', 'cta_quote_request_01', 'ptf_expectations_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_service_media_01', 'Service detail (media-rich)', 'Child/detail media-rich: hero, gallery, offering, service CTA, testimonial, how-it-works, related content, consultation CTA, why choose, trust band, quote CTA, expectations, booking CTA, panel, contact CTA.', 'service_page', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service detail media-rich. Gallery and related content; conversion and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Media-first; gallery and related content.', 'cta_direction_summary' => 'Service detail, consultation, quote, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Media-rich; gallery and related content blocks.' );
	}

	public static function child_detail_service_urgency_01(): array {
		$keys = array( 'hero_cred_01', 'fb_service_offering_01', 'cta_booking_01', 'tp_trust_band_01', 'ptf_how_it_works_01', 'cta_consultation_01', 'tp_testimonial_01', 'fb_why_choose_01', 'cta_quote_request_01', 'ptf_expectations_01', 'tp_guarantee_01', 'cta_service_detail_01', 'lpu_contact_panel_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_service_urgency_01', 'Service detail (urgency-led)', 'Child/detail urgency-led: hero, offering, booking CTA early, trust band, how-it-works, consultation CTA, testimonial, why choose, quote CTA, expectations, guarantee, service CTA, panel, contact CTA.', 'service_page', 'services', array( 'services' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Service detail urgency-led. Booking CTA early; guarantee and service CTA later.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Urgency; booking before education.', 'cta_direction_summary' => 'Booking, consultation, quote, service detail, contact; last CTA contact.' ),
			'Requires section library.', 'Urgency-led; booking CTA in first three sections.' );
	}

	// ---------- Offerings ----------

	public static function child_detail_offer_value_02(): array {
		$keys = array( 'hero_prod_01', 'fb_value_prop_01', 'fb_why_choose_01', 'cta_purchase_01', 'tp_testimonial_01', 'fb_benefit_detail_01', 'cta_quote_request_01', 'ptf_buying_process_01', 'tp_guarantee_01', 'cta_product_detail_01', 'mlp_product_cards_01', 'tp_trust_band_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_offer_value_02', 'Offering detail (value v2)', 'Child/detail offering value variant: product hero, value prop, why choose, purchase CTA, testimonial, benefit detail, quote CTA, buying process, guarantee, product CTA, product cards, trust band, consultation CTA, consent, contact CTA.', 'offer_page', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offering detail value v2. Value prop and why-choose first; product cards and consultation CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Value-first; different order than PT-07 offer_value_01.', 'cta_direction_summary' => 'Purchase, quote, product detail, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Value v2; value prop and why-choose before first CTA.' );
	}

	public static function child_detail_offer_package_02(): array {
		$keys = array( 'hero_prod_01', 'fb_package_summary_01', 'fb_offer_compare_01', 'cta_purchase_01', 'ptf_buying_process_01', 'tp_rating_01', 'cta_quote_request_01', 'fb_benefit_detail_01', 'tp_guarantee_01', 'cta_product_detail_01', 'mlp_product_cards_01', 'tp_trust_band_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_offer_package_02', 'Offering detail (package v2)', 'Child/detail offering package variant: product hero, package summary, offer compare, purchase CTA, buying process, rating, quote CTA, benefit detail, guarantee, product CTA, product cards, trust band, consultation CTA, consent, contact CTA.', 'offer_page', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offering detail package v2. Package summary and offer compare first; rating and product CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Package-first; different order than PT-07 offer_package_01.', 'cta_direction_summary' => 'Purchase, quote, product detail, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Package v2; package summary and offer compare lead.' );
	}

	public static function child_detail_offer_trust_01(): array {
		$keys = array( 'hero_prod_01', 'tp_trust_band_01', 'tp_testimonial_01', 'fb_value_prop_01', 'cta_purchase_01', 'tp_guarantee_01', 'fb_why_choose_01', 'cta_quote_request_01', 'ptf_buying_process_01', 'tp_client_logo_01', 'cta_product_detail_01', 'mlp_product_cards_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_offer_trust_01', 'Offering detail (trust-led)', 'Child/detail offering trust-led: product hero, trust band, testimonial, value prop, purchase CTA, guarantee, why choose, quote CTA, buying process, client logos, product CTA, product cards, consultation CTA, consent, contact CTA.', 'offer_page', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offering detail trust-led. Trust and testimonial before value prop; logos and product CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Trust-first; proof density.', 'cta_direction_summary' => 'Purchase, quote, product detail, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Trust-led; trust band and testimonial before first CTA.' );
	}

	public static function child_detail_offer_consultation_02(): array {
		$keys = array( 'hero_prod_01', 'fb_value_prop_01', 'cta_consultation_01', 'fb_offer_compare_01', 'ptf_buying_process_01', 'cta_quote_request_01', 'tp_testimonial_01', 'fb_benefit_detail_01', 'cta_purchase_01', 'tp_guarantee_01', 'mlp_product_cards_01', 'cta_product_detail_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_offer_consultation_02', 'Offering detail (consultation v2)', 'Child/detail offering consultation variant: product hero, value prop, consultation CTA, offer compare, buying process, quote CTA, testimonial, benefit detail, purchase CTA, guarantee, product cards, product CTA, consent, contact CTA.', 'offer_page', 'offerings', array( 'offerings' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Offering detail consultation v2. Consultation CTA early; quote and purchase CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Consultation-first; different order than PT-07 offer_consultation_01.', 'cta_direction_summary' => 'Consultation, quote, purchase, product detail, contact; last CTA contact.' ),
			'Requires section library.', 'Consultation v2; consultation CTA in first three sections.' );
	}

	// ---------- Locations ----------

	public static function child_detail_location_local_02(): array {
		$keys = array( 'hero_local_01', 'fb_local_value_01', 'mlp_place_highlight_01', 'cta_local_action_01', 'mlp_location_info_01', 'tp_trust_band_01', 'cta_contact_01', 'ptf_expectations_01', 'lpu_contact_detail_01', 'cta_local_action_02', 'mlp_card_grid_01', 'cta_directory_nav_01', 'lpu_contact_panel_01', 'cta_booking_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_location_local_02', 'Location detail (local v2)', 'Child/detail location local variant: local hero, local value, place highlight, local CTA, location info, trust band, contact CTA, expectations, contact detail, local CTA, card grid, directory nav CTA, panel, booking CTA.', 'location_page', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location detail local v2. Local value and place highlight before first CTA; directory nav and booking.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Local-first; different order than PT-07 location_local_01.', 'cta_direction_summary' => 'Local action, contact, local action, directory nav, booking; last CTA booking.' ),
			'Requires section library.', 'Local v2; place highlight and location info early.' );
	}

	public static function child_detail_location_visit_02(): array {
		$keys = array( 'hero_local_01', 'mlp_place_highlight_01', 'ptf_expectations_01', 'cta_booking_01', 'mlp_location_info_01', 'fb_local_value_01', 'cta_local_action_01', 'tp_reassurance_01', 'lpu_contact_detail_01', 'cta_contact_01', 'mlp_card_grid_01', 'tp_trust_band_01', 'cta_directory_nav_01', 'lpu_contact_panel_01', 'cta_local_action_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_location_visit_02', 'Location detail (visit v2)', 'Child/detail location visit variant: local hero, place highlight, expectations, booking CTA, location info, local value, local CTA, reassurance, contact detail, contact CTA, card grid, trust band, directory nav CTA, panel, local CTA.', 'location_page', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location detail visit v2. Expectations before booking CTA; contact and directory nav CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Visit-focused; different order than PT-07 location_visit_01.', 'cta_direction_summary' => 'Booking, local action, contact, directory nav, local action; last CTA local.' ),
			'Requires section library.', 'Visit v2; expectations and booking CTA early.' );
	}

	public static function child_detail_location_subregion_01(): array {
		$keys = array( 'hero_local_01', 'mlp_location_info_01', 'fb_local_value_01', 'cta_local_action_01', 'mlp_place_highlight_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'tp_trust_band_01', 'ptf_expectations_01', 'cta_contact_01', 'lpu_contact_detail_01', 'cta_local_action_02', 'lpu_contact_panel_01', 'cta_booking_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_location_subregion_01', 'Location detail (subregion)', 'Child/detail location subregion: local hero, location info, local value, local CTA, place highlight, card grid, directory nav CTA, trust band, expectations, contact CTA, contact detail, local CTA, panel, booking CTA.', 'location_page', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location detail subregion. Location info and card grid; directory nav and local CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Subregion drill-down; listing and directory emphasis.', 'cta_direction_summary' => 'Local action, directory nav, contact, local action, booking; last CTA booking.' ),
			'Requires section library.', 'Subregion; location info and card grid before directory nav CTA.' );
	}

	public static function child_detail_location_proof_01(): array {
		$keys = array( 'hero_local_01', 'tp_trust_band_01', 'tp_testimonial_01', 'fb_local_value_01', 'cta_local_action_01', 'mlp_place_highlight_01', 'tp_guarantee_01', 'cta_contact_01', 'mlp_location_info_01', 'lpu_contact_detail_01', 'cta_booking_01', 'ptf_expectations_01', 'cta_directory_nav_01', 'lpu_contact_panel_01', 'cta_local_action_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_location_proof_01', 'Location detail (proof-led)', 'Child/detail location proof-led: local hero, trust band, testimonial, local value, local CTA, place highlight, guarantee, contact CTA, location info, contact detail, booking CTA, expectations, directory nav CTA, panel, local CTA.', 'location_page', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location detail proof-led. Trust band and testimonial before first CTA; guarantee and contact.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Proof-first; trust and testimonial lead.', 'cta_direction_summary' => 'Local action, contact, booking, directory nav, local action; last CTA local.' ),
			'Requires section library.', 'Proof-led; trust band and testimonial before local CTA.' );
	}

	public static function child_detail_location_contact_02(): array {
		$keys = array( 'hero_local_01', 'fb_local_value_01', 'lpu_contact_detail_01', 'cta_contact_01', 'mlp_place_highlight_01', 'mlp_location_info_01', 'cta_local_action_01', 'tp_trust_band_01', 'lpu_contact_panel_01', 'cta_booking_01', 'ptf_expectations_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'mlp_listing_01', 'cta_local_action_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_location_contact_02', 'Location detail (contact v2)', 'Child/detail location contact variant: local hero, local value, contact detail, contact CTA, place highlight, location info, local CTA, trust band, contact panel, booking CTA, expectations, card grid, directory nav CTA, listing, local CTA.', 'location_page', 'locations', array( 'locations' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Location detail contact v2. Contact detail and contact CTA early; panel and booking.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Contact-first; different order than PT-07 location_contact_01.', 'cta_direction_summary' => 'Contact, local action, booking, directory nav, local action; last CTA local.' ),
			'Requires section library.', 'Contact v2; contact detail and contact CTA in first four sections.' );
	}

	// ---------- Products ----------

	public static function child_detail_product_spec_03(): array {
		$keys = array( 'hero_prod_01', 'mlp_detail_spec_01', 'fb_benefit_detail_01', 'cta_product_detail_01', 'ptf_buying_process_01', 'fb_offer_compare_01', 'cta_purchase_01', 'tp_rating_01', 'fb_why_choose_01', 'cta_quote_request_01', 'mlp_product_cards_01', 'tp_trust_band_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_spec_03', 'Product detail (spec v3)', 'Child/detail product spec v3: product hero, detail spec, benefit detail, product CTA, buying process, offer compare, purchase CTA, rating, why choose, quote CTA, product cards, trust band, consultation CTA, consent, contact CTA.', 'offer_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail spec v3. Detail spec and benefit detail first; product and purchase CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Spec-first; different order than PT-08 spec_01/02.', 'cta_direction_summary' => 'Product detail, purchase, quote, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Spec v3; detail spec and benefit detail lead.' );
	}

	public static function child_detail_product_comparison_03(): array {
		$keys = array( 'hero_prod_01', 'mlp_comparison_cards_01', 'fb_offer_compare_01', 'cta_compare_next_01', 'ptf_comparison_steps_01', 'fb_differentiator_01', 'cta_product_detail_01', 'tp_rating_01', 'fb_benefit_detail_01', 'cta_purchase_01', 'mlp_product_cards_01', 'tp_trust_band_01', 'cta_quote_request_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_comparison_03', 'Product detail (comparison v3)', 'Child/detail product comparison v3: product hero, comparison cards, offer compare, compare CTA, comparison steps, differentiator, product CTA, rating, benefit detail, purchase CTA, product cards, trust band, quote CTA, consent, contact CTA.', 'comparison_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail comparison v3. Comparison cards and steps; differentiator and product CTA.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Comparison depth; different order than PT-08 comparison_01/02.', 'cta_direction_summary' => 'Compare next, product detail, purchase, quote, contact; last CTA contact.' ),
			'Requires section library.', 'Comparison v3; comparison cards and steps before product CTA.' );
	}

	public static function child_detail_product_media_02(): array {
		$keys = array( 'hero_prod_01', 'mlp_gallery_01', 'fb_value_prop_01', 'cta_product_detail_01', 'mlp_media_band_01', 'fb_benefit_detail_01', 'cta_purchase_01', 'tp_rating_01', 'mlp_product_cards_01', 'cta_quote_request_01', 'tp_trust_band_01', 'ptf_buying_process_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_media_02', 'Product detail (media v2)', 'Child/detail product media v2: product hero, gallery, value prop, product CTA, media band, benefit detail, purchase CTA, rating, product cards, quote CTA, trust band, buying process, consultation CTA, consent, contact CTA.', 'offer_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail media v2. Gallery and media band; product and purchase CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Media-first; different order than PT-08 media_01.', 'cta_direction_summary' => 'Product detail, purchase, quote, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Media v2; gallery and media band early.' );
	}

	public static function child_detail_product_proof_02(): array {
		$keys = array( 'hero_prod_01', 'tp_trust_band_01', 'tp_testimonial_01', 'fb_value_prop_01', 'cta_purchase_01', 'tp_guarantee_01', 'tp_client_logo_01', 'cta_product_detail_01', 'fb_why_choose_01', 'ptf_buying_process_01', 'cta_quote_request_01', 'mlp_product_cards_01', 'tp_rating_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_proof_02', 'Product detail (proof v2)', 'Child/detail product proof v2: product hero, trust band, testimonial, value prop, purchase CTA, guarantee, client logos, product CTA, why choose, buying process, quote CTA, product cards, rating, consultation CTA, consent, contact CTA.', 'offer_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail proof v2. Trust band and testimonial before first CTA; guarantee and logos.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Proof-first; different order than PT-08 proof_01.', 'cta_direction_summary' => 'Purchase, product detail, quote, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Proof v2; trust band and testimonial lead.' );
	}

	public static function child_detail_product_catalog_02(): array {
		$keys = array( 'hero_prod_01', 'mlp_product_cards_01', 'mlp_detail_spec_01', 'cta_product_detail_01', 'fb_offer_compare_01', 'ptf_buying_process_01', 'cta_purchase_01', 'tp_rating_01', 'fb_benefit_detail_01', 'cta_quote_request_01', 'mlp_related_content_01', 'tp_trust_band_01', 'cta_compare_next_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_catalog_02', 'Product detail (catalog v2)', 'Child/detail product catalog v2: product hero, product cards, detail spec, product CTA, offer compare, buying process, purchase CTA, rating, benefit detail, quote CTA, related content, trust band, compare CTA, consent, contact CTA.', 'offer_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail catalog v2. Product cards and detail spec first; compare and contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Catalog item emphasis; different order than PT-08 catalog_item_01.', 'cta_direction_summary' => 'Product detail, purchase, quote, compare next, contact; last CTA contact.' ),
			'Requires section library.', 'Catalog v2; product cards and detail spec early.' );
	}

	public static function child_detail_product_urgency_01(): array {
		$keys = array( 'hero_prod_01', 'fb_value_prop_01', 'cta_purchase_01', 'tp_trust_band_01', 'fb_benefit_detail_01', 'cta_quote_request_01', 'ptf_buying_process_01', 'tp_guarantee_01', 'cta_product_detail_01', 'mlp_product_cards_01', 'tp_rating_01', 'cta_consultation_01', 'lpu_consent_note_01', 'cta_contact_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_product_urgency_01', 'Product detail (urgency-led)', 'Child/detail product urgency-led: product hero, value prop, purchase CTA early, trust band, benefit detail, quote CTA, buying process, guarantee, product CTA, product cards, rating, consultation CTA, consent, contact CTA.', 'offer_page', 'products', array( 'products' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Product detail urgency-led. Purchase CTA in first three sections; quote and product CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Urgency; purchase before education.', 'cta_direction_summary' => 'Purchase, quote, product detail, consultation, contact; last CTA contact.' ),
			'Requires section library.', 'Urgency-led; purchase CTA in first three sections.' );
	}

	// ---------- Directories / profile / entity ----------

	public static function child_detail_directory_profile_02(): array {
		$keys = array( 'hero_dir_01', 'mlp_profile_summary_01', 'mlp_directory_entry_01', 'cta_directory_nav_01', 'fb_directory_value_01', 'tp_testimonial_01', 'cta_contact_01', 'mlp_profile_cards_01', 'tp_trust_band_01', 'cta_consultation_01', 'ptf_how_it_works_01', 'cta_booking_01', 'mlp_related_content_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_directory_profile_02', 'Directory profile detail v2', 'Child/detail directory profile v2: directory hero, profile summary, directory entry, directory nav CTA, directory value, testimonial, contact CTA, profile cards, trust band, consultation CTA, how-it-works, booking CTA, related content, panel, contact CTA.', 'directory_page', 'directories', array( 'directories', 'profiles' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Directory profile v2. Profile summary and directory entry first; directory nav, contact, consultation, booking, contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Profile and directory blend; different order than PT-09 directory_member_01.', 'cta_direction_summary' => 'Directory nav, contact, consultation, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Directory profile v2; profile summary and directory entry lead.' );
	}

	public static function child_detail_entity_detail_02(): array {
		$keys = array( 'hero_cred_01', 'mlp_directory_entry_01', 'fb_value_prop_01', 'cta_consultation_01', 'mlp_card_grid_01', 'tp_trust_band_01', 'cta_directory_nav_01', 'ptf_how_it_works_01', 'tp_testimonial_01', 'cta_contact_01', 'mlp_related_content_01', 'fb_why_choose_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_entity_detail_02', 'Entity detail v2', 'Child/detail entity v2: cred hero, directory entry, value prop, consultation CTA, card grid, trust band, directory nav CTA, how-it-works, testimonial, contact CTA, related content, why choose, booking CTA, panel, contact CTA.', 'directory_page', 'directories', array( 'directories' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Entity detail v2. Directory entry and value prop; consultation, directory nav, contact, booking, contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Entity emphasis; different order than PT-09 directory_entity_01.', 'cta_direction_summary' => 'Consultation, directory nav, contact, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Entity v2; directory entry and card grid early.' );
	}

	public static function child_detail_listing_detail_01(): array {
		$keys = array( 'hero_dir_01', 'mlp_listing_01', 'mlp_card_grid_01', 'cta_directory_nav_01', 'fb_directory_value_01', 'mlp_detail_spec_01', 'cta_product_detail_01', 'tp_trust_band_01', 'mlp_related_content_01', 'cta_contact_01', 'ptf_how_it_works_01', 'cta_compare_next_01', 'lpu_contact_panel_01', 'cta_booking_01' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_listing_detail_01', 'Listing detail', 'Child/detail listing: directory hero, listing, card grid, directory nav CTA, directory value, detail spec, product CTA, trust band, related content, contact CTA, how-it-works, compare CTA, panel, booking CTA.', 'directory_page', 'directories', array( 'directories' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Listing detail. Listing and card grid first; directory nav, product, contact, compare, booking CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Listing-first; detail spec and product CTA.', 'cta_direction_summary' => 'Directory nav, product detail, contact, compare next, booking; last CTA booking.' ),
			'Requires section library.', 'Listing detail; listing and card grid before directory nav CTA.' );
	}

	public static function child_detail_profile_trust_02(): array {
		$keys = array( 'hero_cred_01', 'tp_trust_band_01', 'mlp_profile_summary_01', 'tp_testimonial_01', 'cta_consultation_01', 'tp_guarantee_01', 'fb_why_choose_01', 'cta_contact_01', 'mlp_team_grid_01', 'tp_client_logo_01', 'cta_directory_nav_01', 'ptf_expectations_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_profile_trust_02', 'Profile detail (trust v2)', 'Child/detail profile trust v2: cred hero, trust band, profile summary, testimonial, consultation CTA, guarantee, why choose, contact CTA, team grid, client logos, directory nav CTA, expectations, booking CTA, panel, contact CTA.', 'profile_page', 'profiles', array( 'profiles' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Profile detail trust v2. Trust band and testimonial before first CTA; team grid and logos.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Trust-first; different order than PT-09 profile_proof_01.', 'cta_direction_summary' => 'Consultation, contact, directory nav, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Trust v2; trust band and profile summary lead.' );
	}

	public static function child_detail_profile_educational_02(): array {
		$keys = array( 'hero_cred_01', 'ptf_how_it_works_01', 'mlp_profile_summary_01', 'fb_value_prop_01', 'cta_consultation_01', 'ptf_expectations_01', 'fb_why_choose_01', 'cta_contact_01', 'mlp_team_grid_01', 'tp_testimonial_01', 'cta_directory_nav_01', 'tp_trust_band_01', 'cta_booking_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_profile_educational_02', 'Profile detail (educational v2)', 'Child/detail profile educational v2: cred hero, how-it-works, profile summary, value prop, consultation CTA, expectations, why choose, contact CTA, team grid, testimonial, directory nav CTA, trust band, booking CTA, panel, contact CTA.', 'profile_page', 'profiles', array( 'profiles' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Profile detail educational v2. How-it-works and expectations before first CTA; team grid and testimonial.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Education-first; different order than PT-09 profile_first_01.', 'cta_direction_summary' => 'Consultation, contact, directory nav, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Educational v2; how-it-works and expectations lead.' );
	}

	public static function child_detail_profile_media_02(): array {
		$keys = array( 'hero_cred_01', 'mlp_gallery_01', 'mlp_profile_summary_01', 'cta_consultation_01', 'fb_value_prop_01', 'mlp_media_band_01', 'cta_contact_01', 'mlp_team_grid_01', 'tp_testimonial_01', 'cta_directory_nav_01', 'ptf_how_it_works_01', 'cta_booking_01', 'mlp_related_content_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_profile_media_02', 'Profile detail (media v2)', 'Child/detail profile media v2: cred hero, gallery, profile summary, consultation CTA, value prop, media band, contact CTA, team grid, testimonial, directory nav CTA, how-it-works, booking CTA, related content, panel, contact CTA.', 'profile_page', 'profiles', array( 'profiles' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Profile detail media v2. Gallery and media band; consultation, contact, directory nav, booking, contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Media-first; different order than PT-09 profile_media_01.', 'cta_direction_summary' => 'Consultation, contact, directory nav, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Media v2; gallery and media band early.' );
	}

	// ---------- Resource / informational ----------

	public static function child_detail_resource_detail_02(): array {
		$keys = array( 'hero_edu_01', 'fb_value_prop_01', 'ptf_how_it_works_01', 'cta_consultation_01', 'mlp_related_content_01', 'fb_benefit_band_01', 'cta_contact_01', 'ptf_expectations_01', 'tp_testimonial_01', 'cta_product_detail_01', 'tp_trust_band_01', 'fb_why_choose_01', 'cta_quote_request_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_resource_detail_02', 'Resource detail v2', 'Child/detail resource v2: edu hero, value prop, how-it-works, consultation CTA, related content, benefit band, contact CTA, expectations, testimonial, product CTA, trust band, why choose, quote CTA, panel, contact CTA.', 'informational_detail', 'informational', array( 'informational' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Resource detail v2. Value prop and how-it-works first; consultation, contact, product, quote, contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Resource emphasis; different order than PT-09 resource_article_01.', 'cta_direction_summary' => 'Consultation, contact, product detail, quote, contact; last CTA contact.' ),
			'Requires section library.', 'Resource v2; value prop and related content.' );
	}

	public static function child_detail_article_02(): array {
		$keys = array( 'hero_edu_01', 'mlp_related_content_01', 'fb_value_prop_01', 'cta_contact_01', 'ptf_how_it_works_01', 'fb_benefit_band_01', 'cta_consultation_01', 'tp_testimonial_01', 'ptf_expectations_01', 'cta_product_detail_01', 'tp_trust_band_01', 'cta_quote_request_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_article_02', 'Article detail v2', 'Child/detail article v2: edu hero, related content, value prop, contact CTA, how-it-works, benefit band, consultation CTA, testimonial, expectations, product CTA, trust band, quote CTA, panel, contact CTA.', 'informational_detail', 'informational', array( 'informational' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Article detail v2. Related content and value prop first; contact, consultation, product, quote, contact CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Article emphasis; different order than PT-09 resource_article_01.', 'cta_direction_summary' => 'Contact, consultation, product detail, quote, contact; last CTA contact.' ),
			'Requires section library.', 'Article v2; related content and value prop lead.' );
	}

	public static function child_detail_informational_educational_02(): array {
		$keys = array( 'hero_edu_01', 'ptf_how_it_works_01', 'ptf_expectations_01', 'fb_value_prop_01', 'cta_consultation_01', 'fb_benefit_band_01', 'tp_testimonial_01', 'cta_contact_01', 'ptf_service_flow_01', 'fb_why_choose_01', 'cta_quote_request_01', 'tp_trust_band_01', 'cta_product_detail_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_informational_educational_02', 'Informational detail (educational v2)', 'Child/detail informational educational v2: edu hero, how-it-works, expectations, value prop, consultation CTA, benefit band, testimonial, contact CTA, service flow, why choose, quote CTA, trust band, product CTA, panel, contact CTA.', 'informational_detail', 'informational', array( 'informational' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Informational educational v2. How-it-works and expectations before first CTA; service flow and why-choose.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Education-first; different order than PT-07 informational_educational_01.', 'cta_direction_summary' => 'Consultation, contact, quote, product detail, contact; last CTA contact.' ),
			'Requires section library.', 'Informational educational v2; process and expectations lead.' );
	}

	public static function child_detail_authority_detail_02(): array {
		$keys = array( 'hero_cred_01', 'fb_value_prop_01', 'tp_trust_band_01', 'cta_consultation_01', 'ptf_how_it_works_01', 'tp_testimonial_01', 'cta_contact_01', 'fb_why_choose_01', 'tp_client_logo_01', 'cta_directory_nav_01', 'ptf_expectations_01', 'cta_booking_01', 'mlp_related_content_01', 'lpu_contact_panel_01', 'cta_contact_02' );
		$r = self::ordered_and_requirements( $keys );
		return self::base_template( 'child_detail_authority_detail_02', 'Authority detail v2', 'Child/detail authority v2: cred hero, value prop, trust band, consultation CTA, how-it-works, testimonial, contact CTA, why choose, client logos, directory nav CTA, expectations, booking CTA, related content, panel, contact CTA.', 'informational_detail', 'informational', array( 'informational', 'profiles' ), $r['ordered'], $r['requirements'],
			array( 'page_purpose_summary' => 'Authority detail v2. Value prop and trust band before first CTA; directory nav and booking CTAs.', 'section_helper_order' => 'same_as_template', 'page_flow_explanation' => 'Authority emphasis; different order than PT-09 authority_detail_01.', 'cta_direction_summary' => 'Consultation, contact, directory nav, booking, contact; last CTA contact.' ),
			'Requires section library.', 'Authority v2; value prop and trust band lead.' );
	}
}
