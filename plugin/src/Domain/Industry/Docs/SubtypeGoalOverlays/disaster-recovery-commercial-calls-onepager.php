<?php
/**
 * Combined subtype+goal page one-pager overlays: disaster_recovery_commercial + calls (Prompt 554).
 * Admission: commercial emergency-response flows; call-first page posture.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_OVERLAY_KEY => 'disaster_recovery_commercial_calls_contact',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY => 'disaster_recovery_commercial',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'calls',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_contact_request_01',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_strategy', 'hierarchy_hints', 'compliance_cautions' ),
		'cta_strategy'        => 'Primary CTA: 24/7 commercial emergency line; click-to-call prominent; avoid form-first for emergency.',
		'hierarchy_hints'     => 'Lead with commercial emergency number; immediate dispatch framing.',
		'compliance_cautions' => 'Avoid overstating response time; comply with local emergency-service claims.',
	),
);
