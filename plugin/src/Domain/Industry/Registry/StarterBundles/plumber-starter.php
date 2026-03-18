<?php
/**
 * Plumber starter bundle (industry-starter-bundle-schema.md, Prompt 387).
 * Curated starting set: home, services, contact; call/scheduled-service and trust emphasis.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'plumber_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'plumber',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Plumber Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'A practical starting set for plumbing and trade businesses: home, services overview, and contact with call-now and scheduled-service CTAs. Trust and credentials emphasis.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,

		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'contact', 'faq' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_conversion_01',
			'pt_services_overview_01',
			'pt_contact_request_01',
			'pt_faq_support_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_conv_02',
			'tp_trust_band_01',
			'cta_booking_01',
			'ptf_how_it_works_01',
			'cta_consultation_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'plumber_trust',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'call_now',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'plumber_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 30 ),
	),
);
