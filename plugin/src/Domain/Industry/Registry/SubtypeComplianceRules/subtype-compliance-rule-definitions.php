<?php
/**
 * Built-in subtype compliance and caution rules (subtype-compliance-rule-schema.md, Prompt 447).
 * Advisory only; refines or adds to parent-industry rules for launch subtypes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	// * Cosmetology / Nail — Mobile Nail Tech: service area, travel, and location claims.
	array(
		'subtype_rule_key'        => 'cosmetology_mobile_service_area',
		'subtype_key'             => 'cosmetology_nail_mobile_tech',
		'parent_industry_key'     => 'cosmetology_nail',
		'severity'                => 'caution',
		'caution_summary'         => 'Service area and travel claims (e.g. "we come to you") must be accurate; avoid overclaiming availability or coverage.',
		'guidance_text'           => 'Clearly state service area, travel radius, or booking conditions. Do not imply unlimited availability or guarantee arrival times.',
		'status'                  => 'active',
	),
	array(
		'subtype_rule_key'        => 'cosmetology_mobile_booking_disclosure',
		'subtype_key'             => 'cosmetology_nail_mobile_tech',
		'parent_industry_key'     => 'cosmetology_nail',
		'severity'                => 'info',
		'caution_summary'         => 'Mobile booking and scheduling messaging should be clear (location, minimums, travel fees).',
		'status'                  => 'active',
	),
	// * Cosmetology / Nail — Luxury Salon: premium and experience claims.
	array(
		'subtype_rule_key'        => 'cosmetology_luxury_premium_claims',
		'subtype_key'             => 'cosmetology_nail_luxury_salon',
		'parent_industry_key'     => 'cosmetology_nail',
		'severity'                => 'caution',
		'caution_summary'         => 'Premium and luxury experience claims must be accurate; avoid overclaiming exclusivity or results.',
		'status'                  => 'active',
	),
	// * Realtor — Buyer agent: valuation and buyer-focused language.
	array(
		'subtype_rule_key'        => 'realtor_buyer_valuation_language',
		'subtype_key'             => 'realtor_buyer_agent',
		'parent_industry_key'     => 'realtor',
		'severity'                => 'caution',
		'caution_summary'         => 'Buyer-side valuation and search support language must be accurate; do not guarantee outcomes or imply board/MLS endorsement.',
		'refinement_of_rule_key'  => 'realtor_pricing_valuation',
		'status'                  => 'active',
	),
	array(
		'subtype_rule_key'        => 'realtor_buyer_preapproval_disclosure',
		'subtype_key'             => 'realtor_buyer_agent',
		'parent_industry_key'     => 'realtor',
		'severity'                => 'info',
		'caution_summary'         => 'Pre-approval and financing guidance should be framed as referral/support only; not financial or lending advice.',
		'status'                  => 'active',
	),
	// * Realtor — Listing agent: listing and seller-focused language.
	array(
		'subtype_rule_key'        => 'realtor_listing_marketing_claims',
		'subtype_key'             => 'realtor_listing_agent',
		'parent_industry_key'     => 'realtor',
		'severity'                => 'caution',
		'caution_summary'         => 'Listing and marketing claims (staging, exposure, sale process) must be accurate; comply with board and MLS rules.',
		'refinement_of_rule_key'  => 'realtor_local_market_sensitivity',
		'status'                  => 'active',
	),
	// * Plumber — Residential: emergency and home-service claims.
	array(
		'subtype_rule_key'        => 'plumber_residential_emergency_refinement',
		'subtype_key'             => 'plumber_residential',
		'parent_industry_key'     => 'plumber',
		'severity'                => 'warning',
		'caution_summary'         => 'Residential emergency and response-time claims must be accurate; avoid guaranteed response or 24/7 unless you can deliver.',
		'refinement_of_rule_key'  => 'plumber_emergency_claims',
		'status'                  => 'active',
	),
	// * Plumber — Commercial: maintenance and compliance claims.
	array(
		'subtype_rule_key'        => 'plumber_commercial_compliance',
		'subtype_key'             => 'plumber_commercial',
		'parent_industry_key'     => 'plumber',
		'severity'                => 'caution',
		'caution_summary'         => 'Commercial and maintenance capability claims must be accurate; jurisdiction and contract compliance may apply.',
		'status'                  => 'active',
	),
	array(
		'subtype_rule_key'        => 'plumber_commercial_contract_language',
		'subtype_key'             => 'plumber_commercial',
		'parent_industry_key'     => 'plumber',
		'severity'                => 'info',
		'caution_summary'         => 'Contract and service-level messaging should be clear; avoid implying guarantees beyond written agreements.',
		'status'                  => 'active',
	),
	// * Disaster recovery — Residential: homeowner and insurance focus.
	array(
		'subtype_rule_key'        => 'disaster_recovery_residential_insurance_refinement',
		'subtype_key'             => 'disaster_recovery_residential',
		'parent_industry_key'     => 'disaster_recovery',
		'severity'                => 'caution',
		'caution_summary'         => 'Residential insurance and homeowner assistance messaging must be accurate; do not provide legal or insurance advice.',
		'refinement_of_rule_key'  => 'disaster_recovery_insurance_assistance',
		'status'                  => 'active',
	),
	// * Disaster recovery — Commercial: business continuity and scale.
	array(
		'subtype_rule_key'        => 'disaster_recovery_commercial_continuity',
		'subtype_key'             => 'disaster_recovery_commercial',
		'parent_industry_key'     => 'disaster_recovery',
		'severity'                => 'caution',
		'caution_summary'         => 'Commercial restoration and business-continuity claims must be accurate; avoid overclaiming scale or response capability.',
		'status'                  => 'active',
	),
);
