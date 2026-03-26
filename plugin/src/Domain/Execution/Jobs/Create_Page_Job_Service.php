<?php
/**
 * New-page creation via rendering pipeline and page instantiation (spec §33.5, §17.7, §19; Prompt 081).
 *
 * Validates target metadata and hierarchy; builds assembly from template; creates page;
 * applies parent when valid; triggers ACF assignment. Used by Create_Page_Handler only.
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
use AIOPageBuilder\Domain\Rendering\Section\Section_Guidance_Field_Value_Applier;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Orchestrates template → assembly → payload → create → ACF assignment.
 */
final class Create_Page_Job_Service implements Create_Page_Job_Service_Interface {

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
		$this->page_template_repository    = $page_template_repository;
		$this->section_template_repository = $section_template_repository;
		$this->context_builder             = $context_builder;
		$this->section_renderer            = $section_renderer;
		$this->assembly_pipeline           = $assembly_pipeline;
		$this->payload_builder             = $payload_builder;
		$this->instantiator                = $instantiator;
		$this->assignment_service          = $assignment_service;
	}

	/**
	 * Runs the new-page creation flow. Validates target, builds assembly, creates page, assigns ACF.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference, etc.).
	 * @return Create_Page_Result
	 */
	public function run( array $envelope ): Create_Page_Result {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();

		$template_key = $this->resolve_template_key( $target );
		if ( $template_key === '' ) {
			return Create_Page_Result::failure( __( 'Missing or invalid template reference.', 'aio-page-builder' ), array( 'invalid_template' ) );
		}

		$title = isset( $target['proposed_page_title'] ) && is_string( $target['proposed_page_title'] ) ? trim( $target['proposed_page_title'] ) : '';
		$slug  = isset( $target['proposed_slug'] ) && is_string( $target['proposed_slug'] ) ? trim( $target['proposed_slug'] ) : '';
		if ( $title === '' ) {
			return Create_Page_Result::failure( __( 'Missing or empty proposed page title.', 'aio-page-builder' ), array( 'invalid_target' ) );
		}

		$template_def = $this->page_template_repository->get_definition_by_key( $template_key );
		if ( $template_def === null || empty( $template_def ) ) {
			return Create_Page_Result::failure( __( 'Page template not found.', 'aio-page-builder' ), array( 'template_not_found' ) );
		}

		$parent_post_id = $this->resolve_and_validate_parent( $target );
		if ( $parent_post_id === false ) {
			return Create_Page_Result::failure( __( 'Invalid or missing parent page.', 'aio-page-builder' ), array( 'invalid_parent' ) );
		}

		$guidance_items = Section_Guidance_Field_Value_Applier::parse_guidance_items( $target );
		$assembly       = $this->build_assembly_from_template( $template_key, $template_def, $guidance_items );
		if ( $assembly === null ) {
			return Create_Page_Result::failure( __( 'Failed to build page content from template.', 'aio-page-builder' ), array( 'assembly_failed' ) );
		}
		if ( ! $assembly->is_valid() ) {
			return Create_Page_Result::failure(
				__( 'Template assembly has errors.', 'aio-page-builder' ),
				$assembly->get_errors()
			);
		}

		$overrides = array(
			'page_slug_candidate'   => $slug !== '' ? $slug : \sanitize_title( $title ),
			'post_status_candidate' => 'draft',
		);
		$payload   = $this->payload_builder->build_create_payload( $assembly, $title, $overrides );

		$instantiation = $this->instantiator->create_page( $payload );
		if ( ! $instantiation->is_success() ) {
			return Create_Page_Result::failure(
				__( 'Page creation failed.', 'aio-page-builder' ),
				$instantiation->get_errors()
			);
		}

		$post_id = $instantiation->get_post_id();
		if ( $parent_post_id !== null && $parent_post_id > 0 ) {
			\wp_update_post(
				array(
					'ID'          => $post_id,
					'post_parent' => $parent_post_id,
				),
				true
			);
		}

		$assign_result    = $this->assignment_service->assign_from_template( $post_id, $template_key, true );
		$assignment_count = isset( $assign_result['assigned'] ) && is_numeric( $assign_result['assigned'] ) ? (int) $assign_result['assigned'] : 0;

		return Create_Page_Result::success( $post_id, $template_key, $assignment_count, '' );
	}

	/**
	 * Resolves template key from target_reference (template_key or template_ref).
	 *
	 * @param array<string, mixed> $target
	 * @return string
	 */
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
	 * Validates parent reference if present. Returns parent post ID (int), null if no parent, false if invalid.
	 *
	 * @param array<string, mixed> $target
	 * @return int|null|false
	 */
	private function resolve_and_validate_parent( array $target ) {
		$parent_id = null;
		if ( isset( $target['parent_post_id'] ) && is_numeric( $target['parent_post_id'] ) ) {
			$parent_id = (int) $target['parent_post_id'];
		} elseif ( isset( $target['parent_ref'] ) && is_array( $target['parent_ref'] ) && isset( $target['parent_ref']['value'] ) ) {
			$parent_id = (int) $target['parent_ref']['value'];
		}
		if ( $parent_id === null || $parent_id <= 0 ) {
			return null;
		}
		$post = \get_post( $parent_id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'page' ) {
			return false;
		}
		return $parent_id;
	}

	/**
	 * Builds Page_Block_Assembly_Result from template definition (ordered sections → render → assemble).
	 *
	 * @param string                      $template_key
	 * @param array<string, mixed>        $template_def
	 * @param list<array<string, string>> $guidance_items
	 * @return Page_Block_Assembly_Result|null
	 */
	private function build_assembly_from_template( string $template_key, array $template_def, array $guidance_items = array() ): ?Page_Block_Assembly_Result {
		$ordered = $template_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? null;
		if ( ! is_array( $ordered ) || empty( $ordered ) ) {
			return null;
		}

		$section_results = array();
		$position        = 0;
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

			$row          = Section_Guidance_Field_Value_Applier::find_guidance_for_section( $guidance_items, $section_key );
			$field_values = Section_Guidance_Field_Value_Applier::field_values_for_section( $section_def, $row );
			$built        = $this->context_builder->build( $section_def, $field_values, $pos, null );
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
