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
		'industry_key' => 'disaster_recovery',
		'section_key'  => 'hero_conv_02',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Calm, capable, and urgent without panic. Emphasize 24/7 response and expertise. Avoid sensationalism.',
		'cta_usage_notes' => 'Primary: call now or emergency dispatch. Secondary: insurance/claims info or contact. Emergency CTA central.',
		'seo_notes'    => 'Headline can include response type (water, fire, mold) or 24/7; supports local and emergency intent.',
	),
	array(
		'industry_key' => 'disaster_recovery',
		'section_key'  => 'cta_booking_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Direct and urgent. Use for emergency response or assessment requests.',
		'cta_usage_notes' => 'Prefer "Call now" or "24/7 emergency" as primary. "Request assessment" for non-emergency. Single clear action.',
		'seo_notes'    => 'CTA label should convey emergency or assessment; supports local and disaster-type queries.',
	),
	array(
		'industry_key' => 'disaster_recovery',
		'section_key'  => 'tp_badge_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Certification and trust. IICRC and similar credentials; insurance and compliance signals.',
		'compliance_cautions' => 'Certification and insurance claims must be accurate. Do not imply endorsement without permission.',
		'media_notes'  => 'Certification badges with accurate alt text; only current, valid credentials.',
	),
	array(
		'industry_key' => 'disaster_recovery',
		'section_key'  => 'gc_contact_form_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Responsive and clear. For non-emergency: set expectation for callback; emergency should emphasize phone.',
		'cta_usage_notes' => 'Emergency: call-now over form. Form for insurance coordination or non-urgent assessment.',
		'seo_notes'    => 'Headline can distinguish emergency vs insurance/claims (e.g. "Insurance coordination", "Request assessment").',
	),
	array(
		'industry_key' => 'disaster_recovery',
		'section_key'  => 'gc_offer_value_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Capability and reassurance. Emphasize response time, certifications, and insurance assistance.',
		'cta_usage_notes' => 'Support with call-now or emergency CTA; insurance/claims assistance as secondary where relevant.',
		'seo_notes'    => 'Value props can reinforce service area, response type, and 24/7 availability.',
	),
);
