<?php
/**
 * Secondary-goal page one-pager overlays: primary bookings + secondary consultations (Prompt 546).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'bookings',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_home_conversion_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'     => 'Book/schedule primary; consultation or discovery call as secondary path.',
		'funnel_notes'        => 'Direct booking first; offer consultation for users not ready to book.',
		'cta_placement_notes' => 'Primary: book now / schedule. Secondary: schedule a call or consultation.',
	),
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'bookings',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'consultations',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_contact_request_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'       => 'Contact: booking link primary; consultation request as alternative.',
		'cta_placement_notes' => 'Book CTA first; consultation or discovery-call option clearly secondary.',
	),
);
