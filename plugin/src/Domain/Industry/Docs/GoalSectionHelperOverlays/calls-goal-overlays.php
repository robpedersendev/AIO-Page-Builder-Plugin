<?php
/**
 * Conversion-goal section-helper overlays for calls goal (Prompt 506).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Primary CTA: click-to-call or one clear phone number. Avoid burying the call action.',
		'tone_notes'                                       => 'Direct and action-oriented; encourage immediate call where appropriate.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Prefer call-first CTA (e.g. Call now, Request callback). Secondary: contact form.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Offer phone as primary option; set callback expectation for form submissions.',
	),
);
