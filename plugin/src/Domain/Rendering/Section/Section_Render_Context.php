<?php
/**
 * Input context for section rendering (spec §17.1, rendering-contract §2.3, §2.4).
 * Section definition plus field data and position; built by Section_Render_Context_Builder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable context for a single section render. Caller must supply valid section definition
 * and field values; builder validates before producing context.
 */
final class Section_Render_Context {

	/** @var array<string, mixed> Section template definition (Section_Schema). */
	private array $section_definition;

	/** @var array<string, mixed> Field name => raw value (from ACF/post meta). */
	private array $field_values;

	/** @var int Zero-based position on page. */
	private int $position;

	/** @var string|null Variant override; null = use section default_variant. */
	private ?string $variant_override;

	/**
	 * @param array<string, mixed> $section_definition Section definition with internal_key, variants, default_variant, etc.
	 * @param array<string, mixed> $field_values       Field values keyed by field name.
	 * @param int                  $position          Zero-based position.
	 * @param string|null          $variant_override  Optional variant key override.
	 */
	public function __construct(
		array $section_definition,
		array $field_values,
		int $position = 0,
		?string $variant_override = null
	) {
		$this->section_definition = $section_definition;
		$this->field_values      = $field_values;
		$this->position          = $position;
		$this->variant_override  = $variant_override;
	}

	/** @return array<string, mixed> */
	public function get_section_definition(): array {
		return $this->section_definition;
	}

	/** @return array<string, mixed> */
	public function get_field_values(): array {
		return $this->field_values;
	}

	public function get_position(): int {
		return $this->position;
	}

	public function get_variant_override(): ?string {
		return $this->variant_override;
	}
}
