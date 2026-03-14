<?php
/**
 * Facade that produces diff summary/detail from snapshot IDs (spec §41.4–41.7, §59.11).
 *
 * Loads pre/post snapshots from repository and delegates to the appropriate summarizer.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Summary_Builder;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Builds diff results from pre_snapshot_id and post_snapshot_id using captured snapshots.
 * For page family, enriches with template_diff_summary when Template_Diff_Summary_Builder is provided (Prompt 197).
 */
final class Diff_Summarizer_Service {

	/** @var Operational_Snapshot_Repository_Interface */
	private Operational_Snapshot_Repository_Interface $repository;

	/** @var Page_Diff_Summarizer */
	private Page_Diff_Summarizer $page_summarizer;

	/** @var Navigation_Diff_Summarizer */
	private Navigation_Diff_Summarizer $navigation_summarizer;

	/** @var Token_Diff_Summarizer */
	private Token_Diff_Summarizer $token_summarizer;

	/** @var Template_Diff_Summary_Builder|null */
	private $template_diff_summary_builder;

	public function __construct(
		Operational_Snapshot_Repository_Interface $repository,
		Page_Diff_Summarizer $page_summarizer,
		Navigation_Diff_Summarizer $navigation_summarizer,
		Token_Diff_Summarizer $token_summarizer,
		?Template_Diff_Summary_Builder $template_diff_summary_builder = null
	) {
		$this->repository                   = $repository;
		$this->page_summarizer              = $page_summarizer;
		$this->navigation_summarizer        = $navigation_summarizer;
		$this->token_summarizer             = $token_summarizer;
		$this->template_diff_summary_builder = $template_diff_summary_builder;
	}

	/**
	 * Produces a diff result from stored pre- and post-change snapshot IDs.
	 *
	 * @param string $pre_snapshot_id  Snapshot ID of the pre-change snapshot.
	 * @param string $post_snapshot_id  Snapshot ID of the post-change snapshot.
	 * @param string $level             Diff_Type_Keys::LEVEL_SUMMARY or LEVEL_DETAIL.
	 * @return Diff_Summary_Result Result with contract-shaped diff or failure/no-meaningful-diff.
	 */
	public function summarize_from_snapshots( string $pre_snapshot_id, string $post_snapshot_id, string $level = Diff_Type_Keys::LEVEL_SUMMARY ): Diff_Summary_Result {
		$pre_snapshot  = $this->repository->get_by_id( $pre_snapshot_id );
		$post_snapshot = $this->repository->get_by_id( $post_snapshot_id );
		if ( $pre_snapshot === null ) {
			return Diff_Summary_Result::failure(
				array(
					'diff_id'        => $this->fallback_diff_id( 'pre_missing' ),
					'diff_type'      => Diff_Type_Keys::DIFF_TYPE_CONTENT,
					'level'          => $level,
					'target_ref'     => '',
					'before_summary' => __( 'Not available', 'aio-page-builder' ),
					'after_summary'  => __( 'Not available', 'aio-page-builder' ),
					'change_count'   => 0,
					'rollback'       => array(
						'rollback_eligible' => false,
						'pre_snapshot_id'   => $pre_snapshot_id,
						'post_snapshot_id'  => $post_snapshot_id,
						'rollback_status'   => Operational_Snapshot_Schema::ROLLBACK_STATUS_NONE,
					),
				),
				__( 'Pre-change snapshot not found.', 'aio-page-builder' ),
				'snapshot_missing'
			);
		}
		if ( $post_snapshot === null ) {
			return Diff_Summary_Result::failure(
				array(
					'diff_id'        => $this->fallback_diff_id( 'post_missing' ),
					'diff_type'      => Diff_Type_Keys::DIFF_TYPE_CONTENT,
					'level'          => $level,
					'target_ref'     => (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? '' ),
					'before_summary' => __( 'Not available', 'aio-page-builder' ),
					'after_summary'  => __( 'Not available', 'aio-page-builder' ),
					'change_count'   => 0,
					'rollback'       => array(
						'rollback_eligible' => false,
						'pre_snapshot_id'   => $pre_snapshot_id,
						'post_snapshot_id'  => $post_snapshot_id,
						'rollback_status'   => Operational_Snapshot_Schema::ROLLBACK_STATUS_NONE,
					),
				),
				__( 'Post-change snapshot not found.', 'aio-page-builder' ),
				'snapshot_missing'
			);
		}

		$family = (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY ] ?? $post_snapshot[ Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY ] ?? '' );
		switch ( $family ) {
			case Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE:
				$result = $this->page_summarizer->summarize( $pre_snapshot, $post_snapshot, $level );
				if ( $this->template_diff_summary_builder !== null && $result->is_success() ) {
					$diff = $result->get_diff();
					$diff['template_diff_summary'] = $this->template_diff_summary_builder->build( $pre_snapshot, $post_snapshot );
					return Diff_Summary_Result::with_diff( $diff, $result->get_message() );
				}
				return $result;
			case Operational_Snapshot_Schema::OBJECT_FAMILY_MENU:
				return $this->navigation_summarizer->summarize( $pre_snapshot, $post_snapshot, $level );
			case Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET:
				return $this->token_summarizer->summarize( $pre_snapshot, $post_snapshot, $level );
			default:
				return Diff_Summary_Result::failure(
					array(
						'diff_id'        => $this->fallback_diff_id( $family ),
						'diff_type'      => Diff_Type_Keys::DIFF_TYPE_CONTENT,
						'level'          => $level,
						'target_ref'     => (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? '' ),
						'before_summary' => __( 'Not available', 'aio-page-builder' ),
						'after_summary'  => __( 'Not available', 'aio-page-builder' ),
						'change_count'   => 0,
						'rollback'       => array(
							'rollback_eligible' => false,
							'pre_snapshot_id'   => $pre_snapshot_id,
							'post_snapshot_id'  => $post_snapshot_id,
							'rollback_status'   => Operational_Snapshot_Schema::ROLLBACK_STATUS_NONE,
						),
					),
					__( 'Unsupported object family for diff.', 'aio-page-builder' ),
					'incompatible_format'
				);
		}
	}

	private function fallback_diff_id( string $suffix ): string {
		$id = 'diff-fallback-' . substr( $suffix, 0, 24 );
		return substr( $id, 0, 64 );
	}
}
