<?php
/**
 * Conversion-goal page one-pager overlays for bookings goal (Prompt 508).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_home_conversion_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE  => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'                                 => 'Lead with book/schedule/reserve; show availability or scheduler entry where possible.',
		'funnel_notes'                                    => 'Booking-first funnel: direct path to scheduler or reservation; reduce friction to book.',
		'cta_placement_notes'                             => 'Primary CTA: book now, schedule, or reserve. Link to calendar/scheduler; one clear next step.',
	),
	array(
		Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'bookings',
		Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_contact_request_01',
		Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE  => Goal_Page_OnePager_Overlay_Registry::SCOPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'                                    => 'Contact page supports booking when scheduler link is primary; form for booking request if no live calendar.',
		'cta_placement_notes'                             => 'Prefer booking/scheduler CTA over generic contact; set booking expectation.',
	),
);
