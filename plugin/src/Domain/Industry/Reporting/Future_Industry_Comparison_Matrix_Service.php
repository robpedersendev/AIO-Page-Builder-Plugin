<?php
/**
 * Builds a comparison matrix from multiple future-industry scorecard results (Prompt 473).
 * Internal, advisory only. Supports roadmap prioritization; no auto-selection; no pack mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Compares multiple candidate scorecards side by side for reuse fit, caution burden, subtype complexity, and recommendation.
 * Contract: docs/operations/future-industry-comparison-matrix-template.md.
 */
final class Future_Industry_Comparison_Matrix_Service {

	/** @var array<int, string> */
	private const DIMENSIONS_ORDER = array(
		'content_model_fit',
		'template_overlap',
		'lpagery_posture',
		'cta_complexity',
		'documentation_burden',
		'styling_needs',
		'compliance_caution_burden',
		'starter_bundle_viability',
		'subtype_complexity',
		'long_term_maintenance_cost',
	);

	/**
	 * Builds a comparison matrix from multiple scorecard results.
	 *
	 * @param array<int, array{candidate_label: string, proposed_industry_key?: string, dimension_scores: array<string, int>, aggregate_sum: int, major_risks: list<string>, recommendation: string}> $scorecard_results One result per candidate (from Future_Industry_Scorecard_Executor::execute).
	 * @return array{
	 *   candidates: list<array{label: string, proposed_industry_key: string}>,
	 *   dimension_comparison: array<string, array<string, int>>,
	 *   per_candidate_summary: array<string, array{aggregate_sum: int, recommendation: string, risk_count: int}>,
	 *   reuse_vs_new_build: array<string, string>,
	 *   subtype_caution_highlight: array<string, array{subtype_complexity: int, compliance_caution_burden: int, burden_note: string}>,
	 *   suggested_order: list<string>
	 * }
	 */
	public function build_matrix( array $scorecard_results ): array {
		$candidates = array();
		$per_candidate = array();
		$reuse_vs_new_build = array();
		$subtype_caution = array();
		$dimension_comparison = array_fill_keys( self::DIMENSIONS_ORDER, array() );

		foreach ( $scorecard_results as $result ) {
			if ( ! is_array( $result ) ) {
				continue;
			}
			$label = isset( $result['candidate_label'] ) && is_string( $result['candidate_label'] )
				? trim( $result['candidate_label'] )
				: 'Unknown';
			$key = isset( $result['proposed_industry_key'] ) && is_string( $result['proposed_industry_key'] )
				? trim( $result['proposed_industry_key'] )
				: '';
			if ( $label === '' ) {
				$label = 'Unknown';
			}
			$candidates[] = array( 'label' => $label, 'proposed_industry_key' => $key );

			$scores = isset( $result['dimension_scores'] ) && is_array( $result['dimension_scores'] )
				? $result['dimension_scores']
				: array();
			$aggregate = isset( $result['aggregate_sum'] ) && is_int( $result['aggregate_sum'] )
				? $result['aggregate_sum']
				: 0;
			$risks = isset( $result['major_risks'] ) && is_array( $result['major_risks'] )
				? $result['major_risks']
				: array();
			$recommendation = isset( $result['recommendation'] ) && is_string( $result['recommendation'] )
				? $result['recommendation']
				: 'review';

			$per_candidate[ $label ] = array(
				'aggregate_sum'   => $aggregate,
				'recommendation'  => $recommendation,
				'risk_count'     => count( $risks ),
			);

			foreach ( self::DIMENSIONS_ORDER as $dim ) {
				$dimension_comparison[ $dim ][ $label ] = isset( $scores[ $dim ] ) ? (int) $scores[ $dim ] : 0;
			}

			$reuse_vs_new_build[ $label ] = $this->reuse_vs_new_build_note( $scores );
			$subtype_caution[ $label ] = $this->subtype_caution_entry( $scores );
		}

		$suggested_order = $this->suggest_order( $per_candidate );

		return array(
			'candidates'                => $candidates,
			'dimension_comparison'      => $dimension_comparison,
			'per_candidate_summary'     => $per_candidate,
			'reuse_vs_new_build'        => $reuse_vs_new_build,
			'subtype_caution_highlight' => $subtype_caution,
			'suggested_order'           => $suggested_order,
		);
	}

	/**
	 * Derives a short reuse vs new-build note from template_overlap and content_model_fit.
	 *
	 * @param array<string, int> $scores
	 */
	private function reuse_vs_new_build_note( array $scores ): string {
		$overlap = $scores['template_overlap'] ?? 0;
		$content = $scores['content_model_fit'] ?? 0;
		$avg = ( $overlap + $content ) / 2;
		if ( $avg >= 4 ) {
			return 'High reuse';
		}
		if ( $avg >= 3 ) {
			return 'Mixed';
		}
		return 'New-build heavy';
	}

	/**
	 * Builds subtype/caution highlight entry for one candidate.
	 *
	 * @param array<string, int> $scores
	 * @return array{subtype_complexity: int, compliance_caution_burden: int, burden_note: string}
	 */
	private function subtype_caution_entry( array $scores ): array {
		$subtype = $scores['subtype_complexity'] ?? 3;
		$caution = $scores['compliance_caution_burden'] ?? 3;
		$note = 'Low burden';
		if ( $subtype <= 2 || $caution <= 2 ) {
			$note = 'Caution/subtype burden';
		}
		if ( $subtype <= 2 && $caution <= 2 ) {
			$note = 'High burden';
		}
		return array(
			'subtype_complexity'         => $subtype,
			'compliance_caution_burden' => $caution,
			'burden_note'               => $note,
		);
	}

	/**
	 * Suggests candidate order: go first, then review, then no-go; within same recommendation by aggregate descending.
	 *
	 * @param array<string, array{aggregate_sum: int, recommendation: string}> $per_candidate
	 * @return list<string>
	 */
	private function suggest_order( array $per_candidate ): array {
		$order_rank = array( 'go' => 0, 'review' => 1, 'no-go' => 2 );
		$with_meta = array();
		foreach ( $per_candidate as $label => $meta ) {
			$rec = $meta['recommendation'] ?? 'review';
			$with_meta[] = array(
				'label'    => $label,
				'rec_rank' => $order_rank[ $rec ] ?? 1,
				'aggregate' => $meta['aggregate_sum'] ?? 0,
			);
		}
		usort( $with_meta, function ( $a, $b ) {
			if ( $a['rec_rank'] !== $b['rec_rank'] ) {
				return $a['rec_rank'] - $b['rec_rank'];
			}
			return $b['aggregate'] - $a['aggregate'];
		} );
		return array_values( array_column( $with_meta, 'label' ) );
	}
}
