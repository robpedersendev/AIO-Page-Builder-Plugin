<?php
/**
 * Subtype section-helper overlays for realtor (subtype-section-helper-overlay-schema; Prompt 425).
 * Buyer-focused and seller-focused (listing) realtor refinements.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Docs\Subtype_Section_Helper_Overlay_Registry;

return array(
	// * Buyer-Focused Realtor: search support, buyer guides, closing.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Supportive and buyer-focused. Emphasize search support, neighborhood insight, and buying journey.',
		'cta_usage_notes'   => 'Primary CTA: start your search, get buyer updates, or schedule a buyer consultation.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Clear and action-oriented. Connect with me, Get buyer updates, or Schedule a call.',
		'cta_usage_notes'   => 'Single CTA to contact or buyer-signup; avoid listing-focused language.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_buyer_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'mlp_listing_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Buyer-centric. Frame listings as saved homes, favorites, or properties that match buyer criteria.',
		'cta_usage_notes'   => 'CTA: save this home, schedule a showing, or get more details. Avoid seller-oriented copy.',
	),
	// * Seller-Focused (Listing) Realtor: listing presentation, staging, marketing.
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_listing_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'hero_conv_02',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Confident and seller-focused. Emphasize listing presentation, marketing, and sale results.',
		'cta_usage_notes'   => 'Primary CTA: get a home value, list with me, or schedule a listing consultation.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_listing_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'cta_booking_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Direct and results-oriented. Get your home value, List with me, or Schedule a listing appointment.',
		'cta_usage_notes'   => 'Single CTA to seller contact or home-value; avoid buyer-focused language.',
	),
	array(
		Subtype_Section_Helper_Overlay_Registry::FIELD_SUBTYPE_KEY => 'realtor_listing_agent',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SECTION_KEY => 'mlp_listing_01',
		Subtype_Section_Helper_Overlay_Registry::FIELD_SCOPE       => Subtype_Section_Helper_Overlay_Registry::SCOPE_SUBTYPE_SECTION_HELPER_OVERLAY,
		Subtype_Section_Helper_Overlay_Registry::FIELD_STATUS      => Subtype_Section_Helper_Overlay_Registry::STATUS_ACTIVE,
		'tone_notes'        => 'Seller-centric. Highlight sold listings, marketing approach, and seller success.',
		'cta_usage_notes'   => 'CTA: list your home, get a valuation, or see my seller services. Avoid buyer-only framing.',
	),
);
