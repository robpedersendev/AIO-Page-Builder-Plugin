<?php
/**
 * Resolves effective blueprint for a section using family registry and variant overrides (large-scale-acf-lpagery-binding-contract §2.2, §2.3).
 * Given a section definition and its normalized blueprint, applies additive variant overrides when the section belongs to a registered family.
 * Section-owned embedded blueprint remains the source of truth; overrides only add or hide fields.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;

/**
 * Applies family-level variant overrides (add_fields, hide_field_names) to a normalized blueprint.
 * Does not replace blueprint content; keeps relationships explicit.
 * Used by Library_LPagery_Compatibility_Service to resolve effective blueprint for LPagery mapping summary (Prompt 179).
 */
final class Blueprint_Family_Resolver {

	/** @var Blueprint_Family_Registry */
	private Blueprint_Family_Registry $registry;

	public function __construct( Blueprint_Family_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Resolves effective normalized blueprint for a section: base blueprint + family variant overrides.
	 * If the section definition has variation_family_key and the registry has overrides for its variant, merges add_fields and filters hide_field_names.
	 *
	 * @param array<string, mixed> $definition Section definition (internal_key, field_blueprint_ref, variation_family_key, default_variant, etc.).
	 * @param array<string, mixed> $normalized_blueprint Already normalized blueprint from Section_Field_Blueprint_Service.
	 * @return array<string, mixed> Effective blueprint (clone with overrides applied; or unchanged if no family/override).
	 */
	public function resolve( array $definition, array $normalized_blueprint ): array {
		$family_key = \sanitize_key( (string) ( $definition['variation_family_key'] ?? '' ) );
		if ( $family_key === '' || ! $this->registry->has_family( $family_key ) ) {
			return $normalized_blueprint;
		}

		$variant_key = \sanitize_key( (string) ( $definition[ Section_Schema::FIELD_DEFAULT_VARIANT ] ?? 'default' ) );
		if ( $variant_key === '' ) {
			$variant_key = 'default';
		}

		$overrides = $this->registry->get_variant_overrides( $family_key );
		$variant_config = $overrides[ $variant_key ] ?? null;
		if ( $variant_config === null || ( empty( $variant_config[ Blueprint_Family_Registry::KEY_ADD_FIELDS ] ) && empty( $variant_config[ Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES ] ) ) ) {
			return $normalized_blueprint;
		}

		$effective = $normalized_blueprint;
		$fields = (array) ( $effective[ Field_Blueprint_Schema::FIELDS ] ?? array() );
		$section_key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? $normalized_blueprint[ Field_Blueprint_Schema::SECTION_KEY ] ?? '' );

		$hide = (array) ( $variant_config[ Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES ] ?? array() );
		if ( ! empty( $hide ) ) {
			$fields = array_values( array_filter( $fields, function ( $f ) use ( $hide ) {
				$name = (string) ( \is_array( $f ) ? ( $f[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' ) : '' );
				return $name === '' || ! in_array( $name, $hide, true );
			} ) );
		}

		$add = (array) ( $variant_config[ Blueprint_Family_Registry::KEY_ADD_FIELDS ] ?? array() );
		if ( ! empty( $add ) ) {
			foreach ( $add as $field_def ) {
				if ( ! \is_array( $field_def ) ) {
					continue;
				}
				$fields[] = $this->ensure_field_has_name_and_key( $field_def, $section_key, count( $fields ) );
			}
		}

		$effective[ Field_Blueprint_Schema::FIELDS ] = $fields;
		return $effective;
	}

	/**
	 * Returns variation_family_key from a section definition for use by LPagery compatibility (family-level token maps).
	 *
	 * @param array<string, mixed> $definition Section definition.
	 * @return string Sanitized family key or empty string.
	 */
	public function get_variation_family_key( array $definition ): string {
		return \sanitize_key( (string) ( $definition['variation_family_key'] ?? '' ) );
	}

	/**
	 * Ensures a field definition has name and key (for additive fields). Does not full-normalize.
	 *
	 * @param array<string, mixed> $field_def
	 * @param string               $section_key
	 * @param int                  $index
	 * @return array<string, mixed>
	 */
	private function ensure_field_has_name_and_key( array $field_def, string $section_key, int $index ): array {
		$name = (string) ( $field_def[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' );
		if ( $name === '' ) {
			$name = 'variant_field_' . ( $index + 1 );
			$field_def[ Field_Blueprint_Schema::FIELD_NAME ] = $name;
		}
		if ( empty( $field_def[ Field_Blueprint_Schema::FIELD_KEY ] ) ) {
			$field_def[ Field_Blueprint_Schema::FIELD_KEY ] = Field_Key_Generator::field_key( $section_key, $name );
		}
		return $field_def;
	}
}
