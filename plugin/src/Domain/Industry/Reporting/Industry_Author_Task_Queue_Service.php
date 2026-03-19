<?php
/**
 * Internal author task queue generator (Prompt 525). Synthesizes completeness, gap prioritization,
 * override conflicts, and release blockers into a bounded maintenance queue. Advisory; no mutation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates report outputs into categorized maintenance tasks with source evidence refs.
 */
final class Industry_Author_Task_Queue_Service {

	public const CATEGORY_BLOCKER       = 'blocker';
	public const CATEGORY_CLEANUP       = 'cleanup';
	public const CATEGORY_EXPANSION     = 'expansion';
	public const CATEGORY_DOCUMENTATION = 'documentation';
	public const CATEGORY_VALIDATION    = 'validation';

	public const SEVERITY_HIGH   = 'high';
	public const SEVERITY_MEDIUM = 'medium';
	public const SEVERITY_LOW    = 'low';

	/** Max tasks total to keep queue consumable. */
	private const MAX_TASKS = 200;

	/** Max tasks per category. */
	private const MAX_PER_CATEGORY = 50;

	/**
	 * Generates the task queue from optional report inputs.
	 *
	 * @param array|null $completeness_report    Output of Industry_Pack_Completeness_Report_Service::generate_report().
	 * @param array|null $gap_prioritization_report Output of Industry_Coverage_Gap_Prioritization_Service::generate_report() or run().
	 * @param array|null $override_conflicts    Output of Industry_Override_Conflict_Detector::detect().
	 * @return array{tasks: array<int, array{task_key: string, category: string, severity: string, source_evidence_refs: array<int, string>, suggested_action: string}>, summary: array{blocker: int, cleanup: int, expansion: int, documentation: int, validation: int}}
	 */
	public function generate_queue(
		?array $completeness_report = null,
		?array $gap_prioritization_report = null,
		?array $override_conflicts = null
	): array {
		$tasks = array();

		$tasks = array_merge(
			$tasks,
			$this->tasks_from_completeness( $completeness_report ?? array() )
		);
		$tasks = array_merge(
			$tasks,
			$this->tasks_from_gap_prioritization( $gap_prioritization_report ?? array() )
		);
		$tasks = array_merge(
			$tasks,
			$this->tasks_from_override_conflicts( $override_conflicts ?? array() )
		);

		if ( count( $tasks ) > 0 ) {
			$tasks[] = array(
				'task_key'             => 'validation:run_health_and_prerelease',
				'category'             => self::CATEGORY_VALIDATION,
				'severity'             => self::SEVERITY_MEDIUM,
				'source_evidence_refs' => array( 'release_gate:pre_release_checklist' ),
				'suggested_action'     => 'Run industry health check and pre-release pipeline before release.',
			);
		}
		$tasks   = $this->sort_and_bound( $tasks );
		$summary = $this->summarize( $tasks );

		return array(
			'tasks'   => $tasks,
			'summary' => $summary,
		);
	}

	/**
	 * @param array $completeness_report
	 * @return array<int, array{task_key: string, category: string, severity: string, source_evidence_refs: array<int, string>, suggested_action: string}>
	 */
	private function tasks_from_completeness( array $completeness_report ): array {
		$out     = array();
		$results = isset( $completeness_report['pack_results'] ) && is_array( $completeness_report['pack_results'] ) ? $completeness_report['pack_results'] : array();
		foreach ( $results as $r ) {
			$pack_key      = isset( $r['pack_key'] ) && is_string( $r['pack_key'] ) ? $r['pack_key'] : '';
			$subtype_key   = isset( $r['subtype_key'] ) && is_string( $r['subtype_key'] ) ? $r['subtype_key'] : '';
			$scope         = $pack_key . ( $subtype_key !== '' ? '|' . $subtype_key : '' );
			$ref           = 'completeness:pack:' . $scope;
			$band          = isset( $r['band'] ) && is_string( $r['band'] ) ? $r['band'] : '';
			$blocker_flags = isset( $r['blocker_flags'] ) && is_array( $r['blocker_flags'] ) ? $r['blocker_flags'] : array();

			if ( count( $blocker_flags ) > 0 ) {
				$out[] = array(
					'task_key'             => 'completeness:' . $scope . ':blocker',
					'category'             => self::CATEGORY_BLOCKER,
					'severity'             => self::SEVERITY_HIGH,
					'source_evidence_refs' => array( $ref ),
					'suggested_action'     => 'Resolve completeness blockers for ' . ( $scope !== '' ? $scope : 'pack' ) . ': ' . implode( ', ', array_slice( $blocker_flags, 0, 3 ) ),
				);
			}
			if ( $band === Industry_Pack_Completeness_Report_Service::BAND_BELOW_MINIMAL && count( $blocker_flags ) === 0 ) {
				$out[] = array(
					'task_key'             => 'completeness:' . $scope . ':below_minimal',
					'category'             => self::CATEGORY_EXPANSION,
					'severity'             => self::SEVERITY_MEDIUM,
					'source_evidence_refs' => array( $ref ),
					'suggested_action'     => 'Raise pack completeness for ' . ( $scope !== '' ? $scope : 'pack' ) . ' (currently below minimal).',
				);
			}
		}
		return $out;
	}

	/**
	 * @param array $gap_report
	 * @return array<int, array{task_key: string, category: string, severity: string, source_evidence_refs: array<int, string>, suggested_action: string}>
	 */
	private function tasks_from_gap_prioritization( array $gap_report ): array {
		$out    = array();
		$ranked = isset( $gap_report['ranked'] ) && is_array( $gap_report['ranked'] ) ? $gap_report['ranked'] : array();
		foreach ( $ranked as $g ) {
			$scope     = isset( $g['scope'] ) && is_string( $g['scope'] ) ? $g['scope'] : '';
			$class     = isset( $g['missing_artifact_class'] ) && is_string( $g['missing_artifact_class'] ) ? $g['missing_artifact_class'] : '';
			$tier      = isset( $g['tier'] ) && is_string( $g['tier'] ) ? $g['tier'] : '';
			$rationale = isset( $g['rationale'] ) && is_string( $g['rationale'] ) ? $g['rationale'] : '';
			$ref       = 'gap_prioritization:' . $scope . ':' . $class;

			$category = $tier === Industry_Coverage_Gap_Prioritization_Service::TIER_URGENT ? self::CATEGORY_BLOCKER : ( $tier === Industry_Coverage_Gap_Prioritization_Service::TIER_IMPORTANT ? self::CATEGORY_CLEANUP : self::CATEGORY_EXPANSION );
			$severity = $tier === Industry_Coverage_Gap_Prioritization_Service::TIER_URGENT ? self::SEVERITY_HIGH : ( $tier === Industry_Coverage_Gap_Prioritization_Service::TIER_IMPORTANT ? self::SEVERITY_MEDIUM : self::SEVERITY_LOW );
			$action   = strlen( $rationale ) > 80 ? substr( $rationale, 0, 77 ) . '...' : $rationale;
			if ( $action === '' ) {
				$action = 'Add or fix ' . $class . ' for ' . ( $scope !== '' ? $scope : 'scope' );
			}

			$out[] = array(
				'task_key'             => 'gap:' . $scope . ':' . $class,
				'category'             => $category,
				'severity'             => $severity,
				'source_evidence_refs' => array( $ref ),
				'suggested_action'     => $action,
			);
		}
		return $out;
	}

	/**
	 * @param array $conflicts
	 * @return array<int, array{task_key: string, category: string, severity: string, source_evidence_refs: array<int, string>, suggested_action: string}>
	 */
	private function tasks_from_override_conflicts( array $conflicts ): array {
		$out = array();
		foreach ( $conflicts as $c ) {
			$override_ref  = isset( $c['override_ref'] ) && is_string( $c['override_ref'] ) ? $c['override_ref'] : '';
			$conflict_type = isset( $c['conflict_type'] ) && is_string( $c['conflict_type'] ) ? $c['conflict_type'] : '';
			$severity      = isset( $c['severity'] ) && is_string( $c['severity'] ) ? $c['severity'] : Industry_Override_Conflict_Detector::SEVERITY_WARNING;
			$suggested     = isset( $c['suggested_review_action'] ) && is_string( $c['suggested_review_action'] ) ? $c['suggested_review_action'] : 'Review override ' . $override_ref;
			$ref           = 'override_conflict:' . $override_ref;

			$category = $severity === Industry_Override_Conflict_Detector::SEVERITY_WARNING && in_array( $conflict_type, array( Industry_Override_Conflict_Detector::CONFLICT_TYPE_MISSING_TARGET, Industry_Override_Conflict_Detector::CONFLICT_TYPE_REMOVED_REF ), true )
				? self::CATEGORY_BLOCKER
				: self::CATEGORY_CLEANUP;
			$sev      = $category === self::CATEGORY_BLOCKER ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM;

			$out[] = array(
				'task_key'             => 'conflict:' . $override_ref,
				'category'             => $category,
				'severity'             => $sev,
				'source_evidence_refs' => array( $ref ),
				'suggested_action'     => strlen( $suggested ) > 120 ? substr( $suggested, 0, 117 ) . '...' : $suggested,
			);
		}
		return $out;
	}

	/**
	 * @param array $tasks
	 * @return array<int, array{task_key: string, category: string, severity: string, source_evidence_refs: array<int, string>, suggested_action: string}>
	 */
	private function sort_and_bound( array $tasks ): array {
		$order     = array(
			self::CATEGORY_BLOCKER       => 0,
			self::CATEGORY_CLEANUP       => 1,
			self::CATEGORY_EXPANSION     => 2,
			self::CATEGORY_DOCUMENTATION => 3,
			self::CATEGORY_VALIDATION    => 4,
		);
		$sev_order = array(
			self::SEVERITY_HIGH   => 0,
			self::SEVERITY_MEDIUM => 1,
			self::SEVERITY_LOW    => 2,
		);
		usort(
			$tasks,
			static function ( $a, $b ) use ( $order, $sev_order ) {
				$ca = $order[ $a['category'] ] ?? 5;
				$cb = $order[ $b['category'] ] ?? 5;
				if ( $ca !== $cb ) {
					return $ca <=> $cb;
				}
				$sa = $sev_order[ $a['severity'] ] ?? 3;
				$sb = $sev_order[ $b['severity'] ] ?? 3;
				return $sa <=> $sb;
			}
		);
		return array_slice( $tasks, 0, self::MAX_TASKS );
	}

	/**
	 * @param array $tasks
	 * @return array{blocker_count: int, cleanup_count: int, expansion_count: int, documentation_count: int, validation_count: int}
	 */
	private function summarize( array $tasks ): array {
		$counts = array(
			self::CATEGORY_BLOCKER       => 0,
			self::CATEGORY_CLEANUP       => 0,
			self::CATEGORY_EXPANSION     => 0,
			self::CATEGORY_DOCUMENTATION => 0,
			self::CATEGORY_VALIDATION    => 0,
		);
		foreach ( $tasks as $t ) {
			$cat = $t['category'] ?? '';
			if ( isset( $counts[ $cat ] ) ) {
				++$counts[ $cat ];
			}
		}
		return array(
			'blocker_count'       => $counts[ self::CATEGORY_BLOCKER ],
			'cleanup_count'       => $counts[ self::CATEGORY_CLEANUP ],
			'expansion_count'     => $counts[ self::CATEGORY_EXPANSION ],
			'documentation_count' => $counts[ self::CATEGORY_DOCUMENTATION ],
			'validation_count'    => $counts[ self::CATEGORY_VALIDATION ],
		);
	}
}
