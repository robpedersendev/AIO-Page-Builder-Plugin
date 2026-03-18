<?php
/**
 * Conversion-goal section-helper overlays for estimates goal (Prompt 506).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'estimates',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Primary CTA: get a quote, request estimate, or free estimate. Set expectation for response.',
		'tone_notes'                                       => 'Professional and transparent; build trust for quote request.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'estimates',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Single CTA to request estimate or quote; avoid mixing with booking if funnel is estimate-first.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY => 'estimates',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE  => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes'                                  => 'Use for estimate-request flow; clarify what info is needed and when they will receive the quote.',
	),
);
