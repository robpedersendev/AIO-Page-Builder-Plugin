<?php
/**
 * Existing-page replace/rebuild via rendering pipeline and instantiation (spec §32, §32.9, §40.2, §41.2; Prompt 082).
 *
 * Resolves target page; requires pre-change snapshot when snapshot_required; rebuilds in place or
 * creates new page and archives superseded; updates provenance and ACF. No rollback or finalization.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\ACF\Assignment\Page_Field_Group_Assignment_Service;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiation_Payload_Builder;
use AIOPageBuilder\Domain\Rendering\Page\Page_Instantiator;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Orchestrates existing-page update: resolve target, build assembly, update or replace, ACF assign.
 */
final class Replace_Page_Job_Service implements Replace_Page_Job_Service_Interface {

	private const POST_TYPE = 'page';

	/** Actions that perform in-place rebuild (no new page). */
	private const REBUILD_ACTIONS = array( 'rebuild_from_template' );

	/** Actions that create a new page and archive the existing one. */
	private const REPLACE_ACTIONS = array( 'replace_with_new_page', 'merge_and_archive' );

	/** @var Page_Template_Repository */
	private $page_template_repository;

	/** @var Section_Template_Repository */
	private $section_template_repository;

	/** @var Section_Render_Context_Builder */
	private $context_builder;

	/** @var Section_Renderer_Base */
	private $section_renderer;

	/** @var Native_Block_Assembly_Pipeline */
	private $assembly_pipeline;

	/** @var Page_Instantiation_Payload_Builder */
	private $payload_builder;

	/** @var Page_Instantiator */
	private $instantiator;

	/** @var Page_Field_Group_Assignment_Service */
	private $assignment_service;

	public function __construct(
		Page_Template_Repository $page_template_repository,
		Section_Template_Repository $section_template_repository,
		Section_Render_Context_Builder $context_builder,
		Section_Renderer_Base $section_renderer,
		Native_Block_Assembly_Pipeline $assembly_pipeline,
		Page_Instantiation_Payload_Builder $payload_builder,
		Page_Instantiator $instantiator,
		Page_Field_Group_Assignment_Service $assignment_service
	) {
		$this->page_template_repository   = $page_template_repository;
		$this->section_template_repository = $section_template_repository;
		$this->context_builder            = $context_builder;
		$this->section_renderer           = $section_renderer;
		$this->assembly_pipeline          = $assembly_pipeline;
		$this->payload_builder            = $payload_builder;
		$this->instantiator               = $instantiator;
		$this->assignment_service         = $assignment_service;
	}

	/**
	 * Runs the existing-page replace/rebuild flow. Snapshot must be present when snapshot_required.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference, snapshot_ref when required).
	 * @return Replace_Page_Result
	 */
	public function run( array $envelope ): Replace_Page_Result {
		$target   = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();
		$snapshot_ref = isset( $envelope['snapshot_ref'] ) && is_string( $envelope['snapshot_ref'] ) ? trim( $envelope['snapshot_ref'] ) : '';
		$snapshot_required = ! empty( $envelope['snapshot_required'] );

		if ( $snapshot_required && $snapshot_ref === '' ) {
			return Replace_Page_Result::failure(
				__( 'Pre-change snapshot required but not provided.', 'aio-page-builder' ),
				array( 'snapshot_required' ),
				''
			);
		}

		$target_post_id = $this->resolve_target_page( $target );
		if ( $target_post_id <= 0 ) {
			return Replace_Page_Result::failure(
				__( 'Target page could not be resolved.', 'aio-page-builder' ),
				array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ),
				$snapshot_ref
			);
		}

		$template_key = $this->resolve_template_key( $target );
		if ( $template_key === '' ) {
			return Replace_Page_Result::failure(
				__( 'Missing or invalid template reference for replace.', 'aio-page-builder' ),
				array( 'invalid_template' ),
				$snapshot_ref
			);
		}

		$action = isset( $target['action'] ) && is_string( $target['action'] ) ? $target['action'] : 'rebuild_from_template';
		$is_replace = in_array( $action, self::REPLACE_ACTIONS, true );

		if ( $is_replace ) {
			return $this->run_replace_flow( $target, $target_post_id, $template_key, $snapshot_ref );
		}

		return $this->run_rebuild_flow( $target, $target_post_id, $template_key, $snapshot_ref );
	}

	/**
	 * Rebuild in place: assembly → update payload → update_page → ACF.
	 *
	 * @param array<string, mixed> $target
	 * @param int                  $target_post_id
	 * @param string               $template_key
	 * @param string               $snapshot_ref
	 * @return Replace_Page_Result
	 */
	private function run_rebuild_flow( array $target, int $target_post_id, string $template_key, string $snapshot_ref ): Replace_Page_Result {
		$template_def = $this->page_template_repository->get_definition_by_key( $template_key );
		if ( $template_def === null || empty( $template_def ) ) {
			return Replace_Page_Result::failure( __( 'Page template not found.', 'aio-page-builder' ), array( 'template_not_found' ), $snapshot_ref );
		}

		$assembly = $this->build_assembly_from_template( $template_key, $template_def );
		if ( $assembly === null || ! $assembly->is_valid() ) {
			return Replace_Page_Result::failure(
				__( 'Failed to build page content from template.', 'aio-page-builder' ),
				$assembly ? $assembly->get_errors() : array( 'assembly_failed' ),
				$snapshot_ref
			);
		}

		$title = isset( $target['target_page_title'] ) && is_string( $target['target_page_title'] ) ? trim( $target['target_page_title'] ) : '';
		$slug  = isset( $target['target_slug'] ) && is_string( $target['target_slug'] ) ? trim( $target['target_slug'] ) : '';
		$overrides = array();
		if ( $title !== '' ) {
			$overrides['page_title'] = $title;
		}
		if ( $slug !== '' ) {
			$overrides['page_slug_candidate'] = $slug;
		}

		$payload = $this->payload_builder->build_update_payload( $assembly, $target_post_id, $overrides );
		$result  = $this->instantiator->update_page( $payload );
		if ( ! $result->is_success() ) {
			return Replace_Page_Result::failure(
				__( 'Page update failed.', 'aio-page-builder' ),
				$result->get_errors(),
				$snapshot_ref
			);
		}

		$assign_result = $this->assignment_service->assign_from_template( $target_post_id, $template_key, true );
		$assignment_count = isset( $assign_result['assigned'] ) && is_numeric( $assign_result['assigned'] ) ? (int) $assign_result['assigned'] : 0;

		return Replace_Page_Result::success( $target_post_id, $template_key, $assignment_count, $snapshot_ref, 0 );
	}

	/**
	 * Replace flow: create new page from template, set existing to private, preserve traceability.
	 *
	 * @param array<string, mixed> $target
	 * @param int                  $existing_post_id Page to supersede.
	 * @param string               $template_key
	 * @param string               $snapshot_ref
	 * @return Replace_Page_Result
	 */
	private function run_replace_flow( array $target, int $existing_post_id, string $template_key, string $snapshot_ref ): Replace_Page_Result {
		$template_def = $this->page_template_repository->get_definition_by_key( $template_key );
		if ( $template_def === null || empty( $template_def ) ) {
			return Replace_Page_Result::failure( __( 'Page template not found.', 'aio-page-builder' ), array( 'template_not_found' ), $snapshot_ref );
		}

		$assembly = $this->build_assembly_from_template( $template_key, $template_def );
		if ( $assembly === null || ! $assembly->is_valid() ) {
			return Replace_Page_Result::failure(
				__( 'Failed to build page content from template.', 'aio-page-builder' ),
				$assembly ? $assembly->get_errors() : array( 'assembly_failed' ),
				$snapshot_ref
			);
		}

		$title = isset( $target['target_page_title'] ) && is_string( $target['target_page_title'] ) ? trim( $target['target_page_title'] ) : '';
		$slug  = isset( $target['target_slug'] ) && is_string( $target['target_slug'] ) ? trim( $target['target_slug'] ) : '';
		if ( $title === '' ) {
			$post = \get_post( $existing_post_id );
			$title = $post instanceof \WP_Post ? $post->post_title : __( 'Replacement Page', 'aio-page-builder' );
		}
		if ( $slug === '' ) {
			$slug = \sanitize_title( $title );
		}

		$overrides = array(
			'page_slug_candidate'   => $slug,
			'post_status_candidate' => 'draft',
		);
		$payload = $this->payload_builder->build_create_payload( $assembly, $title, $overrides );
		$create_result = $this->instantiator->create_page( $payload );
		if ( ! $create_result->is_success() ) {
			return Replace_Page_Result::failure(
				__( 'Replacement page creation failed.', 'aio-page-builder' ),
				$create_result->get_errors(),
				$snapshot_ref
			);
		}

		$new_post_id = $create_result->get_post_id();

		// * Archive superseded page: set to private (no hard-delete; spec §32, traceability).
		\wp_update_post( array( 'ID' => $existing_post_id, 'post_status' => 'private' ), true );

		$assign_result = $this->assignment_service->assign_from_template( $new_post_id, $template_key, true );
		$assignment_count = isset( $assign_result['assigned'] ) && is_numeric( $assign_result['assigned'] ) ? (int) $assign_result['assigned'] : 0;

		return Replace_Page_Result::success( $new_post_id, $template_key, $assignment_count, $snapshot_ref, $existing_post_id );
	}

	/**
	 * Resolves target page ID from target_reference (target_post_id or current_page_url/slug).
	 *
	 * @param array<string, mixed> $target
	 * @return int 0 if unresolved.
	 */
	private function resolve_target_page( array $target ): int {
		if ( isset( $target['target_post_id'] ) && is_numeric( $target['target_post_id'] ) ) {
			$id = (int) $target['target_post_id'];
			if ( $id > 0 ) {
				$post = \get_post( $id );
				if ( $post instanceof \WP_Post && $post->post_type === self::POST_TYPE ) {
					return $id;
				}
			}
		}
		if ( isset( $target['page_ref'] ) && is_array( $target['page_ref'] ) && isset( $target['page_ref']['value'] ) ) {
			$id = (int) $target['page_ref']['value'];
			if ( $id > 0 ) {
				$post = \get_post( $id );
				if ( $post instanceof \WP_Post && $post->post_type === self::POST_TYPE ) {
					return $id;
				}
			}
		}

		$url  = isset( $target['current_page_url'] ) && is_string( $target['current_page_url'] ) ? trim( $target['current_page_url'] ) : '';
		$slug = isset( $target['current_page_slug'] ) && is_string( $target['current_page_slug'] ) ? trim( $target['current_page_slug'] ) : '';
		if ( $slug === '' && $url !== '' ) {
			$slug = $this->path_from_url( $url );
		}
		if ( $slug === '' ) {
			return 0;
		}

		$page = \get_page_by_path( $slug, \OBJECT, self::POST_TYPE );
		if ( $page instanceof \WP_Post ) {
			return (int) $page->ID;
		}
		return 0;
	}

	/**
	 * Extracts path (slug or path) from URL for get_page_by_path.
	 *
	 * @param string $url
	 * @return string
	 */
	private function path_from_url( string $url ): string {
		$parsed = \wp_parse_url( $url );
		$path   = isset( $parsed['path'] ) && is_string( $parsed['path'] ) ? $parsed['path'] : '';
		$path   = \trim( $path, '/' );
		if ( $path === '' ) {
			return '';
		}
		return $path;
	}

	private function resolve_template_key( array $target ): string {
		if ( isset( $target['template_key'] ) && is_string( $target['template_key'] ) && trim( $target['template_key'] ) !== '' ) {
			return trim( $target['template_key'] );
		}
		$ref = $target['template_ref'] ?? null;
		if ( is_array( $ref ) && isset( $ref['type'] ) && (string) $ref['type'] === 'internal_key' && isset( $ref['value'] ) && is_string( $ref['value'] ) ) {
			return trim( $ref['value'] );
		}
		return '';
	}

	/**
	 * Builds Page_Block_Assembly_Result from template (same as Create_Page_Job_Service).
	 *
	 * @param string               $template_key
	 * @param array<string, mixed> $template_def
	 * @return Page_Block_Assembly_Result|null
	 */
	private function build_assembly_from_template( string $template_key, array $template_def ): ?Page_Block_Assembly_Result {
		$ordered = $template_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null;
		if ( ! is_array( $ordered ) || empty( $ordered ) ) {
			return null;
		}

		$section_results = array();
		$position = 0;
		foreach ( $ordered as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$section_key = isset( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ) && is_string( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] )
				? $item[ Page_Template_Schema::SECTION_ITEM_KEY ]
				: '';
			if ( $section_key === '' ) {
				continue;
			}
			$pos = isset( $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] ) && is_numeric( $item[ Page_Template_Schema::SECTION_ITEM_POSITION ] )
				? (int) $item[ Page_Template_Schema::SECTION_ITEM_POSITION ]
				: $position;

			$section_def = $this->section_template_repository->get_definition_by_key( $section_key );
			if ( $section_def === null || empty( $section_def ) ) {
				continue;
			}

			$built = $this->context_builder->build( $section_def, array(), $pos, null );
			if ( $built['context'] === null ) {
				continue;
			}
			$section_results[] = $this->section_renderer->render( $built['context'] );
			++$position;
		}

		if ( empty( $section_results ) ) {
			return null;
		}

		return $this->assembly_pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			$template_key,
			$section_results
		);
	}
}
