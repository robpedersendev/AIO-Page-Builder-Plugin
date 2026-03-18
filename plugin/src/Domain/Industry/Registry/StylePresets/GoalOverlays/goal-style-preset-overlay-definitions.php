<?php
/**
 * Built-in conversion-goal style preset overlays (conversion-goal-style-preset-schema.md, Prompt 512).
 * CTA emphasis, call prominence, scheduling prominence, consultation/valuation posture. Token values use --aio-* only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	// Calls: slight CTA/call prominence (button radius, spacing).
	array(
		'goal_preset_key'         => 'goal_calls_realtor',
		'goal_key'                => 'calls',
		'target_preset_ref'       => 'realtor_warm',
		'token_values'            => array(
			'--aio-radius-button' => '0.5rem',
			'--aio-space-md'      => '1.25rem',
		),
		'component_override_refs' => array( 'cta' ),
		'status'                  => 'active',
	),
	array(
		'goal_preset_key'         => 'goal_calls_plumber',
		'goal_key'                => 'calls',
		'target_preset_ref'       => 'plumber_trust',
		'token_values'            => array(
			'--aio-radius-button' => '0.5rem',
		),
		'component_override_refs' => array( 'cta' ),
		'status'                  => 'active',
	),
	// Bookings: scheduling prominence (spacing).
	array(
		'goal_preset_key'         => 'goal_bookings_cosmetology',
		'goal_key'                => 'bookings',
		'target_preset_ref'       => 'cosmetology_elegant',
		'token_values'            => array(
			'--aio-space-section' => '3.25rem',
		),
		'component_override_refs' => array( 'cta', 'card' ),
		'status'                  => 'active',
	),
	array(
		'goal_preset_key'   => 'goal_bookings_plumber',
		'goal_key'          => 'bookings',
		'target_preset_ref' => 'plumber_trust',
		'token_values'      => array(
			'--aio-space-section' => '2.75rem',
		),
		'status'            => 'active',
	),
	// Estimates: quote/estimate posture (muted refinement).
	array(
		'goal_preset_key'         => 'goal_estimates_realtor',
		'goal_key'                => 'estimates',
		'target_preset_ref'       => 'realtor_warm',
		'token_values'            => array(),
		'component_override_refs' => array( 'cta', 'intro' ),
		'status'                  => 'active',
	),
	array(
		'goal_preset_key'   => 'goal_estimates_plumber',
		'goal_key'          => 'estimates',
		'target_preset_ref' => 'plumber_trust',
		'token_values'      => array( '--aio-radius-card' => '0.5rem' ),
		'status'            => 'active',
	),
	// Consultations: consultation posture.
	array(
		'goal_preset_key'         => 'goal_consultations_cosmetology',
		'goal_key'                => 'consultations',
		'target_preset_ref'       => 'cosmetology_elegant',
		'token_values'            => array( '--aio-space-md' => '1.25rem' ),
		'component_override_refs' => array( 'cta', 'card' ),
		'status'                  => 'active',
	),
	array(
		'goal_preset_key'         => 'goal_consultations_realtor',
		'goal_key'                => 'consultations',
		'target_preset_ref'       => 'realtor_warm',
		'token_values'            => array(),
		'component_override_refs' => array( 'cta' ),
		'status'                  => 'active',
	),
	// Valuations: valuation posture (realtor).
	array(
		'goal_preset_key'         => 'goal_valuations_realtor',
		'goal_key'                => 'valuations',
		'target_preset_ref'       => 'realtor_warm',
		'token_values'            => array(
			'--aio-radius-card' => '0.625rem',
			'--aio-shadow-card' => '0 2px 10px rgba(184, 92, 56, 0.12)',
		),
		'component_override_refs' => array( 'card', 'cta', 'intro' ),
		'status'                  => 'active',
	),
	// Lead capture: form/CTA emphasis.
	array(
		'goal_preset_key'         => 'goal_lead_capture_cosmetology',
		'goal_key'                => 'lead_capture',
		'target_preset_ref'       => 'cosmetology_elegant',
		'token_values'            => array( '--aio-radius-button' => '2.25rem' ),
		'component_override_refs' => array( 'cta', 'badge' ),
		'status'                  => 'active',
	),
	array(
		'goal_preset_key'         => 'goal_lead_capture_disaster',
		'goal_key'                => 'lead_capture',
		'target_preset_ref'       => 'disaster_recovery_urgency',
		'token_values'            => array( '--aio-space-section' => '2.25rem' ),
		'component_override_refs' => array( 'cta' ),
		'status'                  => 'active',
	),
);
