<?php
/**
 * Template-driven new-page build with hierarchy, one-pager metadata, and traceable result (spec §33.5, §17.7, §33.9, §42; Prompt 194).
 *
 * Wraps Create_Page_Job_Service; enriches result with template_family, one-pager linkage,
 * hierarchy applied, section count, and per-item template_build_execution_result for logging and rollback input.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Pages;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Result;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;

/**
 * Runs new-page build via job service and produces stable template_build_execution_result payload.
 */
final class Template_Page_Build_Service implements Create_Page_Job_Service_Interface {

	/** @var Create_Page_Job_Service_Interface */
	private $job_service;

	/** @var Page_Template_Repository */
	private $page_template_repository;

	/** @var Form_Provider_Dependency_Validator|null */
	private $form_provider_dependency_validator;

	public function __construct(
		Create_Page_Job_Service_Interface $job_service,
		Page_Template_Repository $page_template_repository,
		?Form_Provider_Dependency_Validator $form_provider_dependency_validator = null
	) {
		$this->job_service                        = $job_service;
		$this->page_template_repository           = $page_template_repository;
		$this->form_provider_dependency_validator = $form_provider_dependency_validator;
	}

	/**
	 * Runs new-page creation and enriches result with template_build_execution_result for traceability.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope.
	 * @return Create_Page_Result Result with artifacts containing template_build_execution_result.
	 */
	public function run( array $envelope ): Create_Page_Result {
		$target       = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();
		$template_key = $this->resolve_template_key_from_target( $target );

		if ( $template_key !== '' && $this->form_provider_dependency_validator !== null ) {
			$validation = $this->form_provider_dependency_validator->validate_for_template( $template_key );
			if ( ! $validation['valid'] ) {
				$message = isset( $validation['errors'][0] ) ? $validation['errors'][0] : __( 'Form provider dependency check failed.', 'aio-page-builder' );
				return Create_Page_Result::failure( $message, $validation['errors'] );
			}
		}

		$result         = $this->job_service->run( $envelope );
		$template_key   = $result->get_artifacts()['template_key'] ?? $template_key;
		$parent_post_id = $this->resolve_parent_from_target( $target );

		if ( $result->is_success() ) {
			$template_def        = $this->page_template_repository->get_definition_by_key( $template_key );
			$family              = '';
			$category_class      = '';
			$one_pager_available = false;
			$one_pager_metadata  = array();
			$section_count       = 0;
			$warnings            = array();

			if ( $template_def !== null && ! empty( $template_def ) ) {
				$family         = (string) ( $template_def['template_family'] ?? '' );
				$category_class = (string) ( $template_def['template_category_class'] ?? '' );
				$ordered        = $template_def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
				$section_count  = is_array( $ordered ) ? count( $ordered ) : 0;
				$one_pager      = $template_def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? null;
				if ( is_array( $one_pager ) && ! empty( $one_pager ) ) {
					$one_pager_available = true;
					$one_pager_metadata  = $one_pager;
				}
			}

			$assignment_count  = isset( $result->get_artifacts()['assignment_count'] ) && is_numeric( $result->get_artifacts()['assignment_count'] )
				? (int) $result->get_artifacts()['assignment_count']
				: 0;
			$hierarchy_applied = $parent_post_id > 0;

			$build_result = Template_Page_Build_Result::success(
				$result->get_post_id(),
				$template_key,
				$family,
				$category_class,
				$hierarchy_applied,
				$parent_post_id,
				$one_pager_available,
				$one_pager_metadata,
				$section_count,
				$assignment_count,
				$warnings,
				$result->get_log_ref()
			);
		} else {
			$build_result = Template_Page_Build_Result::failure(
				$result->get_message(),
				$result->get_errors(),
				$template_key,
				$result->get_log_ref()
			);
		}

		$artifacts = array_merge( $result->get_artifacts(), array( 'template_build_execution_result' => $build_result->to_array() ) );
		return new Create_Page_Result(
			$result->is_success(),
			$result->get_post_id(),
			$result->get_message(),
			$result->get_errors(),
			$artifacts,
			$result->get_log_ref()
		);
	}

	/**
	 * Resolves template key from target_reference when not present in result artifacts.
	 *
	 * @param array<string, mixed> $target
	 * @return string
	 */
	private function resolve_template_key_from_target( array $target ): string {
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
	 * Resolves parent post ID from target for hierarchy_applied and parent_post_id in result.
	 *
	 * @param array<string, mixed> $target
	 * @return int
	 */
	private function resolve_parent_from_target( array $target ): int {
		$parent_id = 0;
		if ( isset( $target['parent_post_id'] ) && is_numeric( $target['parent_post_id'] ) ) {
			$parent_id = (int) $target['parent_post_id'];
		} elseif ( isset( $target['parent_ref'] ) && is_array( $target['parent_ref'] ) && isset( $target['parent_ref']['value'] ) ) {
			$parent_id = (int) $target['parent_ref']['value'];
		}
		return $parent_id > 0 ? $parent_id : 0;
	}
}
