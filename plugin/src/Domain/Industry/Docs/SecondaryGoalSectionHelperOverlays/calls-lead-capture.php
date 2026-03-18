<?php
/**
 * Secondary-goal section-helper overlays: primary calls + secondary lead_capture (Prompt 544).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'calls',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Keep call primary; add optional lead magnet (e.g. download guide) as secondary CTA where space allows.',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'calls',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Form supports both callback request (primary) and optional lead-nurture opt-in (secondary).',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'calls',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Call-first CTA primary; secondary CTA for guide or signup where appropriate.',
	),
);
