<?php
/**
 * Realtor industry pack definition (industry-pack-schema.md, Prompt 350).
 * Buyer/seller focus, valuation CTAs, market-area hierarchy, neighborhood/local-page strategy. No IDX/MLS in this pack.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'             => 'realtor',
		'name'                     => 'Realtor',
		'summary'                  => 'Real estate agents and brokerages: buyer/seller services, valuation CTAs, market-area and neighborhood pages. Local and hierarchy-oriented; agent bio and testimonial emphasis.',
		'status'                   => 'active',
		'version_marker'           => '1',

		'supported_page_families'  => array(
			'home',
			'about',
			'services',
			'offerings',
			'contact',
			'faq',
			'resource',
			'authority',
			'comparison',
			'buyer_guide',
		),
		'preferred_section_keys'   => array(),
		'discouraged_section_keys' => array(),

		'preferred_cta_patterns'   => array( 'valuation_request', 'consult', 'book_now' ),
		'required_cta_patterns'    => array( 'valuation_request' ),
		'discouraged_cta_patterns' => array( 'emergency_dispatch', 'claim_assistance' ),

		'seo_guidance_ref'         => 'realtor',
		'token_preset_ref'         => 'realtor_warm',
		'lpagery_rule_ref'         => 'realtor_01',

		'metadata'                 => array(
			'notes_buyer_seller' => 'Buyer and seller service pages; dual focus supported by page families.',
			'notes_valuation'    => 'Valuation / home value CTA is central; no MLS/IDX in pack.',
			'notes_neighborhood' => 'Neighborhood and local-area pages fit hierarchy and LPagery posture.',
			'notes_agent_bio'    => 'Agent bio and team sections support trust and conversion.',
			'notes_testimonial'  => 'Testimonials and reviews emphasized for credibility.',
		),
	),
);
