<?php
/**
 * Result of template library animation/fallback QA (Prompt 187, spec §7.7, §51.10, §55.5, §56.6, §59.14).
 * Machine-readable payload and human-readable summary; ties failures back to template/section metadata.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\QA;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable animation QA result. Authority: animation-support-and-fallback-contract, template-library-compliance-matrix ANIMATION family.
 *
 * Example animation_qa_result payload (excerpt):
 * [
 *   'passed' => false,
 *   'fallback_violation_summary' => [
 *     [ 'scope' => 'section', 'template_key' => 'st_hero_01', 'code' => 'invalid_tier', 'message' => 'animation_tier must be none|subtle|enhanced|premium.' ],
 *   ],
 *   'reduced_motion_check_result' => [
 *     'sections_checked' => 120,
 *     'all_resolve_safe_tier' => true,
 *     'sections_capped_count' => 45,
 *   ],
 *   'section_summary' => [ 'audited' => 120, 'by_tier' => [ 'none' => 80, 'subtle' => 30, 'enhanced' => 10 ], 'violations' => 1 ],
 *   'page_summary' => [ 'audited' => 45, 'with_tier_cap' => 12, 'violations' => 0 ],
 *   'manual_qa_checklist' => [ 'Tier none: layout and content visible.', 'Reduced-motion: no decorative motion.', ... ],
 * ]
 */
final class Animation_QA_Result {

	/** @var bool True when no machine-checkable fallback/metadata violations. */
	private bool $passed;

	/** @var array<int, array{scope: string, template_key: string, code: string, message: string}> */
	private array $fallback_violation_summary;

	/** @var array{sections_checked: int, all_resolve_safe_tier: bool, sections_capped_count: int} */
	private array $reduced_motion_check_result;

	/** @var array{audited: int, by_tier: array<string, int>, violations: int} */
	private array $section_summary;

	/** @var array{audited: int, with_tier_cap: int, violations: int} */
	private array $page_summary;

	/** @var array<int, string> Manual QA checklist items (animation-support-and-fallback-contract §9). */
	private array $manual_qa_checklist;

	/**
	 * @param bool                                                                                  $passed
	 * @param array<int, array{scope: string, template_key: string, code: string, message: string}>       $fallback_violation_summary
	 * @param array{sections_checked: int, all_resolve_safe_tier: bool, sections_capped_count: int} $reduced_motion_check_result
	 * @param array{audited: int, by_tier: array<string, int>, violations: int}                     $section_summary
	 * @param array{audited: int, with_tier_cap: int, violations: int}                              $page_summary
	 * @param array<int, string>                                                                          $manual_qa_checklist
	 */
	public function __construct(
		bool $passed,
		array $fallback_violation_summary,
		array $reduced_motion_check_result,
		array $section_summary,
		array $page_summary,
		array $manual_qa_checklist
	) {
		$this->passed                      = $passed;
		$this->fallback_violation_summary  = $fallback_violation_summary;
		$this->reduced_motion_check_result = $reduced_motion_check_result;
		$this->section_summary             = $section_summary;
		$this->page_summary                = $page_summary;
		$this->manual_qa_checklist         = $manual_qa_checklist;
	}

	public function is_passed(): bool {
		return $this->passed;
	}

	/** @return array<int, array{scope: string, template_key: string, code: string, message: string}> */
	public function get_fallback_violation_summary(): array {
		return $this->fallback_violation_summary;
	}

	/** @return array{sections_checked: int, all_resolve_safe_tier: bool, sections_capped_count: int} */
	public function get_reduced_motion_check_result(): array {
		return $this->reduced_motion_check_result;
	}

	/** @return array{audited: int, by_tier: array<string, int>, violations: int} */
	public function get_section_summary(): array {
		return $this->section_summary;
	}

	/** @return array{audited: int, with_tier_cap: int, violations: int} */
	public function get_page_summary(): array {
		return $this->page_summary;
	}

	/** @return array<int, string> */
	public function get_manual_qa_checklist(): array {
		return $this->manual_qa_checklist;
	}

	/**
	 * Full payload for machine-readable reporting (animation_qa_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'passed'                      => $this->passed,
			'fallback_violation_summary'  => $this->fallback_violation_summary,
			'reduced_motion_check_result' => $this->reduced_motion_check_result,
			'section_summary'             => $this->section_summary,
			'page_summary'                => $this->page_summary,
			'manual_qa_checklist'         => $this->manual_qa_checklist,
		);
	}

	/**
	 * Human-readable summary lines (for report excerpt).
	 *
	 * @return array<int, string>
	 */
	public function to_summary_lines(): array {
		$lines   = array();
		$lines[] = sprintf(
			'Sections audited: %d (%d violations). Pages audited: %d (%d violations).',
			$this->section_summary['audited'],
			$this->section_summary['violations'],
			$this->page_summary['audited'],
			$this->page_summary['violations']
		);
		$rm      = $this->reduced_motion_check_result;
		$lines[] = sprintf(
			'Reduced-motion check: %d sections, all resolve to safe tier: %s; %d sections capped when reduced motion applied.',
			$rm['sections_checked'],
			$rm['all_resolve_safe_tier'] ? 'yes' : 'no',
			$rm['sections_capped_count']
		);
		if ( ! empty( $this->fallback_violation_summary ) ) {
			$by_code = array();
			foreach ( $this->fallback_violation_summary as $v ) {
				$c             = $v['code'] ?? 'unknown';
				$by_code[ $c ] = ( $by_code[ $c ] ?? 0 ) + 1;
			}
			$lines[] = 'Fallback violations: ' . implode(
				', ',
				array_map(
					function ( $c, $n ) {
						return $c . '=' . $n;
					},
					array_keys( $by_code ),
					$by_code
				)
			);
		}
		$lines[] = $this->passed ? 'Animation QA: PASSED (machine-checkable).' : 'Animation QA: FAILED (resolve fallback/metadata violations).';
		$lines[] = 'Manual checklist: ' . count( $this->manual_qa_checklist ) . ' items (tier none, reduced-motion, no broken layout).';
		return $lines;
	}
}
