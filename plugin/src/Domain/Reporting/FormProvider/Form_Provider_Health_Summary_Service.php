<?php
/**
 * Builds bounded health summary for provider-backed form usage (Prompt 239, spec §0.10.11, §59.12).
 * Aggregates provider availability, section/page counts using forms, and survivability link.
 * Observational only; no secrets; for internal diagnostics and support bundles.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\FormProvider;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Pages\Form_Provider_Dependency_Validator;
use AIOPageBuilder\Domain\FormProvider\Form_Provider_Registry;
use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Availability_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Produces provider dependency health summary for dashboard and support bundle.
 */
final class Form_Provider_Health_Summary_Service {

	private const SECTION_CAP = 500;
	private const PAGE_CAP    = 500;

	/** @var Form_Provider_Registry */
	private Form_Provider_Registry $registry;

	/** @var Form_Provider_Availability_Service|null */
	private ?Form_Provider_Availability_Service $availability_service;

	/** @var Section_Template_Repository|null */
	private ?Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository|null */
	private ?Page_Template_Repository $page_repository;

	/** @var Form_Provider_Dependency_Validator|null */
	private ?Form_Provider_Dependency_Validator $dependency_validator;

	public function __construct(
		Form_Provider_Registry $registry,
		?Section_Template_Repository $section_repository = null,
		?Page_Template_Repository $page_repository = null,
		?Form_Provider_Availability_Service $availability_service = null,
		?Form_Provider_Dependency_Validator $dependency_validator = null
	) {
		$this->registry             = $registry;
		$this->section_repository   = $section_repository;
		$this->page_repository      = $page_repository;
		$this->availability_service = $availability_service;
		$this->dependency_validator = $dependency_validator;
	}

	/**
	 * Builds the full health summary for dashboard or support export (bounded, no secrets).
	 *
	 * @return array{
	 *   provider_availability: array<int, array{provider_key: string, status: string, message: string|null}>,
	 *   registered_provider_ids: array<int, string>,
	 *   section_templates_with_forms_count: int,
	 *   page_templates_using_forms_count: int,
	 *   recent_failures_summary: array<int, array{domain: string, count: int, link_label: string}>,
	 *   built_at: string
	 * }
	 */
	public function build_summary(): array {
		$provider_availability = $this->availability_service !== null
			? $this->availability_service->get_summary_for_admin()
			: array();
		$registered            = $this->registry->get_registered_provider_ids();

		$section_count = $this->count_section_templates_with_forms();
		$page_count    = $this->count_page_templates_using_forms();

		$recent_failures = array();
		if ( $provider_availability !== array() ) {
			$error_count = 0;
			foreach ( $provider_availability as $row ) {
				if ( in_array( (string) $row['status'], array( 'provider_error', 'unavailable' ), true ) ) {
					++$error_count;
				}
			}
			if ( $error_count > 0 ) {
				$recent_failures[] = array(
					'domain'     => 'form_provider',
					'count'      => $error_count,
					'link_label' => __( 'Form provider availability', 'aio-page-builder' ),
				);
			}
		}

		return array(
			'provider_availability'              => $provider_availability,
			'registered_provider_ids'            => $registered,
			'section_templates_with_forms_count' => $section_count,
			'page_templates_using_forms_count'   => $page_count,
			'recent_failures_summary'            => $recent_failures,
			'built_at'                           => gmdate( 'Y-m-d\TH:i:s\Z' ),
		);
	}

	/**
	 * Counts section templates that have category form_embed (bounded).
	 *
	 * @return int
	 */
	private function count_section_templates_with_forms(): int {
		if ( $this->section_repository === null ) {
			return 0;
		}
		$list = $this->section_repository->list_all_definitions_capped( self::SECTION_CAP );
		$n    = 0;
		foreach ( $list as $def ) {
			$category = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			if ( $category === 'form_embed' ) {
				++$n;
			}
		}
		return $n;
	}

	/**
	 * Counts page templates that use at least one form_embed section (bounded).
	 *
	 * @return int
	 */
	private function count_page_templates_using_forms(): int {
		if ( $this->dependency_validator === null || $this->page_repository === null ) {
			return 0;
		}
		$list = $this->page_repository->list_all_definitions_capped( self::PAGE_CAP );
		$n    = 0;
		foreach ( $list as $def ) {
			$key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			if ( $key !== '' && $this->dependency_validator->template_uses_form_sections( $key ) ) {
				++$n;
			}
		}
		return $n;
	}
}
