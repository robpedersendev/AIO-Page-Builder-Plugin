<?php
/**
 * Combined subtype+goal page one-pager overlays: realtor_buyer_agent + consultations (Prompt 554).
 * Admission: buyer-focused consultation flows; joint page-level nuance for contact/consultation request.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Goal_Page_OnePager_Overlay_Registry;

return array(
	array(
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_OVERLAY_KEY => 'realtor_buyer_agent_consultations_contact',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_GOAL_KEY => 'consultations',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_PAGE_KEY => 'pt_contact_request_01',
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_SCOPE => Subtype_Goal_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_GOAL_PAGE_ONEPAGER_OVERLAY,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_STATUS => Subtype_Goal_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		Subtype_Goal_Page_OnePager_Overlay_Registry::FIELD_ALLOWED_OVERRIDE_REGIONS => array( 'cta_strategy', 'hierarchy_hints' ),
		'cta_strategy'    => 'Single CTA: request or schedule buyer consultation; set expectation that agent will reach out to discuss buying journey.',
		'hierarchy_hints' => 'Lead with buyer consultation request; avoid listing/seller contact framing.',
	),
);
