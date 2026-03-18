<?php
/**
 * Combined subtype+goal section-helper overlays: realtor_buyer_agent + consultations (Prompt 554).
 * Admission: buyer-focused consultation flows; joint nuance stronger than subtype-only and goal-only alone.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY => 'realtor_buyer_agent_consultations_hero',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'consultations',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'tone_notes', 'cta_usage_notes' ),
		'tone_notes'      => 'Buyer consultation–specific: position the free consultation as the first step in the buying journey; search support and neighborhood insight lead naturally to a scheduled call.',
		'cta_usage_notes' => 'Single primary CTA: Schedule your buyer consultation, Book a free buyer call, or Get started with a consultation. Avoid generic listing or seller CTAs.',
	),
	array(
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_OVERLAY_KEY => 'realtor_buyer_agent_consultations_cta',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'consultations',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_GOAL_SECTION_HELPER_OVERLAY,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Section_Helper_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_usage_notes' ),
		'cta_usage_notes' => 'Link to consultation scheduler or contact for buyer consultation only; one clear CTA (e.g. Schedule your buyer consultation).',
	),
);
