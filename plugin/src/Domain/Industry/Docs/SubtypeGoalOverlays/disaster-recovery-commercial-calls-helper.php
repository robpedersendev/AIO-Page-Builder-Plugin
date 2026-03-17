<?php
/**
 * Combined subtype+goal section-helper overlays: disaster_recovery_commercial + calls (Prompt 554).
 * Admission: commercial emergency-response flows; 24/7 commercial line and call-first posture.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY             => 'disaster_recovery_commercial_calls_hero',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'disaster_recovery_commercial',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY               => 'calls',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY            => 'hero_conv_02',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE                  => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS                => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'tone_notes', 'cta_usage_notes', 'compliance_cautions' ),
		'tone_notes'            => 'Commercial emergency: business continuity and 24/7 response; urgent but professional.',
		'cta_usage_notes'      => 'Primary CTA: 24/7 commercial emergency line, Call now for commercial response, or Immediate commercial dispatch. Click-to-call prominence.',
		'compliance_cautions'  => 'Avoid overstating response time; comply with local emergency-service claims.',
	),
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY             => 'disaster_recovery_commercial_calls_cta',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'disaster_recovery_commercial',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY               => 'calls',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY            => 'cta_booking_01',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE                  => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS                => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_usage_notes' ),
		'cta_usage_notes' => 'Single CTA: Call commercial emergency line; phone number prominent; avoid form-first for emergency.',
	),
);
