<?php
/**
 * Built-in industry compliance and caution rules (industry-compliance-rule-schema.md, Prompt 405).
 * Advisory only; no legal advice. Supports claims, certification, testimonial, pricing, and local-market cautions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'rule_key'        => 'cosmetology_license_claims',
		'industry_key'    => 'cosmetology_nail',
		'severity'        => 'caution',
		'caution_summary' => 'License and certification claims must be accurate and current.',
		'guidance_text'   => 'Do not imply endorsement without permission. Only display real licenses, certifications, or awards. Verify compliance with state/board requirements.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'cosmetology_testimonial_disclosure',
		'industry_key'    => 'cosmetology_nail',
		'severity'        => 'info',
		'caution_summary' => 'Testimonials and reviews should be genuine; avoid misleading before/after.',
		'guidance_text'   => 'Use real client feedback where permitted. Before/after imagery may require consent and disclosure; do not overclaim results.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'realtor_mls_board',
		'industry_key'    => 'realtor',
		'severity'        => 'warning',
		'caution_summary' => 'MLS and board rules may govern listings, valuation language, and claims.',
		'guidance_text'   => 'Verify permitted language for CMA, valuation, and listing display. Do not imply endorsement or certification without permission.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'realtor_local_market_sensitivity',
		'industry_key'    => 'realtor',
		'severity'        => 'caution',
		'caution_summary' => 'Local and market-area claims should be accurate and not misleading.',
		'guidance_text'   => 'Neighborhood and market descriptions must be factual. Avoid overclaiming expertise or results.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'plumber_license_insurance',
		'industry_key'    => 'plumber',
		'severity'        => 'caution',
		'caution_summary' => 'License and insurance claims must be accurate; jurisdiction rules may apply.',
		'guidance_text'   => 'Verify license and insurance disclosure requirements for your jurisdiction. Do not overclaim guarantees or response time.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'plumber_pricing_disclosure',
		'industry_key'    => 'plumber',
		'severity'        => 'info',
		'caution_summary' => 'Pricing and financing messaging should be clear and accurate.',
		'guidance_text'   => 'Avoid hidden fees or misleading offers. Financing options must be presented accurately.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'disaster_recovery_certification',
		'industry_key'    => 'disaster_recovery',
		'severity'        => 'warning',
		'caution_summary' => 'Certification claims (e.g. IICRC) must be accurate; do not imply endorsement.',
		'guidance_text'   => 'Only display current, valid certifications. Insurance and compliance claims must be accurate. Do not overclaim response time or results.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'disaster_recovery_insurance_assistance',
		'industry_key'    => 'disaster_recovery',
		'severity'        => 'caution',
		'caution_summary' => 'Insurance assistance messaging must be accurate; do not provide legal advice.',
		'guidance_text'   => 'Frame insurance/claims assistance as coordination support only. Do not guarantee outcomes or provide legal or insurance advice.',
		'status'          => 'active',
	),
	// * Cosmetology/nail: wording and product claims (Prompt 406).
	array(
		'rule_key'        => 'cosmetology_product_claims',
		'industry_key'    => 'cosmetology_nail',
		'severity'        => 'caution',
		'caution_summary' => 'Product and result claims must be accurate; avoid overclaiming outcomes.',
		'guidance_text'   => 'Do not make unsubstantiated claims about products or results. Avoid absolute or guaranteed-outcome language. Follow applicable advertising standards.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'cosmetology_pricing_disclosure',
		'industry_key'    => 'cosmetology_nail',
		'severity'        => 'info',
		'caution_summary' => 'Pricing and package descriptions should be clear; avoid hidden fees.',
		'guidance_text'   => 'Present pricing and packages clearly. Promotions and add-ons should be disclosed. Do not use misleading "from" or minimum pricing.',
		'status'          => 'active',
	),
	// * Realtor: pricing/disclosure, review/testimonial (Prompt 406).
	array(
		'rule_key'        => 'realtor_pricing_valuation',
		'industry_key'    => 'realtor',
		'severity'        => 'caution',
		'caution_summary' => 'Valuation and pricing language must be accurate; avoid misleading estimates.',
		'guidance_text'   => 'CMA and valuation language must comply with board/MLS rules. Do not guarantee sale price or imply certainty. Disclose limitations of estimates.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'realtor_testimonial_review',
		'industry_key'    => 'realtor',
		'severity'        => 'info',
		'caution_summary' => 'Client testimonials and reviews should be genuine; avoid misleading success claims.',
		'guidance_text'   => 'Use real client feedback where permitted. Do not overclaim results or imply endorsement. Verify consent for use of names or likeness.',
		'status'          => 'active',
	),
	// * Plumber: emergency-service sensitivity, review/testimonial (Prompt 406).
	array(
		'rule_key'        => 'plumber_emergency_claims',
		'industry_key'    => 'plumber',
		'severity'        => 'warning',
		'caution_summary' => 'Emergency and response-time claims must be accurate; avoid guaranteed response.',
		'guidance_text'   => 'Do not guarantee response time or 24/7 availability unless you can deliver. Emergency service claims may be regulated; verify jurisdiction.',
		'status'          => 'active',
	),
	array(
		'rule_key'        => 'plumber_testimonial_disclosure',
		'industry_key'    => 'plumber',
		'severity'        => 'info',
		'caution_summary' => 'Testimonials should be genuine; avoid misleading before/after or outcome claims.',
		'guidance_text'   => 'Use real client feedback. Do not overclaim results or imply guarantees. Obtain consent where required.',
		'status'          => 'active',
	),
	// * Disaster recovery: emergency-service sensitivity (Prompt 406).
	array(
		'rule_key'        => 'disaster_recovery_emergency_response',
		'industry_key'    => 'disaster_recovery',
		'severity'        => 'warning',
		'caution_summary' => 'Emergency and response-time claims must be accurate; avoid guaranteed availability.',
		'guidance_text'   => 'Do not guarantee 24/7 response or specific arrival times unless you can deliver. Emergency claims may be sensitive; verify insurance and contract language.',
		'status'          => 'active',
	),
);
