<?php
/**
 * Subtype page one-pager overlays for realtor (subtype-page-onepager-overlay-schema; Prompt 427).
 * Buyer-focused and seller-focused (listing) realtor refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Page_OnePager_Overlay_Registry;

return array(
	// * Buyer-Focused Realtor.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_buyer_agent',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; emphasize buyer search support and neighborhood insight. Buyer resources and consultation paths.',
		'cta_strategy'    => 'Primary: start your search, get buyer updates, or schedule a buyer consultation. Avoid listing/seller CTAs.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_buyer_agent',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_services_overview_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Services hub: buyer journey, search support, pre-approval, closing. No seller-only framing.',
		'cta_strategy'    => 'Connect with me, get buyer updates, or schedule a call. Single CTA; buyer-focused.',
	),
	// * Seller-Focused (Listing) Realtor.
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_listing_agent',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_home_conversion_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Home as primary; emphasize listing presentation, home value, and sale results. Seller resources and valuation paths.',
		'cta_strategy'    => 'Primary: get your home value, list with me, or schedule a listing consultation. Avoid buyer-only CTAs.',
	),
	array(
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SUBTYPE_KEY       => 'realtor_listing_agent',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_PAGE_TEMPLATE_KEY => 'pt_services_overview_01',
		Subtype_Page_OnePager_Overlay_Registry::FIELD_SCOPE            => Subtype_Page_OnePager_Overlay_Registry::SCOPE_SUBTYPE_PAGE_ONEPAGER_OVERLAY,
		Subtype_Page_OnePager_Overlay_Registry::FIELD_STATUS           => Subtype_Page_OnePager_Overlay_Registry::STATUS_ACTIVE,
		'hierarchy_hints' => 'Services hub: listing process, staging, marketing, sale process. No buyer-only framing.',
		'cta_strategy'    => 'Get home value, list your home, or schedule listing appointment. Single CTA; seller-focused.',
	),
);
