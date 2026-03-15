<?php
/**
 * Page one-pager overlays for cosmetology/nail (industry-page-onepager-overlay-schema, Prompt 354).
 * Home, About, Contact, Services. Additive to base one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_home_conversion_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Home as primary entry; lead with booking or services. No local-page hierarchy required.',
		'cta_strategy'       => 'Primary: book now or see availability. Secondary: view services or contact. Avoid emergency-style CTAs.',
		'lpagery_seo_notes'  => 'Local SEO optional; focus on service-area or neighborhood if multiple locations.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_about_story_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'About supports trust and team; link to staff or gallery where relevant.',
		'cta_strategy'       => 'Support with single booking or contact CTA; avoid multiple competing actions.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_contact_request_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'cta_strategy'       => 'Use for inquiries or appointment requests; set expectation for response or booking follow-up.',
		'lpagery_seo_notes'  => 'Contact page supports local and service intent.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_services_overview_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Services hub; child pages per service or treatment. Gallery and booking emphasis.',
		'cta_strategy'       => 'Book now or view availability per service; gallery-to-booking pattern where applicable.',
	),
);
