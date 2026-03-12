<?php
/**
 * Page content and structure diff summarizer (spec §41.4, §41.5; diff-service-contract.md).
 *
 * Produces summary/detail diff from pre- and post-change operational snapshots (object_family page).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Builds content (and optional structure) diff results from page snapshots.
 */
final class Page_Diff_Summarizer {

	private const SUMMARY_MAX_LEN = 512;
	private const EXCERPT_MAX_LEN = 200;
	private const CONTENT_REPLACEMENT_NONE       = 'none';
	private const CONTENT_REPLACEMENT_FULL       = 'full_replace';
	private const CONTENT_REPLACEMENT_PARTIAL    = 'partial';
	private const CONTENT_REPLACEMENT_UNKNOWN    = 'unknown';

	/**
	 * Builds a content diff from pre- and post-change snapshot records.
	 *
	 * @param array<string, mixed> $pre_snapshot  Full pre-change snapshot (pre_change.state_snapshot).
	 * @param array<string, mixed> $post_snapshot Full post-change snapshot (post_change.result_snapshot).
	 * @param string               $level         Diff_Type_Keys::LEVEL_SUMMARY or LEVEL_DETAIL.
	 * @return Diff_Summary_Result Contract-shaped diff or no-meaningful-diff / failure.
	 */
	public function summarize( array $pre_snapshot, array $post_snapshot, string $level = Diff_Type_Keys::LEVEL_SUMMARY ): Diff_Summary_Result {
		$pre_state  = $this->extract_pre_state( $pre_snapshot );
		$post_state = $this->extract_post_state( $post_snapshot );
		if ( $pre_state === null || $post_state === null ) {
			$diff = $this->minimal_root( $pre_snapshot, $post_snapshot, Diff_Type_Keys::DIFF_TYPE_CONTENT );
			$diff['before_summary'] = $pre_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->page_one_liner( $pre_state );
			$diff['after_summary']  = $post_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->page_one_liner( $post_state );
			return Diff_Summary_Result::failure( $diff, __( 'Missing or incompatible snapshot state.', 'aio-page-builder' ), 'snapshot_missing' );
		}

		$target_ref = isset( $post_state['post_id'] ) ? (string) $post_state['post_id'] : ( isset( $pre_state['post_id'] ) ? (string) $pre_state['post_id'] : '' );
		if ( $target_ref === '' ) {
			$target_ref = (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? $post_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? 'unknown' );
		}

		$change_count = 0;
		$title_before = isset( $pre_state['post_title'] ) ? (string) $pre_state['post_title'] : '';
		$title_after  = isset( $post_state['post_title'] ) ? (string) $post_state['post_title'] : '';
		$slug_before  = isset( $pre_state['post_name'] ) ? (string) $pre_state['post_name'] : '';
		$slug_after   = isset( $post_state['post_name'] ) ? (string) $post_state['post_name'] : '';
		$status_before = isset( $pre_state['post_status'] ) ? (string) $pre_state['post_status'] : '';
		$status_after  = isset( $post_state['post_status'] ) ? (string) $post_state['post_status'] : '';
		if ( $title_before !== $title_after ) {
			++$change_count;
		}
		if ( $slug_before !== $slug_after ) {
			++$change_count;
		}
		if ( $status_before !== $status_after ) {
			++$change_count;
		}
		$content_hash_before = isset( $pre_state['content_hash'] ) ? (string) $pre_state['content_hash'] : '';
		$content_hash_after  = isset( $post_state['content_hash'] ) ? (string) $post_state['content_hash'] : '';
		$content_changed = $content_hash_before !== '' && $content_hash_after !== '' && $content_hash_before !== $content_hash_after;
		if ( $content_changed ) {
			++$change_count;
		}
		$content_replacement = $this->content_replacement_indicator( $content_hash_before, $content_hash_after, $change_count );

		$before_summary = $this->page_one_liner( $pre_state );
		$after_summary  = $this->page_one_liner( $post_state );
		if ( strlen( $before_summary ) > self::SUMMARY_MAX_LEN ) {
			$before_summary = substr( $before_summary, 0, self::SUMMARY_MAX_LEN - 3 ) . '...';
		}
		if ( strlen( $after_summary ) > self::SUMMARY_MAX_LEN ) {
			$after_summary = substr( $after_summary, 0, self::SUMMARY_MAX_LEN - 3 ) . '...';
		}

		$no_meaningful = ( $change_count === 0 );

		$diff_id = $this->diff_id( Diff_Type_Keys::DIFF_TYPE_CONTENT, $target_ref );
		$diff = array(
			'diff_id'         => $diff_id,
			'diff_type'       => Diff_Type_Keys::DIFF_TYPE_CONTENT,
			'level'           => Diff_Type_Keys::is_valid_level( $level ) ? $level : Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'      => $target_ref,
			'target_type_hint'=> 'post',
			'before_summary'  => $before_summary,
			'after_summary'   => $after_summary,
			'change_count'    => $change_count,
			'execution_ref'   => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' ),
			'build_plan_ref'  => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ?? '' ),
			'plan_item_ref'   => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF ] ?? '' ),
			'rollback'        => $this->rollback_block( $pre_snapshot, $post_snapshot ),
		);

		if ( $level === Diff_Type_Keys::LEVEL_DETAIL ) {
			$family = array(
				'title_before'                 => $title_before,
				'title_after'                  => $title_after,
				'slug_before'                  => $slug_before,
				'slug_after'                   => $slug_after,
				'status_before'                => $status_before,
				'status_after'                 => $status_after,
				'section_structure_changed'    => false,
				'content_replacement_indicator'=> $content_replacement,
			);
			if ( isset( $pre_state['excerpt'] ) && is_string( $pre_state['excerpt'] ) ) {
				$family['content_excerpt_before'] = strlen( $pre_state['excerpt'] ) > self::EXCERPT_MAX_LEN
					? substr( $pre_state['excerpt'], 0, self::EXCERPT_MAX_LEN - 3 ) . '...'
					: $pre_state['excerpt'];
			}
			if ( isset( $post_state['excerpt'] ) && is_string( $post_state['excerpt'] ) ) {
				$family['content_excerpt_after'] = strlen( $post_state['excerpt'] ) > self::EXCERPT_MAX_LEN
					? substr( $post_state['excerpt'], 0, self::EXCERPT_MAX_LEN - 3 ) . '...'
					: $post_state['excerpt'];
			}
			$diff['family_payload'] = $family;
		}

		if ( $no_meaningful ) {
			return Diff_Summary_Result::no_meaningful_diff( $diff, __( 'No meaningful page changes detected.', 'aio-page-builder' ) );
		}
		return Diff_Summary_Result::with_diff( $diff, __( 'Page content diff generated.', 'aio-page-builder' ) );
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @return array<string, mixed>|null
	 */
	private function extract_pre_state( array $pre_snapshot ): ?array {
		$pre = $pre_snapshot[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] ?? null;
		if ( ! is_array( $pre ) ) {
			return null;
		}
		$state = $pre['state_snapshot'] ?? null;
		return is_array( $state ) ? $state : null;
	}

	/**
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>|null
	 */
	private function extract_post_state( array $post_snapshot ): ?array {
		$post = $post_snapshot[ Operational_Snapshot_Schema::FIELD_POST_CHANGE ] ?? null;
		if ( ! is_array( $post ) ) {
			return null;
		}
		$state = $post['result_snapshot'] ?? null;
		return is_array( $state ) ? $state : null;
	}

	/**
	 * @param array<string, mixed> $state state_snapshot or result_snapshot for a page.
	 * @return string
	 */
	private function page_one_liner( array $state ): string {
		$title  = isset( $state['post_title'] ) ? trim( (string) $state['post_title'] ) : '';
		$slug   = isset( $state['post_name'] ) ? trim( (string) $state['post_name'] ) : '';
		$status = isset( $state['post_status'] ) ? trim( (string) $state['post_status'] ) : '';
		if ( $title === '' ) {
			$title = __( 'Untitled', 'aio-page-builder' );
		}
		$slug_part = $slug !== '' ? " ({$slug})" : '';
		$status_part = $status !== '' ? ", {$status}" : '';
		return $title . $slug_part . $status_part;
	}

	private function content_replacement_indicator( string $hash_before, string $hash_after, int $change_count ): string {
		if ( $hash_before !== '' && $hash_after !== '' ) {
			return $hash_before !== $hash_after ? self::CONTENT_REPLACEMENT_FULL : self::CONTENT_REPLACEMENT_NONE;
		}
		if ( $change_count > 0 ) {
			return self::CONTENT_REPLACEMENT_PARTIAL;
		}
		return self::CONTENT_REPLACEMENT_UNKNOWN;
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>
	 */
	private function minimal_root( array $pre_snapshot, array $post_snapshot, string $diff_type ): array {
		$target_ref = (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? 'unknown' );
		return array(
			'diff_id'        => $this->diff_id( $diff_type, $target_ref ),
			'diff_type'      => $diff_type,
			'level'          => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'     => $target_ref,
			'target_type_hint'=> 'post',
			'before_summary' => '',
			'after_summary'  => '',
			'change_count'   => 0,
			'execution_ref'  => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' ),
			'build_plan_ref' => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ?? '' ),
			'plan_item_ref'  => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF ] ?? '' ),
			'rollback'       => $this->rollback_block( $pre_snapshot, $post_snapshot ),
		);
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>
	 */
	private function rollback_block( array $pre_snapshot, array $post_snapshot ): array {
		$pre_id  = isset( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) ? (string) $pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] : '';
		$post_id = isset( $post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) ? (string) $post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] : '';
		$eligible = (bool) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE ] ?? false );
		$status   = (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS ] ?? Operational_Snapshot_Schema::ROLLBACK_STATUS_NONE );
		return array(
			'rollback_eligible' => $eligible,
			'pre_snapshot_id'   => substr( $pre_id, 0, 64 ),
			'post_snapshot_id'  => substr( $post_id, 0, 64 ),
			'rollback_status'   => $status,
		);
	}

	private function diff_id( string $diff_type, string $target_ref ): string {
		$raw = ( function_exists( 'wp_generate_uuid4' ) ? \wp_generate_uuid4() : uniqid( 'diff-', true ) );
		$id = 'diff-' . $diff_type . '-' . substr( str_replace( array( '-', ' ' ), '', $raw ), 0, 24 );
		return substr( $id, 0, 64 );
	}
}
