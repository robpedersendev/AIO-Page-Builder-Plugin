<?php
/**
 * Token-set diff summarizer (spec §41.7; diff-service-contract.md).
 *
 * Produces summary/detail diff from pre- and post-change operational snapshots (object_family token_set).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Builds token diff results (prior/new value, role, group, provenance).
 */
final class Token_Diff_Summarizer {

	private const SUMMARY_MAX_LEN = 512;

	/**
	 * Builds a token diff from pre- and post-change snapshot records.
	 *
	 * @param array<string, mixed> $pre_snapshot  Full pre-change snapshot (pre_change.state_snapshot).
	 * @param array<string, mixed> $post_snapshot  Full post-change snapshot (post_change.result_snapshot).
	 * @param string               $level         Diff_Type_Keys::LEVEL_SUMMARY or LEVEL_DETAIL.
	 * @return Diff_Summary_Result Contract-shaped diff or no-meaningful-diff / failure.
	 */
	public function summarize( array $pre_snapshot, array $post_snapshot, string $level = Diff_Type_Keys::LEVEL_SUMMARY ): Diff_Summary_Result {
		$pre_state  = $this->extract_pre_state( $pre_snapshot );
		$post_state = $this->extract_post_state( $post_snapshot );
		if ( $pre_state === null || $post_state === null ) {
			$diff                   = $this->minimal_root( $pre_snapshot, $post_snapshot );
			$diff['before_summary'] = $pre_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->token_one_liner( $pre_state );
			$diff['after_summary']  = $post_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->token_one_liner( $post_state );
			return Diff_Summary_Result::failure( $diff, __( 'Missing or incompatible token-set snapshot state.', 'aio-page-builder' ), 'snapshot_missing' );
		}

		$token_set_ref = (string) ( $post_state['token_set_id'] ?? $pre_state['token_set_id'] ?? '' );
		$target_ref    = $token_set_ref !== '' ? $token_set_ref : ( (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? $post_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? 'unknown' ) );

		$pre_tokens  = isset( $pre_state['tokens'] ) && is_array( $pre_state['tokens'] ) ? $pre_state['tokens'] : array();
		$post_tokens = isset( $post_state['tokens'] ) && is_array( $post_state['tokens'] ) ? $post_state['tokens'] : array();

		$changes      = $this->compute_token_changes( $pre_tokens, $post_tokens );
		$change_count = count( $changes );

		$before_summary = $this->token_one_liner( $pre_state );
		$after_summary  = $this->token_one_liner( $post_state );
		if ( strlen( $before_summary ) > self::SUMMARY_MAX_LEN ) {
			$before_summary = substr( $before_summary, 0, self::SUMMARY_MAX_LEN - 3 ) . '...';
		}
		if ( strlen( $after_summary ) > self::SUMMARY_MAX_LEN ) {
			$after_summary = substr( $after_summary, 0, self::SUMMARY_MAX_LEN - 3 ) . '...';
		}

		$no_meaningful = ( $change_count === 0 );

		$diff_id = $this->diff_id( $target_ref );
		$diff    = array(
			'diff_id'          => $diff_id,
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_TOKEN,
			'level'            => Diff_Type_Keys::is_valid_level( $level ) ? $level : Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => $target_ref,
			'target_type_hint' => 'token_set',
			'before_summary'   => $before_summary,
			'after_summary'    => $after_summary,
			'change_count'     => $change_count,
			'execution_ref'    => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' ),
			'build_plan_ref'   => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ?? '' ),
			'plan_item_ref'    => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF ] ?? '' ),
			'rollback'         => $this->rollback_block( $pre_snapshot, $post_snapshot ),
		);

		if ( $level === Diff_Type_Keys::LEVEL_DETAIL ) {
			$diff['family_payload'] = array(
				'token_set_ref' => $token_set_ref !== '' ? $token_set_ref : $target_ref,
				'changes'       => $changes,
			);
		}

		if ( $no_meaningful ) {
			return Diff_Summary_Result::no_meaningful_diff( $diff, __( 'No meaningful token changes detected.', 'aio-page-builder' ) );
		}
		return Diff_Summary_Result::with_diff( $diff, __( 'Token diff generated.', 'aio-page-builder' ) );
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
	 * @param array<string, mixed> $state state_snapshot or result_snapshot for a token_set.
	 * @return string
	 */
	private function token_one_liner( array $state ): string {
		$ref    = isset( $state['token_set_id'] ) ? trim( (string) $state['token_set_id'] ) : '';
		$tokens = isset( $state['tokens'] ) && is_array( $state['tokens'] ) ? $state['tokens'] : array();
		$count  = count( $tokens );
		if ( $ref === '' ) {
			$ref = __( 'Unknown set', 'aio-page-builder' );
		}
		return $count > 0 ? "{$ref}, {$count} token(s)" : $ref;
	}

	/**
	 * Builds list of token change descriptors (value_before, value_after, role, group, provenance when present).
	 *
	 * @param array<string, mixed> $pre_tokens  Map of token key => array with at least 'value'.
	 * @param array<string, mixed> $post_tokens  Same shape.
	 * @return array<int, array<string, string>>
	 */
	private function compute_token_changes( array $pre_tokens, array $post_tokens ): array {
		$all_keys = array_unique( array_merge( array_keys( $pre_tokens ), array_keys( $post_tokens ) ) );
		$changes  = array();
		foreach ( $all_keys as $token_key ) {
			$pre_val  = $this->token_value( $pre_tokens[ $token_key ] ?? null );
			$post_val = $this->token_value( $post_tokens[ $token_key ] ?? null );
			if ( $pre_val === $post_val ) {
				continue;
			}
			$entry      = array(
				'token_key'    => $token_key,
				'value_before' => $pre_val,
				'value_after'  => $post_val,
			);
			$pre_entry  = is_array( $pre_tokens[ $token_key ] ?? null ) ? $pre_tokens[ $token_key ] : array();
			$post_entry = is_array( $post_tokens[ $token_key ] ?? null ) ? $post_tokens[ $token_key ] : array();
			if ( isset( $pre_entry['role'] ) && is_string( $pre_entry['role'] ) ) {
				$entry['role'] = $pre_entry['role'];
			} elseif ( isset( $post_entry['role'] ) && is_string( $post_entry['role'] ) ) {
				$entry['role'] = $post_entry['role'];
			}
			if ( isset( $pre_entry['group'] ) && is_string( $pre_entry['group'] ) ) {
				$entry['group'] = $pre_entry['group'];
			} elseif ( isset( $post_entry['group'] ) && is_string( $post_entry['group'] ) ) {
				$entry['group'] = $post_entry['group'];
			}
			if ( isset( $pre_entry['provenance'] ) && is_string( $pre_entry['provenance'] ) ) {
				$entry['provenance'] = $pre_entry['provenance'];
			} elseif ( isset( $post_entry['provenance'] ) && is_string( $post_entry['provenance'] ) ) {
				$entry['provenance'] = $post_entry['provenance'];
			}
			$changes[] = $entry;
		}
		return $changes;
	}

	/**
	 * @param mixed $entry Token entry (array with 'value') or null.
	 * @return string
	 */
	private function token_value( $entry ): string {
		if ( ! is_array( $entry ) ) {
			return '';
		}
		return isset( $entry['value'] ) ? (string) $entry['value'] : '';
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>
	 */
	private function minimal_root( array $pre_snapshot, array $post_snapshot ): array {
		$target_ref = (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? 'unknown' );
		return array(
			'diff_id'          => $this->diff_id( $target_ref ),
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_TOKEN,
			'level'            => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => $target_ref,
			'target_type_hint' => 'token_set',
			'before_summary'   => '',
			'after_summary'    => '',
			'change_count'     => 0,
			'execution_ref'    => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' ),
			'build_plan_ref'   => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ?? '' ),
			'plan_item_ref'    => (string) ( $post_snapshot[ Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF ] ?? '' ),
			'rollback'         => $this->rollback_block( $pre_snapshot, $post_snapshot ),
		);
	}

	/**
	 * @param array<string, mixed> $pre_snapshot
	 * @param array<string, mixed> $post_snapshot
	 * @return array<string, mixed>
	 */
	private function rollback_block( array $pre_snapshot, array $post_snapshot ): array {
		$pre_id   = isset( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) ? (string) $pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] : '';
		$post_id  = isset( $post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) ? (string) $post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] : '';
		$eligible = (bool) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE ] ?? false );
		$status   = (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS ] ?? Operational_Snapshot_Schema::ROLLBACK_STATUS_NONE );
		return array(
			'rollback_eligible' => $eligible,
			'pre_snapshot_id'   => substr( $pre_id, 0, 64 ),
			'post_snapshot_id'  => substr( $post_id, 0, 64 ),
			'rollback_status'   => $status,
		);
	}

	private function diff_id( string $target_ref ): string {
		$raw = ( function_exists( 'wp_generate_uuid4' ) ? \wp_generate_uuid4() : uniqid( 'diff-', true ) );
		$id  = 'diff-token-' . substr( str_replace( array( '-', ' ' ), '', $raw ), 0, 20 );
		return substr( $id, 0, 64 );
	}
}
