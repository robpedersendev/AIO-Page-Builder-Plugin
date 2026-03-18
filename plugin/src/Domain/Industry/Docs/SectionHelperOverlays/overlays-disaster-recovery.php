<?php
/**
 * Section-helper overlays for disaster recovery/restoration (industry-section-helper-overlay-schema, Prompt 353).
 * Hero, CTA, Proof, Contact/Form, Feature/benefit. Additive to base helper docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'hero_conv_02',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Calm, capable, and urgent without panic. Emphasize 24/7 response and expertise. Avoid sensationalism.',
		'cta_usage_notes' => 'Primary: call now or emergency dispatch. Secondary: insurance/claims info or contact. Emergency CTA central.',
		'seo_notes'       => 'Headline can include response type (water, fire, mold) or 24/7; supports local and emergency intent.',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'cta_booking_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Direct and urgent. Use for emergency response or assessment requests.',
		'cta_usage_notes' => 'Prefer "Call now" or "24/7 emergency" as primary. "Request assessment" for non-emergency. Single clear action.',
		'seo_notes'       => 'CTA label should convey emergency or assessment; supports local and disaster-type queries.',
	),
	array(
		'industry_key'        => 'disaster_recovery',
		'section_key'         => 'tp_badge_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Certification and trust. IICRC and similar credentials; insurance and compliance signals.',
		'compliance_cautions' => 'Certification and insurance claims must be accurate. Do not imply endorsement without permission.',
		'media_notes'         => 'Certification badges with accurate alt text; only current, valid credentials.',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'gc_contact_form_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Responsive and clear. For non-emergency: set expectation for callback; emergency should emphasize phone.',
		'cta_usage_notes' => 'Emergency: call-now over form. Form for insurance coordination or non-urgent assessment.',
		'seo_notes'       => 'Headline can distinguish emergency vs insurance/claims (e.g. "Insurance coordination", "Request assessment").',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'gc_offer_value_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Capability and reassurance. Emphasize response time, certifications, and insurance assistance.',
		'cta_usage_notes' => 'Support with call-now or emergency CTA; insurance/claims assistance as secondary where relevant.',
		'seo_notes'       => 'Value props can reinforce service area, response type, and 24/7 availability.',
	),
	// * Second-wave (Prompt 401): trust, certification, location, commercial/residential nuance, urgency-proof.
	array(
		'industry_key'        => 'disaster_recovery',
		'section_key'         => 'tp_trust_band_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Calm and capable. Emphasize 24/7 response, certifications (e.g. IICRC), and insurance coordination.',
		'compliance_cautions' => 'Certification and insurance claims must be accurate; do not imply endorsement without permission.',
		'media_notes'         => 'Trust visuals must match claims; meaningful alt text for certifications and guarantees.',
	),
	array(
		'industry_key'        => 'disaster_recovery',
		'section_key'         => 'tp_certification_01',
		'scope'               => 'section_helper_overlay',
		'status'              => 'active',
		'tone_notes'          => 'Credible and compliant. IICRC and similar credentials; insurance and restoration standards.',
		'compliance_cautions' => 'Certification claims must be accurate and current; do not imply endorsement without permission.',
		'media_notes'         => 'Certification badges must match the named credential; meaningful alt text required.',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'mlp_location_info_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Clear and service-area focused. Emphasize coverage area and 24/7 availability.',
		'cta_usage_notes' => 'Pair with call-now or emergency CTA; one clear next step.',
		'seo_notes'       => 'Service area supports local and disaster-type intent; keep format consistent.',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'mlp_comparison_cards_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Informative and nuanced. Use for commercial vs residential, or service-type comparison; avoid sensationalism.',
		'cta_usage_notes' => 'One CTA per option or single emergency CTA; avoid multiple competing CTAs.',
		'seo_notes'       => 'Comparison labels can support service type (water, fire, mold) and local intent.',
	),
	array(
		'industry_key'    => 'disaster_recovery',
		'section_key'     => 'tp_reassurance_01',
		'scope'           => 'section_helper_overlay',
		'status'          => 'active',
		'tone_notes'      => 'Reassuring and urgent without panic. Emphasize response speed, insurance assistance, and next steps.',
		'cta_usage_notes' => 'Support with call-now or emergency CTA; insurance/claims as secondary where relevant.',
		'seo_notes'       => 'Reassurance copy can reinforce 24/7 and service-area signals.',
	),
);
