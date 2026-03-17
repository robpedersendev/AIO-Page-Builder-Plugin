<?php
/**
 * Conversion-goal page one-pager overlays for estimates goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'estimates',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'      => 'Lead with get a quote or request estimate; set clear expectation for response and process.',
		'funnel_notes'         => 'Estimate-first funnel: one primary path to quote request; trust cues support form submission.',
		'cta_placement_notes'  => 'Primary CTA: get a quote, request estimate, or free estimate. One clear form or CTA.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'estimates',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_contact_request_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'         => 'Use for estimate-request flow; clarify what info is needed and when they receive the quote.',
		'cta_placement_notes'  => 'Single CTA to request estimate; avoid mixing with booking if funnel is estimate-first.',
	),
);
