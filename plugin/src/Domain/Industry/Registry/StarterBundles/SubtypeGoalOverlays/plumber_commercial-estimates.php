<?php
/**
 * Combined subtype+goal overlay: Commercial Plumber + Estimates (Prompt 552).
 * Admitted: commercial plumbing has distinct estimate flow (project scoping, multi-site) that subtype+goal joint overlay expresses better than separate layers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Subtype_Goal_Starter_Bundle_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_OVERLAY_KEY             => 'plumber_commercial_estimates',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SUBTYPE_KEY             => 'plumber_commercial',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_GOAL_KEY                => 'estimates',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_TARGET_BUNDLE_REF      => 'plumber_commercial_starter',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_ALLOWED_OVERLAY_REGIONS => array( 'section_emphasis', 'cta_posture', 'funnel_shape', 'page_family_emphasis' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_SECTION_EMPHASIS       => array( 'ptf_how_it_works_01', 'lpu_contact_panel_01' ),
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_CTA_POSTURE             => 'commercial-estimate-request',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_FUNNEL_SHAPE             => 'commercial-project-estimate',
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_STATUS                  => Subtype_Goal_Starter_Bundle_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Starter_Bundle_Overlay_Registry::FIELD_VERSION_MARKER          => Subtype_Goal_Starter_Bundle_Overlay_Registry::SUPPORTED_SCHEMA_VERSION,
	),
);
