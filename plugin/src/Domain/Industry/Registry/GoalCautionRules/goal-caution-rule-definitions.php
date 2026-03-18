<?php
/**
 * Built-in conversion-goal caution rules (conversion-goal-caution-rule-schema.md, Prompt 510).
 * Advisory only; no legal advice. Focus: urgency language, conversion pressure, claim phrasing, form promises, valuation/estimate posture.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array_merge(
	_calls_goal_caution_rules(),
	_bookings_goal_caution_rules(),
	_estimates_goal_caution_rules(),
	_consultations_goal_caution_rules(),
	_valuations_goal_caution_rules(),
	_lead_capture_goal_caution_rules()
);

/**
 * Call-focused goal caution rules (urgency, conversion pressure).
 *
 * @return array<int, array<string, mixed>>
 */
function _calls_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'              => 'goal_calls_urgency_language',
			'goal_key'                   => 'calls',
			'severity'                   => 'caution',
			'caution_summary'            => 'Urgency language for call conversion must be accurate; avoid guaranteed response or availability.',
			'guidance_text'              => 'Call-now and same-day messaging should not promise specific response times unless you can deliver. Avoid overclaiming availability.',
			'guidance_text_fragment_ref' => 'caution_urgency_accuracy',
			'refinement_area'            => 'urgency_language',
			'status'                     => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_calls_conversion_pressure',
			'goal_key'        => 'calls',
			'severity'        => 'info',
			'caution_summary' => 'Strong call CTAs should align with actual capacity and callback handling.',
			'guidance_text'   => 'Ensure call volume and follow-up processes can support the conversion posture you present.',
			'refinement_area' => 'conversion_pressure',
			'status'          => 'active',
		),
	);
}

/**
 * Booking-focused goal caution rules (scheduling prominence, promises).
 *
 * @return array<int, array<string, mixed>>
 */
function _bookings_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'              => 'goal_bookings_urgency_language',
			'goal_key'                   => 'bookings',
			'severity'                   => 'caution',
			'caution_summary'            => 'Booking urgency (e.g. limited slots) must reflect real availability.',
			'guidance_text'              => 'Do not imply scarcity or limited availability unless it is accurate. Avoid misleading next-available framing.',
			'guidance_text_fragment_ref' => 'caution_urgency_accuracy',
			'refinement_area'            => 'urgency_language',
			'status'                     => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_bookings_form_promises',
			'goal_key'        => 'bookings',
			'severity'        => 'info',
			'caution_summary' => 'Low-friction booking promises (e.g. no obligation) must stay accurate post-booking.',
			'guidance_text'   => 'Cancellation and confirmation policies should align with any no-commitment or easy-booking messaging.',
			'refinement_area' => 'form_promises',
			'status'          => 'active',
		),
	);
}

/**
 * Estimate/quote-focused goal caution rules (promise posture).
 *
 * @return array<int, array<string, mixed>>
 */
function _estimates_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'   => 'goal_estimates_valuation_posture',
			'goal_key'        => 'estimates',
			'severity'        => 'caution',
			'caution_summary' => 'Estimate and quote requests must not imply binding or final pricing.',
			'guidance_text'   => 'Free estimate and quote language should be clear that final scope and price are determined after assessment.',
			'refinement_area' => 'valuation_estimate_posture',
			'status'          => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_estimates_form_promises',
			'goal_key'        => 'estimates',
			'severity'        => 'info',
			'caution_summary' => 'No-obligation estimate messaging must align with follow-up and sales process.',
			'guidance_text'   => 'If you promise no obligation, ensure follow-up communications and process respect that framing.',
			'refinement_area' => 'form_promises',
			'status'          => 'active',
		),
	);
}

/**
 * Consultation-focused goal caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function _consultations_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'   => 'goal_consultations_claim_phrasing',
			'goal_key'        => 'consultations',
			'severity'        => 'caution',
			'caution_summary' => 'Consultation outcome claims must not overpromise results or advice.',
			'guidance_text'   => 'Consultation framing should not imply specific outcomes, diagnoses, or guarantees that require formal processes.',
			'refinement_area' => 'claim_phrasing',
			'status'          => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_consultations_form_promises',
			'goal_key'        => 'consultations',
			'severity'        => 'info',
			'caution_summary' => 'Free-consultation and no-pressure messaging must be consistent with follow-up.',
			'guidance_text'   => 'Ensure consultation booking and post-consultation flow match the low-pressure positioning.',
			'refinement_area' => 'form_promises',
			'status'          => 'active',
		),
	);
}

/**
 * Valuation-focused goal caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function _valuations_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'   => 'goal_valuations_valuation_posture',
			'goal_key'        => 'valuations',
			'severity'        => 'warning',
			'caution_summary' => 'Valuation and market-value language must not imply formal appraisal or binding value.',
			'guidance_text'   => 'Informal or indicative valuations must be clearly distinguished from formal appraisals. Avoid misleading value claims.',
			'refinement_area' => 'valuation_estimate_posture',
			'status'          => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_valuations_claim_phrasing',
			'goal_key'        => 'valuations',
			'severity'        => 'caution',
			'caution_summary' => 'Valuation request CTAs should not overclaim accuracy or speed of result.',
			'guidance_text'   => 'Set accurate expectations for how valuations are produced and communicated.',
			'refinement_area' => 'claim_phrasing',
			'status'          => 'active',
		),
	);
}

/**
 * Lead-capture-focused goal caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function _lead_capture_goal_caution_rules(): array {
	return array(
		array(
			'goal_rule_key'   => 'goal_lead_capture_form_promises',
			'goal_key'        => 'lead_capture',
			'severity'        => 'caution',
			'caution_summary' => 'Low-friction form promises (e.g. no spam, quick response) must be honored.',
			'guidance_text'   => 'Privacy and follow-up commitments made on lead forms must align with actual data use and response practices.',
			'refinement_area' => 'form_promises',
			'status'          => 'active',
		),
		array(
			'goal_rule_key'   => 'goal_lead_capture_conversion_pressure',
			'goal_key'        => 'lead_capture',
			'severity'        => 'info',
			'caution_summary' => 'Lead-capture CTAs should avoid overstating what happens after submit.',
			'guidance_text'   => 'Be clear about next steps (e.g. contact within X, download link) so expectations match process.',
			'refinement_area' => 'conversion_pressure',
			'status'          => 'active',
		),
	);
}
