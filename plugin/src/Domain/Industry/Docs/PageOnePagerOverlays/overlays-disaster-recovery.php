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
	// * Second-wave (Prompt 402): emergency-response, service-area, commercial, insurance-assistance.
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'hub_geo_service_area_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Service-area hub; coverage and response hierarchy. Child pages per area or response type.',
		'cta_strategy'       => 'Call now or emergency per area; insurance assistance as secondary where relevant.',
		'lpagery_seo_notes'  => 'Area and disaster-type (water, fire, mold) support local and intent.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'hub_geo_area_trust_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Trust or commercial/residential nuance; certification and insurance coordination. Supports conversion.',
		'cta_strategy'       => 'Call now or emergency; insurance/claims assistance where applicable.',
		'compliance_cautions' => 'Certification (e.g. IICRC) and insurance claims must be accurate; do not imply endorsement without permission.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'pt_support_help_02',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Insurance or claims-assistance resource; supports help-oriented flow. Link to call or emergency.',
		'cta_strategy'       => 'Call now or insurance coordination CTA; avoid low-urgency-only.',
		'lpagery_seo_notes'  => 'Insurance and claims intent support findability; 24/7 where applicable.',
		'compliance_cautions' => 'Insurance assistance claims must be accurate; do not provide legal advice.',
	),
	array(
		'industry_key'       => 'disaster_recovery',
		'page_template_key'  => 'child_detail_service_conversion_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Emergency or response-type detail; child of services hub. 24/7 and call-now emphasis.',
		'cta_strategy'       => 'Call now or 24/7 emergency; insurance coordination as secondary. Single primary CTA.',
		'lpagery_seo_notes'  => 'Response type (water, fire, mold) and area support local and intent.',
	),
);
