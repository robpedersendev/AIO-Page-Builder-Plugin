<?php
/**
 * Subtype starter bundles for cosmetology/nail (subtype-starter-bundle-contract.md; Prompt 429).
 * Luxury salon and mobile nail tech.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'cosmetology_nail_luxury_salon_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_luxury_salon',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Luxury Nail Salon Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for in-salon luxury nail experiences: home, services, about, contact with reserve-your-experience and visit emphasis. Refined tone and single primary CTA.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'about', 'contact', 'offerings' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_conversion_01',
			'pt_services_overview_01',
			'pt_about_story_01',
			'pt_contact_request_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_conv_02',
			'tp_testimonial_02',
			'cta_booking_01',
			'fb_benefit_band_01',
			'mlp_card_grid_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'cosmetology_elegant',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'book_now',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'cosmetology_nail_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 11 ),
	),
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'cosmetology_nail_mobile_tech_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Mobile Nail Tech Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for mobile nail technicians: home, services, contact with service-area and book-at-your-location emphasis. Travel-to-you and convenience focus.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'contact', 'offerings' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_conversion_01',
			'pt_services_overview_01',
			'pt_contact_request_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_conv_02',
			'cta_booking_01',
			'mlp_location_info_01',
			'fb_benefit_band_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'cosmetology_elegant',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'book_now',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'cosmetology_nail_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 12 ),
	),
);
