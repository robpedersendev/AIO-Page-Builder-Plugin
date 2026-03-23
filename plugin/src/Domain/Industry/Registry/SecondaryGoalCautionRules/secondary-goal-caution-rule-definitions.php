<?php
/**
 * Built-in secondary-goal caution rules (secondary-goal-caution-rule-schema.md, Prompt 548).
 * Advisory only; mixed-funnel messaging overload, CTA confusion, promise ambiguity.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array_merge(
	aio_page_builder_calls_lead_capture_rules(),
	aio_page_builder_bookings_consultation_rules(),
	aio_page_builder_estimates_calls_rules(),
	aio_page_builder_consultation_lead_nurture_rules()
);

/**
 * Primary calls + secondary lead_capture caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function aio_page_builder_calls_lead_capture_rules(): array {
	return array(
		array(
			'secondary_goal_rule_key' => 'sec_calls_lead_capture_cta_confusion',
			'primary_goal_key'        => 'calls',
			'secondary_goal_key'      => 'lead_capture',
			'severity'                => 'caution',
			'caution_summary'         => 'Call-first plus lead capture: avoid competing CTAs; keep call primary and lead magnet clearly secondary.',
			'guidance_text'           => 'When both call and lead capture are active, ensure one primary CTA (call) and one secondary path (e.g. download guide). Do not present equal-weight options that dilute focus.',
			'refinement_area'         => 'cta_confusion',
			'status'                  => 'active',
		),
	);
}

/**
 * Primary bookings + secondary consultations caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function aio_page_builder_bookings_consultation_rules(): array {
	return array(
		array(
			'secondary_goal_rule_key' => 'sec_bookings_consultation_messaging_overload',
			'primary_goal_key'        => 'bookings',
			'secondary_goal_key'      => 'consultations',
			'severity'                => 'info',
			'caution_summary'         => 'Booking plus consultation: avoid messaging overload; book primary, consultation as clear alternative.',
			'guidance_text'           => 'Present book/schedule as the main path and consultation or discovery call as the alternative for users not ready to book. Avoid listing both with equal prominence.',
			'refinement_area'         => 'messaging_overload',
			'status'                  => 'active',
		),
	);
}

/**
 * Primary estimates + secondary calls caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function aio_page_builder_estimates_calls_rules(): array {
	return array(
		array(
			'secondary_goal_rule_key' => 'sec_estimates_calls_promise_ambiguity',
			'primary_goal_key'        => 'estimates',
			'secondary_goal_key'      => 'calls',
			'severity'                => 'caution',
			'caution_summary'         => 'Estimate plus call: set clear expectations for quote vs callback; avoid overpromising response time for either path.',
			'guidance_text'           => 'Estimate-request and call-back messaging should not promise specific turnaround unless you can deliver. Keep promise framing consistent across both paths.',
			'refinement_area'         => 'promise_ambiguity',
			'status'                  => 'active',
		),
	);
}

/**
 * Primary consultations + secondary lead_capture (nurture) caution rules.
 *
 * @return array<int, array<string, mixed>>
 */
function aio_page_builder_consultation_lead_nurture_rules(): array {
	return array(
		array(
			'secondary_goal_rule_key' => 'sec_consultation_lead_nurture_cta_confusion',
			'primary_goal_key'        => 'consultations',
			'secondary_goal_key'      => 'lead_capture',
			'severity'                => 'info',
			'caution_summary'         => 'Consultation plus lead nurture: keep schedule-consultation primary; lead magnet or signup clearly secondary.',
			'guidance_text'           => 'Consultation CTA should be the main conversion path; guide download or newsletter signup is the secondary path for users not ready to schedule.',
			'refinement_area'         => 'cta_confusion',
			'status'                  => 'active',
		),
	);
}
