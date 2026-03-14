<?php
/**
 * Machine-readable result of template library compliance run (Prompt 176, template-library-compliance-matrix).
 * Holds count summary, category coverage, CTA rule violations, preview/one-pager, metadata, and export checks.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\QA;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable compliance result for reporting and gating.
 */
final class Template_Library_Compliance_Result {

	/** @var array{section_total: int, page_total: int, section_target: int, page_target: int, by_section_purpose_family: array<string, int>, by_page_category_class: array<string, int>, by_page_family: array<string, int>} */
	private array $count_summary;

	/** @var array{section_family_minimums: array<string, bool>, page_class_minimums: array<string, bool>, max_share_violations: list<string>} */
	private array $category_coverage_summary;

	/** @var list<array{template_key: string, code: string, message: string}> */
	private array $cta_rule_violations;

	/** @var array{sections_missing_preview: list<string>, pages_missing_one_pager: list<string>} */
	private array $preview_readiness;

	/** @var array{sections_missing_accessibility: list<string>, sections_invalid_animation: list<string>} */
	private array $metadata_checks;

	/** @var array{viable: bool, errors: list<string>} */
	private array $export_viability;

	/** @var bool True when no hard-fail violations. */
	private bool $passed;

	/**
	 * @param array $count_summary
	 * @param array $category_coverage_summary
	 * @param array $cta_rule_violations
	 * @param array $preview_readiness
	 * @param array $metadata_checks
	 * @param array $export_viability
	 * @param bool  $passed
	 */
	public function __construct(
		array $count_summary,
		array $category_coverage_summary,
		array $cta_rule_violations,
		array $preview_readiness,
		array $metadata_checks,
		array $export_viability,
		bool $passed
	) {
		$this->count_summary            = $count_summary;
		$this->category_coverage_summary = $category_coverage_summary;
		$this->cta_rule_violations      = $cta_rule_violations;
		$this->preview_readiness        = $preview_readiness;
		$this->metadata_checks         = $metadata_checks;
		$this->export_viability        = $export_viability;
		$this->passed                  = $passed;
	}

	public function get_count_summary(): array {
		return $this->count_summary;
	}

	public function get_category_coverage_summary(): array {
		return $this->category_coverage_summary;
	}

	/** @return list<array{template_key: string, code: string, message: string}> */
	public function get_cta_rule_violations(): array {
		return $this->cta_rule_violations;
	}

	public function get_preview_readiness(): array {
		return $this->preview_readiness;
	}

	public function get_metadata_checks(): array {
		return $this->metadata_checks;
	}

	public function get_export_viability(): array {
		return $this->export_viability;
	}

	public function is_passed(): bool {
		return $this->passed;
	}

	/**
	 * Full payload for machine-readable reporting.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'count_summary'             => $this->count_summary,
			'category_coverage_summary' => $this->category_coverage_summary,
			'cta_rule_violations'        => $this->cta_rule_violations,
			'preview_readiness'          => $this->preview_readiness,
			'metadata_checks'            => $this->metadata_checks,
			'export_viability'           => $this->export_viability,
			'passed'                     => $this->passed,
		);
	}

	/**
	 * Human-readable summary lines (for report excerpt).
	 *
	 * @return list<string>
	 */
	public function to_summary_lines(): array {
		$lines = array();
		$c = $this->count_summary;
		$lines[] = sprintf(
			'Sections: %d / %d. Pages: %d / %d.',
			$c['section_total'] ?? 0,
			$c['section_target'] ?? 250,
			$c['page_total'] ?? 0,
			$c['page_target'] ?? 500
		);
		$viol = $this->cta_rule_violations;
		$hard = array_filter( $viol, function ( $v ) {
			$code = $v['code'] ?? '';
			return $code !== 'non_cta_count_above_max';
		} );
		if ( ! empty( $hard ) ) {
			$lines[] = 'CTA rule violations (hard): ' . count( $hard );
		}
		if ( ! empty( $this->category_coverage_summary['max_share_violations'] ?? array() ) ) {
			$lines[] = 'Max-share violations: ' . implode( ', ', $this->category_coverage_summary['max_share_violations'] );
		}
		$lines[] = $this->passed ? 'Compliance: PASSED.' : 'Compliance: FAILED (resolve hard-fail items).';
		return $lines;
	}
}
