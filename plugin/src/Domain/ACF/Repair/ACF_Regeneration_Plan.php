<?php
/**
 * Dry-run or executable plan for ACF field group and page-assignment regeneration (spec §20, §20.13–20.15; Prompt 222).
 * Built by ACF_Regeneration_Service::build_plan(). Immutable.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Repair;

defined( 'ABSPATH' ) || exit;

/**
 * Plan payload for regeneration/repair. Supports dry-run analysis and selective scope.
 *
 * Example dry-run plan payload (to_array()):
 * {
 *   "dry_run": true,
 *   "scope": "full",
 *   "section_family_key": null,
 *   "page_template_family_key": null,
 *   "include_page_assignments": true,
 *   "field_group_mismatches": [
 *     { "section_key": "st01_hero", "group_key": "group_aio_st01_hero", "status": "missing" }
 *   ],
 *   "page_assignment_repair_candidates": [
 *     { "page_id": 42, "type": "page_template", "key": "pt_landing" }
 *   ],
 *   "refused_cleanup": [ "Destructive cleanup not supported; only regeneration from registry (spec §20.15)." ],
 *   "missing_count": 1,
 *   "version_stale_count": 0,
 *   "ok_count": 0,
 *   "candidate_count": 1
 * }
 */
final class ACF_Regeneration_Plan {

	public const SCOPE_FULL               = 'full';
	public const SCOPE_SECTION_FAMILY     = 'section_family';
	public const SCOPE_PAGE_TEMPLATE_FAMILY = 'page_template_family';

	public const MISMATCH_STATUS_OK             = 'ok';
	public const MISMATCH_STATUS_MISSING        = 'missing';
	public const MISMATCH_STATUS_VERSION_STALE  = 'version_stale';

	/** @var bool True when plan is for analysis only (no mutation). */
	private bool $dry_run;

	/** @var string One of SCOPE_* */
	private string $scope;

	/** @var string|null When scope is section_family, the variation_family_key. */
	private ?string $section_family_key;

	/** @var string|null When scope is page_template_family, the template_family. */
	private ?string $page_template_family_key;

	/** @var bool Whether to include page-assignment repair in execution. */
	private bool $include_page_assignments;

	/**
	 * List of field group mismatch entries: section_key, group_key, status (ok|missing|version_stale).
	 *
	 * @var list<array{section_key: string, group_key: string, status: string}>
	 */
	private array $field_group_mismatches;

	/**
	 * Page assignment repair candidates: page_id, type (page_template|page_composition), key (template_key or composition_id).
	 *
	 * @var list<array{page_id: int, type: string, key: string}>
	 */
	private array $page_assignment_repair_candidates;

	/** @var list<string> Reasons why unsafe cleanup was refused. */
	private array $refused_cleanup;

	/**
	 * @param bool        $dry_run
	 * @param string      $scope
	 * @param string|null $section_family_key
	 * @param string|null $page_template_family_key
	 * @param bool        $include_page_assignments
	 * @param list<array{section_key: string, group_key: string, status: string}> $field_group_mismatches
	 * @param list<array{page_id: int, type: string, key: string}>                  $page_assignment_repair_candidates
	 * @param list<string> $refused_cleanup
	 */
	public function __construct(
		bool $dry_run,
		string $scope,
		?string $section_family_key,
		?string $page_template_family_key,
		bool $include_page_assignments,
		array $field_group_mismatches,
		array $page_assignment_repair_candidates,
		array $refused_cleanup
	) {
		$this->dry_run                        = $dry_run;
		$this->scope                          = $scope;
		$this->section_family_key             = $section_family_key;
		$this->page_template_family_key       = $page_template_family_key;
		$this->include_page_assignments        = $include_page_assignments;
		$this->field_group_mismatches          = $field_group_mismatches;
		$this->page_assignment_repair_candidates = $page_assignment_repair_candidates;
		$this->refused_cleanup                 = $refused_cleanup;
	}

	public function is_dry_run(): bool {
		return $this->dry_run;
	}

	public function get_scope(): string {
		return $this->scope;
	}

	public function get_section_family_key(): ?string {
		return $this->section_family_key;
	}

	public function get_page_template_family_key(): ?string {
		return $this->page_template_family_key;
	}

	public function get_include_page_assignments(): bool {
		return $this->include_page_assignments;
	}

	/** @return list<array{section_key: string, group_key: string, status: string}> */
	public function get_field_group_mismatches(): array {
		return $this->field_group_mismatches;
	}

	/** @return list<array{page_id: int, type: string, key: string}> */
	public function get_page_assignment_repair_candidates(): array {
		return $this->page_assignment_repair_candidates;
	}

	/** @return list<string> */
	public function get_refused_cleanup(): array {
		return $this->refused_cleanup;
	}

	/**
	 * Returns counts suitable for acf_regeneration_plan payload.
	 *
	 * @return array{ dry_run: bool, scope: string, section_family_key: string|null, page_template_family_key: string|null, include_page_assignments: bool, field_group_mismatches: list<array>, page_assignment_repair_candidates: list<array>, refused_cleanup: list<string>, missing_count: int, version_stale_count: int, ok_count: int, candidate_count: int }
	 */
	public function to_array(): array {
		$missing = 0;
		$version_stale = 0;
		$ok = 0;
		foreach ( $this->field_group_mismatches as $m ) {
			$s = $m['status'] ?? '';
			if ( $s === self::MISMATCH_STATUS_MISSING ) {
				++$missing;
			} elseif ( $s === self::MISMATCH_STATUS_VERSION_STALE ) {
				++$version_stale;
			} else {
				++$ok;
			}
		}
		return array(
			'dry_run'                        => $this->dry_run,
			'scope'                          => $this->scope,
			'section_family_key'             => $this->section_family_key,
			'page_template_family_key'      => $this->page_template_family_key,
			'include_page_assignments'       => $this->include_page_assignments,
			'field_group_mismatches'         => $this->field_group_mismatches,
			'page_assignment_repair_candidates' => $this->page_assignment_repair_candidates,
			'refused_cleanup'                => $this->refused_cleanup,
			'missing_count'                  => $missing,
			'version_stale_count'            => $version_stale,
			'ok_count'                       => $ok,
			'candidate_count'                => count( $this->page_assignment_repair_candidates ),
		);
	}
}
