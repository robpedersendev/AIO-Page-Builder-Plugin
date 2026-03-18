<?php
/**
 * Secondary-goal section-helper overlays: primary bookings + secondary consultations (Prompt 544).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'bookings',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Book/schedule primary; offer consultation or discovery call as secondary option.',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'bookings',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_consultation_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Consultation CTA supports mixed intent: book direct or schedule a consult first.',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'bookings',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'lpu_contact_panel_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Contact panel: primary book/schedule; secondary request consultation.',
	),
);
