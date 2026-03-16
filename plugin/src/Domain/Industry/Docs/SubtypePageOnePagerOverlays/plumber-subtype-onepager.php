<?php
/**
 * Subtype page one-pager overlays for plumber (subtype-page-onepager-overlay-schema; Prompt 427).
 * Residential and commercial plumber refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;

return array(
	// * Residential Plumber.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'plumber_residential',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; emphasize repairs, installations, and emergency response for homeowners.',
		'cta_strategy'    => 'Primary: request service, schedule repair, or call for emergency. One clear action.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'plumber_residential',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for service requests or estimates. Set response-time expectation; one CTA (e.g. request callback).',
	),
	// * Commercial Plumber.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'plumber_commercial',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home/landing as primary; emphasize maintenance contracts, installations, and commercial compliance.',
		'cta_strategy'    => 'Primary: request quote, schedule inspection, or contact commercial team. Business-oriented.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'plumber_commercial',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for commercial quotes or inspection requests. One CTA; avoid residential-only language.',
	),
);
