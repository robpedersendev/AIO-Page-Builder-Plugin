<?php
/**
 * Secondary-goal section-helper overlays: primary consultations + secondary lead_capture (Prompt 544).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_consultation_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Consultation/schedule primary; lead magnet or nurture signup as secondary.',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Consultation request primary; optional newsletter or resource signup for nurture.',
	),
);
