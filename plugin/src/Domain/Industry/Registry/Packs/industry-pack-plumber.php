<?php
/**
 * Plumber industry pack definition (industry-pack-schema.md, Prompt 351).
 * Emergency vs scheduled service posture, trust and financing emphasis, service-area hierarchy, direct-response CTAs. No dispatch/telephony in this pack.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'plumber',
		'name'            => 'Plumber',
		'summary'         => 'Plumbing contractors: emergency and scheduled service, call-now and book-now CTAs, trust and financing emphasis, service-area pages. Direct-response and urgency patterns.',
		'status'          => 'active',
		'version_marker'  => '1',

		'supported_page_families' => array(
			'home',
			'about',
			'services',
			'offerings',
			'contact',
			'faq',
			'support',
			'resource',
			'authority',
			'utility',
		),
		'preferred_section_keys'   => array(),
		'discouraged_section_keys' => array(),

		'preferred_cta_patterns'   => array( 'call_now', 'book_now', 'emergency_dispatch' ),
		'required_cta_patterns'   => array( 'call_now' ),
		'discouraged_cta_patterns' => array( 'valuation_request', 'gallery_to_booking' ),

		'seo_guidance_ref' => 'plumber',
		'token_preset_ref'  => 'plumber_trust',
		'lpagery_rule_ref'  => 'plumber_01',

		'metadata' => array(
			'notes_emergency'   => 'Emergency vs scheduled service posture; call-now CTA central.',
			'notes_trust'       => 'Trust, licensing, and guarantees emphasized; financing options common.',
			'notes_service_area'=> 'Service-area hierarchy and local pages support findability.',
			'notes_direct'      => 'Direct-response and urgency framing; no dispatch integration in pack.',
		),
	),
);
