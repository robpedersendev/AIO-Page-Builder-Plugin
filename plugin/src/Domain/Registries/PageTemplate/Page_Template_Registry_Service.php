<?php
/**
 * Page template registry service: create, read, update, deprecate (spec §13, §10.2).
 * Validates via Page_Template_Validator; persists via Page_Template_Repository.
 * Callers must perform capability and nonce checks before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Page templates are ordered reusable patterns. Section references must exist in section registry.
 */
final class Page_Template_Registry_Service {

	/** @var Page_Template_Validator */
	private Page_Template_Validator $validator;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $repository;

	public function __construct(
		Page_Template_Validator $validator,
		Page_Template_Repository $repository
	) {
		$this->validator  = $validator;
		$this->repository = $repository;
	}

	/**
	 * Creates a new page template.
	 *
	 * @param array<string, mixed> $input
	 * @return Page_Template_Registry_Result
	 */
	public function create( array $input ): Page_Template_Registry_Result {
		$result = $this->validator->validate_for_create( $input );
		if ( ! $result->valid || $result->normalized === null ) {
			return Page_Template_Registry_Result::failure( $result->errors, 0 );
		}
		$id = $this->repository->save_definition( $result->normalized );
		if ( $id <= 0 ) {
			return Page_Template_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Page_Template_Registry_Result::success( $id, $result->normalized );
	}

	/**
	 * Updates an existing page template. Internal key is immutable.
	 *
	 * @param int                  $post_id
	 * @param array<string, mixed> $input
	 * @return Page_Template_Registry_Result
	 */
	public function update( int $post_id, array $input ): Page_Template_Registry_Result {
		$existing = $this->repository->get_by_id( $post_id );
		if ( $existing === null ) {
			return Page_Template_Registry_Result::failure( array( 'Page template not found' ), 0 );
		}
		$existing_def = $existing['definition'] ?? null;
		if ( ! is_array( $existing_def ) ) {
			return Page_Template_Registry_Result::failure( array( 'Existing template has no definition' ), 0 );
		}
		$merged = array_merge( $existing_def, $input );
		$merged[ Page_Template_Schema::FIELD_INTERNAL_KEY ] = $existing_def[ Page_Template_Schema::FIELD_INTERNAL_KEY ];
		$result = $this->validator->validate_for_update( $merged, $post_id );
		if ( ! $result->valid || $result->normalized === null ) {
			return Page_Template_Registry_Result::failure( $result->errors, 0 );
		}
		$id = $this->repository->save_definition( $result->normalized );
		if ( $id <= 0 ) {
			return Page_Template_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Page_Template_Registry_Result::success( $id, $result->normalized );
	}

	/**
	 * Transitions page template to deprecated status.
	 *
	 * @param int    $post_id
	 * @param string $reason
	 * @param string $replacement_key
	 * @return Page_Template_Registry_Result
	 */
	public function deprecate( int $post_id, string $reason, string $replacement_key = '' ): Page_Template_Registry_Result {
		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Page_Template_Registry_Result::failure( array( 'Page template not found' ), 0 );
		}
		$existing[ Page_Template_Schema::FIELD_STATUS ] = 'deprecated';
		$existing['deprecation'] = array(
			'deprecated'                     => true,
			'reason'                         => \sanitize_text_field( $reason ),
			'replacement_template_key'       => $this->sanitize_key( $replacement_key ),
			'interpretability_of_old_plans'  => true,
			'exclude_from_new_build_selection' => true,
		);
		if ( $replacement_key !== '' ) {
			$existing['replacement_template_refs'] = array_merge(
				(array) ( $existing['replacement_template_refs'] ?? array() ),
				array( $this->sanitize_key( $replacement_key ) )
			);
		}
		$errors = $this->validator->validate_completeness( $existing );
		if ( ! empty( $errors ) ) {
			return Page_Template_Registry_Result::failure( $errors, 0 );
		}
		$id = $this->repository->save_definition( $existing );
		if ( $id <= 0 ) {
			return Page_Template_Registry_Result::failure( array( 'Persistence failed' ), 0 );
		}
		return Page_Template_Registry_Result::success( $id, $existing );
	}

	/**
	 * Reads page template definition by internal key.
	 *
	 * @param string $key
	 * @return array<string, mixed>|null
	 */
	public function get_by_key( string $key ): ?array {
		return $this->repository->get_definition_by_key( $key );
	}

	/**
	 * Reads page template definition by post ID.
	 *
	 * @param int $id
	 * @return array<string, mixed>|null
	 */
	public function get_by_id( int $id ): ?array {
		return $this->repository->get_definition_by_id( $id );
	}

	/**
	 * Lists page template definitions by status.
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
	 * Lists page template definitions by archetype.
	 *
	 * @param string $archetype
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_by_archetype( string $archetype, int $limit = 0, int $offset = 0 ): array {
		return $this->repository->list_by_archetype( $archetype, $limit, $offset );
	}

	/**
	 * Returns one-pager metadata from a page template definition.
	 *
	 * @param array<string, mixed> $definition
	 * @return array<string, mixed>
	 */
	public function get_one_pager_metadata( array $definition ): array {
		$op = $definition[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
		return is_array( $op ) ? $op : array();
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, Page_Template_Schema::INTERNAL_KEY_MAX_LENGTH );
	}
}
