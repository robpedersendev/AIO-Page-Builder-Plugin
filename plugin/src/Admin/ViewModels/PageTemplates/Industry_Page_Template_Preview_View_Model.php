<?php
/**
 * View model for industry-aware page template detail preview (Prompt 383, industry-admin-screen-contract).
 * Read-only; safe for admin template detail screen. Escape on output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\PageTemplates;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for industry context on page template detail: fit, one-pager, hierarchy, LPagery, substitutes.
 */
final class Industry_Page_Template_Preview_View_Model {

	public const KEY_HAS_INDUSTRY           = 'has_industry';
	public const KEY_PRIMARY_INDUSTRY_KEY   = 'primary_industry_key';
	public const KEY_RECOMMENDATION_FIT     = 'recommendation_fit';
	public const KEY_HIERARCHY_FIT          = 'hierarchy_fit';
	public const KEY_LPAGERY_POSTURE        = 'lpagery_posture';
	public const KEY_COMPOSED_ONE_PAGER     = 'composed_one_pager';
	public const KEY_SUBSTITUTE_SUGGESTIONS = 'substitute_suggestions';
	public const KEY_WARNING_FLAGS          = 'warning_flags';
	public const KEY_EXPLANATION_REASONS    = 'explanation_reasons';
	public const KEY_COMPLIANCE_WARNINGS    = 'compliance_warnings';
	public const KEY_SUBTYPE_INFLUENCE      = 'subtype_influence';
	public const KEY_GOAL_INFLUENCE         = 'goal_influence';

	/** @var bool */
	private bool $has_industry;

	/** @var string */
	private string $primary_industry_key;

	/** @var string */
	private string $recommendation_fit;

	/** @var string */
	private string $hierarchy_fit;

	/** @var string */
	private string $lpagery_posture;

	/** @var array<string, mixed> Composed one-pager (allowed regions) for display. */
	private array $composed_one_pager;

	/** @var array<int, array<string, mixed>> Substitute suggestion result shapes. */
	private array $substitute_suggestions;

	/** @var array<int, string> */
	private array $warning_flags;

	/** @var array<int, string> */
	private array $explanation_reasons;

	/** @var array<int, array{rule_key: string, severity: string, caution_summary: string}> Advisory compliance cautions (Prompt 407). */
	private array $compliance_warnings;

	/** @var array<string, mixed> Subtype influence (Industry_Subtype_Preview_Influence_View_Model::to_array()); empty when no subtype (Prompt 441). */
	private array $subtype_influence;

	/** @var array<string, mixed> Conversion-goal influence (Conversion_Goal_Preview_Influence_View_Model::to_array()); empty when no goal (Prompt 513). */
	private array $goal_influence;

	public function __construct(
		bool $has_industry,
		string $primary_industry_key,
		string $recommendation_fit,
		string $hierarchy_fit,
		string $lpagery_posture,
		array $composed_one_pager,
		array $substitute_suggestions,
		array $warning_flags,
		array $explanation_reasons,
		array $compliance_warnings = array(),
		array $subtype_influence = array(),
		array $goal_influence = array()
	) {
		$this->has_industry           = $has_industry;
		$this->primary_industry_key   = $primary_industry_key;
		$this->recommendation_fit     = $recommendation_fit;
		$this->hierarchy_fit          = $hierarchy_fit;
		$this->lpagery_posture        = $lpagery_posture;
		$this->composed_one_pager     = $composed_one_pager;
		$this->substitute_suggestions = $substitute_suggestions;
		$this->warning_flags          = $warning_flags;
		$this->explanation_reasons    = $explanation_reasons;
		$this->compliance_warnings    = $compliance_warnings;
		$this->subtype_influence      = $subtype_influence;
		$this->goal_influence         = $goal_influence;
	}

	public function has_industry(): bool {
		return $this->has_industry;
	}

	public function get_primary_industry_key(): string {
		return $this->primary_industry_key;
	}

	public function get_recommendation_fit(): string {
		return $this->recommendation_fit;
	}

	public function get_hierarchy_fit(): string {
		return $this->hierarchy_fit;
	}

	public function get_lpagery_posture(): string {
		return $this->lpagery_posture;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function get_composed_one_pager(): array {
		return $this->composed_one_pager;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_substitute_suggestions(): array {
		return $this->substitute_suggestions;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_warning_flags(): array {
		return $this->warning_flags;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_explanation_reasons(): array {
		return $this->explanation_reasons;
	}

	/**
	 * Returns advisory compliance/caution rules for display (Prompt 407).
	 *
	 * @return array<int, array{rule_key: string, severity: string, caution_summary: string}>
	 */
	public function get_compliance_warnings(): array {
		return $this->compliance_warnings;
	}

	/**
	 * Returns subtype influence for preview (Prompt 441). Empty when no subtype.
	 *
	 * @return array<string, mixed>
	 */
	public function get_subtype_influence(): array {
		return $this->subtype_influence;
	}

	/**
	 * Returns conversion-goal influence for preview (Prompt 513). Empty when no goal.
	 *
	 * @return array<string, mixed>
	 */
	public function get_goal_influence(): array {
		return $this->goal_influence;
	}

	/**
	 * For view layer (escape on output).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			self::KEY_HAS_INDUSTRY           => $this->has_industry,
			self::KEY_PRIMARY_INDUSTRY_KEY   => $this->primary_industry_key,
			self::KEY_RECOMMENDATION_FIT     => $this->recommendation_fit,
			self::KEY_HIERARCHY_FIT          => $this->hierarchy_fit,
			self::KEY_LPAGERY_POSTURE        => $this->lpagery_posture,
			self::KEY_COMPOSED_ONE_PAGER     => $this->composed_one_pager,
			self::KEY_SUBSTITUTE_SUGGESTIONS => $this->substitute_suggestions,
			self::KEY_WARNING_FLAGS          => $this->warning_flags,
			self::KEY_EXPLANATION_REASONS    => $this->explanation_reasons,
			self::KEY_COMPLIANCE_WARNINGS    => $this->compliance_warnings,
			self::KEY_SUBTYPE_INFLUENCE      => $this->subtype_influence,
			self::KEY_GOAL_INFLUENCE         => $this->goal_influence,
		);
	}
}
