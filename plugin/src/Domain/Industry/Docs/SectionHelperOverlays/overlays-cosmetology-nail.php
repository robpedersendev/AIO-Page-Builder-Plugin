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
		'compliance_cautions_fragment_ref' => 'caution_testimonial_genuine',
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
	// * Second-wave (Prompt 401): gallery, pricing, profile, location.
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'mlp_gallery_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Warm and aspirational. Showcase work quality and variety; avoid clinical or stock-looking imagery.',
		'cta_usage_notes' => 'Link gallery to booking or service detail; one primary CTA (e.g. "Book this look", "See services").',
		'seo_notes'    => 'Alt text and captions support service and local relevance; avoid generic descriptions.',
		'media_notes'  => 'Use real work or licensed imagery; ensure consent for before/after or client work.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'fb_package_summary_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Clear and inviting. Emphasize value and experience; avoid hard-sell or hidden fees.',
		'cta_usage_notes' => 'Single CTA per package to book or inquire; avoid stacking multiple competing CTAs.',
		'seo_notes'    => 'Package names and highlights can reinforce service type and local intent.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'mlp_profile_cards_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Personal and professional. Highlight stylist/technician expertise and specialties.',
		'cta_usage_notes' => 'Support with book-with-this-team or contact CTA; avoid generic "Learn more".',
		'seo_notes'    => 'Names and roles support authority and local trust; keep bios concise.',
	),
	array(
		'industry_key' => 'cosmetology_nail',
		'section_key'  => 'mlp_location_info_01',
		'scope'        => 'section_helper_overlay',
		'status'       => 'active',
		'tone_notes'   => 'Welcoming and clear. Set expectations for parking, access, or visit details.',
		'cta_usage_notes' => 'Pair with directions or book-now CTA; one clear next step.',
		'seo_notes'    => 'Address and area name support local SEO; keep format consistent.',
	),
);
