<?php
/**
 * Maps a completed future-industry intake dossier into a scorecard evaluation report (Prompt 472).
 * Internal, advisory only. No auto-approval; no mutation of runtime pack registries.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Converts dossier inputs into scorecard dimensions and produces a structured recommendation summary.
 * Contract: docs/contracts/future-industry-scorecard-executor-contract.md.
 */
final class Future_Industry_Scorecard_Executor {

	private const DIMENSIONS = array(
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

	private const DEFAULT_GO_THRESHOLD    = 40;
	private const DEFAULT_NO_GO_THRESHOLD = 25;
	private const MIN_DIMENSION           = 2;
	private const NO_GO_DIMENSIONS        = array( 'template_overlap', 'long_term_maintenance_cost' );

	/** @var int */
	private $go_threshold;

	/** @var int */
	private $no_go_threshold;

	public function __construct( ?int $go_threshold = null, ?int $no_go_threshold = null ) {
		$this->go_threshold    = $go_threshold ?? self::DEFAULT_GO_THRESHOLD;
		$this->no_go_threshold = $no_go_threshold ?? self::DEFAULT_NO_GO_THRESHOLD;
	}

	/**
	 * Executes scorecard evaluation from a completed intake dossier.
	 *
	 * @param array<string, mixed> $dossier Dossier structure per future-industry-scorecard-executor-contract (candidate_identity, dimension_scores, template_overlap, etc.).
	 * @return array{
	 *   candidate_label: string,
	 *   proposed_industry_key: string,
	 *   evaluated_at: string,
	 *   dimension_scores: array<string, int>,
	 *   aggregate_sum: int,
	 *   major_risks: list<string>,
	 *   recommendation: string,
	 *   summary_text: string
	 * }
	 */
	public function execute( array $dossier ): array {
		$identity        = isset( $dossier['candidate_identity'] ) && is_array( $dossier['candidate_identity'] )
			? $dossier['candidate_identity']
			: array();
		$candidate_label = isset( $identity['candidate_label'] ) && is_string( $identity['candidate_label'] )
			? trim( $identity['candidate_label'] )
			: 'Unknown';
		$proposed_key    = isset( $identity['proposed_industry_key'] ) && is_string( $identity['proposed_industry_key'] )
			? trim( $identity['proposed_industry_key'] )
			: '';

		$scores         = $this->resolve_dimension_scores( $dossier );
		$aggregate      = array_sum( $scores );
		$risks          = $this->collect_major_risks( $scores, $dossier );
		$recommendation = $this->derive_recommendation( $scores, $aggregate, $risks );
		$summary        = $this->build_summary_text( $candidate_label, $aggregate, $recommendation, $risks );

		return array(
			'candidate_label'       => $candidate_label,
			'proposed_industry_key' => $proposed_key,
			'evaluated_at'          => gmdate( 'c' ),
			'dimension_scores'      => $scores,
			'aggregate_sum'         => $aggregate,
			'major_risks'           => $risks,
			'recommendation'        => $recommendation,
			'summary_text'          => $summary,
		);
	}

	/**
	 * Resolves dimension scores from dossier: use pre-filled dimension_scores or derive from evidence.
	 *
	 * @param array<string, mixed> $dossier
	 * @return array<string, int>
	 */
	private function resolve_dimension_scores( array $dossier ): array {
		$prefilled = isset( $dossier['dimension_scores'] ) && is_array( $dossier['dimension_scores'] )
			? $dossier['dimension_scores']
			: null;

		if ( $prefilled !== null && $this->has_all_dimensions( $prefilled ) ) {
			$out = array();
			foreach ( self::DIMENSIONS as $dim ) {
				$v           = isset( $prefilled[ $dim ] ) ? (int) $prefilled[ $dim ] : 3;
				$out[ $dim ] = $this->clamp_score( $v );
			}
			return $out;
		}

		return $this->derive_scores_from_dossier( $dossier );
	}

	/**
	 * @param array<string, int> $scores
	 */
	private function has_all_dimensions( array $scores ): bool {
		foreach ( self::DIMENSIONS as $dim ) {
			if ( ! array_key_exists( $dim, $scores ) ) {
				return false;
			}
		}
		return true;
	}

	private function clamp_score( int $v ): int {
		if ( $v < 1 ) {
			return 1;
		}
		if ( $v > 5 ) {
			return 5;
		}
		return $v;
	}

	/**
	 * Derives dimension scores from dossier evidence using simple heuristics.
	 *
	 * @param array<string, mixed> $dossier
	 * @return array<string, int>
	 */
	private function derive_scores_from_dossier( array $dossier ): array {
		$scores = array_fill_keys( self::DIMENSIONS, 3 );

		// Template overlap: use overlap_score if present (0–1 -> 1–5).
		if ( isset( $dossier['template_overlap'] ) && is_array( $dossier['template_overlap'] ) ) {
			$overlap = $dossier['template_overlap'];
			if ( isset( $overlap['overlap_score'] ) && is_numeric( $overlap['overlap_score'] ) ) {
				$s                          = (float) $overlap['overlap_score'];
				$scores['template_overlap'] = $this->clamp_score( (int) round( 1 + $s * 4 ) );
			}
			$weak = isset( $overlap['weak_coverage_families'] ) && is_array( $overlap['weak_coverage_families'] )
				? count( $overlap['weak_coverage_families'] ) : 0;
			if ( $weak > 5 && $scores['template_overlap'] > 2 ) {
				--$scores['template_overlap'];
			}
		}

		// Compliance: "heavy" / "legal" / "medical" / "financial" -> lower score.
		$compliance = $this->get_section_text( $dossier, 'compliance_caution' );
		if ( $compliance !== '' ) {
			$lower = preg_match( '/\b(heavy|legal|medical|financial|regulatory|liability)\b/i', $compliance );
			if ( $lower ) {
				$scores['compliance_caution_burden'] = 2;
			}
		}

		// Starter bundle: "clear default shape" -> higher; "ambiguous" -> lower.
		$bundle = $this->get_section_text( $dossier, 'page_hierarchy_bundle' );
		if ( $bundle !== '' ) {
			if ( preg_match( '/\bclear\s+(default\s+)?shape\b/i', $bundle ) ) {
				$scores['starter_bundle_viability'] = 4;
			} elseif ( preg_match( '/\b(ambiguous|many\s+valid)\b/i', $bundle ) ) {
				$scores['starter_bundle_viability'] = 2;
			}
		}

		// Subtype: "none" / "few" -> higher; "many" / "fuzzy" -> lower.
		$subtype = $this->get_section_text( $dossier, 'subtype_complexity' );
		if ( $subtype !== '' ) {
			if ( preg_match( '/\b(none|few|well[- ]?defined)\b/i', $subtype ) ) {
				$scores['subtype_complexity'] = 4;
			} elseif ( preg_match( '/\b(many|fuzzy)\b/i', $subtype ) ) {
				$scores['subtype_complexity'] = 2;
			}
		}

		// Maintenance: "low churn" -> higher; "high churn" / "large" -> lower.
		$maint = $this->get_section_text( $dossier, 'documentation_maintenance' );
		if ( $maint !== '' ) {
			if ( preg_match( '/\b(low\s+churn|minimal)\b/i', $maint ) ) {
				$scores['long_term_maintenance_cost'] = 4;
			} elseif ( preg_match( '/\b(high\s+churn|large\s+doc)\b/i', $maint ) ) {
				$scores['long_term_maintenance_cost'] = 2;
			}
		}

		return $scores;
	}

	/**
	 * @param array<string, mixed> $dossier
	 */
	private function get_section_text( array $dossier, string $key ): string {
		if ( ! isset( $dossier[ $key ] ) ) {
			return '';
		}
		$v = $dossier[ $key ];
		if ( is_string( $v ) ) {
			return trim( $v );
		}
		if ( is_array( $v ) ) {
			$parts = array();
			foreach ( $v as $item ) {
				if ( is_string( $item ) ) {
					$parts[] = $item;
				}
			}
			return implode( ' ', $parts );
		}
		return '';
	}

	/**
	 * Collects major risks from scores and dossier.
	 *
	 * @param array<string, int>   $scores
	 * @param array<string, mixed> $dossier
	 * @return list<string>
	 */
	private function collect_major_risks( array $scores, array $dossier ): array {
		$risks = array();
		foreach ( self::NO_GO_DIMENSIONS as $dim ) {
			if ( ( $scores[ $dim ] ?? 3 ) === 1 ) {
				$risks[] = ucfirst( str_replace( '_', ' ', $dim ) ) . ' scored 1 (policy no-go).';
			}
		}
		foreach ( $scores as $dim => $score ) {
			if ( $score <= 2 && ! in_array( $dim, self::NO_GO_DIMENSIONS, true ) ) {
				$risks[] = ucfirst( str_replace( '_', ' ', $dim ) ) . ' low (' . $score . '/5).';
			}
		}
		$compliance = $this->get_section_text( $dossier, 'compliance_caution' );
		if ( $compliance !== '' && preg_match( '/\bnew\s+core\s+seams?\b/i', $compliance ) ) {
			$risks[] = 'Dossier notes imply new core seams (no-go condition).';
		}
		return array_values( array_unique( $risks ) );
	}

	/**
	 * Derives go / review / no-go from scores, aggregate, and risks.
	 *
	 * @param array<string, int> $scores
	 * @param list<string>       $risks
	 */
	private function derive_recommendation( array $scores, int $aggregate, array $risks ): string {
		foreach ( $risks as $r ) {
			if ( strpos( $r, 'no-go' ) !== false || strpos( $r, 'new core seams' ) !== false ) {
				return 'no-go';
			}
		}
		foreach ( self::NO_GO_DIMENSIONS as $dim ) {
			if ( ( $scores[ $dim ] ?? 3 ) === 1 ) {
				return 'no-go';
			}
		}
		if ( $aggregate <= $this->no_go_threshold ) {
			return 'no-go';
		}
		$min = min( $scores );
		if ( $min < self::MIN_DIMENSION ) {
			return 'review';
		}
		if ( $aggregate >= $this->go_threshold ) {
			return 'go';
		}
		return 'review';
	}

	/**
	 * Builds a short summary string.
	 *
	 * @param list<string> $risks
	 */
	private function build_summary_text( string $label, int $aggregate, string $recommendation, array $risks ): string {
		$out = sprintf( '%s: aggregate %d/50, recommendation %s.', $label, $aggregate, $recommendation );
		if ( count( $risks ) > 0 ) {
			$out .= ' Risks: ' . implode( ' ', array_slice( $risks, 0, 3 ) );
		}
		return $out;
	}
}
