<?php
/**
 * Immutable result of industry section recommendation resolution (industry-section-recommendation-contract.md).
 * Holds ranked items with score, fit classification, and explanation metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only result: list of per-section recommendation items, ordered by score descending.
 */
final class Industry_Section_Recommendation_Result {

	/** @var array<int, array{section_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}> */
	private array $items;

	/**
	 * @param array<int, array{section_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}> $items
	 */
	public function __construct( array $items = array() ) {
		$this->items = $items;
	}

	/**
	 * Returns all recommendation items (order preserved from resolver: ranked by score desc, then by section_key).
	 *
	 * @return array<int, array{section_key: string, score: int, fit_classification: string, explanation_reasons: array, industry_source_refs: array, warning_flags: array, subtype_influence_applied?: bool, subtype_reason_summary?: string}>
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Returns section keys in recommendation order (best first).
	 *
	 * @return list<string>
	 */
	public function get_ranked_keys(): array {
		$out = array();
		foreach ( $this->items as $item ) {
			$key = $item['section_key'] ?? '';
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
