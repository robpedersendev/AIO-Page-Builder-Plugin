<?php
/**
 * Applies subtype influence to page-template recommendation results (Prompt 423; industry-page-template-recommendation-contract).
 * Additive overlay on parent-industry scoring (page_family_emphasis, one_pager_overlay_refs); safe when subtype is null or invalid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;

/**
 * Extends base page-template recommendation result with optional subtype weighting and explanation.
 */
final class Industry_Subtype_Page_Template_Recommendation_Extender {

	private const SUBTYPE_BOOST_SCORE         = 5;
	private const REASON_PAGE_FAMILY_EMPHASIS = 'subtype_page_family_emphasis';
	private const REASON_ONE_PAGER_PRIORITY   = 'subtype_one_pager_priority';
	private const SCORE_RECOMMENDED_MIN       = 20;
	private const SCORE_DISCOURAGED_MAX       = -10;
	private const TEMPLATE_FAMILY_FIELD       = 'template_family';

	/**
	 * Applies subtype influence to the base result. When subtype is null or empty, returns result with subtype fields set to false/empty per item (no score change).
	 * When subtype has page_family_emphasis, templates in those families get a score boost. When subtype has one_pager_overlay_refs, those template keys get a boost.
	 *
	 * @param Industry_Page_Template_Recommendation_Result $base_result Result from parent-industry resolution.
	 * @param array<string, mixed>|null                    $subtype_definition Subtype definition (e.g. from Industry_Subtype_Registry) or null.
	 * @param array<int, array<string, mixed>>             $page_templates List of page template definitions (internal_key, template_family).
	 * @return Industry_Page_Template_Recommendation_Result New result with optional subtype_influence_applied and subtype_reason_summary on each item.
	 */
	public function apply_subtype_influence(
		Industry_Page_Template_Recommendation_Result $base_result,
		?array $subtype_definition,
		array $page_templates = array()
	): Industry_Page_Template_Recommendation_Result {
		$items                  = $base_result->get_items();
		$family_emphasis        = $this->subtype_page_family_emphasis( $subtype_definition );
		$one_pager_refs         = $this->subtype_one_pager_overlay_refs( $subtype_definition );
		$template_family_by_key = $this->template_family_by_key( $page_templates );
		$new_items              = array();
		foreach ( $items as $item ) {
			$template_key      = $item['page_template_key'] ?? '';
			$score             = (int) ( $item['score'] ?? 0 );
			$influence_applied = false;
			$reason_summary    = '';
			$family            = $template_family_by_key[ $template_key ] ?? '';
			if ( $template_key !== '' ) {
				if ( $family !== '' && $family_emphasis !== array() && in_array( $family, $family_emphasis, true ) ) {
					$score            += self::SUBTYPE_BOOST_SCORE;
					$influence_applied = true;
					$reason_summary    = $reason_summary === '' ? self::REASON_PAGE_FAMILY_EMPHASIS : $reason_summary . ';' . self::REASON_PAGE_FAMILY_EMPHASIS;
				}
				if ( $one_pager_refs !== array() && in_array( $template_key, $one_pager_refs, true ) ) {
					$score            += self::SUBTYPE_BOOST_SCORE;
					$influence_applied = true;
					$reason_summary    = $reason_summary === '' ? self::REASON_ONE_PAGER_PRIORITY : $reason_summary . ';' . self::REASON_ONE_PAGER_PRIORITY;
				}
			}
			$new_item                              = array_merge( $item, array( 'score' => $score ) );
			$new_item['fit_classification']        = $this->score_to_fit( $score );
			$new_item['subtype_influence_applied'] = $influence_applied;
			$new_item['subtype_reason_summary']    = trim( $reason_summary, ';' );
			$new_items[]                           = $new_item;
		}
		usort(
			$new_items,
			function ( array $a, array $b ) {
				$score_a = $a['score'] ?? 0;
				$score_b = $b['score'] ?? 0;
				if ( $score_b !== $score_a ) {
					return $score_b <=> $score_a;
				}
				return strcmp( $a['page_template_key'] ?? '', $b['page_template_key'] ?? '' );
			}
		);
		return new Industry_Page_Template_Recommendation_Result( $new_items );
	}

	/**
	 * @param array<string, mixed>|null $subtype_definition
	 * @return array<int, string>
	 */
	private function subtype_page_family_emphasis( ?array $subtype_definition ): array {
		if ( $subtype_definition === null || ! isset( $subtype_definition['page_family_emphasis'] ) || ! is_array( $subtype_definition['page_family_emphasis'] ) ) {
			return array();
		}
		$out = array();
		foreach ( $subtype_definition['page_family_emphasis'] as $v ) {
			if ( is_string( $v ) && trim( $v ) !== '' ) {
				$out[] = trim( $v );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<string, mixed>|null $subtype_definition
	 * @return array<int, string>
	 */
	private function subtype_one_pager_overlay_refs( ?array $subtype_definition ): array {
		if ( $subtype_definition === null || ! isset( $subtype_definition['one_pager_overlay_refs'] ) || ! is_array( $subtype_definition['one_pager_overlay_refs'] ) ) {
			return array();
		}
		$out = array();
		foreach ( $subtype_definition['one_pager_overlay_refs'] as $ref ) {
			if ( is_string( $ref ) && trim( $ref ) !== '' ) {
				$out[] = trim( $ref );
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $page_templates
	 * @return array<string, string> Map of page_template_key => template_family.
	 */
	private function template_family_by_key( array $page_templates ): array {
		$out = array();
		foreach ( $page_templates as $t ) {
			if ( ! is_array( $t ) ) {
				continue;
			}
			$key = isset( $t[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ) && is_string( $t[ Page_Template_Schema::FIELD_INTERNAL_KEY ] )
				? trim( $t[ Page_Template_Schema::FIELD_INTERNAL_KEY ] )
				: '';
			if ( $key === '' ) {
				continue;
			}
			$family      = isset( $t[ self::TEMPLATE_FAMILY_FIELD ] ) && is_string( $t[ self::TEMPLATE_FAMILY_FIELD ] )
				? trim( $t[ self::TEMPLATE_FAMILY_FIELD ] )
				: '';
			$out[ $key ] = $family;
		}
		return $out;
	}

	private function score_to_fit( int $score ): string {
		if ( $score >= self::SCORE_RECOMMENDED_MIN ) {
			return Industry_Page_Template_Recommendation_Resolver::FIT_RECOMMENDED;
		}
		if ( $score <= self::SCORE_DISCOURAGED_MAX ) {
			return Industry_Page_Template_Recommendation_Resolver::FIT_DISCOURAGED;
		}
		return $score > 0 ? Industry_Page_Template_Recommendation_Resolver::FIT_ALLOWED_WEAK : Industry_Page_Template_Recommendation_Resolver::FIT_NEUTRAL;
	}
}
