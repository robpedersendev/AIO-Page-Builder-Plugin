<?php
/**
 * Section-helper overlays for realtor (industry-section-helper-overlay-schema, Prompt 353).
 * Hero, CTA, Proof, Contact/Form, Feature/benefit. Additive to base helper docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'hero_conv_02',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Professional and approachable. Emphasize market expertise and client focus; avoid hype or pressure.',
		'cta_usage_notes' => 'Primary: home value/valuation or contact. Secondary: search listings or buyer/seller resources. Align with market focus.',
		'seo_notes'       => 'Headline can include area or service (e.g. buyer/seller); supports local and intent signals.',
	),
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'cta_booking_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Clear and low-pressure. Use for consultation or home valuation requests.',
		'cta_usage_notes' => 'Frame as "Get your home value", "Schedule a consultation", or "Contact for a free assessment". Avoid generic "Book now" where valuation fits.',
		'seo_notes'       => 'CTA label should reflect valuation or consultation intent for local SEO.',
	),
	array(
		'industry_key'        => 'realtor',
		'section_key'         => 'tp_badge_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Credibility and results. Use real designations, awards, and production or client-satisfaction metrics.',
		'compliance_cautions' => 'Do not imply endorsement or certification without permission. MLS and board rules may apply to claims.',
		'media_notes'         => 'Badge or logo images must be accurate; alt text should name the credential or organization.',
	),
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'gc_contact_form_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Professional and responsive. Set expectations for callback or email response.',
		'cta_usage_notes' => 'Use for listing inquiries, buyer/seller questions, or valuation requests. One clear next step.',
		'seo_notes'       => 'Headline can specify inquiry type (e.g. "Ask about your home", "Buyer inquiry").',
	),
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'gc_offer_value_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Value and differentiation. Emphasize process, market knowledge, and client outcomes.',
		'cta_usage_notes' => 'Support with valuation or contact CTA; avoid multiple competing offers.',
		'seo_notes'       => 'Value props can reinforce local expertise and service area.',
	),
	// * Second-wave (Prompt 401): profile, listing, location, proof.
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'mlp_profile_summary_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Professional and approachable. Emphasize experience, designations, and client focus.',
		'cta_usage_notes' => 'Support with contact or valuation CTA; one clear next step.',
		'seo_notes'       => 'Bio and credentials support authority and local trust; MLS/board compliance for claims.',
	),
	array(
		'industry_key'        => 'realtor',
		'section_key'         => 'mlp_listing_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Clear and accurate. Listings must reflect current or representative inventory; avoid misleading availability.',
		'cta_usage_notes'     => 'Link to listing detail or contact for inquiry; align with MLS and board rules.',
		'seo_notes'           => 'Listing context supports local and property-type intent; do not duplicate full listing content.',
		'compliance_cautions' => 'MLS and board rules may govern how listings are displayed or linked; verify permitted use.',
	),
	array(
		'industry_key'    => 'realtor',
		'section_key'     => 'mlp_location_info_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Informative and neighborhood-focused. Emphasize area benefits and service territory.',
		'cta_usage_notes' => 'Pair with search-listings or contact CTA; one clear next step.',
		'seo_notes'       => 'Location and neighborhood names support local SEO; keep format consistent.',
	),
	array(
		'industry_key'        => 'realtor',
		'section_key'         => 'tp_certification_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Credible and results-oriented. Use real designations, certifications, and production metrics.',
		'compliance_cautions' => 'Do not imply endorsement without permission. MLS and board rules may apply to credential claims.',
		'media_notes'         => 'Certification images must match the named credential; provide meaningful alt text.',
	),
);
