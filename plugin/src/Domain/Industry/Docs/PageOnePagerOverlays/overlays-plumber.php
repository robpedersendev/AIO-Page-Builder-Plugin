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
		'industry_key'      => 'plumber',
		'page_template_key' => 'pt_home_conversion_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Home as primary; emergency vs scheduled. Service-area pages support findability.',
		'cta_strategy'      => 'Primary: call now or emergency. Secondary: schedule service or contact. Call-now central.',
		'lpagery_seo_notes' => 'Local and service-area pages; 24/7 and emergency intent where applicable.',
	),
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'pt_about_story_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'About supports trust: license, insurance, guarantees.',
		'cta_strategy'      => 'Call now or schedule; avoid low-urgency-only CTAs.',
	),
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'pt_contact_request_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'cta_strategy'      => 'Emergency: prefer prominent call-now. Form for scheduling or general inquiry.',
		'lpagery_seo_notes' => 'Contact and service-area support local intent.',
	),
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'pt_services_overview_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Services hub; child pages per service type. Service-area hierarchy.',
		'cta_strategy'      => 'Call now and schedule; trust and financing messaging where relevant.',
	),
	// * Second-wave (Prompt 402): service-area, service-detail, financing, emergency.
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'hub_geo_service_area_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Service-area hub; coverage and local hierarchy. Child pages per area or city where relevant.',
		'cta_strategy'      => 'Call now or schedule per area; one primary CTA.',
		'lpagery_seo_notes' => 'Service area and city names support local SEO; 24/7 where applicable.',
	),
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'child_detail_service_conversion_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Service detail; child of services hub. Emergency vs scheduled nuance.',
		'cta_strategy'      => 'Call now for emergency; schedule or callback for non-urgent. Single primary CTA.',
		'lpagery_seo_notes' => 'Service type and area support local and intent.',
	),
	array(
		'industry_key'        => 'plumber',
		'page_template_key'   => 'pt_offerings_compare_01',
		'scope'               => 'page_onepager_overlay',
		'status'              => 'active',
		'hierarchy_hints'     => 'Financing or tier comparison; supports trust and conversion. Link to call or schedule.',
		'cta_strategy'        => 'Call now or schedule; financing messaging where offered. Avoid low-urgency-only CTAs.',
		'compliance_cautions' => 'Financing and guarantee claims must be accurate; jurisdiction rules may apply.',
	),
	array(
		'industry_key'      => 'plumber',
		'page_template_key' => 'child_detail_service_booking_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Booking or schedule flow; child of services. Emergency path should emphasize call-now.',
		'cta_strategy'      => 'Schedule or call now; one clear action. Emergency: call over form.',
		'lpagery_seo_notes' => 'Service and area support local intent.',
	),
);
