<?php
/**
 * Subtype page one-pager overlays for disaster recovery (subtype-page-onepager-overlay-schema; Prompt 427).
 * Residential and commercial restoration refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;

return array(
	// * Residential Restoration.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'disaster_recovery_residential',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; emphasize rapid response, insurance coordination, and residential restoration.',
		'cta_strategy'    => 'Primary: emergency line, request assessment, or start a claim. One clear action; homeowner-focused.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'disaster_recovery_residential',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for assessment requests or claim assistance. Set response expectation; one CTA (e.g. get help now).',
	),
	// * Commercial Restoration.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'disaster_recovery_commercial',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Landing as primary; emphasize business continuity, rapid response, and commercial-scale mitigation.',
		'cta_strategy'    => 'Primary: 24/7 commercial line, request commercial assessment, or contact commercial team. Business-focused.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'disaster_recovery_commercial',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_contact_request_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'cta_strategy' => 'Use for commercial assessment or emergency response. One CTA; avoid residential-only framing.',
	),
);
