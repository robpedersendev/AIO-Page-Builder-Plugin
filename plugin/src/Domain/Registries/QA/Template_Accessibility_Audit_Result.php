<?php
/**
 * Result of template library semantic/accessibility audit (Prompt 186, spec §12.12, §51.3, §51.6, §51.7, §56.6).
 * Machine-readable payload and human-readable summary; does not claim full legal compliance.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\QA;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable audit result for reporting. Authority: semantic-seo-accessibility-extension-contract, template-library-compliance-matrix SEMANTIC family.
 *
 * Example template_accessibility_audit_result payload (excerpt):
 * [
 *   'passed' => false,
 *   'semantic_rule_violations' => [
 *     [ 'scope' => 'section', 'template_key' => 'st_hero_01', 'rule_code' => 'cta_clarity_marker_missing', 'message' => 'CTA-classified section must declare CTA clarity or accessibility expectations.' ],
 *     [ 'scope' => 'page', 'template_key' => 'pt_landing_01', 'rule_code' => 'bottom_cta_missing', 'message' => 'Last section is not CTA-classified.' ],
 *   ],
 *   'section_audit_summary' => [ 'audited' => 120, 'violations' => 2, 'by_rule_code' => [ 'cta_clarity_marker_missing' => 2 ] ],
 *   'page_audit_summary' => [ 'audited' => 45, 'violations' => 1, 'by_rule_code' => [ 'bottom_cta_missing' => 1 ] ],
 *   'human_review_required' => [ 'Heading hierarchy in rendered output (single h1, no skip).', 'Landmark presence (main, nav) in rendered output.', ... ],
 * ]
 */
final class Template_Accessibility_Audit_Result {

	/** @var bool True when no machine-checkable violations. */
	private bool $passed;

	/** @var list<array{scope: string, template_key: string, rule_code: string, message: string}> */
	private array $semantic_rule_violations;

	/** @var array{audited: int, violations: int, by_rule_code: array<string, int>} */
	private array $section_audit_summary;

	/** @var array{audited: int, violations: int, by_rule_code: array<string, int>} */
	private array $page_audit_summary;

	/** @var list<string> Items that still require manual or preview-based review. */
	private array $human_review_required;

	/**
	 * @param bool                                                                                 $passed
	 * @param list<array{scope: string, template_key: string, rule_code: string, message: string}> $semantic_rule_violations
	 * @param array{audited: int, violations: int, by_rule_code: array<string, int>}               $section_audit_summary
	 * @param array{audited: int, violations: int, by_rule_code: array<string, int>}               $page_audit_summary
	 * @param list<string>                                                                         $human_review_required
	 */
	public function __construct(
		bool $passed,
		array $semantic_rule_violations,
		array $section_audit_summary,
		array $page_audit_summary,
		array $human_review_required
	) {
		$this->passed                   = $passed;
		$this->semantic_rule_violations = $semantic_rule_violations;
		$this->section_audit_summary    = $section_audit_summary;
		$this->page_audit_summary       = $page_audit_summary;
		$this->human_review_required    = $human_review_required;
	}

	public function is_passed(): bool {
		return $this->passed;
	}

	/** @return list<array{scope: string, template_key: string, rule_code: string, message: string}> */
	public function get_semantic_rule_violations(): array {
		return $this->semantic_rule_violations;
	}

	/** @return array{audited: int, violations: int, by_rule_code: array<string, int>} */
	public function get_section_audit_summary(): array {
		return $this->section_audit_summary;
	}

	/** @return array{audited: int, violations: int, by_rule_code: array<string, int>} */
	public function get_page_audit_summary(): array {
		return $this->page_audit_summary;
	}

	/** @return list<string> */
	public function get_human_review_required(): array {
		return $this->human_review_required;
	}

	/**
	 * Full payload for machine-readable reporting (template_accessibility_audit_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'passed'                   => $this->passed,
			'semantic_rule_violations' => $this->semantic_rule_violations,
			'section_audit_summary'    => $this->section_audit_summary,
			'page_audit_summary'       => $this->page_audit_summary,
			'human_review_required'    => $this->human_review_required,
		);
	}

	/**
	 * Human-readable summary lines (for report excerpt).
	 *
	 * @return list<string>
	 */
	public function to_summary_lines(): array {
		$lines   = array();
		$lines[] = sprintf(
			'Sections audited: %d (%d violations). Pages audited: %d (%d violations).',
			$this->section_audit_summary['audited'],
			$this->section_audit_summary['violations'],
			$this->page_audit_summary['audited'],
			$this->page_audit_summary['violations']
		);
		if ( ! empty( $this->semantic_rule_violations ) ) {
			$by_code = array();
			foreach ( $this->semantic_rule_violations as $v ) {
				$code             = $v['rule_code'] ?? 'unknown';
				$by_code[ $code ] = ( $by_code[ $code ] ?? 0 ) + 1;
			}
			$lines[] = 'Rule violations: ' . implode(
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
		$lines[] = $this->passed ? 'Accessibility audit: PASSED (machine-checkable rules).' : 'Accessibility audit: FAILED (resolve violations).';
		if ( ! empty( $this->human_review_required ) ) {
			$lines[] = 'Human review still required: ' . implode( ' ', array_slice( $this->human_review_required, 0, 2 ) ) . ( count( $this->human_review_required ) > 2 ? ' …' : '' );
		}
		return $lines;
	}
}
