<?php
/**
 * Section-helper overlays for plumber (industry-section-helper-overlay-schema, Prompt 353).
 * Hero, CTA, Proof, Contact/Form, Feature/benefit. Additive to base helper docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'hero_conv_02',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Trustworthy and direct. Balance urgency (emergency) with reassurance (licensed, insured). Avoid fear-based copy.',
		'cta_usage_notes' => 'Primary: call now or emergency dispatch. Secondary: schedule service or contact. Call-now CTA central for emergency posture.',
		'seo_notes'       => 'Headline can include service area or 24/7; supports local and emergency intent.',
	),
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'cta_booking_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Clear and action-oriented. Use for scheduled service or callback requests.',
		'cta_usage_notes' => 'Use "Call now" for emergency; "Schedule service" or "Request callback" for non-urgent. One primary action.',
		'seo_notes'       => 'Button label should describe action; supports local and service-area queries.',
	),
	array(
		'industry_key'        => 'plumber',
		'section_key'         => 'tp_badge_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Licensing and trust. Emphasize license, insurance, guarantees, and trade affiliations.',
		'compliance_cautions' => 'License and insurance claims must be accurate and current. Jurisdiction rules may apply.',
		'media_notes'         => 'Badge images for licenses or affiliations; meaningful alt text required.',
	),
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'gc_contact_form_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Responsive and clear. For non-emergency: set expectation for callback or dispatch window.',
		'cta_usage_notes' => 'Emergency: prefer prominent call-now over form. Form for scheduling or general inquiry.',
		'seo_notes'       => 'Headline can distinguish emergency vs scheduled (e.g. "Schedule a visit", "Request service").',
	),
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'gc_offer_value_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Trust and value. Emphasize guarantees, financing options, and service quality.',
		'cta_usage_notes' => 'Support with call-now or schedule CTA; avoid diluting with multiple offers.',
		'seo_notes'       => 'Value props can reinforce service area and emergency/scheduled options.',
	),
	// * Second-wave (Prompt 401): pricing, trust, location, comparison, certification.
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'fb_package_summary_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Clear and trustworthy. Emphasize transparent pricing, guarantees, and financing where offered.',
		'cta_usage_notes' => 'Single CTA per tier: call now or schedule; avoid stacking competing CTAs.',
		'seo_notes'       => 'Package names and highlights can reinforce service type and service-area intent.',
	),
	array(
		'industry_key'        => 'plumber',
		'section_key'         => 'tp_trust_band_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Reassuring and factual. Emphasize license, insurance, guarantees, and response commitment.',
		'compliance_cautions' => 'License and insurance claims must be accurate and current; jurisdiction rules may apply.',
		'media_notes'         => 'Trust visuals must match claims; meaningful alt text for badges or guarantees.',
	),
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'mlp_location_info_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Clear and service-area focused. Set expectations for coverage and response time.',
		'cta_usage_notes' => 'Pair with call-now or schedule CTA; one clear next step.',
		'seo_notes'       => 'Service area and address support local SEO; keep format consistent.',
	),
	array(
		'industry_key'    => 'plumber',
		'section_key'     => 'mlp_comparison_cards_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Fair and informative. Compare service tiers or options without disparaging; emphasize fit for need.',
		'cta_usage_notes' => 'One CTA per option or single primary CTA (call/schedule); avoid multiple competing CTAs.',
		'seo_notes'       => 'Comparison labels can support service-type and local intent.',
	),
	array(
		'industry_key'        => 'plumber',
		'section_key'         => 'tp_certification_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Credible and compliant. Emphasize trade certifications, licenses, and continuing education where relevant.',
		'compliance_cautions' => 'Certification and license claims must be accurate and current; jurisdiction rules apply.',
		'media_notes'         => 'Certification badges must match the named credential; meaningful alt text required.',
	),
);
