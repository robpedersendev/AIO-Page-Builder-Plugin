<?php
/**
 * Applies subtype influence to section recommendation results (Prompt 422; industry-section-recommendation-contract).
 * Additive overlay on parent-industry scoring; safe when subtype is null or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Extends base section recommendation result with optional subtype weighting and explanation.
 */
final class Industry_Subtype_Section_Recommendation_Extender {

	private const SUBTYPE_BOOST_SCORE = 5;
	private const REASON_SUBTYPE_OVERLAY_PRIORITY = 'subtype_overlay_priority';
	private const SCORE_RECOMMENDED_MIN = 20;
	private const SCORE_DISCOURAGED_MAX = -10;

	/**
	 * Applies subtype influence to the base result. When subtype is null or empty, returns result with subtype fields set to false/empty per item (no score change).
	 * When subtype has helper_overlay_refs, sections in that list receive a small score boost and subtype_reason_summary.
	 *
	 * @param Industry_Section_Recommendation_Result $base_result Result from parent-industry resolution.
	 * @param array<string, mixed>|null              $subtype_definition Subtype definition (e.g. from Industry_Subtype_Registry) or null.
	 * @param array<int, array<string, mixed>>       $sections Section definitions (for consistency; not required for overlay refs).
	 * @return Industry_Section_Recommendation_Result New result with optional subtype_influence_applied and subtype_reason_summary on each item.
	 */
	public function apply_subtype_influence(
		Industry_Section_Recommendation_Result $base_result,
		?array $subtype_definition,
		array $sections = array()
	): Industry_Section_Recommendation_Result {
		$items = $base_result->get_items();
		$priority_keys = $this->subtype_section_priority_keys( $subtype_definition );
		$new_items = array();
		foreach ( $items as $item ) {
			$section_key = $item['section_key'] ?? '';
			$score = (int) ( $item['score'] ?? 0 );
			$influence_applied = false;
			$reason_summary = '';
			if ( $section_key !== '' && $priority_keys !== array() && in_array( $section_key, $priority_keys, true ) ) {
				$score += self::SUBTYPE_BOOST_SCORE;
				$influence_applied = true;
				$reason_summary = self::REASON_SUBTYPE_OVERLAY_PRIORITY;
			}
			$new_item = array_merge( $item, array( 'score' => $score ) );
			$new_item['fit_classification'] = $this->score_to_fit( $score );
			$new_item['subtype_influence_applied'] = $influence_applied;
			$new_item['subtype_reason_summary'] = $reason_summary;
			$new_items[] = $new_item;
		}
		usort( $new_items, function ( array $a, array $b ) {
			$score_a = $a['score'] ?? 0;
			$score_b = $b['score'] ?? 0;
			if ( $score_b !== $score_a ) {
				return $score_b <=> $score_a;
			}
			return strcmp( $a['section_key'] ?? '', $b['section_key'] ?? '' );
		} );
		return new Industry_Section_Recommendation_Result( $new_items );
	}

	/**
	 * Returns list of section keys to prioritize from subtype helper_overlay_refs (treated as section_key refs).
	 *
	 * @param array<string, mixed>|null $subtype_definition
	 * @return list<string>
	 */
	private function subtype_section_priority_keys( ?array $subtype_definition ): array {
		if ( $subtype_definition === null || ! isset( $subtype_definition['helper_overlay_refs'] ) || ! is_array( $subtype_definition['helper_overlay_refs'] ) ) {
			return array();
		}
		$out = array();
		foreach ( $subtype_definition['helper_overlay_refs'] as $ref ) {
			if ( is_string( $ref ) && trim( $ref ) !== '' ) {
				$out[] = trim( $ref );
			}
		}
		return array_values( array_unique( $out ) );
	}

	private function score_to_fit( int $score ): string {
		if ( $score >= self::SCORE_RECOMMENDED_MIN ) {
			return Industry_Section_Recommendation_Resolver::FIT_RECOMMENDED;
		}
		if ( $score <= self::SCORE_DISCOURAGED_MAX ) {
			return Industry_Section_Recommendation_Resolver::FIT_DISCOURAGED;
		}
		return $score > 0 ? Industry_Section_Recommendation_Resolver::FIT_ALLOWED_WEAK : Industry_Section_Recommendation_Resolver::FIT_NEUTRAL;
	}
}
