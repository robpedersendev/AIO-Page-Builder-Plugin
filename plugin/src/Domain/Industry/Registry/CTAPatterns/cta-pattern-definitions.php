<?php
/**
 * Built-in CTA pattern definitions for industry packs (Prompt 358, industry-cta-pattern-contract.md).
 * Reusable across cosmetology_nail, realtor, plumber, disaster_recovery. Keys referenced by pack preferred/required/discouraged_cta_patterns.
 *
 * @package AIOPageBuilder
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'pattern_key'     => 'book_now',
		'name'            => 'Book now / Appointment',
		'description'     => 'Primary CTA for scheduling an appointment or booking a service. Use on hero, service detail, and contact-adjacent sections.',
		'urgency_notes'   => 'Soft urgency (e.g. limited availability, next opening); avoid false scarcity.',
		'trust_notes'     => 'Reinforce with guarantees, reviews, or clear next-step expectations.',
		'action_framing'  => 'Single clear action: Book now, Schedule, or Request appointment. One primary button per section.',
	),
	array(
		'pattern_key'     => 'gallery_to_booking',
		'name'            => 'Gallery to booking',
		'description'     => 'CTA that follows gallery or portfolio content; bridges visual proof to appointment or consultation.',
		'urgency_notes'   => 'Optional: "Get the same look" or "Book this service"; keep tasteful.',
		'trust_notes'     => 'Gallery acts as proof; CTA should feel like natural next step, not pushy.',
		'action_framing'  => 'Book this service, Get a consultation, or See availability after gallery block.',
	),
	array(
		'pattern_key'     => 'consult',
		'name'            => 'Consultation request',
		'description'     => 'Request a consultation or discovery call. Fits professional services and high-consideration offerings.',
		'urgency_notes'   => 'Low urgency; emphasize value of the conversation, not scarcity.',
		'trust_notes'     => 'Consultation CTAs benefit from credentials, process explanation, or what to expect.',
		'action_framing'  => 'Request consultation, Schedule a call, or Free discovery call. Often paired with short form.',
	),
	array(
		'pattern_key'     => 'valuation_request',
		'name'            => 'Valuation / Quote request',
		'description'     => 'Request a valuation, quote, or estimate (e.g. property, project, repair). Common in real estate and trades.',
		'urgency_notes'   => 'Market or timeline can support gentle urgency; avoid pressure.',
		'trust_notes'     => 'Transparent process and no-obligation language support trust.',
		'action_framing'  => 'Get a valuation, Request quote, or Free estimate. Clarify what happens next.',
	),
	array(
		'pattern_key'     => 'call_now',
		'name'            => 'Call now',
		'description'     => 'Immediate phone contact. Suited to local service, emergency-adjacent, or high-touch support.',
		'urgency_notes'   => 'Use for time-sensitive or same-day need; 24/7 when applicable.',
		'trust_notes'     => 'Visible number, optional click-to-call; avoid fake urgency.',
		'action_framing'  => 'Call now, Phone support, or Contact us. One prominent CTA per context.',
	),
	array(
		'pattern_key'     => 'emergency_dispatch',
		'name'            => 'Emergency / 24/7 dispatch',
		'description'     => 'Emergency or 24/7 response CTA. For disaster recovery, plumbing, and other urgent-service verticals.',
		'urgency_notes'   => 'Genuine urgency only; 24/7 availability and clear response expectation.',
		'trust_notes'     => 'Certifications, response time, or guarantee of contact build trust.',
		'action_framing'  => '24/7 emergency line, Call for immediate help, or Dispatch now. No false emergency framing.',
	),
	array(
		'pattern_key'     => 'claim_assistance',
		'name'            => 'Claims / Insurance assistance',
		'description'     => 'CTA for insurance or claims assistance (e.g. disaster restoration, damage assessment).',
		'urgency_notes'   => 'Document and act quickly; avoid exaggerating urgency.',
		'trust_notes'     => 'IICRC or similar credentials; clear "we work with your insurer" messaging.',
		'action_framing'  => 'Start a claim, Insurance assistance, or Free assessment. Set expectations for process.',
	),
	array(
		'pattern_key'     => 'scheduled_service',
		'name'            => 'Scheduled service request',
		'description'     => 'Request a scheduled (non-emergency) service visit. Fits plumbing, HVAC, and other trade or field service.',
		'urgency_notes'   => 'Next available or calendar-based; no fake scarcity.',
		'trust_notes'     => 'Clear scope, pricing approach, and what to expect on the day.',
		'action_framing'  => 'Schedule service, Request a visit, or Book a technician. Differentiate from emergency path.',
	),
	array(
		'pattern_key'     => 'quote_request',
		'name'            => 'Quote / Estimate request',
		'description'     => 'Generic quote or estimate request. Reusable across industries for project-based or custom-scope work.',
		'urgency_notes'   => 'Optional timeline (e.g. valid for 30 days); keep honest.',
		'trust_notes'     => 'No-obligation and clear next steps support conversion.',
		'action_framing'  => 'Get a quote, Request estimate, or Free quote. Single primary CTA per section.',
	),
);
