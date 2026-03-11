<?php
/**
 * Section registry service: create, read, update, deprecate (spec §12, §10.1).
 * Validates via Section_Validator; persists via Section_Template_Repository.
 * Callers must perform capability checks and nonces before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\Section;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Section templates are foundational reusable building blocks. This service provides:
 * - create/read/update with validation
 * - status transitions (active/deprecated) with deprecation reason and replacement reference
 * - query by key, status, category
 * - immutable internal_key after creation (no runtime mutation).
 */
final class Section_Registry_Service {

	/** @var Section_Validator */
	private Section_Validator $validator;

	/** @var Section_Template_Repository */
	private Section_Template_Repository $repository;

	public function __construct(
		Section_Validator $validator,
		Section_Template_Repository $repository
	) {
		$this->validator  = $validator;
		$this->repository = $repository;
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
	 *
	 * @param int    $post_id Section template post ID.
	 * @param string $reason  Reason for deprecation.
	 * @param string $replacement_key Optional replacement section internal_key.
	 * @return Section_Registry_Result
	 */
	public function deprecate( int $post_id, string $reason, string $replacement_key = '' ): Section_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Section_Registry_Result::failure( array( 'Section not found' ), 0 );
		}
		$existing[ Section_Schema::FIELD_STATUS ] = 'deprecated';
		$existing['deprecation'] = array(
			'deprecated'                 => true,
			'reason'                     => \sanitize_text_field( $reason ),
			'replacement_section_key'    => $this->sanitize_key( $replacement_key ),
			'retain_existing_references' => true,
			'exclude_from_new_selection' => true,
			'preserve_rendered_pages'    => true,
		);
		if ( $replacement_key !== '' ) {
			$existing['replacement_section_suggestions'] = array_merge(
				(array) ( $existing['replacement_section_suggestions'] ?? array() ),
				array( $this->sanitize_key( $replacement_key ) )
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
