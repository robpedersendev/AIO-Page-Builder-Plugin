<?php
/**
 * Secondary-goal starter-bundle overlay: primary consultation + secondary lead nurture (Prompt 542).
 * Lead nurture expressed via lead_capture as secondary goal.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'consultation_lead_nurture',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRIMARY_GOAL_KEY      => 'consultations',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECONDARY_GOAL_KEY   => 'lead_capture',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF     => '',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape' ),
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS      => array( 'gc_contact_form_01' ),
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE           => 'consultation-first-with-lead-nurture',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE          => 'lead-nurture',
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_PRECEDENCE_MARKER     => Secondary_Goal_Starter_Bundle_Overlay_Registry::PRECEDENCE_SECONDARY,
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS               => Secondary_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Secondary_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER      => Secondary_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
