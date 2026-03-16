<?php
/**
 * Subtype section-helper overlays for plumber (subtype-section-helper-overlay-schema; Prompt 425).
 * Residential and commercial plumber refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;

return array(
	// * Residential Plumber: home repairs, emergency, small property.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Friendly and homeowner-focused. Emphasize repairs, installations, and emergency response for homes.',
		'cta_usage_notes'   => 'Primary CTA: request service, schedule repair, or call for emergency. One clear action.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Clear and action-oriented. Request a visit, Schedule repair, or Call for emergency.',
		'cta_usage_notes'   => 'Single CTA to contact or booking; set expectation for residential service area.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Welcoming and clear. Use for service requests, estimates, or non-emergency inquiries.',
		'cta_usage_notes'   => 'Set response-time expectation; one primary CTA (e.g. request callback, get estimate).',
	),
	// * Commercial Plumber: maintenance contracts, large installations, compliance.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Professional and business-focused. Emphasize maintenance contracts, installations, and compliance.',
		'cta_usage_notes'   => 'Primary CTA: request quote, schedule inspection, or contact commercial team.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Business-oriented. Request a quote, Schedule inspection, or Contact commercial services.',
		'cta_usage_notes'   => 'Single CTA to quote or contact; avoid residential-only language.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'plumber_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'tp_certification_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Credible and compliance-focused. Highlight commercial licenses, codes, and industry credentials.',
		'compliance_cautions' => 'Commercial and industrial compliance claims must be accurate and current.',
	),
);
