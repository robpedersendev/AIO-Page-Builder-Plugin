<?php
/**
 * Conversion-goal page one-pager overlays for lead_capture goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'lead_capture',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE  => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'                                 => 'Lead with sign up, get the guide, or download; one clear value exchange above the fold.',
		'funnel_notes'                                    => 'Lead-capture funnel: form or signup as primary conversion; make the offer and benefit explicit.',
		'cta_placement_notes'                             => 'Primary CTA: sign up, get the guide, or download. One clear form or gated entry; avoid mixing with booking/call.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'lead_capture',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_contact_request_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE  => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'                                    => 'Use for newsletter, resource signup, or lead form; clarify value and what happens next.',
		'cta_placement_notes'                             => 'Single CTA to form or signup; emphasize value exchange and next step.',
	),
);
