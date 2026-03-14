<?php
/**
 * Result of an ACF migration/upgrade verification run (spec §53, §58.4, §58.5, §59.14; Prompt 225).
 * Machine-readable and human-readable; carries field_key_stability_summary, assignment_continuity_summary,
 * mirror coherence, regeneration safety, and breaking/deprecation risk flags.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\ACF\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of ACF_Migration_Verification_Service::run_verification().
 *
 * Example acf_migration_verification_result payload:
 * {
 *   "verification_run_at": "2025-03-14T14:00:00Z",
 *   "plugin_version": "1.0.0",
 *   "registry_schema": "1",
 *   "field_key_stability_summary": { "stable_group_keys": 12, "stable_field_keys": 48, "unstable_or_missing": [], "summary": "All keys stable." },
 *   "assignment_continuity_summary": { "assignments_checked": 5, "assignments_relevant": 5, "orphaned_or_invalid": [], "summary": "All assignments relevant." },
 *   "mirror_coherence": { "in_sync": 12, "in_registry_not_mirror": 0, "version_mismatch": 0, "summary": "12 in sync." },
 *   "regeneration_safe": { "plan_buildable": true, "repair_candidates_consistent": true, "summary": "Regeneration plan buildable; repair safe." },
 *   "breaking_change_risks": [],
 *   "deprecation_risks": [],
 *   "overall_status": "pass",
 *   "human_summary": "ACF migration verification passed."
 * }
 */
final class ACF_Migration_Verification_Result {

	public const STATUS_PASS   = 'pass';
	public const STATUS_WARNING = 'warning';
	public const STATUS_FAIL   = 'fail';

	/** @var string ISO 8601 UTC */
	private string $verification_run_at;

	/** @var string */
	private string $plugin_version;

	/** @var string */
	private string $registry_schema;

	/** @var array<string, mixed> field_key_stability_summary */
	private array $field_key_stability_summary;

	/** @var array<string, mixed> assignment_continuity_summary */
	private array $assignment_continuity_summary;

	/** @var array<string, mixed> mirror coherence (from diff or synthetic) */
	private array $mirror_coherence;

	/** @var array<string, mixed> regeneration_safe */
	private array $regeneration_safe;

	/** @var list<string> */
	private array $breaking_change_risks;

	/** @var list<string> */
	private array $deprecation_risks;

	/** @var string One of STATUS_* */
	private string $overall_status;

	/** @var string Human-readable one-line summary */
	private string $human_summary;

	public function __construct(
		string $verification_run_at,
		string $plugin_version,
		string $registry_schema,
		array $field_key_stability_summary,
		array $assignment_continuity_summary,
		array $mirror_coherence,
		array $regeneration_safe,
		array $breaking_change_risks,
		array $deprecation_risks,
		string $overall_status,
		string $human_summary
	) {
		$this->verification_run_at          = $verification_run_at;
		$this->plugin_version                = $plugin_version;
		$this->registry_schema               = $registry_schema;
		$this->field_key_stability_summary   = $field_key_stability_summary;
		$this->assignment_continuity_summary = $assignment_continuity_summary;
		$this->mirror_coherence              = $mirror_coherence;
		$this->regeneration_safe             = $regeneration_safe;
		$this->breaking_change_risks          = $breaking_change_risks;
		$this->deprecation_risks             = $deprecation_risks;
		$this->overall_status                = $overall_status;
		$this->human_summary                 = $human_summary;
	}

	public function get_verification_run_at(): string {
		return $this->verification_run_at;
	}

	public function get_plugin_version(): string {
		return $this->plugin_version;
	}

	public function get_registry_schema(): string {
		return $this->registry_schema;
	}

	/** @return array<string, mixed> */
	public function get_field_key_stability_summary(): array {
		return $this->field_key_stability_summary;
	}

	/** @return array<string, mixed> */
	public function get_assignment_continuity_summary(): array {
		return $this->assignment_continuity_summary;
	}

	/** @return array<string, mixed> */
	public function get_mirror_coherence(): array {
		return $this->mirror_coherence;
	}

	/** @return array<string, mixed> */
	public function get_regeneration_safe(): array {
		return $this->regeneration_safe;
	}

	/** @return list<string> */
	public function get_breaking_change_risks(): array {
		return $this->breaking_change_risks;
	}

	/** @return list<string> */
	public function get_deprecation_risks(): array {
		return $this->deprecation_risks;
	}

	public function get_overall_status(): string {
		return $this->overall_status;
	}

	public function get_human_summary(): string {
		return $this->human_summary;
	}

	public function is_pass(): bool {
		return $this->overall_status === self::STATUS_PASS;
	}

	/** @return array<string, mixed> acf_migration_verification_result payload for reports/logging */
	public function to_array(): array {
		return array(
			'verification_run_at'            => $this->verification_run_at,
			'plugin_version'                 => $this->plugin_version,
			'registry_schema'                => $this->registry_schema,
			'field_key_stability_summary'    => $this->field_key_stability_summary,
			'assignment_continuity_summary'  => $this->assignment_continuity_summary,
			'mirror_coherence'                => $this->mirror_coherence,
			'regeneration_safe'              => $this->regeneration_safe,
			'breaking_change_risks'          => $this->breaking_change_risks,
			'deprecation_risks'              => $this->deprecation_risks,
			'overall_status'                 => $this->overall_status,
			'human_summary'                  => $this->human_summary,
		);
	}
}
