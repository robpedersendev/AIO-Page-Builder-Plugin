<?php
/**
 * Subtype page one-pager overlays for cosmetology/nail (subtype-page-onepager-overlay-schema; Prompt 427).
 * Luxury salon and mobile nail tech refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;

return array(
	// * Luxury Nail Salon: in-salon experience, premium.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'cosmetology_nail_luxury_salon',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; emphasize in-salon experience and ambiance. No multi-location hierarchy required.',
		'cta_strategy'    => 'Primary: reserve your experience or book a visit. Refined, single CTA; avoid urgency.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'cosmetology_nail_luxury_salon',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for reservation inquiries or experience requests. Set expectation for response; one clear next step.',
	),
	// * Mobile Nail Technician: service area, travel, convenience.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'cosmetology_nail_mobile_tech',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; clarify service area and travel-to-you. No fixed-location hierarchy.',
		'cta_strategy'    => 'Primary: book at your location, check service area, or request a visit. One clear action.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'cosmetology_nail_mobile_tech',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for visit requests or service-area questions. Set response expectation; one CTA (e.g. book at your location).',
	),
);
