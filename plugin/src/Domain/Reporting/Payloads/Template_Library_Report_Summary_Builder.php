<?php
/**
 * Builds bounded template_library_report_summary for install, heartbeat, and error reports (spec §4.16, §46, §62.7–62.9, Prompt 214).
 *
 * Provides support-safe template-library health context: counts, version markers, appendix availability.
 * No secrets, no raw registry dumps, no preview content. Safe for transport and redaction.
 *
 * Example template_library_report_summary payload (build()):
 * [
 *   'section_template_count'   => 250,
 *   'page_template_count'      => 500,
 *   'composition_count'       => 120,
 *   'library_version_marker'  => '1',
 *   'plugin_version_marker'   => '1.0.0',
 *   'appendices_available'    => true,
 *   'compliance_summary'      => 'unknown',
 * ]
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Payloads;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use AIOPageBuilder\Infrastructure\Config\Versions;

/**
 * Produces stable template_library_report_summary for reporting payloads.
 */
final class Template_Library_Report_Summary_Builder {

	/** @var Section_Template_Repository|null */
	private ?Section_Template_Repository $section_repository;

	/** @var Page_Template_Repository|null */
	private ?Page_Template_Repository $page_repository;

	/** @var Composition_Repository|null */
	private ?Composition_Repository $composition_repository;

	/** @var bool Whether appendix generators are available (appendices can be generated). */
	private bool $appendices_available;

	/** @var callable(): string|null Optional. Returns one of ok, warning, critical, unknown. */
	private $compliance_status_provider;

	/**
	 * @param Section_Template_Repository|null $section_repository
	 * @param Page_Template_Repository|null    $page_repository
	 * @param Composition_Repository|null      $composition_repository
	 * @param bool                             $appendices_available
	 * @param callable(): string|null          $compliance_status_provider Optional. Returns ok|warning|critical|unknown.
	 */
	public function __construct(
		?Section_Template_Repository $section_repository = null,
		?Page_Template_Repository $page_repository = null,
		?Composition_Repository $composition_repository = null,
		bool $appendices_available = false,
		?callable $compliance_status_provider = null
	) {
		$this->section_repository         = $section_repository;
		$this->page_repository            = $page_repository;
		$this->composition_repository     = $composition_repository;
		$this->appendices_available       = $appendices_available;
		$this->compliance_status_provider = $compliance_status_provider;
	}

	/**
	 * Builds the template_library_report_summary payload. Safe for reporting; no secrets.
	 *
	 * @return array<string, mixed> Keys: section_template_count, page_template_count, composition_count, library_version_marker, plugin_version_marker, appendices_available, compliance_summary.
	 */
	public function build(): array {
		$counts     = $this->build_counts();
		$compliance = 'unknown';
		if ( $this->compliance_status_provider !== null ) {
			$v = ( $this->compliance_status_provider )();
			if ( is_string( $v ) && in_array( $v, array( 'ok', 'warning', 'critical', 'unknown' ), true ) ) {
				$compliance = $v;
			}
		}

		return array_merge(
			array(
				'library_version_marker' => Versions::registry_schema(),
				'plugin_version_marker'  => Versions::plugin(),
				'appendices_available'   => $this->appendices_available,
				'compliance_summary'     => $compliance,
			),
			$counts
		);
	}

	/**
	 * Counts when repositories are available. No secrets.
	 *
	 * @return array<string, int>
	 */
	private function build_counts(): array {
		$out = array(
			'section_template_count' => 0,
			'page_template_count'    => 0,
			'composition_count'      => 0,
		);
		if ( $this->section_repository !== null ) {
			$all                           = $this->section_repository->list_definitions_by_status( 'active', 10000, 0 );
			$out['section_template_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		if ( $this->page_repository !== null ) {
			$all                        = $this->page_repository->list_definitions_by_status( 'active', 10000, 0 );
			$out['page_template_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		if ( $this->composition_repository !== null ) {
			$all                      = $this->composition_repository->list_all_definitions( 10000, 0 );
			$out['composition_count'] = is_array( $all ) ? count( $all ) : 0;
		}
		return $out;
	}
}
