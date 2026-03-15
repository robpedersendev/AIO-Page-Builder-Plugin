<?php
/**
 * Page one-pager overlays for realtor (industry-page-onepager-overlay-schema, Prompt 354).
 * Home, About, Contact, Services. Additive to base one-pagers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'       => 'realtor',
		'page_template_key'  => 'pt_home_conversion_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Home as primary; valuation and buyer/seller paths. Neighborhood or area pages fit hierarchy.',
		'cta_strategy'       => 'Primary: home value/valuation or contact. Secondary: search listings or buyer/seller resources.',
		'lpagery_seo_notes'  => 'Local and market-area pages support findability; agent bio and testimonials support conversion.',
	),
	array(
		'industry_key'       => 'realtor',
		'page_template_key'  => 'pt_about_story_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'About supports agent/team credibility; link to market focus and service areas.',
		'cta_strategy'       => 'Valuation or contact CTA; avoid generic learn-more.',
	),
	array(
		'industry_key'       => 'realtor',
		'page_template_key'  => 'pt_contact_request_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'cta_strategy'       => 'Use for listing inquiries, buyer/seller questions, or valuation requests. One clear next step.',
		'lpagery_seo_notes'  => 'Contact supports local and intent signals.',
	),
	array(
		'industry_key'       => 'realtor',
		'page_template_key'  => 'pt_services_overview_01',
		'scope'              => 'page_onepager_overlay',
		'status'             => 'active',
		'hierarchy_hints'    => 'Services hub: buyer vs seller vs valuation. Neighborhood or area child pages where relevant.',
		'cta_strategy'       => 'Valuation request and contact; align with market focus.',
	),
);
