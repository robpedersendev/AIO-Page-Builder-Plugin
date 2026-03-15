<?php
/**
 * Section-helper overlays for cosmetology/nail (industry-section-helper-overlay-schema, Prompt 353).
 * Hero, CTA, Proof, Contact/Form, Feature/benefit. Additive to base helper docs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'hero_conv_02',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Warm, inviting, and professional. Avoid clinical or corporate tone. Emphasize choice and personal care.',
		'cta_usage_notes' => 'Primary CTA: book now or see availability. Secondary: contact or view services. Avoid multiple competing CTAs.',
		'seo_notes'    => 'Headline can align with primary service or location; keep subheadline supportive and concise.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'cta_booking_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Direct and friendly. Use action verbs: Book now, Reserve your spot, See availability.',
		'cta_usage_notes' => 'Single clear booking action. Link to booking flow or contact; avoid generic "Learn more".',
		'seo_notes'    => 'Button label should describe the action; supports local and service intent.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'tp_badge_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Credible and reassuring. Use only real licenses, certifications, or awards.',
		'compliance_cautions' => 'Do not imply endorsement without permission. License and compliance claims must be accurate and current.',
		'media_notes'  => 'Badge images must match the named credential; provide meaningful alt text.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'gc_contact_form_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Welcoming and clear. Set expectations for response time or booking follow-up.',
		'cta_usage_notes' => 'Use for inquiries, appointment requests, or contact when booking is secondary.',
		'seo_notes'    => 'Headline specific to inquiry type; avoid generic "Contact us" where a service label fits.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'gc_offer_value_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Benefit-focused and clear. Emphasize outcomes and experience, not just features.',
		'cta_usage_notes' => 'Support with a single CTA to book or learn more; avoid stacking multiple offers.',
		'seo_notes'    => 'Value propositions can reinforce service and local relevance.',
	),
);
