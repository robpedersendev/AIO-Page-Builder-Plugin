<?php
/**
 * Builds rollback UI state from pre/post snapshot IDs (spec §59.11; Prompt 197).
 *
 * Aggregates eligibility, diff summary (including template_diff_summary), and rollback_template_context
 * for the rollback review UI. Template-aware context improves explainability without changing rollback semantics.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summary_Result;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Summarizer_Service;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Type_Keys;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Result;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;

/**
 * Produces rollback UI state: eligibility, diff, and template context for a pre/post snapshot pair.
 */
final class Rollback_State_Builder {

	/** @var Rollback_Eligibility_Service */
	private Rollback_Eligibility_Service $eligibility_service;

	/** @var Diff_Summarizer_Service */
	private Diff_Summarizer_Service $diff_summarizer;

	public function __construct(
		Rollback_Eligibility_Service $eligibility_service,
		Diff_Summarizer_Service $diff_summarizer
	) {
		$this->eligibility_service = $eligibility_service;
		$this->diff_summarizer     = $diff_summarizer;
	}

	/**
	 * Builds rollback state for the given pre- and post-change snapshot IDs.
	 *
	 * @param string $pre_snapshot_id  Pre-change snapshot ID.
	 * @param string $post_snapshot_id Post-change snapshot ID.
	 * @param string $diff_level       Diff_Type_Keys::LEVEL_SUMMARY or LEVEL_DETAIL.
	 * @param array<string, mixed> $eligibility_options Optional: skip_permission_check, user_id.
	 * @return array{eligibility: Rollback_Eligibility_Result, diff_result: Diff_Summary_Result, rollback_template_context: array<string, mixed>}
	 */
	public function build( string $pre_snapshot_id, string $post_snapshot_id, string $diff_level = Diff_Type_Keys::LEVEL_SUMMARY, array $eligibility_options = array() ): array {
		$eligibility = $this->eligibility_service->evaluate( $pre_snapshot_id, $post_snapshot_id, $eligibility_options );
		$diff_result = $this->diff_summarizer->summarize_from_snapshots( $pre_snapshot_id, $post_snapshot_id, $diff_level );

		$rollback_template_context = array();
		if ( $diff_result->is_success() ) {
			$diff = $diff_result->get_diff();
			$template_summary = $diff['template_diff_summary'] ?? null;
			if ( is_array( $template_summary ) && isset( $template_summary['rollback_template_context'] ) && is_array( $template_summary['rollback_template_context'] ) ) {
				$rollback_template_context = $template_summary['rollback_template_context'];
			}
		}

		return array(
			'eligibility'               => $eligibility,
			'diff_result'                => $diff_result,
			'rollback_template_context' => $rollback_template_context,
		);
	}
}
