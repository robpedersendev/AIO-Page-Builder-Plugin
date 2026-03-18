<?php
/**
 * Result of an ACF regeneration/repair run (spec §20, §20.15; Prompt 222).
 * Produced by ACF_Regeneration_Service::execute_repair(). Immutable.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Repair;

defined( 'ABSPATH' ) || exit;

/**
 * Structured repair summary: changed groups, skipped, warnings, failures.
 *
 * Example repair result payload (to_array()):
 * {
 *   "groups_regenerated": 2,
 *   "groups_skipped": [],
 *   "page_assignments_repaired": 1,
 *   "page_assignments_failed": 0,
 *   "warnings": [],
 *   "errors": [],
 *   "field_group_mismatch_summary": { "missing": 2, "version_stale": 0, "repaired": 2 },
 *   "page_assignment_repair_summary": { "repaired": 1, "failed": 0, "skipped": 0 }
 * }
 */
final class ACF_Regeneration_Result {

	/** @var int Number of field groups successfully registered. */
	private int $groups_regenerated;

	/** @var list<string> Group keys that were skipped (e.g. no blueprint, ACF unavailable). */
	private array $groups_skipped;

	/** @var int Number of page assignments successfully repaired. */
	private int $page_assignments_repaired;

	/** @var int Number of page assignment repair attempts that failed. */
	private int $page_assignments_failed;

	/** @var list<string> Non-fatal warnings. */
	private array $warnings;

	/** @var list<string> Fatal or recorded errors. */
	private array $errors;

	/** @var array{ missing: int, version_stale: int, repaired: int } */
	private array $field_group_mismatch_summary;

	/** @var array{ repaired: int, failed: int, skipped: int } */
	private array $page_assignment_repair_summary;

	/**
	 * @param int                                                      $groups_regenerated
	 * @param list<string>                                             $groups_skipped
	 * @param int                                                      $page_assignments_repaired
	 * @param int                                                      $page_assignments_failed
	 * @param list<string>                                             $warnings
	 * @param list<string>                                             $errors
	 * @param array{ missing: int, version_stale: int, repaired: int } $field_group_mismatch_summary
	 * @param array{ repaired: int, failed: int, skipped: int }        $page_assignment_repair_summary
	 */
	public function __construct(
		int $groups_regenerated,
		array $groups_skipped,
		int $page_assignments_repaired,
		int $page_assignments_failed,
		array $warnings,
		array $errors,
		array $field_group_mismatch_summary,
		array $page_assignment_repair_summary
	) {
		$this->groups_regenerated             = $groups_regenerated;
		$this->groups_skipped                 = $groups_skipped;
		$this->page_assignments_repaired      = $page_assignments_repaired;
		$this->page_assignments_failed        = $page_assignments_failed;
		$this->warnings                       = $warnings;
		$this->errors                         = $errors;
		$this->field_group_mismatch_summary   = $field_group_mismatch_summary;
		$this->page_assignment_repair_summary = $page_assignment_repair_summary;
	}

	public function get_groups_regenerated(): int {
		return $this->groups_regenerated;
	}

	/** @return list<string> */
	public function get_groups_skipped(): array {
		return $this->groups_skipped;
	}

	public function get_page_assignments_repaired(): int {
		return $this->page_assignments_repaired;
	}

	public function get_page_assignments_failed(): int {
		return $this->page_assignments_failed;
	}

	/** @return list<string> */
	public function get_warnings(): array {
		return $this->warnings;
	}

	/** @return list<string> */
	public function get_errors(): array {
		return $this->errors;
	}

	/** @return array{ missing: int, version_stale: int, repaired: int } */
	public function get_field_group_mismatch_summary(): array {
		return $this->field_group_mismatch_summary;
	}

	/** @return array{ repaired: int, failed: int, skipped: int } */
	public function get_page_assignment_repair_summary(): array {
		return $this->page_assignment_repair_summary;
	}

	/**
	 * Returns stable payload for logging and UI (acf_regeneration_result).
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'groups_regenerated'             => $this->groups_regenerated,
			'groups_skipped'                 => $this->groups_skipped,
			'page_assignments_repaired'      => $this->page_assignments_repaired,
			'page_assignments_failed'        => $this->page_assignments_failed,
			'warnings'                       => $this->warnings,
			'errors'                         => $this->errors,
			'field_group_mismatch_summary'   => $this->field_group_mismatch_summary,
			'page_assignment_repair_summary' => $this->page_assignment_repair_summary,
		);
	}
}
