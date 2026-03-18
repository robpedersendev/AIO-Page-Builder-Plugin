<?php
/**
 * Conversion-goal section-helper overlays for lead_capture goal (Prompt 506).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'lead_capture',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Primary CTA: sign up, get the guide, or download. One clear value exchange.',
		'tone_notes'                                       => 'Offer-led; make the lead magnet or signup benefit explicit.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'lead_capture',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Single CTA to form, signup, or gated content; avoid mixing with booking or call if funnel is lead-first.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'lead_capture',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Use for newsletter, resource signup, or lead form; clarify value and what happens next.',
	),
);
