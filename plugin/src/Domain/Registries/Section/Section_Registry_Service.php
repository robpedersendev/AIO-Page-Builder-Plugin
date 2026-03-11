<?php
/**
 * Section registry service: create, read, update, deprecate (spec §12, §10.1, §12.15).
 * Validates via Section_Validator; deprecation via Registry_Deprecation_Service.
 * Callers must perform capability checks and nonces before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Section templates are foundational reusable building blocks. This service provides:
 * - create/read/update with validation
 * - status transitions (active/deprecated) with deprecation reason and replacement reference
 * - query by key, status, category; list_eligible_for_new_selection excludes deprecated
 * - immutable internal_key after creation (no runtime mutation).
 */
final class Section_Registry_Service {
	/** @var Section_Validator */
	private Section_Validator $validator;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $repository;

	/** @var Registry_Deprecation_Service|null */
	private ?Registry_Deprecation_Service $deprecation_service;

	/** @var \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service|null */
	private $blueprint_service;

	public function __construct(
		Section_Validator $validator,
		Section_Template_Repository $repository,
		?Registry_Deprecation_Service $deprecation_service = null
	) {
		$this->validator          = $validator;
		$this->repository         = $repository;
		$this->deprecation_service = $deprecation_service;
	}

	/**
	 * Creates a new section template. Validates; rejects incomplete or duplicate key.
	 *
	 * @param array<string, mixed> $input Raw or partial section definition.
	 * @return Section_Registry_Result
	 */
	public function create( array $input ): Section_Registry_Result {
		$result = $this->validator->validate_for_create( $input );
		if ( ! $result->valid || $result->normalized === null ) {
			return Section_Registry_Result::failure( $result->errors, 0 );
		}
		$id = $this->repository->save_definition( $result->normalized );
		if ( $id <= 0 ) {
			return Section_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Section_Registry_Result::success( $id, $result->normalized );
	}

	/**
	 * Updates an existing section template. Internal key is immutable.
	 *
	 * @param int                  $post_id Existing section template post ID.
	 * @param array<string, mixed> $input   Raw or partial definition (internal_key must match existing).
	 * @return Section_Registry_Result
	 */
	public function update( int $post_id, array $input ): Section_Registry_Result {
		$existing = $this->repository->get_by_id( $post_id );
		if ( $existing === null ) {
			return Section_Registry_Result::failure( array( 'Section not found' ), 0 );
		}
		$existing_def = $existing['definition'] ?? null;
		if ( ! is_array( $existing_def ) ) {
			return Section_Registry_Result::failure( array( 'Existing section has no definition' ), 0 );
		}
		$merged = array_merge( $existing_def, $input );
		$merged[ Section_Schema::FIELD_INTERNAL_KEY ] = $existing_def[ Section_Schema::FIELD_INTERNAL_KEY ];
		$result = $this->validator->validate_for_update( $merged, $post_id );
		if ( ! $result->valid || $result->normalized === null ) {
			return Section_Registry_Result::failure( $result->errors, 0 );
		}
		$id = $this->repository->save_definition( $result->normalized );
		if ( $id <= 0 ) {
			return Section_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Section_Registry_Result::success( $id, $result->normalized );
	}

	/**
	 * Transitions section to deprecated status with reason and optional replacement reference.
	 * Uses Registry_Deprecation_Service when available for validation.
	 *
	 * @param int    $post_id Section template post ID.
	 * @param string $reason  Reason for deprecation (required).
	 * @param string $replacement_key Optional replacement section internal_key.
	 * @return Section_Registry_Result
	 */
	public function deprecate( int $post_id, string $reason, string $replacement_key = '' ): Section_Registry_Result {
		if ( $this->deprecation_service !== null ) {
			$validation = $this->deprecation_service->validate_section_deprecation( $post_id, $reason, $replacement_key );
			if ( ! $validation->valid ) {
				return Section_Registry_Result::failure( $validation->errors, 0 );
			}
		} elseif ( \sanitize_text_field( $reason ) === '' ) {
			return Section_Registry_Result::failure( array( 'Deprecation reason is required' ), 0 );
		}

		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Section_Registry_Result::failure( array( 'Section not found' ), 0 );
		}
		$existing[ Section_Schema::FIELD_STATUS ] = 'deprecated';
		$existing['deprecation'] = $this->deprecation_service !== null
			? $this->deprecation_service->get_section_deprecation_block( $reason, $replacement_key )
			: Deprecation_Metadata::for_section( $reason, $replacement_key );
		if ( $replacement_key !== '' ) {
			$key = $this->sanitize_key( $replacement_key );
			$existing['replacement_section_suggestions'] = array_merge(
				(array) ( $existing['replacement_section_suggestions'] ?? array() ),
				array( $key )
			);
		}
		$result = $this->validator->validate_completeness( $existing );
		if ( ! empty( $result ) ) {
			return Section_Registry_Result::failure( $result, 0 );
		}
		$id = $this->repository->save_definition( $existing );
		if ( $id <= 0 ) {
			return Section_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Section_Registry_Result::success( $id, $existing );
	}

	/**
	 * Reads section definition by internal key.
	 *
	 * @param string $key Internal section key.
	 * @return array<string, mixed>|null
	 */
	public function get_by_key( string $key ): ?array {
		return $this->repository->get_definition_by_key( $key );
	}

	/**
	 * Reads section definition by post ID.
	 *
	 * @param int $id Post ID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( int $id ): ?array {
		return $this->repository->get_definition_by_id( $id );
	}

	/**
	 * Lists section definitions by status.
	 *
	 * @param string $status
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_by_status( string $status, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_definitions_by_status( $status, $limit, $offset );
	}

	/**
	 * Lists section definitions by category.
	 *
	 * @param string $category
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_by_category( string $category, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_by_category( $category, $limit, $offset );
	}

	/**
	 * Lists section definitions eligible for new selection (excludes deprecated).
	 *
	 * @param string $status Optional filter by status (e.g. 'active'); empty = all non-deprecated.
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_eligible_for_new_selection( string $status = 'active', int $limit = 0, int $offset = 0 ): array {
		$list = $status !== '' ? $this->repository->list_definitions_by_status( $status, $limit, $offset ) : $this->repository->list_all_definitions( $limit, $offset );
		return array_values( array_filter( $list, [ Deprecation_Metadata::class, 'is_eligible_for_new_use' ] ) );
	}

	/**
	 * Returns normalized field blueprint for section when blueprint service is available and section has embedded blueprint.
	 *
	 * @param string      $section_key Section internal_key.
	 * @param string|null $version     Optional version filter.
	 * @return array<string, mixed>|null
	 */
	public function get_blueprint_for_section( string $section_key, ?string $version = null ): ?array {
		if ( $this->blueprint_service === null ) {
			return null;
		}
		return $this->blueprint_service->get_blueprint_for_section( $section_key, $version );
	}

	/**
	 * Injects blueprint service for get_blueprint_for_section. Call from provider after both services exist.
	 *
	 * @param \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service $service
	 */
	public function set_blueprint_service( \AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service $service ): void {
		$this->blueprint_service = $service;
	}

	/**
	 * Returns asset dependency and documentation references from a section definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array{asset_declaration: array, helper_ref: string, css_contract_ref: string}
	 */
	public function get_asset_and_doc_refs( array $definition ): array {
		return array(
			'asset_declaration'    => $definition[ Section_Schema::FIELD_ASSET_DECLARATION ] ?? array( 'none' => true ),
			'helper_ref'          => (string) ( $definition[ Section_Schema::FIELD_HELPER_REF ] ?? '' ),
			'css_contract_ref'    => (string) ( $definition[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ),
		);
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, Section_Schema::INTERNAL_KEY_MAX_LENGTH );
	}
}
