<?php
/**
 * Prioritization report generator for coverage gaps (Prompt 524).
 * Consumes gap analysis results and produces ranked, grouped, explained maintenance priorities.
 * Internal-only; advisory; no auto-creation of tickets or mutation of assets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Coverage_Gap_Analyzer;

/**
 * Ranks and groups coverage gaps by priority tier for backlog and author dashboard.
 */
final class Industry_Coverage_Gap_Prioritization_Service {

	public const TIER_URGENT    = 'urgent';
	public const TIER_IMPORTANT = 'important';
	public const TIER_OPTIONAL  = 'optional';

	/** @var Industry_Coverage_Gap_Analyzer|null */
	private $gap_analyzer;

	public function __construct( ?Industry_Coverage_Gap_Analyzer $gap_analyzer = null ) {
		$this->gap_analyzer = $gap_analyzer;
	}

	/**
	 * Generates prioritized report from gap analyzer output. Ranks by tier, groups by artifact class and scope.
	 *
	 * @param array{gaps: array<int, array{scope: string, missing_artifact_class: string, priority: string, explanation: string}>, by_scope: array<string, array<int, array{missing_artifact_class: string, priority: string, explanation: string}>>} $gap_result Result from Industry_Coverage_Gap_Analyzer::analyze().
	 * @return array{
	 *   ranked: array<int, array{scope: string, missing_artifact_class: string, priority: string, tier: string, priority_score: int, rationale: string, related_scopes: array<int, string>}>,
	 *   by_tier: array<string, array<int, array{scope: string, missing_artifact_class: string, rationale: string}>>,
	 *   release_blocker_cues: array<int, string>,
	 *   summary: array{urgent: int, important: int, optional: int}
	 * }
	 */
	public function generate_report( array $gap_result ): array {
		$gaps                 = isset( $gap_result['gaps'] ) && is_array( $gap_result['gaps'] ) ? $gap_result['gaps'] : array();
		$ranked               = array();
		$release_blocker_cues = array();

		foreach ( $gaps as $gap ) {
			$scope       = isset( $gap['scope'] ) && is_string( $gap['scope'] ) ? trim( $gap['scope'] ) : '';
			$class       = isset( $gap['missing_artifact_class'] ) && is_string( $gap['missing_artifact_class'] ) ? trim( $gap['missing_artifact_class'] ) : '';
			$priority    = isset( $gap['priority'] ) && is_string( $gap['priority'] ) ? trim( $gap['priority'] ) : 'low';
			$explanation = isset( $gap['explanation'] ) && is_string( $gap['explanation'] ) ? trim( $gap['explanation'] ) : '';

			$tier      = $this->tier_for_gap( $class, $priority );
			$score     = $this->priority_score( $tier, $class, $scope );
			$rationale = $explanation !== '' ? $explanation : $this->default_rationale( $class, $tier );

			if ( $tier === self::TIER_URGENT ) {
				$release_blocker_cues[] = $scope . ': ' . $class;
			}

			$ranked[] = array(
				'scope'                  => $scope,
				'missing_artifact_class' => $class,
				'priority'               => $priority,
				'tier'                   => $tier,
				'priority_score'         => $score,
				'rationale'              => $rationale,
				'related_scopes'         => array( $scope ),
			);
		}

		usort(
			$ranked,
			static function ( $a, $b ) {
				$tier_order = array(
					self::TIER_URGENT    => 0,
					self::TIER_IMPORTANT => 1,
					self::TIER_OPTIONAL  => 2,
				);
				$ta         = $tier_order[ $a['tier'] ] ?? 3;
				$tb         = $tier_order[ $b['tier'] ] ?? 3;
				if ( $ta !== $tb ) {
					return $ta <=> $tb;
				}
				return ( $b['priority_score'] ?? 0 ) <=> ( $a['priority_score'] ?? 0 );
			}
		);

		$by_tier = array(
			self::TIER_URGENT    => array(),
			self::TIER_IMPORTANT => array(),
			self::TIER_OPTIONAL  => array(),
		);
		foreach ( $ranked as $r ) {
			$t               = $r['tier'];
			$by_tier[ $t ][] = array(
				'scope'                  => $r['scope'],
				'missing_artifact_class' => $r['missing_artifact_class'],
				'rationale'              => $r['rationale'],
			);
		}

		$summary = array(
			'urgent'    => count( $by_tier[ self::TIER_URGENT ] ),
			'important' => count( $by_tier[ self::TIER_IMPORTANT ] ),
			'optional'  => count( $by_tier[ self::TIER_OPTIONAL ] ),
		);

		return array(
			'ranked'               => $ranked,
			'by_tier'              => $by_tier,
			'release_blocker_cues' => $release_blocker_cues,
			'summary'              => $summary,
		);
	}

	/**
	 * Runs gap analyzer (if set) and returns prioritized report.
	 *
	 * @param bool $include_subtypes
	 * @return array{ranked: list, by_tier: array, release_blocker_cues: array<int, string>, summary: array}
	 */
	public function run( bool $include_subtypes = true ): array {
		$gap_result = array(
			'gaps'     => array(),
			'by_scope' => array(),
		);
		if ( $this->gap_analyzer !== null ) {
			$gap_result = $this->gap_analyzer->analyze( $include_subtypes );
		}
		return $this->generate_report( $gap_result );
	}

	private function tier_for_gap( string $artifact_class, string $analyzer_priority ): string {
		if ( $artifact_class === Industry_Coverage_Gap_Analyzer::GAP_STARTER_BUNDLE ) {
			return self::TIER_URGENT;
		}
		if ( in_array(
			$artifact_class,
			array(
				Industry_Coverage_Gap_Analyzer::GAP_SECTION_HELPER_OVERLAYS,
				Industry_Coverage_Gap_Analyzer::GAP_PAGE_ONEPAGER_OVERLAYS,
			),
			true
		) ) {
			return $analyzer_priority === Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH ? self::TIER_URGENT : self::TIER_IMPORTANT;
		}
		if ( in_array(
			$artifact_class,
			array(
				Industry_Coverage_Gap_Analyzer::GAP_STYLE_PRESET,
				Industry_Coverage_Gap_Analyzer::GAP_SEO_GUIDANCE,
			),
			true
		) ) {
			return $analyzer_priority === Industry_Coverage_Gap_Analyzer::PRIORITY_MEDIUM ? self::TIER_IMPORTANT : self::TIER_OPTIONAL;
		}
		if ( in_array(
			$artifact_class,
			array(
				Industry_Coverage_Gap_Analyzer::GAP_COMPLIANCE_RULES,
				Industry_Coverage_Gap_Analyzer::GAP_QUESTION_PACK,
			),
			true
		) ) {
			return self::TIER_OPTIONAL;
		}
		return $analyzer_priority === Industry_Coverage_Gap_Analyzer::PRIORITY_HIGH ? self::TIER_URGENT : ( $analyzer_priority === Industry_Coverage_Gap_Analyzer::PRIORITY_MEDIUM ? self::TIER_IMPORTANT : self::TIER_OPTIONAL );
	}

	private function priority_score( string $tier, string $artifact_class, string $scope ): int {
		$base  = array(
			self::TIER_URGENT    => 90,
			self::TIER_IMPORTANT => 50,
			self::TIER_OPTIONAL  => 10,
		);
		$score = $base[ $tier ] ?? 0;
		if ( strpos( $scope, '|' ) !== false ) {
			$score += 2;
		}
		return $score;
	}

	private function default_rationale( string $artifact_class, string $tier ): string {
		if ( $artifact_class === Industry_Coverage_Gap_Analyzer::GAP_STARTER_BUNDLE ) {
			return 'No starter bundle for this scope; high impact on onboarding and recommendations.';
		}
		if ( $artifact_class === Industry_Coverage_Gap_Analyzer::GAP_SECTION_HELPER_OVERLAYS ) {
			return 'No section helper overlays; affects section recommendation and preview.';
		}
		if ( $artifact_class === Industry_Coverage_Gap_Analyzer::GAP_PAGE_ONEPAGER_OVERLAYS ) {
			return 'No page one-pager overlays; affects page planning and one-pager content.';
		}
		return 'Coverage gap: ' . $artifact_class . ' (' . $tier . ').';
	}
}
