<?php
/**
 * Immutable result of industry page-template recommendation resolution (industry-page-template-recommendation-contract.md).
 * Holds ranked items with score, fit classification, hierarchy_fit, lpagery_fit, and explanation metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only result: list of per-page-template recommendation items, ordered by score descending.
 */
final class Industry_Page_Template_Recommendation_Result {

	/** @var array<int, array{page_template_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, hierarchy_fit: string, lpagery_fit: string, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}> */
	private array $items;

	/**
	 * @param array<int, array{page_template_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, hierarchy_fit: string, lpagery_fit: string, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}> $items
	 */
	public function __construct( array $items = array() ) {
		$this->items = $items;
	}

	/**
	 * Returns all recommendation items (order preserved: ranked by score desc, then by page_template_key).
	 *
	 * @return array<int, array{page_template_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, hierarchy_fit: string, lpagery_fit: string, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Returns page template keys in recommendation order (best first).
	 *
	 * @return list<string>
	 */
	public function get_ranked_keys(): array {
		$out = array();
		foreach ( $this->items as $item ) {
			$key = $item['page_template_key'] ?? '';
			if ( $key !== '' ) {
				$out[] = $key;
			}
		}
		return $out;
	}

	/**
	 * Machine-readable shape for APIs and logging.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array( 'items' => $this->items );
	}
}
