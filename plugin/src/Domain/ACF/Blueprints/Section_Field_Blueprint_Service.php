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

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Exposes blueprint retrieval by section key and version.
 * Blueprints are extracted from section definitions (embedded field_blueprint) or provided via resolver.
 */
final class Section_Field_Blueprint_Service {

	/** Key for embedded blueprint in section definition. */
	public const EMBEDDED_BLUEPRINT_KEY = 'field_blueprint';

	/** @var Section_Template_Repository */
	private Section_Template_Repository $section_repository;

	/** @var Section_Field_Blueprint_Validator */
	private Section_Field_Blueprint_Validator $validator;

	/** @var Section_Field_Blueprint_Normalizer */
	private Section_Field_Blueprint_Normalizer $normalizer;

	public function __construct(
		Section_Template_Repository $section_repository,
		Section_Field_Blueprint_Validator $validator,
		Section_Field_Blueprint_Normalizer $normalizer
	) {
		$this->section_repository = $section_repository;
		$this->validator         = $validator;
		$this->normalizer        = $normalizer;
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
		return $result['normalized'];
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
	 * Returns the group key for a section per acf-key-naming-contract.
	 *
	 * @param string $section_key
	 * @return string
	 */
	public function get_group_key_for_section( string $section_key ): string {
		return Field_Key_Generator::group_key( $section_key );
	}
}
