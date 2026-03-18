<?php
/**
 * Secondary-goal page one-pager overlays: primary calls + secondary lead_capture (Prompt 546).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'calls',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_home_conversion_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'     => 'Keep call primary above the fold; add lead magnet or signup as secondary CTA where space allows.',
		'funnel_notes'        => 'Call-first with nurture: one clear call path; secondary path for guide/download or newsletter.',
		'cta_placement_notes' => 'Primary: phone or click-to-call. Secondary: lead magnet CTA or form below fold.',
	),
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'calls',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_contact_request_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'        => 'Contact page: call/callback primary; form can double as lead capture with opt-in.',
		'cta_placement_notes' => 'Phone prominent; form supports callback request and optional nurture signup.',
	),
);
