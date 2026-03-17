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
	// * Second-wave (Prompt 402): booking, pricing, location, gallery, service-detail.
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'child_detail_service_booking_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Child of services hub; single booking or appointment flow. Link from gallery or service list.',
		'cta_strategy'       => 'Single clear booking CTA; avoid competing learn-more or contact.',
		'lpagery_seo_notes'  => 'Service name and location support local and treatment intent.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_offerings_overview_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Pricing or package hub; child pages per package or tier where relevant.',
		'cta_strategy'       => 'Book now or see availability per package; one primary CTA.',
		'compliance_cautions' => 'Pricing must be accurate; avoid misleading offers or hidden fees.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_contact_directions_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Location and directions; supports multi-location or single salon. Child of contact or about where relevant.',
		'cta_strategy'       => 'Pair with book-now or contact; one clear next step.',
		'lpagery_seo_notes'  => 'Address and area support local SEO; keep NAP consistent.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'pt_home_media_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Gallery or portfolio page; can sit under home or services. Lead to booking or service detail.',
		'cta_strategy'       => 'Gallery-to-booking or view-services CTA; avoid generic learn-more.',
		'lpagery_seo_notes'  => 'Image alt text and captions support service and local intent.',
	),
	array(
		'industry_key'       => 'cosmetology_nail',
		'page_template_key'  => 'child_detail_treatment_detail_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Service or treatment detail; child of services hub. Proof and booking emphasis.',
		'cta_strategy'       => 'Book this treatment or see availability; single primary CTA.',
		'lpagery_seo_notes'  => 'Treatment name and location support local and service intent.',
	),
);
