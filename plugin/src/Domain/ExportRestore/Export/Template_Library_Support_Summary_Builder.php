<?php
/**
 * Builds support-safe template library summary for support bundles (spec §52.1, §48.10, §45.9, §59.15; Prompt 198).
 *
 * Provides health summaries, validation/compliance failures, CTA violations, preview issues, inventory and appendix sync,
 * and version/deprecation counts. No raw registry dumps or secrets; redaction applied to messages.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ExportRestore\Export;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Integrations\FormProviders\Form_Provider_Availability_Service;
use AIOPageBuilder\Domain\Registries\Docs\Page_Template_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Reporting\FormProvider\Form_Provider_Health_Summary_Service;
use AIOPageBuilder\Domain\Registries\Docs\Section_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\QA\Template_Library_Compliance_Result;
use AIOPageBuilder\Domain\Registries\QA\Template_Library_Compliance_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Versioning\Template_Deprecation_Service;
use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;

/**
 * Builds stable template_library_support_summary payload for support packages and diagnostics.
 */
final class Template_Library_Support_Summary_Builder {

	private const DEPRECATED_COUNT_CAP = 500;

	/** @var Template_Library_Compliance_Service|null */
	private ?Template_Library_Compliance_Service $compliance_service;

	/** @var Section_Inventory_Appendix_Generator|null */
	private ?Section_Inventory_Appendix_Generator $section_appendix;

	/** @var Page_Template_Inventory_Appendix_Generator|null */
	private ?Page_Template_Inventory_Appendix_Generator $page_appendix;

	/** @var Template_Deprecation_Service|null */
	private ?Template_Deprecation_Service $deprecation_service;

	/** @var Section_Template_Repository|null */
	private ?Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository|null */
	private ?Page_Template_Repository $page_repository;

	/** @var Reporting_Redaction_Service|null */
	private ?Reporting_Redaction_Service $redaction;

	/** @var Form_Provider_Availability_Service|null */
	private ?Form_Provider_Availability_Service $form_provider_availability;

	/** @var Form_Provider_Health_Summary_Service|null */
	private ?Form_Provider_Health_Summary_Service $form_provider_health_summary_service;

	public function __construct(
		?Template_Library_Compliance_Service $compliance_service = null,
		?Section_Inventory_Appendix_Generator $section_appendix = null,
		?Page_Template_Inventory_Appendix_Generator $page_appendix = null,
		?Template_Deprecation_Service $deprecation_service = null,
		?Section_Template_Repository $section_repository = null,
		?Page_Template_Repository $page_repository = null,
		?Reporting_Redaction_Service $redaction = null,
		?Form_Provider_Availability_Service $form_provider_availability = null,
		?Form_Provider_Health_Summary_Service $form_provider_health_summary_service = null
	) {
		$this->compliance_service                   = $compliance_service;
		$this->section_appendix                     = $section_appendix;
		$this->page_appendix                        = $page_appendix;
		$this->deprecation_service                  = $deprecation_service;
		$this->section_repository                   = $section_repository;
		$this->page_repository                      = $page_repository;
		$this->redaction                            = $redaction;
		$this->form_provider_availability           = $form_provider_availability;
		$this->form_provider_health_summary_service = $form_provider_health_summary_service;
	}

	/**
	 * Builds the template_library_support_summary payload. Safe for support bundle inclusion; no secrets.
	 *
	 * @return array<string, mixed> Stable payload: health, validation_failures, cta_violations, preview_issues, inventory, appendix_sync, version_summary.
	 */
	public function build(): array {
		$health          = $this->build_health();
		$inventory       = $this->build_inventory();
		$appendix_sync   = $this->build_appendix_sync();
		$version_summary = $this->build_version_summary();

		$validation_failures = array();
		$cta_violations      = array();
		$preview_issues      = array(
			'sections_missing_preview' => array(),
			'pages_missing_one_pager'  => array(),
		);

		if ( $health !== array() ) {
			$validation_failures = $this->extract_validation_failures( $health );
			$cta_violations      = $health['cta_rule_violations'] ?? array();
			$preview_issues      = $this->extract_preview_issues( $health );
		}

		$payload = array(
			'health'              => $health,
			'validation_failures' => $validation_failures,
			'cta_violations'      => $this->redact_violation_messages( $cta_violations ),
			'preview_issues'      => $preview_issues,
			'inventory'           => $inventory,
			'appendix_sync'       => $appendix_sync,
			'version_summary'     => $version_summary,
		);
		if ( $this->form_provider_availability !== null ) {
			$payload['form_provider_availability'] = $this->build_form_provider_availability();
		}
		if ( $this->form_provider_health_summary_service !== null ) {
			$payload['form_provider_health_summary'] = $this->form_provider_health_summary_service->build_summary();
		}
		return $payload;
	}

	/**
	 * Bounded provider availability summary for diagnostics/support (Prompt 237). No secrets.
	 *
	 * @return list<array{provider_key: string, status: string, message: string|null}>
	 */
	private function build_form_provider_availability(): array {
		return $this->form_provider_availability->get_summary_for_admin();
	}

	/**
	 * Health block: compliance run result in support-safe shape (counts, passed, structured issues).
	 *
	 * @return array<string, mixed>
	 */
	private function build_health(): array {
		if ( $this->compliance_service === null ) {
			return array();
		}
		try {
			$result = $this->compliance_service->run();
		} catch ( \Throwable $e ) {
			return array(
				'error'   => 'compliance_run_failed',
				'message' => $this->redact_message( 'Compliance run failed.' ),
			);
		}
		if ( ! $result instanceof Template_Library_Compliance_Result ) {
			return array();
		}

		$count_summary    = $result->get_count_summary();
		$category         = $result->get_category_coverage_summary();
		$cta_violations   = $result->get_cta_rule_violations();
		$preview          = $result->get_preview_readiness();
		$metadata         = $result->get_metadata_checks();
		$export_viability = $result->get_export_viability();

		return array(
			'passed'               => $result->is_passed(),
			'count_summary'        => array(
				'section_total'  => $count_summary['section_total'] ?? 0,
				'page_total'     => $count_summary['page_total'] ?? 0,
				'section_target' => $count_summary['section_target'] ?? 250,
				'page_target'    => $count_summary['page_target'] ?? 500,
			),
			'max_share_violations' => $category['max_share_violations'] ?? array(),
			'cta_rule_violations'  => $cta_violations,
			'preview_readiness'    => array(
				'sections_missing_preview_count' => count( $preview['sections_missing_preview'] ?? array() ),
				'sections_missing_preview'       => array_slice( $preview['sections_missing_preview'] ?? array(), 0, 50 ),
				'pages_missing_one_pager_count'  => count( $preview['pages_missing_one_pager'] ?? array() ),
				'pages_missing_one_pager'        => array_slice( $preview['pages_missing_one_pager'] ?? array(), 0, 50 ),
			),
			'metadata_checks'      => array(
				'sections_missing_accessibility_count' => count( $metadata['sections_missing_accessibility'] ?? array() ),
				'sections_invalid_animation_count'     => count( $metadata['sections_invalid_animation'] ?? array() ),
			),
			'export_viability'     => array(
				'viable'       => $export_viability['viable'] ?? false,
				'errors_count' => count( $export_viability['errors'] ?? array() ),
				'errors'       => $this->redact_export_errors( $export_viability['errors'] ?? array() ),
			),
		);
	}

	/**
	 * @param array<string, mixed> $health
	 * @return list<array{template_key: string, code: string, message: string}>
	 */
	private function extract_validation_failures( array $health ): array {
		$out = array();
		$cta = $health['cta_rule_violations'] ?? array();
		foreach ( $cta as $v ) {
			if ( is_array( $v ) && isset( $v['template_key'], $v['code'], $v['message'] ) ) {
				$out[] = array(
					'template_key' => (string) $v['template_key'],
					'code'         => (string) $v['code'],
					'message'      => $this->redact_message( (string) $v['message'] ),
				);
			}
		}
		$export_errors = $health['export_viability']['errors'] ?? array();
		if ( is_array( $export_errors ) ) {
			foreach ( array_slice( $export_errors, 0, 20 ) as $err ) {
				$out[] = array(
					'template_key' => '',
					'code'         => 'export_viability',
					'message'      => is_string( $err ) ? $err : (string) \wp_json_encode( $err ),
				);
			}
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $health
	 * @return array{sections_missing_preview: list<string>, pages_missing_one_pager: list<string>}
	 */
	private function extract_preview_issues( array $health ): array {
		$preview = $health['preview_readiness'] ?? array();
		return array(
			'sections_missing_preview' => array_slice( $preview['sections_missing_preview'] ?? array(), 0, 50 ),
			'pages_missing_one_pager'  => array_slice( $preview['pages_missing_one_pager'] ?? array(), 0, 50 ),
		);
	}

	/**
	 * @param list<array{template_key: string, code: string, message: string}> $violations
	 * @return list<array{template_key: string, code: string, message: string}>
	 */
	private function redact_violation_messages( array $violations ): array {
		$out = array();
		foreach ( $violations as $v ) {
			if ( ! is_array( $v ) ) {
				continue;
			}
			$out[] = array(
				'template_key' => (string) ( $v['template_key'] ?? '' ),
				'code'         => (string) ( $v['code'] ?? '' ),
				'message'      => $this->redact_message( (string) ( $v['message'] ?? '' ) ),
			);
		}
		return $out;
	}

	private function redact_message( string $message ): string {
		if ( $this->redaction !== null ) {
			return $this->redaction->redact_message( $message );
		}
		return $message;
	}

	/**
	 * @param list<string> $errors
	 * @return list<string>
	 */
	private function redact_export_errors( array $errors ): array {
		$out = array();
		foreach ( array_slice( $errors, 0, 20 ) as $err ) {
			$out[] = $this->redact_message( is_string( $err ) ? $err : (string) \wp_json_encode( $err ) );
		}
		return $out;
	}

	/**
	 * Inventory counts from appendix generators when available.
	 *
	 * @return array{section_total: int, page_total: int}
	 */
	private function build_inventory(): array {
		$section_total = 0;
		$page_total    = 0;
		if ( $this->section_appendix !== null ) {
			$res           = $this->section_appendix->build_result();
			$section_total = $res['total'] ?? 0;
		}
		if ( $this->page_appendix !== null ) {
			$res        = $this->page_appendix->build_result();
			$page_total = $res['total'] ?? 0;
		}
		return array(
			'section_total' => $section_total,
			'page_total'    => $page_total,
		);
	}

	/**
	 * Appendix sync: compare inventory totals with compliance count_summary when both available.
	 *
	 * @return array{in_sync: bool, section_match: bool, page_match: bool, note: string}
	 */
	private function build_appendix_sync(): array {
		$inventory     = $this->build_inventory();
		$health        = $this->build_health();
		$comp_section  = (int) ( $health['count_summary']['section_total'] ?? 0 );
		$comp_page     = (int) ( $health['count_summary']['page_total'] ?? 0 );
		$section_match = $comp_section === 0 && $inventory['section_total'] === 0 || $comp_section > 0 && $inventory['section_total'] === $comp_section;
		$page_match    = $comp_page === 0 && $inventory['page_total'] === 0 || $comp_page > 0 && $inventory['page_total'] === $comp_page;
		$in_sync       = $section_match && $page_match;
		$note          = $in_sync ? 'Appendix totals match compliance counts.' : 'Appendix and compliance counts differ; re-run appendix generation or compliance.';
		return array(
			'in_sync'       => $in_sync,
			'section_match' => $section_match,
			'page_match'    => $page_match,
			'note'          => $note,
		);
	}

	/**
	 * Version/deprecation counts (deprecated_sections_count, deprecated_pages_count) from repositories.
	 *
	 * @return array{deprecated_sections_count: int, deprecated_pages_count: int}
	 */
	private function build_version_summary(): array {
		$dep_sections = 0;
		$dep_pages    = 0;
		if ( $this->section_repository !== null && method_exists( $this->section_repository, 'list_all_definitions_capped' ) ) {
			$list = $this->section_repository->list_all_definitions_capped( self::DEPRECATED_COUNT_CAP );
			foreach ( $list as $def ) {
				if ( ( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ) ) === 'deprecated' ) {
					++$dep_sections;
				}
			}
		}
		if ( $this->page_repository !== null && method_exists( $this->page_repository, 'list_all_definitions_capped' ) ) {
			$list = $this->page_repository->list_all_definitions_capped( self::DEPRECATED_COUNT_CAP );
			foreach ( $list as $def ) {
				if ( ( (string) ( $def[ Page_Template_Schema::FIELD_STATUS ] ?? '' ) ) === 'deprecated' ) {
					++$dep_pages;
				}
			}
		}
		return array(
			'deprecated_sections_count' => $dep_sections,
			'deprecated_pages_count'    => $dep_pages,
		);
	}
}
