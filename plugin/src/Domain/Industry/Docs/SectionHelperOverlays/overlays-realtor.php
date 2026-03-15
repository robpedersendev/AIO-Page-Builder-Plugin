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
		'industry_key' => 'realtor',
		'section_key'  => 'hero_conv_02',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Professional and approachable. Emphasize market expertise and client focus; avoid hype or pressure.',
		'cta_usage_notes' => 'Primary: home value/valuation or contact. Secondary: search listings or buyer/seller resources. Align with market focus.',
		'seo_notes'    => 'Headline can include area or service (e.g. buyer/seller); supports local and intent signals.',
	),
	array(
		'industry_key' => 'realtor',
		'section_key'  => 'cta_booking_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Clear and low-pressure. Use for consultation or home valuation requests.',
		'cta_usage_notes' => 'Frame as "Get your home value", "Schedule a consultation", or "Contact for a free assessment". Avoid generic "Book now" where valuation fits.',
		'seo_notes'    => 'CTA label should reflect valuation or consultation intent for local SEO.',
	),
	array(
		'industry_key' => 'realtor',
		'section_key'  => 'tp_badge_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Credibility and results. Use real designations, awards, and production or client-satisfaction metrics.',
		'compliance_cautions' => 'Do not imply endorsement or certification without permission. MLS and board rules may apply to claims.',
		'media_notes'  => 'Badge or logo images must be accurate; alt text should name the credential or organization.',
	),
	array(
		'industry_key' => 'realtor',
		'section_key'  => 'gc_contact_form_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Professional and responsive. Set expectations for callback or email response.',
		'cta_usage_notes' => 'Use for listing inquiries, buyer/seller questions, or valuation requests. One clear next step.',
		'seo_notes'    => 'Headline can specify inquiry type (e.g. "Ask about your home", "Buyer inquiry").',
	),
	array(
		'industry_key' => 'realtor',
		'section_key'  => 'gc_offer_value_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Value and differentiation. Emphasize process, market knowledge, and client outcomes.',
		'cta_usage_notes' => 'Support with valuation or contact CTA; avoid multiple competing offers.',
		'seo_notes'    => 'Value props can reinforce local expertise and service area.',
	),
);
