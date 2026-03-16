<?php
/**
 * View model for industry-aware section detail preview (Prompt 384, industry-admin-screen-contract).
 * Read-only; safe for admin section detail screen. Escape on output.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin\ViewModels\Sections;

defined( 'ABSPATH' ) || exit;

/**
 * DTO for industry context on section detail: fit, composed helper, warnings, substitutes.
 */
final class Industry_Section_Preview_View_Model {

	public const KEY_HAS_INDUSTRY           = 'has_industry';
	public const KEY_PRIMARY_INDUSTRY_KEY   = 'primary_industry_key';
	public const KEY_RECOMMENDATION_FIT     = 'recommendation_fit';
	public const KEY_COMPOSED_HELPER        = 'composed_helper';
	public const KEY_SUBSTITUTE_SUGGESTIONS  = 'substitute_suggestions';
	public const KEY_WARNING_FLAGS           = 'warning_flags';
	public const KEY_EXPLANATION_REASONS    = 'explanation_reasons';
	public const KEY_COMPLIANCE_WARNINGS     = 'compliance_warnings';

	/** @var bool */
	private bool $has_industry;

	/** @var string */
	private string $primary_industry_key;

	/** @var string */
	private string $recommendation_fit;

	/** @var array<string, mixed> Composed helper (allowed regions) for display. */
	private array $composed_helper;

	/** @var array<int, array<string, mixed>> Substitute suggestion result shapes. */
	private array $substitute_suggestions;

	/** @var list<string> */
	private array $warning_flags;

	/** @var list<string> */
	private array $explanation_reasons;

	/** @var list<array{rule_key: string, severity: string, caution_summary: string}> Advisory compliance cautions (Prompt 407). */
	private array $compliance_warnings;

	public function __construct(
		bool $has_industry,
		string $primary_industry_key,
		string $recommendation_fit,
		array $composed_helper,
		array $substitute_suggestions,
		array $warning_flags,
		array $explanation_reasons,
		array $compliance_warnings = array()
	) {
		$this->has_industry           = $has_industry;
		$this->primary_industry_key   = $primary_industry_key;
		$this->recommendation_fit     = $recommendation_fit;
		$this->composed_helper        = $composed_helper;
		$this->substitute_suggestions = $substitute_suggestions;
		$this->warning_flags          = $warning_flags;
		$this->explanation_reasons    = $explanation_reasons;
		$this->compliance_warnings    = $compliance_warnings;
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

	/**
	 * @return array<string, mixed>
	 */
	public function get_composed_helper(): array {
		return $this->composed_helper;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_substitute_suggestions(): array {
		return $this->substitute_suggestions;
	}

	/**
	 * @return list<string>
	 */
	public function get_warning_flags(): array {
		return $this->warning_flags;
	}

	/**
	 * @return list<string>
	 */
	public function get_explanation_reasons(): array {
		return $this->explanation_reasons;
	}

	/**
	 * Returns advisory compliance/caution rules for display (Prompt 407).
	 *
	 * @return list<array{rule_key: string, severity: string, caution_summary: string}>
	 */
	public function get_compliance_warnings(): array {
		return $this->compliance_warnings;
	}

	/**
	 * For view layer (escape on output).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			self::KEY_HAS_INDUSTRY          => $this->has_industry,
			self::KEY_PRIMARY_INDUSTRY_KEY  => $this->primary_industry_key,
			self::KEY_RECOMMENDATION_FIT    => $this->recommendation_fit,
			self::KEY_COMPOSED_HELPER       => $this->composed_helper,
			self::KEY_SUBSTITUTE_SUGGESTIONS => $this->substitute_suggestions,
			self::KEY_WARNING_FLAGS         => $this->warning_flags,
			self::KEY_EXPLANATION_REASONS   => $this->explanation_reasons,
			self::KEY_COMPLIANCE_WARNINGS   => $this->compliance_warnings,
		);
	}
}
