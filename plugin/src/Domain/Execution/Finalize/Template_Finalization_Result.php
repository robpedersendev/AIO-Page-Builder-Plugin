<?php
/**
 * Result DTO for template-aware finalization (spec §59.10, §1.9.9; Prompt 208).
 *
 * Stable payloads: finalization_summary, template_execution_closure_record, run_completion_state.
 * Export-safe; supports operator-facing completion summaries and rollback/diff review.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Finalize;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of building template-aware finalization summaries.
 */
final class Template_Finalization_Result {

	/** Run completion state: all approved items completed, no failures. */
	public const RUN_STATE_COMPLETE = 'complete';

	/** Run completion state: some failures but at least one success; operator attention. */
	public const RUN_STATE_WARNING = 'warning';

	/** Run completion state: mixed outcomes (e.g. some completed, some failed, some skipped). */
	public const RUN_STATE_PARTIAL = 'partial';

	/** Run completion state: all failed or conflicts block. */
	public const RUN_STATE_FAILED = 'failed';

	/** @var array<string, mixed> finalization_summary (counts, etc.). */
	private $finalization_summary;

	/** @var list<array<string, mixed>> template_execution_closure_record (trace links). */
	private $template_execution_closure_record;

	/** @var string run_completion_state (complete|warning|partial|failed). */
	private $run_completion_state;

	/** @var array<string, mixed> Optional one_pager_retention_summary. */
	private $one_pager_retention_summary;

	public function __construct(
		array $finalization_summary,
		array $template_execution_closure_record,
		string $run_completion_state,
		array $one_pager_retention_summary = array()
	) {
		$this->finalization_summary             = $finalization_summary;
		$this->template_execution_closure_record = $template_execution_closure_record;
		$this->run_completion_state             = $run_completion_state;
		$this->one_pager_retention_summary      = $one_pager_retention_summary;
	}

	/** @return array<string, mixed> */
	public function get_finalization_summary(): array {
		return $this->finalization_summary;
	}

	/** @return list<array<string, mixed>> */
	public function get_template_execution_closure_record(): array {
		return $this->template_execution_closure_record;
	}

	public function get_run_completion_state(): string {
		return $this->run_completion_state;
	}

	/** @return array<string, mixed> */
	public function get_one_pager_retention_summary(): array {
		return $this->one_pager_retention_summary;
	}

	/**
	 * Stable payload for persistence and export (finalization_summary, template_execution_closure_record, run_completion_state).
	 *
	 * @return array<string, mixed>
	 */
	public function to_payload(): array {
		return array(
			'finalization_summary'              => $this->finalization_summary,
			'template_execution_closure_record' => $this->template_execution_closure_record,
			'run_completion_state'              => $this->run_completion_state,
			'one_pager_retention_summary'       => $this->one_pager_retention_summary,
		);
	}
}
