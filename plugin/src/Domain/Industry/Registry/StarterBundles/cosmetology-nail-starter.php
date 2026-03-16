<?php
/**
 * Cosmetology / Nail starter bundle (industry-starter-bundle-schema.md, Prompt 387).
 * Curated starting set: home, services, about, contact; booking and gallery emphasis.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => 'cosmetology_nail_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'cosmetology_nail',
		Industry_Starter_Bundle_Registry::FIELD_LABEL       => 'Salon & Nail Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY     => 'A practical starting set for salon and nail businesses: home, services overview, about, and contact with booking and gallery emphasis. Use this to jump-start your site structure.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS      => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
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
		Industry_Starter_Bundle_Registry::FIELD_METADATA => array( 'sort_order' => 10 ),
	),
);
