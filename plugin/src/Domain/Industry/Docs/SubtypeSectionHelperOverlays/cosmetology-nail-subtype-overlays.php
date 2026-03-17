<?php
/**
 * Subtype section-helper overlays for cosmetology/nail (subtype-section-helper-overlay-schema; Prompt 425).
 * Luxury salon and mobile nail tech refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;

return array(
	// * Luxury Nail Salon: premium experience, in-salon.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_luxury_salon',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Elevated and experience-focused. Emphasize ambiance, premium products, and the in-salon experience.',
		'cta_usage_notes'   => 'Primary CTA: reserve or book your experience. Avoid urgency language; focus on exclusivity and care.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_luxury_salon',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Refined and inviting. Reserve your spot, Book your experience, or See availability.',
		'cta_usage_notes'   => 'Link to booking or reservation flow; one clear action. Avoid multiple competing CTAs.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_luxury_salon',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'mlp_location_info_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Welcoming and clear. Include parking, entrance, and any salon-specific visit details.',
		'cta_usage_notes'   => 'Pair with directions or book-now; one clear next step.',
	),
	// * Mobile Nail Technician: travel, service area, convenience.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Friendly and convenience-focused. Emphasize coming to you: home, office, or event.',
		'cta_usage_notes'   => 'Primary CTA: book a visit, check availability, or see service area. Clarify travel/service area.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Direct and clear. Book at your location, Request a visit, or Check my service area.',
		'cta_usage_notes'   => 'Single CTA to booking or contact; set expectation for travel/service area.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'cosmetology_nail_mobile_tech',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'mlp_location_info_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Clear and practical. Describe service area, travel policy, or "I come to you" coverage.',
		'cta_usage_notes'   => 'Pair with book-now or contact; one clear next step. Avoid fixed-address emphasis if service is mobile-only.',
	),
);
