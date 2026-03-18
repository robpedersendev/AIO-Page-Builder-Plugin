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
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_home_conversion_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Home as primary; valuation and buyer/seller paths. Neighborhood or area pages fit hierarchy.',
		'cta_strategy'      => 'Primary: home value/valuation or contact. Secondary: search listings or buyer/seller resources.',
		'lpagery_seo_notes' => 'Local and market-area pages support findability; agent bio and testimonials support conversion.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_about_story_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'About supports agent/team credibility; link to market focus and service areas.',
		'cta_strategy'      => 'Valuation or contact CTA; avoid generic learn-more.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_contact_request_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'cta_strategy'      => 'Use for listing inquiries, buyer/seller questions, or valuation requests. One clear next step.',
		'lpagery_seo_notes' => 'Contact supports local and intent signals.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_services_overview_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Services hub: buyer vs seller vs valuation. Neighborhood or area child pages where relevant.',
		'cta_strategy'      => 'Valuation request and contact; align with market focus.',
	),
	// * Second-wave (Prompt 402): valuation, neighborhood, buyer, seller, local-market.
	array(
		'industry_key'        => 'realtor',
		'page_template_key'   => 'pt_offerings_compare_01',
		'scope'               => 'page_onepager_overlay',
		'status'              => 'active',
		'hierarchy_hints'     => 'Use for service-tier or valuation comparison; supports buyer/seller decision. Link to valuation request or contact.',
		'cta_strategy'        => 'Primary: valuation request or contact. Align with pack valuation_request.',
		'compliance_cautions' => 'MLS and board rules may govern comparison or CMA language; verify permitted claims.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'hub_geo_neighborhood_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Neighborhood or market-area hub; child pages per area. Supports local and buyer/seller intent.',
		'cta_strategy'      => 'Valuation or contact CTA per area; one clear next step.',
		'lpagery_seo_notes' => 'Neighborhood and area names support local SEO; align with market focus.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_buyer_guide_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Buyer resource; supports authority and conversion. Link to valuation or contact.',
		'cta_strategy'      => 'Valuation request or contact for buyers; avoid generic learn-more.',
		'lpagery_seo_notes' => 'Buyer intent and local market support findability.',
	),
	array(
		'industry_key'      => 'realtor',
		'page_template_key' => 'pt_services_value_01',
		'scope'             => 'page_onepager_overlay',
		'status'            => 'active',
		'hierarchy_hints'   => 'Seller or listing value page; supports seller conversion. Link to valuation or listing inquiry.',
		'cta_strategy'      => 'Valuation request or list-with-me CTA; align with seller focus.',
		'lpagery_seo_notes' => 'Seller and local market support intent.',
	),
	array(
		'industry_key'        => 'realtor',
		'page_template_key'   => 'hub_geo_coverage_listing_01',
		'scope'               => 'page_onepager_overlay',
		'status'              => 'active',
		'hierarchy_hints'     => 'Local market or coverage listing; service-area hierarchy. Child pages per area or city.',
		'cta_strategy'        => 'Valuation or contact per area; one primary CTA.',
		'lpagery_seo_notes'   => 'Coverage and area names support local SEO; MLS rules may apply to listing display.',
		'compliance_cautions' => 'MLS and board rules may govern listing and coverage claims.',
	),
);
