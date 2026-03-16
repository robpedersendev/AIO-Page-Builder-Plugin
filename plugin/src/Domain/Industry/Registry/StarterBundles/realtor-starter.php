<?php
/**
 * Realtor starter bundle (industry-starter-bundle-schema.md, Prompt 387).
 * Curated starting set: home, services, about, contact; valuation and consult CTAs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => 'realtor_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
		Industry_Starter_Bundle_Registry::FIELD_LABEL       => 'Realtor Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY     => 'A practical starting set for real estate agents and brokerages: home, services, about, and contact with valuation and consultation emphasis. Fits buyer/seller and local market focus.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS      => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,

		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'about', 'contact', 'resource', 'buyer_guide' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_trust_01',
			'pt_services_overview_01',
			'pt_about_team_01',
			'pt_contact_request_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_cred_01',
			'tp_testimonial_01',
			'cta_consultation_01',
			'fb_why_choose_01',
			'ptf_faq_01',
			'mlp_team_grid_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'realtor_warm',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'valuation_request',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'realtor_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA => array( 'sort_order' => 20 ),
	),
);
