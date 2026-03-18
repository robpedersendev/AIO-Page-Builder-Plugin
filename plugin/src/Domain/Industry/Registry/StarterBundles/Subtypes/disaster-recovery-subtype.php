<?php
/**
 * Subtype starter bundles for disaster recovery (subtype-starter-bundle-contract.md; Prompt 429).
 * Residential and commercial restoration.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'disaster_recovery_residential_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'disaster_recovery',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_residential',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Residential Restoration Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for residential restoration: home, services, contact with emergency line, assessment, and claim-assistance emphasis. Homeowner and insurance focus.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'contact', 'support' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_conversion_01',
			'pt_services_overview_01',
			'pt_contact_request_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_conv_02',
			'tp_trust_band_01',
			'tp_reassurance_01',
			'cta_consultation_01',
			'ptf_how_it_works_01',
			'fb_benefit_band_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'disaster_recovery_urgency',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'emergency_dispatch',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'disaster_recovery_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 41 ),
	),
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'disaster_recovery_commercial_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'disaster_recovery',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_commercial',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Commercial Restoration Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for commercial restoration: landing, services, contact with 24/7 commercial line, commercial assessment, and business-continuity emphasis.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'contact', 'support' ),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS => array(
			'pt_home_conversion_01',
			'pt_services_overview_01',
			'pt_contact_request_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS => array(
			'hero_conv_02',
			'tp_trust_band_01',
			'tp_certification_01',
			'cta_consultation_01',
			'ptf_how_it_works_01',
			'fb_benefit_band_01',
			'lpu_contact_panel_01',
		),
		Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF => 'disaster_recovery_urgency',
		Industry_Starter_Bundle_Registry::FIELD_CTA_GUIDANCE_REF => 'emergency_dispatch',
		Industry_Starter_Bundle_Registry::FIELD_LPAGERY_GUIDANCE_REF => 'disaster_recovery_01',
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 42 ),
	),
);
