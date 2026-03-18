<?php
/**
 * Conversion-goal section-helper overlays for bookings goal (Prompt 506).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Primary CTA: book now, schedule, or reserve. Link to scheduler or booking flow.',
		'tone_notes'                                       => 'Clear availability and next-step; reduce friction to book.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Single CTA to book or schedule; show availability or calendar where possible.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Prefer booking/scheduler link over form; or use form for booking request with clear next step.',
	),
);
