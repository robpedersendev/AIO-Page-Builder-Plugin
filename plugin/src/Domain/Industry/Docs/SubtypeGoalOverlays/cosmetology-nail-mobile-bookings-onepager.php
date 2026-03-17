<?php
/**
 * Combined subtype+goal page one-pager overlays: cosmetology_nail_mobile_tech + bookings (Prompt 554).
 * Admission: mobile booking flows; joint page-level nuance for at-home booking.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_OVERLAY_KEY             => 'cosmetology_nail_mobile_tech_bookings_contact',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'cosmetology_nail_mobile_tech',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY               => 'bookings',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY               => 'pt_contact_request_01',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE                 => Subtype_Goal_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS                 => Subtype_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_strategy', 'hierarchy_hints' ),
		'cta_strategy'   => 'Single CTA: book mobile appointment; include location/address in request or link to scheduler with "we come to you" framing.',
		'hierarchy_hints' => 'Lead with mobile booking; service area and at-home appointment before general contact.',
	),
);
