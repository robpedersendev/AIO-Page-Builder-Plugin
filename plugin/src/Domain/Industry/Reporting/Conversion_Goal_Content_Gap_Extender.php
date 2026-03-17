<?php
/**
 * Extends content-gap detection with conversion-goal context (Prompt 504).
 * Refines severity and explanation for missing assets (phone-first proof, booking signals, valuation lead magnets, etc.).
 * Advisory only; no-goal fallback preserved.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;

/**
 * Provides goal-specific refinement for content gap results (refined_action_summary, additive_note per gap_type).
 */
final class Conversion_Goal_Content_Gap_Extender {

	/** Result key: goal influence on this gap (refined_action_summary?, additive_note?). */
	public const RESULT_GOAL_INFLUENCE = 'goal_influence';

	private const REFINEMENT_SUMMARY_MAX = 256;
	private const ADDITIVE_NOTE_MAX      = 256;

	/** Goal-specific refinement: goal_key => gap_type => [ refined_action_summary?, additive_note? ]. */
	private const GOAL_REFINEMENTS = array(
		'calls' => array(
			Industry_Content_Gap_Detector::GAP_TRUST_PROOF => array(
				'additive_note' => 'For call-focused sites, visible phone number and click-to-call proof are especially relevant.',
			),
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'additive_note' => 'Calls goal: consider lead magnets or offers that encourage phone contact.',
			),
		),
		'bookings' => array(
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'refined_action_summary' => 'Add booking availability signals, scheduler links, or calendar integration so visitors can book directly.',
				'additive_note'          => 'Booking-focused sites benefit from clear availability and booking CTAs.',
			),
		),
		'estimates' => array(
			Industry_Content_Gap_Detector::GAP_TRUST_PROOF => array(
				'additive_note' => 'Estimate/quote goals: trust cues (certifications, reviews) support form submission.',
			),
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'refined_action_summary' => 'Add estimate-request or quote-request paths with clear expectations and trust cues.',
				'additive_note'          => 'Estimate-focused funnel benefits from structured request flow.',
			),
		),
		'consultations' => array(
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'refined_action_summary' => 'Add consultation offer, scheduling, or contact path for consultation requests.',
				'additive_note'          => 'Consultation goal: make the consultation CTA and value clear.',
			),
		),
		'valuations' => array(
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'refined_action_summary' => 'Add valuation tool, CMA, or valuation lead magnet where appropriate.',
				'additive_note'          => 'Valuation-focused sites need clear valuation entry points.',
			),
		),
		'lead_capture' => array(
			Industry_Content_Gap_Detector::GAP_VALUATION_CONVERSION => array(
				'refined_action_summary' => 'Add lead capture forms, gated content, or signup paths with clear value exchange.',
				'additive_note'          => 'Lead capture goal: emphasize form placement and offer clarity.',
			),
		),
	);

	/**
	 * Returns goal-specific refinement for a gap type (refined_action_summary, additive_note). Null when none.
	 *
	 * @param string $goal_key Conversion goal key.
	 * @param string $gap_type Gap type constant (e.g. GAP_TRUST_PROOF).
	 * @return array{refined_action_summary?: string, additive_note?: string}|null
	 */
	public function get_refinement( string $goal_key, string $gap_type ): ?array {
		$goal_key = trim( $goal_key );
		$gap_type = trim( $gap_type );
		if ( $goal_key === '' || $gap_type === '' ) {
			return null;
		}
		$ref = self::GOAL_REFINEMENTS[ $goal_key ][ $gap_type ] ?? null;
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

	/**
	 * Applies goal refinement to a single gap result. Merges goal_influence into the gap item when refinement exists.
	 *
	 * @param array<string, mixed> $gap_item One gap result from Industry_Content_Gap_Detector::detect().
	 * @param string               $goal_key Resolved conversion goal key from profile.
	 * @return array<string, mixed> Gap item with optional goal_influence key.
	 */
	public function apply_to_gap_item( array $gap_item, string $goal_key ): array {
		$gap_type = isset( $gap_item['gap_type'] ) && is_string( $gap_item['gap_type'] ) ? $gap_item['gap_type'] : '';
		$refinement = $this->get_refinement( $goal_key, $gap_type );
		if ( $refinement === null ) {
			return $gap_item;
		}
		if ( isset( $refinement['refined_action_summary'] ) && $refinement['refined_action_summary'] !== '' ) {
			$gap_item['recommended_action_summary'] = $refinement['refined_action_summary'];
		}
		$gap_item[ self::RESULT_GOAL_INFLUENCE ] = $refinement;
		return $gap_item;
	}
}
