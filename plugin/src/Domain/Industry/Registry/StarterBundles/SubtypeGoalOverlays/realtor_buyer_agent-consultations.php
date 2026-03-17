<?php
/**
 * Combined subtype+goal overlay: Buyer-Focused Realtor + Consultations (Prompt 552).
 * Admitted: buyer agents benefit from joint consultation-flow nuance (buyer consultation, discovery call) that is stronger than generic realtor + goal overlay alone.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'realtor_buyer_agent_consultations',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'realtor_buyer_agent',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY                => 'consultations',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF      => 'realtor_buyer_agent_starter',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS       => array( 'cta_consultation_01', 'fb_why_choose_01' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE             => 'buyer-consultation-discovery',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE            => 'consultation-led-buyer-journey',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                  => Subtype_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER          => Subtype_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
