<?php
/**
 * Extends content gap expectations by subtype context (Prompt 448).
 * Subtype-specific expectations (e.g. mobile service proof, commercial credibility, buyer/seller balance)
 * refine or add to parent-industry expectations. Advisory only; safe fallback when subtype empty or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Provides subtype-specific content gap expectations and optional refinement for gap explanations.
 */
final class Industry_Subtype_Content_Gap_Extender {

	/** Subtype expectations: (industry_key, subtype_key) => [ gap_type => severity ]. Additive or override on parent. */
	private const SUBTYPE_EXPECTATIONS = array(
		'cosmetology_nail' => array(
			'cosmetology_nail_mobile_tech' => array(
				Industry_Content_Gap_Detector::GAP_SERVICE_AREA => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF  => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
			),
			'cosmetology_nail_luxury_salon' => array(
				Industry_Content_Gap_Detector::GAP_STAFF_BIOS => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_GALLERY    => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
			),
		),
		'realtor' => array(
			'realtor_buyer_agent' => array(
				Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF           => Industry_Content_Gap_Detector::SEVERITY_INFO,
			),
			'realtor_listing_agent' => array(
				Industry_Content_Gap_Detector::GAP_GALLERY     => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF => Industry_Content_Gap_Detector::SEVERITY_INFO,
			),
		),
		'plumber' => array(
			'plumber_residential' => array(
				Industry_Content_Gap_Detector::GAP_EMERGENCY_RESPONSE => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_SERVICE_AREA       => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
			),
			'plumber_commercial' => array(
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF   => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_SERVICE_AREA => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
			),
		),
		'disaster_recovery' => array(
			'disaster_recovery_residential' => array(
				Industry_Content_Gap_Detector::GAP_EMERGENCY_RESPONSE => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF        => Industry_Content_Gap_Detector::SEVERITY_INFO,
			),
			'disaster_recovery_commercial' => array(
				Industry_Content_Gap_Detector::GAP_EMERGENCY_RESPONSE => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
				Industry_Content_Gap_Detector::GAP_SERVICE_AREA       => Industry_Content_Gap_Detector::SEVERITY_CAUTION,
			),
		),
	);

	/** Subtype refinement: (industry_key, subtype_key, gap_type) => [ refined_action_summary?, additive_note? ]. */
	private const SUBTYPE_REFINEMENTS = array(
		'cosmetology_nail' => array(
			'cosmetology_nail_mobile_tech' => array(
				Industry_Content_Gap_Detector::GAP_SERVICE_AREA => array(
					'refined_action_summary' => 'Define service area, travel radius, or booking locations so clients know where you serve.',
					'additive_note'          => 'Mobile services rely on clear service-area and availability messaging.',
				),
			),
		),
		'realtor' => array(
			'realtor_buyer_agent' => array(
				Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
					'additive_note' => 'Buyer-focused: CMA, search tools, and lead capture are especially relevant.',
				),
			),
			'realtor_listing_agent' => array(
				Industry_Content_Gap_Detector::GAP_GALLERY => array(
					'additive_note' => 'Listing-focused: portfolio and listing presentation support seller marketing.',
				),
			),
		),
		'plumber' => array(
			'plumber_commercial' => array(
				Industry_Content_Gap_Detector::GAP_TRUST_PROOF => array(
					'additive_note' => 'Commercial clients often expect credentials, compliance, and maintenance capability proof.',
				),
			),
		),
	);

	private const REFINEMENT_SUMMARY_MAX = 256;
	private const ADDITIVE_NOTE_MAX      = 256;

	/**
	 * Returns subtype-specific expectations (gap_type => severity). Empty when industry/subtype not configured.
	 * Used to merge with parent expectations: subtype overrides or adds; parent remains base.
	 *
	 * @param string $industry_key Parent industry pack key.
	 * @param string $subtype_key  Subtype key.
	 * @return array<string, string> Map of gap_type => severity.
	 */
	public function get_expectations( string $industry_key, string $subtype_key ): array {
		$industry_key = trim( $industry_key );
		$subtype_key  = trim( $subtype_key );
		if ( $industry_key === '' || $subtype_key === '' ) {
			return array();
		}
		return self::SUBTYPE_EXPECTATIONS[ $industry_key ][ $subtype_key ] ?? array();
	}

	/**
	 * Returns optional refinement for a gap type in subtype context (refined_action_summary, additive_note).
	 * Caller may attach to gap result as subtype_influence.
	 *
	 * @param string $industry_key Parent industry pack key.
	 * @param string $subtype_key  Subtype key.
	 * @param string $gap_type     Gap type constant (e.g. GAP_SERVICE_AREA).
	 * @return array{refined_action_summary?: string, additive_note?: string}|null Null when no refinement.
	 */
	public function get_refinement( string $industry_key, string $subtype_key, string $gap_type ): ?array {
		$industry_key = trim( $industry_key );
		$subtype_key  = trim( $subtype_key );
		$gap_type     = trim( $gap_type );
		if ( $industry_key === '' || $subtype_key === '' || $gap_type === '' ) {
			return null;
		}
		$ref = self::SUBTYPE_REFINEMENTS[ $industry_key ][ $subtype_key ][ $gap_type ] ?? null;
		if ( $ref === null || ! is_array( $ref ) ) {
			return null;
		}
		$out = array();
		if ( isset( $ref['refined_action_summary'] ) && is_string( $ref['refined_action_summary'] ) ) {
			$s = trim( $ref['refined_action_summary'] );
			if ( strlen( $s ) > self::REFINEMENT_SUMMARY_MAX ) {
				$s = substr( $s, 0, self::REFINEMENT_SUMMARY_MAX - 3 ) . '...';
			}
			$out['refined_action_summary'] = $s;
		}
		if ( isset( $ref['additive_note'] ) && is_string( $ref['additive_note'] ) ) {
			$s = trim( $ref['additive_note'] );
			if ( strlen( $s ) > self::ADDITIVE_NOTE_MAX ) {
				$s = substr( $s, 0, self::ADDITIVE_NOTE_MAX - 3 ) . '...';
			}
			$out['additive_note'] = $s;
		}
		return $out !== array() ? $out : null;
	}
}
