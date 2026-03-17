<?php
/**
 * Combined subtype+goal overlay: Mobile Nail Tech + Bookings (Prompt 552).
 * Admitted: mobile nail techs benefit from a single joint booking-flow overlay that subtype-only and goal-only do not express (on-the-go booking, mobile-first CTA).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'cosmetology_nail_mobile_tech_bookings',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'cosmetology_nail_mobile_tech',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY                => 'bookings',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF      => 'cosmetology_nail_mobile_tech_starter',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS       => array( 'cta_booking_01', 'lpu_contact_panel_01' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE             => 'mobile-first-book-now',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE            => 'mobile-booking-direct',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                  => Subtype_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER          => Subtype_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
