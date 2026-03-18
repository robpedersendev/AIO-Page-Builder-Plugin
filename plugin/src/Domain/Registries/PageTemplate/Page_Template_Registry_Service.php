<?php
/**
 * Page template registry service: create, read, update, deprecate (spec §13, §10.2, §13.13).
 * Validates via Page_Template_Validator; deprecation via Registry_Deprecation_Service.
 * Callers must perform capability and nonce checks before mutating.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Registries\PageTemplate;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Filter_Result;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Pagination;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\FormProvider\Form_Integration_Definitions;
use AIOPageBuilder\Domain\FormProvider\Form_Template_Seeder;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Page templates are ordered reusable patterns. Section references must exist in section registry.
 */
final class Page_Template_Registry_Service {

	/** @var Page_Template_Validator */
	private Page_Template_Validator $validator;

	/** @var Page_Template_Repository */
	private Page_Template_Repository $repository;

	/** @var Registry_Deprecation_Service|null */
	private ?Registry_Deprecation_Service $deprecation_service;

	/** @var Large_Library_Query_Service|null */
	private ?Large_Library_Query_Service $large_library_query_service = null;

	public function __construct(
		Page_Template_Validator $validator,
		Page_Template_Repository $repository,
		?Registry_Deprecation_Service $deprecation_service = null
	) {
		$this->validator           = $validator;
		$this->repository          = $repository;
		$this->deprecation_service = $deprecation_service;
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
	 * Uses Registry_Deprecation_Service when available for validation.
	 *
	 * @param int    $post_id
	 * @param string $reason
	 * @param string $replacement_key
	 * @return Page_Template_Registry_Result
	 */
	public function deprecate( int $post_id, string $reason, string $replacement_key = '' ): Page_Template_Registry_Result {
		if ( $this->deprecation_service !== null ) {
			$validation = $this->deprecation_service->validate_page_template_deprecation( $post_id, $reason, $replacement_key );
			if ( ! $validation->valid ) {
				return Page_Template_Registry_Result::failure( $validation->errors, 0 );
			}
		} elseif ( \sanitize_text_field( $reason ) === '' ) {
			return Page_Template_Registry_Result::failure( array( 'Deprecation reason is required' ), 0 );
		}

		$existing = $this->repository->get_definition_by_id( $post_id );
		if ( $existing === null ) {
			return Page_Template_Registry_Result::failure( array( 'Page template not found' ), 0 );
		}
		$existing[ Page_Template_Schema::FIELD_STATUS ] = 'deprecated';
		$existing['deprecation']                        = $this->deprecation_service !== null
			? $this->deprecation_service->get_page_template_deprecation_block( $reason, $replacement_key )
			: Deprecation_Metadata::for_page_template( $reason, $replacement_key );
		if ( $replacement_key !== '' ) {
			$key                                   = $this->sanitize_key( $replacement_key );
			$existing['replacement_template_refs'] = array_merge(
				(array) ( $existing['replacement_template_refs'] ?? array() ),
				array( $key )
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
	 * Lists page template definitions eligible for new selection (excludes deprecated).
	 *
	 * @param string $status Optional filter (e.g. 'active'); empty = all non-deprecated.
	 * @param int    $limit
	 * @param int    $offset
	 * @return list<array<string, mixed>>
	 */
	public function list_eligible_for_new_selection( string $status = 'active', int $limit = 0, int $offset = 0 ): array {
		$list = $status !== '' ? $this->repository->list_definitions_by_status( $status, $limit, $offset ) : $this->repository->list_all_definitions( $limit, $offset );
		return array_values( array_filter( $list, array( Deprecation_Metadata::class, 'is_eligible_for_new_use' ) ) );
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

	/**
	 * Injects large-library query service for filtered, paginated directory queries (spec §55.8).
	 *
	 * @param Large_Library_Query_Service $service
	 */
	public function set_large_library_query_service( Large_Library_Query_Service $service ): void {
		$this->large_library_query_service = $service;
	}

	/**
	 * Ensures bundled form section and request page template exist in the registries (spec §53.1, Prompt 227).
	 * Idempotent: overwrites existing definitions for form_section_ndr and pt_request_form.
	 * Call from activation, first-time setup, or admin seed action. Requires the section template repository.
	 *
	 * @param Section_Template_Repository $section_repo Section template repository (for form_section_ndr).
	 * @return array{ success: bool, section_id: int, page_id: int, errors: list<string> }
	 */
	public function ensure_bundled_form_templates( Section_Template_Repository $section_repo ): array {
		return Form_Template_Seeder::run( $section_repo, $this->repository );
	}

	/**
	 * Returns whether the bundled request-form page template exists in the registry (by internal key).
	 *
	 * @return bool
	 */
	public function has_bundled_request_form_template(): bool {
		return $this->get_by_key( Form_Integration_Definitions::REQUEST_PAGE_TEMPLATE_KEY ) !== null;
	}

	/**
	 * Returns filtered, paginated page template list for directory IA. Uses Large_Library_Query_Service when set.
	 *
	 * @param array<string, mixed> $filters  status, archetype, template_category_class, template_family, preview_available, search.
	 * @param int                  $page    1-based page.
	 * @param int                  $per_page Items per page.
	 * @return Large_Library_Filter_Result
	 */
	public function list_filtered_paginated( array $filters, int $page = 1, int $per_page = 25 ): Large_Library_Filter_Result {
		if ( $this->large_library_query_service !== null ) {
			return $this->large_library_query_service->query_page_templates( $filters, $page, $per_page );
		}
		$pagination = Large_Library_Pagination::from_page_size( $page, $per_page, 0 );
		return new Large_Library_Filter_Result( array(), $pagination, array(), 0 );
	}

	private function sanitize_key( string $key ): string {
		$key = \sanitize_text_field( strtolower( $key ) );
		$key = preg_replace( '/[^a-z0-9_]/', '', $key );
		return substr( $key, 0, Page_Template_Schema::INTERNAL_KEY_MAX_LENGTH );
	}
}
