<?php
/**
 * Admin read-model item for one page template in the industry-aware directory (industry-page-template-recommendation-contract).
 * Carries template key, recommendation metadata, hierarchy/LPagery fit, and explanation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable view item: page template key, recommendation status, score, hierarchy_fit, lpagery_fit, explanation.
 */
final class Industry_Page_Template_Directory_Item_View {

	private string $page_template_key;
	private string $recommendation_status;
	private int $score;
	/** @var list<string> */
	private array $explanation_reasons;
	/** @var list<string> */
	private array $industry_source_refs;
	private string $hierarchy_fit;
	private string $lpagery_fit;
	/** @var list<string> */
	private array $warning_flags;
	/** @var array<string, mixed> Page template definition snapshot (optional). */
	private array $template_definition;

	/**
	 * @param string               $page_template_key    Page template internal_key.
	 * @param string               $recommendation_status One of Industry_Page_Template_Recommendation_Resolver::FIT_*.
	 * @param int                  $score                Recommendation score.
	 * @param list<string>         $explanation_reasons  Reason codes.
	 * @param list<string>         $industry_source_refs Industry keys that contributed.
	 * @param string               $hierarchy_fit        Hierarchy fit note.
	 * @param string               $lpagery_fit         LPagery fit note.
	 * @param list<string>         $warning_flags        Warning flags.
	 * @param array<string, mixed> $template_definition  Optional template definition snapshot.
	 */
	public function __construct(
		string $page_template_key,
		string $recommendation_status,
		int $score,
		array $explanation_reasons,
		array $industry_source_refs,
		string $hierarchy_fit,
		string $lpagery_fit,
		array $warning_flags,
		array $template_definition = array()
	) {
		$this->page_template_key     = $page_template_key;
		$this->recommendation_status = $recommendation_status;
		$this->score                 = $score;
		$this->explanation_reasons   = $explanation_reasons;
		$this->industry_source_refs  = $industry_source_refs;
		$this->hierarchy_fit         = $hierarchy_fit;
		$this->lpagery_fit           = $lpagery_fit;
		$this->warning_flags         = $warning_flags;
		$this->template_definition   = $template_definition;
	}

	public function get_page_template_key(): string {
		return $this->page_template_key;
	}

	public function get_recommendation_status(): string {
		return $this->recommendation_status;
	}

	public function get_score(): int {
		return $this->score;
	}

	/** @return list<string> */
	public function get_explanation_reasons(): array {
		return $this->explanation_reasons;
	}

	/** @return list<string> */
	public function get_industry_source_refs(): array {
		return $this->industry_source_refs;
	}

	public function get_hierarchy_fit(): string {
		return $this->hierarchy_fit;
	}

	public function get_lpagery_fit(): string {
		return $this->lpagery_fit;
	}

	/** @return list<string> */
	public function get_warning_flags(): array {
		return $this->warning_flags;
	}

	/** @return array<string, mixed> */
	public function get_template_definition(): array {
		return $this->template_definition;
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
			'page_template_key'     => $this->page_template_key,
			'recommendation_status' => $this->recommendation_status,
			'score'                 => $this->score,
			'explanation_reasons'   => $this->explanation_reasons,
			'industry_source_refs'  => $this->industry_source_refs,
			'hierarchy_fit'         => $this->hierarchy_fit,
			'lpagery_fit'           => $this->lpagery_fit,
			'warning_flags'         => $this->warning_flags,
			'explanation_snippet'   => $this->get_explanation_snippet(),
		);
	}
}
