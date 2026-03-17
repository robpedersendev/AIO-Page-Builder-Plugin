<?php
/**
 * Secondary-goal starter-bundle overlay: primary estimates + secondary calls (Prompt 542).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'estimates_calls',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY      => 'estimates',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY    => 'calls',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF      => '',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape' ),
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS       => array( 'lpu_contact_panel_01' ),
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE            => 'estimate-request-with-call-option',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE           => 'direct-conversion-with-call',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRECEDENCE_MARKER     => Secondary_Goal_Starter_Bundle_Overlay_Registry::PRECEDENCE_SECONDARY,
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                 => Secondary_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER        => Secondary_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
