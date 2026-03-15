<?php
/**
 * Disaster Recovery / Restoration industry pack definition (industry-pack-schema.md, Prompt 352).
 * Emergency-response posture, insurance/claims assistance, certification/trust, service-area urgency. No insurance API or dispatch in this pack.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'disaster_recovery',
		'name'            => 'Disaster Recovery / Restoration',
		'summary'         => 'Water, fire, mold, and restoration contractors: emergency response, 24/7 emphasis, insurance and claims-assistance messaging, certification and trust signals, service-area urgency hierarchy. Commercial and residential nuance.',
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

		'preferred_cta_patterns'   => array( 'emergency_dispatch', 'call_now', 'claim_assistance' ),
		'required_cta_patterns'   => array( 'emergency_dispatch', 'call_now' ),
		'discouraged_cta_patterns' => array( 'valuation_request', 'gallery_to_booking' ),

		'seo_guidance_ref' => 'disaster_recovery',
		'token_preset_ref'  => 'disaster_recovery_urgency',
		'lpagery_rule_ref'  => 'disaster_recovery_01',

		'metadata' => array(
			'notes_emergency'   => 'Emergency response and 24/7 availability central; urgency hierarchy in service-area pages.',
			'notes_insurance'   => 'Insurance and claims-assistance messaging preferred; no API or lead-routing in pack.',
			'notes_certification' => 'Certification, IICRC-style credentials, and trust signals emphasized.',
			'notes_service_area' => 'Service-area and local pages support findability and urgency; commercial vs residential nuance.',
		),
	),
);
