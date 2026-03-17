<?php
/**
 * Conversion-goal section-helper overlays for valuations goal (Prompt 506).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY   => 'valuations',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE    => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS  => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Primary CTA: get a valuation, home value, or CMA. Lead magnet or tool entry point.',
		'tone_notes'      => 'Value-focused; position valuation as the key conversion step.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY   => 'valuations',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE    => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS  => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Single CTA to valuation tool, CMA request, or value estimate; avoid diluting with other CTAs.',
	),
	array(
		Goal_Section_Helper_Overlay_Registry::FIELD_GOAL_KEY   => 'valuations',
		Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'gc_contact_form_01',
		Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE    => Goal_Section_Helper_Overlay_Registry::SCOPE_GOAL_SECTION_HELPER_OVERLAY,
		Goal_Section_Helper_Overlay_Registry::FIELD_STATUS  => Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Use for valuation request or lead capture tied to valuation; clarify what they receive.',
	),
);
