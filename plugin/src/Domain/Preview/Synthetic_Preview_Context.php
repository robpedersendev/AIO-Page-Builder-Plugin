<?php
/**
 * Immutable context for synthetic preview data generation (spec §17.1, template-preview-and-dummy-data-contract).
 * Carries type (section/page), key, family/category, variant, and options (reduced-motion, animation tier, omission case).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Value object describing for whom and how to generate synthetic preview data.
 * Deterministic; no secrets or production data.
 */
final class Synthetic_Preview_Context {

	/** Preview target: section template. */
	public const TYPE_SECTION = 'section';

	/** Preview target: page template. */
	public const TYPE_PAGE = 'page';

	/** Animation tier: no animation (animation-support-and-fallback-contract §2). */
	public const ANIMATION_TIER_NONE = 'none';

	/** Animation tier: minimal motion. */
	public const ANIMATION_TIER_SUBTLE = 'subtle';

	/** Animation tier: moderate motion. */
	public const ANIMATION_TIER_ENHANCED = 'enhanced';

	/** Animation tier: rich motion. */
	public const ANIMATION_TIER_PREMIUM = 'premium';

	/** Omission test: include optional empty fields to exercise smart-omission (smart-omission-rendering-contract). */
	public const OMISSION_CASE_OPTIONAL_EMPTY = 'optional_empty';

	/** @var string */
	private string $type;

	/** @var string */
	private string $key;

	/** @var string Section purpose_family (when type=section) or page template_family (when type=page). */
	private string $purpose_family;

	/** @var string Page template_category_class when type=page; empty when type=section. */
	private string $template_category_class;

	/** @var string */
	private string $variant;

	/** @var bool */
	private bool $reduced_motion;

	/** @var string */
	private string $animation_tier;

	/** @var string Omission test case: '' or OMISSION_CASE_OPTIONAL_EMPTY. */
	private string $omission_case;

	private function __construct(
		string $type,
		string $key,
		string $purpose_family,
		string $template_category_class,
		string $variant,
		bool $reduced_motion,
		string $animation_tier,
		string $omission_case
	) {
		$this->type                   = $type;
		$this->key                    = $key;
		$this->purpose_family         = $purpose_family;
		$this->template_category_class = $template_category_class;
		$this->variant                = $variant;
		$this->reduced_motion         = $reduced_motion;
		$this->animation_tier         = $animation_tier;
		$this->omission_case          = $omission_case;
	}

	/**
	 * Builds context for a section template preview.
	 *
	 * @param string $section_key      Section template internal_key.
	 * @param string $purpose_family   section_purpose_family (hero, proof, cta, etc.).
	 * @param string $variant          Variant key (default 'default').
	 * @param bool   $reduced_motion   Whether to honor reduced-motion (animation tier effective = none when true).
	 * @param string $animation_tier   none, subtle, enhanced, premium.
	 * @param string $omission_case    '' or OMISSION_CASE_OPTIONAL_EMPTY for omission testing.
	 * @return self
	 */
	public static function for_section(
		string $section_key,
		string $purpose_family = 'other',
		string $variant = 'default',
		bool $reduced_motion = false,
		string $animation_tier = self::ANIMATION_TIER_NONE,
		string $omission_case = ''
	): self {
		return new self(
			self::TYPE_SECTION,
			$section_key,
			$purpose_family !== '' ? $purpose_family : 'other',
			'',
			$variant !== '' ? $variant : 'default',
			$reduced_motion,
			self::normalize_animation_tier( $animation_tier, $reduced_motion ),
			$omission_case
		);
	}

	/**
	 * Builds context for a page template preview.
	 *
	 * @param string $template_key           Page template internal_key.
	 * @param string $template_category_class top_level, hub, nested_hub, child_detail.
	 * @param string $template_family        template_family (home, services, etc.).
	 * @param string $variant                Unused for page; kept for API consistency.
	 * @param bool   $reduced_motion
	 * @param string $animation_tier
	 * @param string $omission_case
	 * @return self
	 */
	public static function for_page(
		string $template_key,
		string $template_category_class = '',
		string $template_family = '',
		string $variant = 'default',
		bool $reduced_motion = false,
		string $animation_tier = self::ANIMATION_TIER_NONE,
		string $omission_case = ''
	): self {
		return new self(
			self::TYPE_PAGE,
			$template_key,
			$template_family !== '' ? $template_family : 'other',
			$template_category_class,
			$variant,
			$reduced_motion,
			self::normalize_animation_tier( $animation_tier, $reduced_motion ),
			$omission_case
		);
	}

	private static function normalize_animation_tier( string $tier, bool $reduced_motion ): string {
		if ( $reduced_motion ) {
			return self::ANIMATION_TIER_NONE;
		}
		$valid = array( self::ANIMATION_TIER_NONE, self::ANIMATION_TIER_SUBTLE, self::ANIMATION_TIER_ENHANCED, self::ANIMATION_TIER_PREMIUM );
		return \in_array( $tier, $valid, true ) ? $tier : self::ANIMATION_TIER_NONE;
	}

	public function get_type(): string {
		return $this->type;
	}

	public function get_key(): string {
		return $this->key;
	}

	public function get_purpose_family(): string {
		return $this->purpose_family;
	}

	public function get_template_category_class(): string {
		return $this->template_category_class;
	}

	public function get_variant(): string {
		return $this->variant;
	}

	public function is_reduced_motion(): bool {
		return $this->reduced_motion;
	}

	public function get_animation_tier(): string {
		return $this->animation_tier;
	}

	public function get_omission_case(): string {
		return $this->omission_case;
	}

	/** Whether this context is for a section (vs page). */
	public function is_section(): bool {
		return $this->type === self::TYPE_SECTION;
	}

	/** Whether this context is for a page template. */
	public function is_page(): bool {
		return $this->type === self::TYPE_PAGE;
	}

	/**
	 * Exports to array for payloads and tests.
	 *
	 * @return array{type: string, key: string, purpose_family: string, template_category_class: string, variant: string, reduced_motion: bool, animation_tier: string, omission_case: string}
	 */
	public function to_array(): array {
		return array(
			'type'                    => $this->type,
			'key'                     => $this->key,
			'purpose_family'          => $this->purpose_family,
			'template_category_class' => $this->template_category_class,
			'variant'                 => $this->variant,
			'reduced_motion'          => $this->reduced_motion,
			'animation_tier'          => $this->animation_tier,
			'omission_case'           => $this->omission_case,
		);
	}
}
