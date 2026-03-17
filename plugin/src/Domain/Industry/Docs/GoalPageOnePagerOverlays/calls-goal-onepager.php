<?php
/**
 * Conversion-goal page one-pager overlays for calls goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'calls',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'      => 'Lead with one primary call action; keep phone or click-to-call above the fold.',
		'funnel_notes'         => 'Call-first funnel: reduce steps between landing and dial; avoid burying the call CTA.',
		'cta_placement_notes'  => 'Primary CTA: call now or request callback. Place phone number and CTA in hero and sticky/footer.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'calls',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_contact_request_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'         => 'Contact page supports call goal when phone is primary; form can offer callback option.',
		'cta_placement_notes'  => 'Prominent phone number; form secondary. Set callback expectation in copy.',
	),
);
