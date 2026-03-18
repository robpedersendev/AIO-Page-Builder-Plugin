<?php
/**
 * Combined subtype+goal section-helper overlays: cosmetology_nail_mobile_tech + bookings (Prompt 554).
 * Admission: mobile booking flows; joint nuance for "come to you" + book-appointment stronger than independent layers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY => 'cosmetology_nail_mobile_tech_bookings_hero',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'tone_notes', 'cta_usage_notes' ),
		'tone_notes'      => 'Mobile + booking: convenience and "book at your location"; lead with service area and at-home appointment.',
		'cta_usage_notes' => 'Primary CTA: Book at your location, Schedule mobile appointment, or Choose your time and address. Emphasize at-home booking, not in-salon.',
	),
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY => 'cosmetology_nail_mobile_tech_bookings_cta',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_usage_notes' ),
		'cta_usage_notes' => 'Single CTA to book mobile appointment; link to scheduler with location/address field or "we come to you" copy.',
	),
);
