<?php
/**
 * Navigation (menu) diff summarizer (spec §41.6; diff-service-contract.md).
 *
 * Produces summary/detail diff from pre- and post-change operational snapshots (object_family menu).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Diffs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Builds navigation diff results from menu snapshots (add/remove, label, order, location).
 */
final class Navigation_Diff_Summarizer {

	private const SUMMARY_MAX_LEN = 512;

	/**
	 * Builds a navigation diff from pre- and post-change snapshot records.
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
			$diff                   = $this->minimal_root( $pre_snapshot, $post_snapshot );
			$diff['before_summary'] = $pre_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->menu_one_liner( $pre_state );
			$diff['after_summary']  = $post_state === null ? __( 'Not available', 'aio-page-builder' ) : $this->menu_one_liner( $post_state );
			return Diff_Summary_Result::failure( $diff, __( 'Missing or incompatible menu snapshot state.', 'aio-page-builder' ), 'snapshot_missing' );
		}

		$menu_id    = (int) ( $post_state['menu_id'] ?? $pre_state['menu_id'] ?? 0 );
		$target_ref = $menu_id > 0 ? (string) $menu_id : ( (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? $post_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? 'unknown' ) );

		$name_before     = isset( $pre_state['name'] ) ? (string) $pre_state['name'] : '';
		$name_after      = isset( $post_state['name'] ) ? (string) $post_state['name'] : '';
		$location_before = isset( $pre_state['location'] ) ? (string) $pre_state['location'] : '';
		$location_after  = isset( $post_state['location'] ) ? (string) $post_state['location'] : '';

		$change_count = 0;
		if ( $name_before !== $name_after ) {
			++$change_count;
		}
		if ( $location_before !== $location_after ) {
			++$change_count;
		}

		$pre_items       = isset( $pre_state['items'] ) && is_array( $pre_state['items'] ) ? $pre_state['items'] : array();
		$post_items      = isset( $post_state['items'] ) && is_array( $post_state['items'] ) ? $post_state['items'] : array();
		$items_added     = array();
		$items_removed   = array();
		$labels_changed  = array();
		$items_reordered = false;
		if ( $post_items !== array() ) {
			$pre_ids  = $this->menu_item_ids( $pre_items );
			$post_ids = $this->menu_item_ids( $post_items );
			foreach ( $post_items as $item ) {
				$id = isset( $item['id'] ) ? (int) $item['id'] : 0;
				if ( $id > 0 && ! in_array( $id, $pre_ids, true ) ) {
					$items_added[] = $this->item_descriptor( $item );
				}
			}
			foreach ( $pre_items as $item ) {
				$id = isset( $item['id'] ) ? (int) $item['id'] : 0;
				if ( $id > 0 && ! in_array( $id, $post_ids, true ) ) {
					$items_removed[] = $this->item_descriptor( $item );
				}
			}
			$labels_changed  = $this->label_changes( $pre_items, $post_items );
			$items_reordered = $this->order_changed( $pre_items, $post_items );
			if ( count( $items_added ) > 0 || count( $items_removed ) > 0 ) {
				$change_count += count( $items_added ) + count( $items_removed );
			}
			if ( ! empty( $labels_changed ) ) {
				$change_count += count( $labels_changed );
			}
			if ( $items_reordered ) {
				++$change_count;
			}
		}

		$before_summary = $this->menu_one_liner( $pre_state );
		$after_summary  = $this->menu_one_liner( $post_state );
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
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_NAVIGATION,
			'level'            => Diff_Type_Keys::is_valid_level( $level ) ? $level : Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => $target_ref,
			'target_type_hint' => 'term',
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
				'menu_id'          => $menu_id > 0 ? $menu_id : $target_ref,
				'menu_name_before' => $name_before,
				'menu_name_after'  => $name_after,
				'location_before'  => $location_before,
				'location_after'   => $location_after,
				'items_added'      => $items_added,
				'items_removed'    => $items_removed,
				'items_reordered'  => $items_reordered,
				'labels_changed'   => $labels_changed,
			);
		}

		if ( $no_meaningful ) {
			return Diff_Summary_Result::no_meaningful_diff( $diff, __( 'No meaningful menu changes detected.', 'aio-page-builder' ) );
		}
		return Diff_Summary_Result::with_diff( $diff, __( 'Navigation diff generated.', 'aio-page-builder' ) );
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
	 * @param array<string, mixed> $state state_snapshot or result_snapshot for a menu.
	 * @return string
	 */
	private function menu_one_liner( array $state ): string {
		$name  = isset( $state['name'] ) ? trim( (string) $state['name'] ) : '';
		$loc   = isset( $state['location'] ) ? trim( (string) $state['location'] ) : '';
		$items = isset( $state['items'] ) && is_array( $state['items'] ) ? $state['items'] : array();
		if ( $name === '' ) {
			$name = __( 'Unnamed menu', 'aio-page-builder' );
		}
		$loc_part   = $loc !== '' ? " @ {$loc}" : '';
		$count      = count( $items );
		$count_part = $count > 0 ? ", {$count} items" : '';
		return $name . $loc_part . $count_part;
	}

	/**
	 * @param list<array<string, mixed>> $items
	 * @return list<int>
	 */
	private function menu_item_ids( array $items ): array {
		$ids = array();
		foreach ( $items as $item ) {
			if ( isset( $item['id'] ) && is_numeric( $item['id'] ) ) {
				$ids[] = (int) $item['id'];
			}
		}
		return $ids;
	}

	/**
	 * @param array<string, mixed> $item
	 * @return array<string, string|int>
	 */
	private function item_descriptor( array $item ): array {
		$d = array(
			'title'  => isset( $item['title'] ) ? (string) $item['title'] : '',
			'url'    => isset( $item['url'] ) ? (string) $item['url'] : '',
			'parent' => isset( $item['parent'] ) ? (int) $item['parent'] : 0,
		);
		if ( isset( $item['id'] ) ) {
			$d['item_ref'] = (string) $item['id'];
		}
		return $d;
	}

	/**
	 * @param list<array<string, mixed>> $pre_items
	 * @param list<array<string, mixed>> $post_items
	 * @return list<array<string, string>>
	 */
	private function label_changes( array $pre_items, array $post_items ): array {
		$out       = array();
		$pre_by_id = array();
		foreach ( $pre_items as $item ) {
			if ( isset( $item['id'] ) && is_numeric( $item['id'] ) ) {
				$pre_by_id[ (int) $item['id'] ] = isset( $item['title'] ) ? (string) $item['title'] : '';
			}
		}
		foreach ( $post_items as $item ) {
			$id = isset( $item['id'] ) && is_numeric( $item['id'] ) ? (int) $item['id'] : 0;
			if ( $id > 0 && isset( $pre_by_id[ $id ] ) ) {
				$label_after = isset( $item['title'] ) ? (string) $item['title'] : '';
				if ( $pre_by_id[ $id ] !== $label_after ) {
					$out[] = array(
						'item_ref'     => (string) $id,
						'label_before' => $pre_by_id[ $id ],
						'label_after'  => $label_after,
					);
				}
			}
		}
		return $out;
	}

	/**
	 * @param list<array<string, mixed>> $pre_items
	 * @param list<array<string, mixed>> $post_items
	 * @return bool
	 */
	private function order_changed( array $pre_items, array $post_items ): bool {
		$pre_ids  = $this->menu_item_ids( $pre_items );
		$post_ids = $this->menu_item_ids( $post_items );
		if ( $pre_ids !== $post_ids ) {
			return true;
		}
		$pre_orders = array();
		foreach ( $pre_items as $item ) {
			if ( isset( $item['id'] ) && isset( $item['order'] ) ) {
				$pre_orders[ (int) $item['id'] ] = (int) $item['order'];
			}
		}
		foreach ( $post_items as $item ) {
			$id = isset( $item['id'] ) && is_numeric( $item['id'] ) ? (int) $item['id'] : 0;
			if ( $id > 0 && isset( $item['order'] ) ) {
				$order = (int) $item['order'];
				if ( ( $pre_orders[ $id ] ?? -1 ) !== $order ) {
					return true;
				}
			}
		}
		return false;
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
			'diff_type'        => Diff_Type_Keys::DIFF_TYPE_NAVIGATION,
			'level'            => Diff_Type_Keys::LEVEL_SUMMARY,
			'target_ref'       => $target_ref,
			'target_type_hint' => 'term',
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
		$id  = 'diff-navigation-' . substr( str_replace( array( '-', ' ' ), '', $raw ), 0, 20 );
		return substr( $id, 0, 64 );
	}
}
