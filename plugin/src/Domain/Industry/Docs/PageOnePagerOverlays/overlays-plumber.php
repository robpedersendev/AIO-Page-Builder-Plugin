<?php
/**
 * Page one-pager overlays for plumber (industry-page-onepager-overlay-schema, Prompt 354).
 * Home, About, Contact, Services. Additive to base one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'       => 'plumber',
		'page_template_key'  => 'pt_home_conversion_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Home as primary; emergency vs scheduled. Service-area pages support findability.',
		'cta_strategy'       => 'Primary: call now or emergency. Secondary: schedule service or contact. Call-now central.',
		'lpagery_seo_notes'  => 'Local and service-area pages; 24/7 and emergency intent where applicable.',
	),
	array(
		'industry_key'       => 'plumber',
		'page_template_key'  => 'pt_about_story_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'About supports trust: license, insurance, guarantees.',
		'cta_strategy'       => 'Call now or schedule; avoid low-urgency-only CTAs.',
	),
	array(
		'industry_key'       => 'plumber',
		'page_template_key'  => 'pt_contact_request_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'cta_strategy'       => 'Emergency: prefer prominent call-now. Form for scheduling or general inquiry.',
		'lpagery_seo_notes'  => 'Contact and service-area support local intent.',
	),
	array(
		'industry_key'       => 'plumber',
		'page_template_key'  => 'pt_services_overview_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Services hub; child pages per service type. Service-area hierarchy.',
		'cta_strategy'       => 'Call now and schedule; trust and financing messaging where relevant.',
	),
);
