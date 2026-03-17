<?php
/**
 * Conversion-goal page one-pager overlays for consultations goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'consultations',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'      => 'Lead with schedule consultation or book a call; emphasize value of the session.',
		'funnel_notes'         => 'Consultation-first funnel: consultation as the key conversion step; clear next step to schedule.',
		'cta_placement_notes'  => 'Primary CTA: schedule consultation, book a call, or free consultation. One clear scheduling path.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY   => 'consultations',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY   => 'pt_contact_request_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE      => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS     => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'         => 'Use for consultation request; set expectation for who will reach out and how.',
		'cta_placement_notes'  => 'Single CTA to schedule or request consultation; link to scheduler or contact.',
	),
);
