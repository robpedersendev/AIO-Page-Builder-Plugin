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
		'industry_key' => 'plumber',
		'section_key'  => 'hero_conv_02',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Trustworthy and direct. Balance urgency (emergency) with reassurance (licensed, insured). Avoid fear-based copy.',
		'cta_usage_notes' => 'Primary: call now or emergency dispatch. Secondary: schedule service or contact. Call-now CTA central for emergency posture.',
		'seo_notes'    => 'Headline can include service area or 24/7; supports local and emergency intent.',
	),
	array(
		'industry_key' => 'plumber',
		'section_key'  => 'cta_booking_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Clear and action-oriented. Use for scheduled service or callback requests.',
		'cta_usage_notes' => 'Use "Call now" for emergency; "Schedule service" or "Request callback" for non-urgent. One primary action.',
		'seo_notes'    => 'Button label should describe action; supports local and service-area queries.',
	),
	array(
		'industry_key' => 'plumber',
		'section_key'  => 'tp_badge_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Licensing and trust. Emphasize license, insurance, guarantees, and trade affiliations.',
		'compliance_cautions' => 'License and insurance claims must be accurate and current. Jurisdiction rules may apply.',
		'media_notes'  => 'Badge images for licenses or affiliations; meaningful alt text required.',
	),
	array(
		'industry_key' => 'plumber',
		'section_key'  => 'gc_contact_form_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Responsive and clear. For non-emergency: set expectation for callback or dispatch window.',
		'cta_usage_notes' => 'Emergency: prefer prominent call-now over form. Form for scheduling or general inquiry.',
		'seo_notes'    => 'Headline can distinguish emergency vs scheduled (e.g. "Schedule a visit", "Request service").',
	),
	array(
		'industry_key' => 'plumber',
		'section_key'  => 'gc_offer_value_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Trust and value. Emphasize guarantees, financing options, and service quality.',
		'cta_usage_notes' => 'Support with call-now or schedule CTA; avoid diluting with multiple offers.',
		'seo_notes'    => 'Value props can reinforce service area and emergency/scheduled options.',
	),
);
