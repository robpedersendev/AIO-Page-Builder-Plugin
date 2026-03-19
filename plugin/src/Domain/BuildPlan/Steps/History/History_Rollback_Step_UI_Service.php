<?php
/**
 * Step 8 (logs, history, rollback) workspace (spec §38, Prompt 642).
 *
 * Renders history rows from operational snapshots (v1: page replacement + token changes).
 * Rollback-capable rows show "Request rollback"; eligibility is validated before execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\History;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;

/**
 * Logs/rollback step UI. v1 supports rollback for page replacements and design token changes (Prompt 642).
 */
final class History_Rollback_Step_UI_Service {

	/** Step index for logs/rollback in canonical step order. */
	public const STEP_INDEX_LOGS_ROLLBACK = 8;

	/** Column order for history table (sortable placeholders). */
	public const COLUMN_ORDER = array(
		'event_at',
		'action_type',
		'scope',
		'before_after',
		'rollback_eligible',
	);

	/**
	 * Builds step workspace payload for Step 8 (logs / history / rollback).
	 *
	 * @param array<string, mixed>                                                                                                              $plan_definition Plan root.
	 * @param int                                                                                                                               $step_index Must be 8.
	 * @param array<string, bool>                                                                                                               $capabilities can_approve, can_execute, can_view_artifacts, can_rollback (optional).
	 * @param string|null                                                                                                                       $selected_item_id Item id for detail panel.
	 * @param array<int, string>                                                                                                                $selected_item_ids Unused.
	 * @param array<int, array{post_snapshot_id: string, pre_snapshot_id: string, action_type: string, target_ref: string, created_at: string}> $history_entries Optional rollback-capable entries from snapshot repo (when provided, overrides step items).
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages, history_summary?, rollback_eligibility_placeholder?
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array(),
		array $history_entries = array()
	): array {
		if ( $step_index !== self::STEP_INDEX_LOGS_ROLLBACK ) {
			return $this->empty_workspace();
		}
		$steps_raw = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step      = $steps_raw[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return $this->empty_workspace();
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK ) {
			return $this->empty_workspace();
		}

		$can_rollback = ! empty( $capabilities['can_rollback'] ) || ! empty( $capabilities['can_execute'] );
		$rows         = array();

		if ( ! empty( $history_entries ) ) {
			foreach ( $history_entries as $i => $entry ) {
				$pre_snapshot_id  = $entry['pre_snapshot_id'] ?? '';
				$post_snapshot_id = $entry['post_snapshot_id'] ?? '';
				$action_type      = $entry['action_type'] ?? '';
				$target_ref       = $entry['target_ref'] ?? '';
				$created_at       = $entry['created_at'] ?? '';
				$event_at_display = $created_at !== '' ? \wp_date( 'Y-m-d H:i:s', strtotime( $created_at ) ) : '—';
				$item_id          = 'hist_' . $i . '_' . $post_snapshot_id;
				$row_actions      = array();
				if ( $can_rollback && $pre_snapshot_id !== '' && $post_snapshot_id !== '' ) {
					$row_actions[] = array(
						'action_id' => 'request_rollback',
						'label'     => \__( 'Request rollback', 'aio-page-builder' ),
						'enabled'   => true,
					);
				}
				$rows[] = array(
					Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
					Step_Item_List_Component::ROW_KEY_STATUS => 'completed',
					Step_Item_List_Component::ROW_KEY_STATUS_BADGE => 'completed',
					'pre_snapshot_id'  => $pre_snapshot_id,
					'post_snapshot_id' => $post_snapshot_id,
					Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => array(
						'event_at'          => $event_at_display,
						'action_type'       => $action_type !== '' ? $action_type : '—',
						'scope'             => $target_ref !== '' ? $target_ref : '—',
						'before_after'      => 'Pre/Post',
						'rollback_eligible' => 'yes',
					),
					Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
					Step_Item_List_Component::ROW_KEY_IS_SELECTED => false,
				);
			}
		} else {
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $i => $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$item_id           = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? 'hist_' . $i );
				$payload           = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
				$pre_snapshot_id   = isset( $payload['pre_snapshot_id'] ) ? (string) $payload['pre_snapshot_id'] : '';
				$post_snapshot_id  = isset( $payload['post_snapshot_id'] ) ? (string) $payload['post_snapshot_id'] : '';
				$rollback_eligible = ( $pre_snapshot_id !== '' && $post_snapshot_id !== '' ) ? 'yes' : (string) ( $payload['rollback_eligible'] ?? 'no' );
				$row_actions       = array();
				if ( $can_rollback && $pre_snapshot_id !== '' && $post_snapshot_id !== '' ) {
					$row_actions[] = array(
						'action_id' => 'request_rollback',
						'label'     => \__( 'Request rollback', 'aio-page-builder' ),
						'enabled'   => true,
					);
				}
				$rows[] = array(
					Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
					Step_Item_List_Component::ROW_KEY_STATUS => (string) ( $item['status'] ?? '' ),
					Step_Item_List_Component::ROW_KEY_STATUS_BADGE => 'completed',
					'pre_snapshot_id'  => $pre_snapshot_id,
					'post_snapshot_id' => $post_snapshot_id,
					Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => array(
						'event_at'          => (string) ( $payload['event_at'] ?? '—' ),
						'action_type'       => (string) ( $payload['action_type'] ?? '—' ),
						'scope'             => (string) ( $payload['scope'] ?? '—' ),
						'before_after'      => (string) ( $payload['before_after'] ?? '—' ),
						'rollback_eligible' => $rollback_eligible,
					),
					Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
					Step_Item_List_Component::ROW_KEY_IS_SELECTED => false,
				);
			}
		}
		if ( empty( $rows ) ) {
			$rows = array(
				array(
					Step_Item_List_Component::ROW_KEY_ITEM_ID => 'placeholder_0',
					Step_Item_List_Component::ROW_KEY_STATUS  => '',
					Step_Item_List_Component::ROW_KEY_STATUS_BADGE => '',
					Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => array(
						'event_at'          => '—',
						'action_type'       => \__( 'No history recorded yet (placeholder).', 'aio-page-builder' ),
						'scope'             => '—',
						'before_after'      => '—',
						'rollback_eligible' => '—',
					),
					Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => array(),
					Step_Item_List_Component::ROW_KEY_IS_SELECTED => false,
				),
			);
		}

		$bulk_states                      = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => false,
				'label'          => \__( 'Rollback', 'aio-page-builder' ),
				'count_eligible' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => false,
				'label'          => \__( 'Rollback selected', 'aio-page-builder' ),
				'count_selected' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => false,
				'label'          => \__( 'N/A', 'aio-page-builder' ),
				'count_eligible' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => false,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
			),
		);
		$detail_panel                     = array(
			'item_id'     => $selected_item_id ?? '',
			'sections'    => array(
				array(
					'heading'       => \__( 'Before / after snapshot', 'aio-page-builder' ),
					'key'           => 'snapshot',
					'content_lines' => array( \__( 'Placeholder — no snapshot capture in this step.', 'aio-page-builder' ) ),
				),
				array(
					'heading'       => \__( 'Audit trail', 'aio-page-builder' ),
					'key'           => 'audit',
					'content_lines' => array( \__( 'Immutable audit trail (placeholder).', 'aio-page-builder' ) ),
				),
			),
			'row_actions' => array(),
		);
		$step_messages                    = array(
			array(
				'severity' => 'info',
				'message'  => \__( 'Rollback is supported for page replacements and design token changes (v1). Request rollback from a row when eligible.', 'aio-page-builder' ),
				'level'    => 'step',
			),
		);
		$history_summary                  = array(
			'total_events' => count( $rows ),
			'grouped_by'   => 'action_type',
		);
		$rollback_eligibility_placeholder = array(
			'eligible_count' => 0,
			'can_rollback'   => $can_rollback,
		);

		return array(
			'step_list_rows'                   => $rows,
			'column_order'                     => self::COLUMN_ORDER,
			'bulk_action_states'               => $bulk_states,
			'detail_panel'                     => $detail_panel,
			'step_messages'                    => $step_messages,
			'history_summary'                  => $history_summary,
			'rollback_eligibility_placeholder' => $rollback_eligibility_placeholder,
		);
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'                   => array(),
			'column_order'                     => self::COLUMN_ORDER,
			'bulk_action_states'               => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Rollback', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Rollback selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'N/A', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					'enabled' => false,
					'label'   => \__( 'Clear selection', 'aio-page-builder' ),
				),
			),
			'detail_panel'                     => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'                    => array(),
			'history_summary'                  => array(
				'total_events' => 0,
				'grouped_by'   => '',
			),
			'rollback_eligibility_placeholder' => array(
				'eligible_count' => 0,
				'can_rollback'   => false,
			),
		);
	}
}
