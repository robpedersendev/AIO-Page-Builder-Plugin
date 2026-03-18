<?php
/**
 * Builds and validates section render context (spec §17.1, §12).
 * Consumes section definition and field data; produces Section_Render_Context or validation errors.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rendering\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Builds render context from section definition and field values.
 * Validates definition completeness; rejects invalid or incomplete context clearly.
 */
final class Section_Render_Context_Builder {

	/**
	 * Builds context and validates definition. Returns context and errors; if errors non-empty, context is null.
	 *
	 * @param array<string, mixed> $section_definition Section template definition (Section_Schema).
	 * @param array<string, mixed> $field_values       Field name => value.
	 * @param int                  $position           Zero-based position on page.
	 * @param string|null          $variant_override   Optional variant key override.
	 * @return array{ context: Section_Render_Context|null, errors: list<string> }
	 */
	public function build(
		array $section_definition,
		array $field_values,
		int $position = 0,
		?string $variant_override = null
	): array {
		$errors = $this->validate_definition( $section_definition, $variant_override );
		if ( ! empty( $errors ) ) {
			return array(
				'context' => null,
				'errors'  => $errors,
			);
		}

		$sanitized_values = $this->sanitize_field_values( $field_values );
		$context          = new Section_Render_Context(
			$section_definition,
			$sanitized_values,
			$position,
			$variant_override
		);

		return array(
			'context' => $context,
			'errors'  => array(),
		);
	}

	/**
	 * Validates section definition for rendering. Returns list of error messages.
	 *
	 * @param array<string, mixed> $section_definition Section definition.
	 * @param string|null          $variant_override   Optional variant override to validate.
	 * @return list<string>
	 */
	public function validate_definition( array $section_definition, ?string $variant_override = null ): array {
		$errors = array();

		$key = (string) ( $section_definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		if ( $key === '' ) {
			$errors[] = 'Section definition missing internal_key.';
			return $errors;
		}

		if ( ! preg_match( Section_Schema::INTERNAL_KEY_PATTERN, $key ) ) {
			$errors[] = 'Section internal_key must match pattern ^[a-z0-9_]+$.';
		}

		$variants = $section_definition[ Section_Schema::FIELD_VARIANTS ] ?? null;
		if ( ! is_array( $variants ) || empty( $variants ) ) {
			$errors[] = 'Section definition must have non-empty variants.';
			return $errors;
		}

		$default = (string) ( $section_definition[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? '' );
		if ( $default === '' ) {
			$errors[] = 'Section definition missing default_variant.';
		} elseif ( ! array_key_exists( $default, $variants ) ) {
			$errors[] = 'Section default_variant must be a key in variants.';
		}

		if ( $variant_override !== null && $variant_override !== '' ) {
			if ( ! array_key_exists( $variant_override, $variants ) ) {
				$errors[] = 'Variant override must be a key in section variants.';
			}
		}

		return $errors;
	}

	/**
	 * Sanitizes field values for safe use in render output. No privileged or secret data.
	 *
	 * @param array<string, mixed> $field_values Raw field values.
	 * @return array<string, mixed>
	 */
	private function sanitize_field_values( array $field_values ): array {
		$out = array();
		foreach ( $field_values as $name => $value ) {
			if ( ! is_string( $name ) || $name === '' ) {
				continue;
			}
			$out[ $name ] = $this->sanitize_single_value( $value );
		}
		return $out;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return mixed Sanitized value (scalar or array of scalars).
	 */
	private function sanitize_single_value( $value ) {
		if ( is_scalar( $value ) ) {
			if ( is_string( $value ) ) {
				$value = \strip_tags( $value );
				return \sanitize_text_field( $value );
			}
			if ( is_int( $value ) || is_float( $value ) ) {
				return $value;
			}
			if ( is_bool( $value ) ) {
				return $value;
			}
			return (string) $value;
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$out[ $k ] = $this->sanitize_single_value( $v );
			}
			return $out;
		}
		return '';
	}
}
