<?php
/**
 * Cosmetology / Nail Technician industry pack definition (industry-pack-schema.md, Prompt 349).
 * Booking-centered, gallery and staff emphasis; LPagery optional. No booking integrations in this pack.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'cosmetology_nail',
		'name'            => 'Cosmetology / Nail Technician',
		'summary'         => 'Salon, spa, and nail businesses: booking-centered services, gallery and staff profiles, promotions. Local pages optional; service-area hubs possible.',
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
		),
		'preferred_section_keys'   => array(),
		'discouraged_section_keys' => array(),

		'preferred_cta_patterns'   => array( 'book_now', 'gallery_to_booking', 'consult' ),
		'required_cta_patterns'   => array( 'book_now' ),
		'discouraged_cta_patterns' => array( 'emergency_dispatch', 'claim_assistance' ),

		'seo_guidance_ref' => 'cosmetology_nail',
		'token_preset_ref'  => 'cosmetology_elegant',
		'lpagery_rule_ref'  => 'cosmetology_nail_01',

		'metadata' => array(
			'notes_booking'   => 'Emphasis on booking CTAs and appointment flow; no integration logic in pack.',
			'notes_gallery'   => 'Gallery and portfolio sections preferred for services and results.',
			'notes_staff'     => 'Staff/team profiles and bios support trust and booking.',
			'notes_promotion' => 'Promotions and seasonal offers are common; CTA patterns support them.',
		),
	),
);
