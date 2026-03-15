<?php
/**
 * Page one-pager overlays for disaster recovery/restoration (industry-page-onepager-overlay-schema, Prompt 354).
 * Home, About, Contact, Services. Additive to base one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'pt_home_conversion_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Home as primary; 24/7 and emergency emphasis. Service-area and response-type hierarchy.',
		'cta_strategy'       => 'Primary: call now or 24/7 emergency. Secondary: insurance/claims info or assessment request.',
		'lpagery_seo_notes'  => 'Local and disaster-type (water, fire, mold) intent; urgency and certification signals.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'pt_about_story_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'About supports certification (e.g. IICRC), insurance coordination, and response capability.',
		'cta_strategy'       => 'Call now or emergency CTA; insurance assistance as secondary where relevant.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'pt_contact_request_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'cta_strategy'       => 'Emergency: call-now over form. Form for insurance coordination or non-urgent assessment.',
		'lpagery_seo_notes'  => 'Contact supports emergency and local intent.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'pt_services_overview_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Services hub: response types (water, fire, mold). Service-area and commercial/residential nuance.',
		'cta_strategy'       => 'Call now and emergency; insurance/claims assistance where applicable.',
	),
);
