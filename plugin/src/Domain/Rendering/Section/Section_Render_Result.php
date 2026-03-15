<?php
/**
 * Render-ready section payload (spec §17.1–17.3, rendering-contract §3.1).
 * Structured output from section renderer; suitable for later block serialization.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rendering\Omission\Omission_Result;

/**
 * Immutable value object. Payload keys are stable; do not rename.
 *
 * - section_key: Section internal_key.
 * - variant: Resolved variant key.
 * - position: Zero-based order on page.
 * - field_values: Map of field_name => sanitized value.
 * - wrapper_attrs: class (list), id, data_attributes (data-aio-* only).
 * - selector_map: wrapper_class, inner_class, element_classes (role => class).
 * - structural_nodes: Ordered list of { role, class } for downstream markup.
 * - structural_hint: Optional structural_blueprint_ref.
 * - asset_hints: From section asset_declaration.
 * - accessibility_notes: Optional notes from section.
 * - errors: Non-empty when result is invalid; no payload use.
 * - omission_result: Optional (smart-omission-rendering-contract); omitted/refused/fallbacks for tests and debugging.
 *
 * Example render-ready section payload (from to_array()):
 *
 * [
 *   'section_key'        => 'st01_hero',
 *   'variant'            => 'default',
 *   'position'           => 0,
 *   'field_values'       => [ 'headline' => 'Welcome', 'subheadline' => 'Intro text', 'cta' => 'Learn more' ],
 *   'wrapper_attrs'      => [
 *     'class'           => [ 'aio-s-st01_hero', 'aio-s-st01_hero--variant-default' ],
 *     'id'              => 'aio-section-st01_hero-0',
 *     'data_attributes' => [ 'data-aio-section' => 'st01_hero', 'data-aio-variant' => 'default', 'data-aio-position' => '0' ],
 *   ],
 *   'selector_map'       => [
 *     'wrapper_class'    => 'aio-s-st01_hero',
 *     'inner_class'      => 'aio-s-st01_hero__inner',
 *     'element_classes'  => [ 'inner' => 'aio-s-st01_hero__inner' ],
 *   ],
 *   'structural_nodes'   => [ [ 'role' => 'wrapper', 'class' => 'aio-s-st01_hero' ], [ 'role' => 'inner', 'class' => 'aio-s-st01_hero__inner' ] ],
 *   'structural_hint'     => 'blueprint_st01_structure',
 *   'asset_hints'        => [ 'none' => true ],
 *   'accessibility_notes' => [],
 *   'errors'             => [],
 * ]
 */
final class Section_Render_Result {

	/** @var string */
	private string $section_key;

	/** @var string */
	private string $variant;

	/** @var int */
	private int $position;

	/** @var array<string, mixed> */
	private array $field_values;

	/** @var array{wrapper_attrs: array{class: list<string>, id: string, data_attributes: array<string, string>}, selector_map: array{wrapper_class: string, inner_class: string, element_classes: array<string, string>}, structural_nodes: list<array{role: string, class: string}>, structural_hint: string, asset_hints: array, accessibility_notes: list<string>} */
	private array $structure;

	/** @var list<string> */
	private array $errors;

	/** @var Omission_Result|null */
	private ?Omission_Result $omission_result;

	/**
	 * @param string                   $section_key     Section internal_key.
	 * @param string                   $variant         Resolved variant key.
	 * @param int                      $position        Zero-based position on page.
	 * @param array<string, mixed>     $field_values    Sanitized field name => value (may be omission-filtered).
	 * @param array<string, mixed>     $structure       wrapper_attrs, selector_map, structural_nodes, structural_hint, asset_hints, accessibility_notes.
	 * @param list<string>             $errors          Validation/render errors; non-empty when invalid.
	 * @param Omission_Result|null     $omission_result Optional; set when Smart_Omission_Service was applied.
	 */
	public function __construct(
		string $section_key,
		string $variant,
		int $position,
		array $field_values,
		array $structure,
		array $errors = array(),
		?Omission_Result $omission_result = null
	) {
		$this->section_key     = $section_key;
		$this->variant         = $variant;
		$this->position        = $position;
		$this->field_values    = $field_values;
		$this->structure       = $structure;
		$this->errors          = $errors;
		$this->omission_result = $omission_result;
	}

	/** @return Omission_Result|null */
	public function get_omission_result(): ?Omission_Result {
		return $this->omission_result;
	}

	public function get_section_key(): string {
		return $this->section_key;
	}

	public function get_variant(): string {
		return $this->variant;
	}

	public function get_position(): int {
		return $this->position;
	}

	/** @return array<string, mixed> */
	public function get_field_values(): array {
		return $this->field_values;
	}

	/** @return array{class: list<string>, id: string, data_attributes: array<string, string>, style?: string} */
	public function get_wrapper_attrs(): array {
		return $this->structure['wrapper_attrs'] ?? array(
			'class'            => array(),
			'id'               => '',
			'data_attributes'  => array(),
		);
	}

	/** Section-scoped component override style block (Prompt 254). Empty when not set. */
	public function get_section_style_block(): string {
		return (string) ( $this->structure['section_style_block'] ?? '' );
	}

	/** @return array{wrapper_class: string, inner_class: string, element_classes: array<string, string>} */
	public function get_selector_map(): array {
		return $this->structure['selector_map'] ?? array(
			'wrapper_class'   => '',
			'inner_class'     => '',
			'element_classes' => array(),
		);
	}

	/** @return list<array{role: string, class: string}> */
	public function get_structural_nodes(): array {
		return $this->structure['structural_nodes'] ?? array();
	}

	public function get_structural_hint(): string {
		return (string) ( $this->structure['structural_hint'] ?? '' );
	}

	/** @return array<string, mixed> */
	public function get_asset_hints(): array {
		return $this->structure['asset_hints'] ?? array();
	}

	/** @return list<string> */
	public function get_accessibility_notes(): array {
		return $this->structure['accessibility_notes'] ?? array();
	}

	/**
	 * Animation resolution (effective_tier, effective_families, reduced_motion_applied) when Animation_Tier_Resolver was used.
	 *
	 * @return array{effective_tier: string, effective_families: list<string>, reduced_motion_applied: bool, resolution_reason: string}|null
	 */
	public function get_animation_resolution(): ?array {
		return isset( $this->structure['animation_resolution'] ) && is_array( $this->structure['animation_resolution'] ) ? $this->structure['animation_resolution'] : null;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}

	public function is_valid(): bool {
		return empty( $this->errors );
	}

	/**
	 * Full payload for serialization or downstream assembly.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		$out = array(
			'section_key'         => $this->section_key,
			'variant'             => $this->variant,
			'position'            => $this->position,
			'field_values'        => $this->field_values,
			'wrapper_attrs'       => $this->get_wrapper_attrs(),
			'selector_map'        => $this->get_selector_map(),
			'structural_nodes'    => $this->get_structural_nodes(),
			'structural_hint'     => $this->get_structural_hint(),
			'asset_hints'         => $this->get_asset_hints(),
			'accessibility_notes'  => $this->get_accessibility_notes(),
			'errors'              => $this->errors,
			'section_style_block' => $this->get_section_style_block(),
		);
		if ( $this->omission_result !== null ) {
			$out['omission_result'] = $this->omission_result->to_array();
		}
		$anim = $this->get_animation_resolution();
		if ( $anim !== null ) {
			$out['animation_resolution'] = $anim;
		}
		return $out;
	}
}
