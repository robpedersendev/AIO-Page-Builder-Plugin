<?php
/**
 * Retrieves and serves validated, normalized section field blueprints (spec §20.1–20.6, §20.8).
 * Resolves blueprints from section definitions. Does not register fields with ACF.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Blueprints;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Exposes blueprint retrieval by section key and version.
 * Blueprints are extracted from section definitions (embedded field_blueprint) or resolved via Blueprint_Family_Resolver when applicable.
 */
final class Section_Field_Blueprint_Service implements Section_Field_Blueprint_Service_Interface {

	/** Key for embedded blueprint in section definition. */
	public const EMBEDDED_BLUEPRINT_KEY = 'field_blueprint';

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	/** @var Section_Field_Blueprint_Validator */
	private Section_Field_Blueprint_Validator $validator;

	/** @var Section_Field_Blueprint_Normalizer */
	private Section_Field_Blueprint_Normalizer $normalizer;

	/** @var Blueprint_Family_Resolver|null */
	private ?Blueprint_Family_Resolver $family_resolver;

	/** @var Form_Provider_Registry|null Optional; when set, form_embed blueprints get form_provider choices (Prompt 228). */
	private ?Form_Provider_Registry $form_provider_registry = null;

	public function __construct(
		Section_Template_Repository $section_repository,
		Section_Field_Blueprint_Validator $validator,
		Section_Field_Blueprint_Normalizer $normalizer,
		?Blueprint_Family_Resolver $family_resolver = null
	) {
		$this->section_repository = $section_repository;
		$this->validator          = $validator;
		$this->normalizer        = $normalizer;
		$this->family_resolver   = $family_resolver;
	}

	/**
	 * Retrieves normalized blueprint for a section by key.
	 *
	 * @param string      $section_key Section internal_key.
	 * @param string|null $version     Optional version filter; null = use section's current version.
	 * @return array<string, mixed>|null Normalized blueprint or null if not found/invalid.
	 */
	public function get_blueprint_for_section( string $section_key, ?string $version = null ): ?array {
		$definition = $this->section_repository->get_definition_by_key( $section_key );
		if ( $definition === null ) {
			return null;
		}
		return $this->get_blueprint_from_definition( $definition, $version );
	}

	/**
	 * Retrieves normalized blueprint for a section by post ID.
	 *
	 * @param int         $post_id Section template post ID.
	 * @param string|null $version Optional version filter.
	 * @return array<string, mixed>|null
	 */
	public function get_blueprint_for_section_id( int $post_id, ?string $version = null ): ?array {
		$definition = $this->section_repository->get_definition_by_id( $post_id );
		if ( $definition === null ) {
			return null;
		}
		return $this->get_blueprint_from_definition( $definition, $version );
	}

	/**
	 * Extracts, validates, and normalizes blueprint from section definition.
	 *
	 * @param array<string, mixed> $definition Section definition (may contain field_blueprint).
	 * @param string|null          $version    Optional version filter.
	 * @return array<string, mixed>|null
	 */
	public function get_blueprint_from_definition( array $definition, ?string $version = null ): ?array {
		$blueprint_raw = $definition[ self::EMBEDDED_BLUEPRINT_KEY ] ?? null;
		if ( ! is_array( $blueprint_raw ) || empty( $blueprint_raw ) ) {
			return null;
		}

		$section_key = (string) ( $definition[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
		$field_blueprint_ref = (string) ( $definition[ Section_Schema::FIELD_FIELD_BLUEPRINT_REF ] ?? '' );
		$section_version = (string) ( $definition['version']['version'] ?? $blueprint_raw[ Field_Blueprint_Schema::SECTION_VERSION ] ?? '1' );

		if ( $version !== null && $section_version !== $version ) {
			return null;
		}

		$result = $this->normalizer->normalize( $blueprint_raw, $section_key, $field_blueprint_ref ?: null );
		if ( ! empty( $result['errors'] ) ) {
			return null;
		}
		$normalized = $result['normalized'];
		if ( $this->family_resolver !== null ) {
			$normalized = $this->family_resolver->resolve( $definition, $normalized );
		}
		if ( $this->form_provider_registry !== null && (string) ( $definition[ Section_Schema::FIELD_CATEGORY ] ?? '' ) === 'form_embed' ) {
			$normalized = $this->augment_form_provider_choices( $normalized );
		}
		return $normalized;
	}

	/**
	 * Injects form_provider choices from the registry for form_embed blueprints (Prompt 228).
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint with FIELDS.
	 * @return array<string, mixed>
	 */
	private function augment_form_provider_choices( array $blueprint ): array {
		$fields = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? null;
		if ( ! is_array( $fields ) ) {
			return $blueprint;
		}
		$ids = $this->form_provider_registry->get_registered_provider_ids();
		if ( empty( $ids ) ) {
			return $blueprint;
		}
		$choices = array();
		foreach ( $ids as $id ) {
			$choices[ $id ] = $id;
		}
		$out = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				$out[] = $field;
				continue;
			}
			$name = (string) ( $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? '' );
			if ( $name === Form_Provider_Registry::FIELD_FORM_PROVIDER ) {
				$field['choices'] = $choices;
				$field['type']    = Field_Blueprint_Schema::TYPE_SELECT;
			}
			$out[] = $field;
		}
		$blueprint[ Field_Blueprint_Schema::FIELDS ] = $out;
		return $blueprint;
	}

	/**
	 * Sets the form provider registry for augmenting form_provider field with choices (Prompt 228). Call from container after construction.
	 *
	 * @param Form_Provider_Registry $registry
	 * @return void
	 */
	public function set_form_provider_registry( Form_Provider_Registry $registry ): void {
		$this->form_provider_registry = $registry;
	}

	/**
	 * Validates and normalizes a raw blueprint array. Caller provides section context.
	 *
	 * @param array<string, mixed> $blueprint Raw blueprint.
	 * @param string|null          $section_key Section internal_key for alignment.
	 * @param string|null          $field_blueprint_ref Section field_blueprint_ref for alignment.
	 * @return array{normalized: array<string, mixed>|null, errors: list<string>}
	 */
	public function validate_and_normalize( array $blueprint, ?string $section_key = null, ?string $field_blueprint_ref = null ): array {
		$result = $this->normalizer->normalize( $blueprint, $section_key, $field_blueprint_ref );
		return array(
			'normalized' => empty( $result['errors'] ) ? $result['normalized'] : null,
			'errors'     => $result['errors'],
		);
	}

	/**
	 * Returns all normalized blueprints from sections that have embedded field_blueprint.
	 * Used by ACF Group Registrar for bulk registration.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_all_blueprints(): array {
		$definitions = $this->section_repository->list_all_definitions( 9999, 0 );
		$out = array();
		foreach ( $definitions as $def ) {
			$bp = $this->get_blueprint_from_definition( $def );
			if ( $bp !== null ) {
				$out[] = $bp;
			}
		}
		return $out;
	}

	/**
	 * Returns the group key for a section per acf-key-naming-contract.
	 *
	 * @param string $section_key
	 * @return string
	 */
	public function get_group_key_for_section( string $section_key ): string {
		return Field_Key_Generator::group_key( $section_key );
	}

	/**
	 * Returns field keys (name or key) that are LPagery token-compatible per field type (spec §20.7, §21.3).
	 * Additive compatibility annotation; does not change blueprint validation or registration.
	 *
	 * @param array<string, mixed> $blueprint Normalized blueprint (must contain Field_Blueprint_Schema::FIELDS).
	 * @return list<string> Field keys/names whose type is in LPAGERY_SUPPORTED_TYPES.
	 */
	public function get_lpagery_compatible_field_keys( array $blueprint ): array {
		$fields = $blueprint[ Field_Blueprint_Schema::FIELDS ] ?? null;
		if ( ! is_array( $fields ) ) {
			return array();
		}
		$out = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$type = (string) ( $field[ Field_Blueprint_Schema::FIELD_TYPE ] ?? '' );
			if ( ! in_array( $type, Field_Blueprint_Schema::LPAGERY_SUPPORTED_TYPES, true ) ) {
				continue;
			}
			$key = (string) ( $field[ Field_Blueprint_Schema::FIELD_KEY ] ?? $field[ Field_Blueprint_Schema::FIELD_NAME ] ?? '' );
			if ( $key !== '' ) {
				$out[] = $key;
			}
		}
		return $out;
	}
}
