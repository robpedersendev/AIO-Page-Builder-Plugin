<?php
/**
 * Secondary-goal page one-pager overlays: primary estimates + secondary calls (Prompt 546).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'estimates',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'calls',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_home_conversion_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'structure_notes'     => 'Estimate/quote request primary; call or callback as secondary for urgency.',
		'funnel_notes'        => 'Quote-first funnel with call option for users who prefer immediate contact.',
		'cta_placement_notes' => 'Primary: request estimate. Secondary: call now or request callback.',
	),
	array(
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY   => 'estimates',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'calls',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY          => 'pt_contact_request_01',
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE             => Secondary_Goal_Page_OnePager_Overlay_Registry::SCOPE_SECONDARY_GOAL_PAGE_ONEPAGER_OVERLAY,
		Secondary_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS            => Secondary_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'funnel_notes'       => 'Contact: estimate request form primary; phone/callback secondary.',
		'cta_placement_notes' => 'Estimate form prominent; phone number visible for call option.',
	),
);
