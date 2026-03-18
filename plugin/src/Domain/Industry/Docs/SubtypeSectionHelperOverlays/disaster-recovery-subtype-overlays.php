<?php
/**
 * Subtype section-helper overlays for disaster recovery (subtype-section-helper-overlay-schema; Prompt 425).
 * Residential restoration and commercial restoration refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;

return array(
	// * Residential Restoration: water, fire, storm; homeowner, insurance.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Reassuring and homeowner-focused. Emphasize rapid response, insurance coordination, and residential restoration.',
		'cta_usage_notes' => 'Primary CTA: emergency line, request assessment, or start a claim. One clear action.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Clear and urgent when needed. Call 24/7, Request assessment, or Get help now.',
		'cta_usage_notes' => 'Single CTA to emergency or assessment; set expectation for response time.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_residential',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'tp_reassurance_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Reassuring and supportive. Focus on homeowner peace of mind and step-by-step process.',
		'cta_usage_notes' => 'Support with one CTA to contact or learn about the process.',
	),
	// * Commercial Restoration: business continuity, larger-scale, commercial insurance.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Professional and business-focused. Emphasize business continuity, rapid response, and commercial-scale mitigation.',
		'cta_usage_notes' => 'Primary CTA: emergency response, request commercial assessment, or contact commercial team.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Business-oriented. 24/7 commercial line, Request assessment, or Contact commercial services.',
		'cta_usage_notes' => 'Single CTA to emergency or commercial contact; avoid residential-only framing.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_commercial',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'tp_reassurance_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'      => 'Credible and process-focused. Emphasize commercial experience, compliance, and business continuity.',
		'cta_usage_notes' => 'Support with one CTA to contact or request commercial assessment.',
	),
);
