<?php
/**
 * Combined subtype+goal overlay: Commercial Restoration + Calls / Emergency (Prompt 552).
 * Admitted: commercial restoration benefits from emergency-call posture (24/7 dispatch, commercial claim) that joint overlay expresses clearly.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'disaster_recovery_commercial_calls',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'disaster_recovery_commercial',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY                => 'calls',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF      => 'disaster_recovery_commercial_starter',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS       => array( 'cta_consultation_01', 'tp_trust_band_01' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE             => 'emergency-dispatch-24-7',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE            => 'commercial-emergency-response',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                  => Subtype_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER          => Subtype_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
