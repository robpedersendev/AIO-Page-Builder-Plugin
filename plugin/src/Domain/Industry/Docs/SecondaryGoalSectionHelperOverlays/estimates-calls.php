<?php
/**
 * Secondary-goal section-helper overlays: primary estimates + secondary calls (Prompt 544).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Secondary_Goal_Section_Helper_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'estimates',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'calls',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Estimate/quote request primary; offer call or callback as secondary for urgency.',
	),
	array(
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY => 'estimates',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY => 'calls',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'lpu_contact_panel_01',
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_SCOPE => Secondary_Goal_Section_Helper_Overlay_Registry::SCOPE_SECONDARY_GOAL_SECTION_HELPER_OVERLAY,
		Secondary_Goal_Section_Helper_Overlay_Registry::FIELD_STATUS => Secondary_Goal_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'cta_usage_notes' => 'Estimate request form primary; phone/callback as secondary for immediate contact.',
	),
);
