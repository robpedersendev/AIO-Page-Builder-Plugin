<?php
/**
 * Admin read-model item for one section in the industry-aware section library (industry-section-recommendation-contract).
 * Carries section key, recommendation metadata, and explanation for filtering/display.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable view item: section key, recommendation status, score, explanation, and optional section definition ref.
 */
final class Industry_Section_Library_Item_View {

	private string $section_key;
	private string $recommendation_status;
	private int $score;
	/** @var array<int, string> */
	private array $explanation_reasons;
	/** @var array<int, string> */
	private array $industry_source_refs;
	/** @var array<int, string> */
	private array $warning_flags;
	/** @var array<string, mixed> Section definition snapshot (optional). */
	private array $section_definition;

	/**
	 * @param string               $section_key          Section template internal_key.
	 * @param string               $recommendation_status One of Industry_Section_Recommendation_Resolver::FIT_*.
	 * @param int                  $score                Recommendation score.
	 * @param array<int, string>   $explanation_reasons  Reason codes.
	 * @param array<int, string>   $industry_source_refs Industry keys that contributed.
	 * @param array<int, string>   $warning_flags        Warning flags.
	 * @param array<string, mixed> $section_definition   Optional section definition snapshot.
	 */
	public function __construct(
		string $section_key,
		string $recommendation_status,
		int $score,
		array $explanation_reasons,
		array $industry_source_refs,
		array $warning_flags,
		array $section_definition = array()
	) {
		$this->section_key           = $section_key;
		$this->recommendation_status = $recommendation_status;
		$this->score                 = $score;
		$this->explanation_reasons   = $explanation_reasons;
		$this->industry_source_refs  = $industry_source_refs;
		$this->warning_flags         = $warning_flags;
		$this->section_definition    = $section_definition;
	}

	public function get_section_key(): string {
		return $this->section_key;
	}

	public function get_recommendation_status(): string {
		return $this->recommendation_status;
	}

	public function get_score(): int {
		return $this->score;
	}

	/** @return array<int, string> */
	public function get_explanation_reasons(): array {
		return $this->explanation_reasons;
	}

	/** @return array<int, string> */
	public function get_industry_source_refs(): array {
		return $this->industry_source_refs;
	}

	/** @return array<int, string> */
	public function get_warning_flags(): array {
		return $this->warning_flags;
	}

	/** @return array<string, mixed> */
	public function get_section_definition(): array {
		return $this->section_definition;
	}

	/**
	 * Short explanation snippet for UI (reason codes joined).
	 */
	public function get_explanation_snippet(): string {
		return implode( ', ', $this->explanation_reasons );
	}

	/** @return array<string, mixed> */
	public function to_array(): array {
		return array(
			'section_key'           => $this->section_key,
			'recommendation_status' => $this->recommendation_status,
			'score'                 => $this->score,
			'explanation_reasons'   => $this->explanation_reasons,
			'industry_source_refs'  => $this->industry_source_refs,
			'warning_flags'         => $this->warning_flags,
			'explanation_snippet'   => $this->get_explanation_snippet(),
		);
	}
}
