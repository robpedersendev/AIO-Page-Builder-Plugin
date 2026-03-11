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

	/**
	 * @param string                   $section_key   Section internal_key.
	 * @param string                   $variant       Resolved variant key.
	 * @param int                      $position      Zero-based position on page.
	 * @param array<string, mixed>     $field_values  Sanitized field name => value.
	 * @param array<string, mixed>     $structure     wrapper_attrs, selector_map, structural_nodes, structural_hint, asset_hints, accessibility_notes.
	 * @param list<string>             $errors        Validation/render errors; non-empty when invalid.
	 */
	public function __construct(
		string $section_key,
		string $variant,
		int $position,
		array $field_values,
		array $structure,
		array $errors = array()
	) {
		$this->section_key  = $section_key;
		$this->variant      = $variant;
		$this->position     = $position;
		$this->field_values = $field_values;
		$this->structure    = $structure;
		$this->errors       = $errors;
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

	/** @return array{class: list<string>, id: string, data_attributes: array<string, string>} */
	public function get_wrapper_attrs(): array {
		return $this->structure['wrapper_attrs'] ?? array(
			'class'            => array(),
			'id'               => '',
			'data_attributes'  => array(),
		);
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
		return array(
			'section_key'        => $this->section_key,
			'variant'            => $this->variant,
			'position'           => $this->position,
			'field_values'       => $this->field_values,
			'wrapper_attrs'      => $this->get_wrapper_attrs(),
			'selector_map'       => $this->get_selector_map(),
			'structural_nodes'   => $this->get_structural_nodes(),
			'structural_hint'     => $this->get_structural_hint(),
			'asset_hints'        => $this->get_asset_hints(),
			'accessibility_notes' => $this->get_accessibility_notes(),
			'errors'             => $this->errors,
		);
	}
}
