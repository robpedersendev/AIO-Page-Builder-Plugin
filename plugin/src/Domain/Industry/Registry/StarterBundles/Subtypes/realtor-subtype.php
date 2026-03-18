<?php
/**
 * Subtype starter bundles for realtor (subtype-starter-bundle-contract.md; Prompt 429).
 * Buyer-focused and listing (seller) agent.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;

return array(
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'realtor_buyer_agent_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Buyer Agent Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for buyer-focused agents: home, services, about, contact with search support, buyer updates, and buyer consultation emphasis. No seller-only framing.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
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
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 21 ),
	),
	array(
		Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'realtor_listing_agent_starter',
		Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
		Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY => 'realtor_listing_agent',
		Industry_Starter_Bundle_Registry::FIELD_LABEL      => 'Listing Agent Starter',
		Industry_Starter_Bundle_Registry::FIELD_SUMMARY    => 'Starting set for listing (seller) agents: home, services, about, contact with home value, list-with-me, and listing consultation emphasis. No buyer-only framing.',
		Industry_Starter_Bundle_Registry::FIELD_STATUS     => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
		Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES => array( 'home', 'services', 'about', 'contact', 'resource' ),
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
		Industry_Starter_Bundle_Registry::FIELD_METADATA   => array( 'sort_order' => 22 ),
	),
);
