<?php
/**
 * Secondary-goal page one-pager overlays: primary consultations + secondary lead_capture (Prompt 546).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'consultations',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_home_conversion_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'     => 'Consultation/schedule primary; lead magnet or nurture signup as secondary.',
		'funnel_notes'        => 'Consultation-first with nurture: schedule call primary; guide/signup for those not ready.',
		'cta_placement_notes' => 'Primary: schedule consultation. Secondary: download guide or newsletter signup.',
	),
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'consultations',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'lead_capture',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_contact_request_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'       => 'Contact: consultation request primary; optional nurture opt-in.',
		'cta_placement_notes' => 'Consultation CTA first; optional resource signup in form or footer.',
	),
);
