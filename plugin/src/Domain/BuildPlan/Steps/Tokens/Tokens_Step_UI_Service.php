<?php
/**
 * Step 4 (design tokens) workspace UI (spec §35, Prompt 076).
 *
 * Renders token recommendation rows with current/proposed values and review controls.
 * Token application happens via the execution queue for approved items.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Tokens;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\Styling\Global_Style_Settings_Repository;
use AIOPageBuilder\Domain\Rollback\Diffs\Diff_Type_Keys;
use AIOPageBuilder\Domain\Rollback\Diffs\Token_Diff_Summarizer;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;

final class Tokens_Step_UI_Service {

	/** Step index for design tokens in canonical step order. */
	public const STEP_INDEX_DESIGN_TOKENS = 5;

	/** Bulk execute-all control key for token execution. */
	public const BULK_CONTROL_EXECUTE_ALL_REMAINING = 'execute_to_all_remaining';

	/** Bulk execute-selected control key for token execution. */
	public const BULK_CONTROL_EXECUTE_SELECTED = 'execute_to_selected';

	/** Column order per spec §35. */
	public const COLUMN_ORDER = array(
		'token_group',
		'token_name',
		'current_value',
		'proposed_value',
		'confidence',
	);

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	/** @var Global_Style_Settings_Repository */
	private $global_style_settings_repository;

	/** @var Operational_Snapshot_Repository_Interface|null */
	private ?Operational_Snapshot_Repository_Interface $operational_snapshot_repository;

	/** @var Token_Diff_Summarizer|null */
	private ?Token_Diff_Summarizer $token_diff_summarizer;

	/** @var string|null Internal build-plan key for lookup of rollback/diff state. */
	private ?string $current_plan_id = null;

	/** @var array<string, array<string, string>>|null */
	private ?array $current_tokens_cache = null;

	public function __construct(
		Build_Plan_Row_Action_Resolver $row_action_resolver,
		Global_Style_Settings_Repository $global_style_settings_repository,
		?Operational_Snapshot_Repository_Interface $operational_snapshot_repository = null,
		?Token_Diff_Summarizer $token_diff_summarizer = null
	) {
		$this->row_action_resolver              = $row_action_resolver;
		$this->global_style_settings_repository = $global_style_settings_repository;
		$this->operational_snapshot_repository  = $operational_snapshot_repository;
		$this->token_diff_summarizer            = $token_diff_summarizer;
	}

	/**
	 * Builds step workspace payload for Step 4 (design tokens).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 5.
	 * @param array<string, bool>  $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Item id for detail panel.
	 * @param array<int, string>   $selected_item_ids Item ids for bulk selection.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages, token_set_summary?
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $step_index !== self::STEP_INDEX_DESIGN_TOKENS ) {
			return $this->empty_workspace();
		}
		$this->current_plan_id = isset( $plan_definition[ Build_Plan_Schema::KEY_PLAN_ID ] ) && is_string( $plan_definition[ Build_Plan_Schema::KEY_PLAN_ID ] )
			? $plan_definition[ Build_Plan_Schema::KEY_PLAN_ID ]
			: null;
		$steps_raw             = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step                  = $steps_raw[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return $this->empty_workspace();
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS ) {
			return $this->empty_workspace();
		}

		$items                   = $this->eligible_items_from_step( $step );
		$rows                    = array();
		$pending_count           = 0;
		$approved_count          = 0;
		$selected_pending_count  = 0;
		$selected_approved_count = 0;
		$selected_any_count      = 0;
		foreach ( $items as $item ) {
			$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $item_id === '' ) {
				continue;
			}
			$status      = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
			$is_selected = in_array( $item_id, $selected_item_ids, true );
			if ( $status === Build_Plan_Item_Statuses::PENDING ) {
				++$pending_count;
				if ( $is_selected ) {
					++$selected_pending_count;
					++$selected_any_count;
				}
			} elseif ( $status === Build_Plan_Item_Statuses::APPROVED ) {
				++$approved_count;
				if ( $is_selected ) {
					++$selected_approved_count;
					++$selected_any_count;
				}
			} elseif ( $is_selected ) {
				++$selected_any_count;
			}
			$row_actions = $this->row_action_resolver->resolve( $item, $capabilities );
			$rows[]      = array(
				Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
				Step_Item_List_Component::ROW_KEY_STATUS  => $status,
				Step_Item_List_Component::ROW_KEY_STATUS_BADGE => $this->status_to_badge( $status ),
				Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => $this->summary_columns_for_item( $item ),
				Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
				Step_Item_List_Component::ROW_KEY_IS_SELECTED => $is_selected,
			);
		}

		$bulk_states       = $this->bulk_states( $pending_count, $approved_count, $selected_pending_count, $selected_approved_count, $selected_any_count, $capabilities );
		$detail_panel      = $this->build_detail_panel( $items, $selected_item_id, $capabilities );
		$step_messages     = $this->step_messages( count( $rows ), $pending_count );
		$token_set_summary = $this->build_token_set_summary( $items );

		return array(
			'step_list_rows'     => $rows,
			'column_order'       => self::COLUMN_ORDER,
			'bulk_action_states' => $bulk_states,
			'detail_panel'       => $detail_panel,
			'step_messages'      => $step_messages,
			'token_set_summary'  => $token_set_summary,
		);
	}

	private function eligible_items_from_step( array $step ): array {
		$items_raw = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$out       = array();
		foreach ( $items_raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN ) {
				continue;
			}
			$out[] = $item;
		}
		return $out;
	}

	private function summary_columns_for_item( array $item ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$group   = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? $payload['token_group'] : '';
		$name    = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? $payload['token_name'] : '';
		$curr    = $this->current_value_for_token( $group, $name );
		$cols    = array();
		foreach ( self::COLUMN_ORDER as $key ) {
			if ( $key === 'current_value' ) {
				$cols[ $key ] = $curr !== '' ? $curr : '—';
				continue;
			}
			if ( $key === 'proposed_value' ) {
				$val          = $payload['proposed_value'] ?? '';
				$cols[ $key ] = is_string( $val ) ? $val : (string) \wp_json_encode( $val );
				continue;
			}
			$val          = $payload[ $key ] ?? '';
			$cols[ $key ] = is_string( $val ) ? $val : (string) \wp_json_encode( $val );
		}
		return $cols;
	}

	private function build_detail_panel( array $items, ?string $selected_item_id, array $capabilities ): array {
		$detail_panel = array(
			'item_id'     => $selected_item_id ?? '',
			'sections'    => array(),
			'row_actions' => array(),
		);
		if ( $selected_item_id === null || $selected_item_id === '' ) {
			return $detail_panel;
		}
		foreach ( $items as $item ) {
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) !== $selected_item_id ) {
				continue;
			}
			$status                = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
			$payload               = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
			$proposed_val          = $payload['proposed_value'] ?? null;
			$proposed_str          = is_scalar( $proposed_val ) ? (string) $proposed_val : \wp_json_encode( $proposed_val );
			$group                 = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? $payload['token_group'] : '';
			$name                  = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? $payload['token_name'] : '';
			$token_set_ref         = ( $group !== '' && $name !== '' ) ? $group . ':' . $name : '';
			$current_str           = $this->current_value_for_token( $group, $name );
			$has_current           = $current_str !== '';
			$rollback_info         = $this->rollback_info_for_token_set_ref( $token_set_ref );
			$rollback_eligible     = ! empty( $rollback_info['rollback_eligible'] );
			$pre_snapshot_id       = (string) ( $rollback_info['pre_snapshot_id'] ?? '' );
			$post_snapshot_id      = (string) ( $rollback_info['post_snapshot_id'] ?? '' );
			$diff_payload          = isset( $rollback_info['diff_payload'] ) && is_array( $rollback_info['diff_payload'] ) ? $rollback_info['diff_payload'] : array();
			$diff_id               = (string) ( $diff_payload['diff_id'] ?? '' );
			$can_execute           = ! empty( $capabilities['can_execute'] );
			$execute_enabled       = $can_execute && $status === Build_Plan_Item_Statuses::APPROVED && Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::IN_PROGRESS );
			$retry_enabled         = $can_execute && $status === Build_Plan_Item_Statuses::FAILED && Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::FAILED, Build_Plan_Item_Statuses::IN_PROGRESS );
			$execution_state_lines = array();
			if ( $status === Build_Plan_Item_Statuses::PENDING ) {
				$execution_state_lines[] = \__( 'Pending review; execution is disabled until approved.', 'aio-page-builder' );
			} elseif ( $status === Build_Plan_Item_Statuses::APPROVED ) {
				$execution_state_lines[] = $execute_enabled ? \__( 'Approved and ready for execution.', 'aio-page-builder' ) : \__( 'Approved but execution is disabled by permissions.', 'aio-page-builder' );
			} elseif ( $status === Build_Plan_Item_Statuses::IN_PROGRESS ) {
				$execution_state_lines[] = \__( 'Execution is in progress.', 'aio-page-builder' );
			} elseif ( $status === Build_Plan_Item_Statuses::COMPLETED ) {
				$execution_state_lines[] = \__( 'Execution completed.', 'aio-page-builder' );
			} elseif ( $status === Build_Plan_Item_Statuses::FAILED ) {
				$execution_state_lines[] = $retry_enabled ? \__( 'Execution failed; retry is available.', 'aio-page-builder' ) : \__( 'Execution failed; retry is disabled by state or permissions.', 'aio-page-builder' );
			} else {
				$execution_state_lines[] = \__( 'Execution is disabled in this state.', 'aio-page-builder' );
			}
			$execution_state_lines[] = \__( 'Rollback eligible:', 'aio-page-builder' ) . ' ' . ( $rollback_eligible ? 'yes' : 'no' );
			if ( $rollback_eligible && $pre_snapshot_id !== '' ) {
				$execution_state_lines[] = \__( 'Pre snapshot:', 'aio-page-builder' ) . ' ' . substr( $pre_snapshot_id, 0, 64 );
			}
			if ( $rollback_eligible && $post_snapshot_id !== '' ) {
				$execution_state_lines[] = \__( 'Post snapshot:', 'aio-page-builder' ) . ' ' . substr( $post_snapshot_id, 0, 64 );
			}
			if ( $rollback_eligible && $diff_id !== '' ) {
				$execution_state_lines[] = \__( 'Diff reference:', 'aio-page-builder' ) . ' ' . substr( $diff_id, 0, 64 );
			}
			$detail_panel['sections']    = array(
				array(
					'heading'       => \__( 'Token', 'aio-page-builder' ),
					'key'           => 'token',
					'content_lines' => array(
						\__( 'Group:', 'aio-page-builder' ) . ' ' . (string) ( $payload['token_group'] ?? '' ),
						\__( 'Name:', 'aio-page-builder' ) . ' ' . (string) ( $payload['token_name'] ?? '' ),
						\__( 'Token set:', 'aio-page-builder' ) . ' ' . (string) $token_set_ref,
						\__( 'Proposed value:', 'aio-page-builder' ) . ' ' . $proposed_str,
					),
				),
				array(
					'heading'       => \__( 'Current / proposed values', 'aio-page-builder' ),
					'key'           => 'values',
					'content_lines' => array(
						\__( 'Current value:', 'aio-page-builder' ) . ' ' . ( $has_current ? (string) $current_str : '—' ),
						\__( 'Proposed value:', 'aio-page-builder' ) . ' ' . $proposed_str,
					),
				),
				array(
					'heading'       => \__( 'Rationale', 'aio-page-builder' ),
					'key'           => 'rationale',
					'content_lines' => array( (string) ( $payload['rationale'] ?? '—' ) ),
				),
				array(
					'heading'       => \__( 'Execution and rollback', 'aio-page-builder' ),
					'key'           => 'execution_rollback',
					'content_lines' => array(
						...$execution_state_lines,
					),
				),
			);
			$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $item, $capabilities );
			break;
		}
		return $detail_panel;
	}

	/**
	 * Returns truthful rollback availability and diff reference for a token set.
	 *
	 * @param string $token_set_ref token_group:token_name
	 * @return array<string, mixed> Empty when rollback/diff dependencies are unavailable.
	 */
	private function rollback_info_for_token_set_ref( string $token_set_ref ): array {
		if ( $token_set_ref === '' || $this->operational_snapshot_repository === null || $this->current_plan_id === null ) {
			return array(
				'rollback_eligible' => false,
			);
		}
		$entries = $this->operational_snapshot_repository->list_rollback_entries_for_plan( $this->current_plan_id );
		if ( ! is_array( $entries ) || empty( $entries ) ) {
			return array(
				'rollback_eligible' => false,
			);
		}
		$match = null;
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$target_ref = isset( $entry['target_ref'] ) ? (string) $entry['target_ref'] : '';
			if ( $target_ref === $token_set_ref ) {
				$match = $entry;
				break;
			}
		}
		if ( $match === null ) {
			return array(
				'rollback_eligible' => false,
			);
		}
		$pre_snapshot_id  = isset( $match['pre_snapshot_id'] ) ? (string) $match['pre_snapshot_id'] : '';
		$post_snapshot_id = isset( $match['post_snapshot_id'] ) ? (string) $match['post_snapshot_id'] : '';

		$diff_payload = array();
		if ( $this->token_diff_summarizer !== null && $pre_snapshot_id !== '' && $post_snapshot_id !== '' ) {
			$pre_snapshot  = $this->operational_snapshot_repository->get_by_id( $pre_snapshot_id );
			$post_snapshot = $this->operational_snapshot_repository->get_by_id( $post_snapshot_id );
			if ( is_array( $pre_snapshot ) && is_array( $post_snapshot ) ) {
				$diff_result = $this->token_diff_summarizer->summarize( $pre_snapshot, $post_snapshot, Diff_Type_Keys::LEVEL_SUMMARY );
				if ( $diff_result->is_success() ) {
					$diff_payload = $diff_result->to_array();
				}
			}
		}

		return array(
			'rollback_eligible' => true,
			'pre_snapshot_id'   => $pre_snapshot_id,
			'post_snapshot_id'  => $post_snapshot_id,
			'diff_payload'      => $diff_payload,
		);
	}

	private function build_token_set_summary( array $items ): array {
		$groups = array();
		foreach ( $items as $item ) {
			$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
			$g       = (string) ( $payload['token_group'] ?? '' );
			if ( $g !== '' ) {
				$groups[ $g ] = ( $groups[ $g ] ?? 0 ) + 1;
			}
		}
		return array(
			'groups' => $groups,
			'total'  => count( $items ),
		);
	}

	private function bulk_states(
		int $pending_count,
		int $approved_count,
		int $selected_pending_count,
		int $selected_approved_count,
		int $selected_any_count,
		array $capabilities
	): array {
		$can_approve = ! empty( $capabilities['can_approve'] );
		$can_execute = ! empty( $capabilities['can_execute'] );

		$apply_all_ok = $can_approve && $pending_count > 0;
		$apply_sel_ok = $can_approve && $selected_pending_count > 0;
		$deny_all_ok  = $can_approve && $pending_count > 0;

		$exec_all_ok = $can_execute && $approved_count > 0;
		$exec_sel_ok = $can_execute && $selected_approved_count > 0;

		return array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $apply_all_ok,
				'label'          => \__( 'Apply all tokens', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $apply_sel_ok,
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => $selected_pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $deny_all_ok,
				'label'          => \__( 'Deny all', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => $selected_any_count > 0,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
			),
			self::BULK_CONTROL_EXECUTE_ALL_REMAINING    => array(
				'enabled'        => $exec_all_ok,
				'label'          => \__( 'Execute all remaining', 'aio-page-builder' ),
				'count_eligible' => $approved_count,
			),
			self::BULK_CONTROL_EXECUTE_SELECTED         => array(
				'enabled'        => $exec_sel_ok,
				'label'          => \__( 'Execute selected', 'aio-page-builder' ),
				'count_selected' => $selected_approved_count,
			),
		);
	}

	/**
	 * Returns current applied token value for a single group/name pair.
	 *
	 * @param string $group Token group key.
	 * @param string $name Token name key.
	 * @return string Current value or empty string when unknown.
	 */
	private function current_value_for_token( string $group, string $name ): string {
		if ( $group === '' || $name === '' ) {
			return '';
		}
		if ( $this->current_tokens_cache === null ) {
			$this->current_tokens_cache = $this->global_style_settings_repository->get_global_tokens();
		}
		if ( ! isset( $this->current_tokens_cache[ $group ] ) || ! is_array( $this->current_tokens_cache[ $group ] ) ) {
			return '';
		}
		$value = $this->current_tokens_cache[ $group ][ $name ] ?? '';
		return is_string( $value ) ? $value : '';
	}

	private function status_to_badge( string $status ): string {
		$map = array(
			Build_Plan_Item_Statuses::PENDING     => 'pending',
			Build_Plan_Item_Statuses::APPROVED    => 'approved',
			Build_Plan_Item_Statuses::REJECTED    => 'rejected',
			Build_Plan_Item_Statuses::SKIPPED     => 'skipped',
			Build_Plan_Item_Statuses::IN_PROGRESS => 'in_progress',
			Build_Plan_Item_Statuses::COMPLETED   => 'completed',
			Build_Plan_Item_Statuses::FAILED      => 'failed',
		);
		return $map[ $status ] ?? $status;
	}

	private function step_messages( int $total, int $eligible ): array {
		if ( $total === 0 ) {
			return array(
				array(
					'severity' => 'info',
					'message'  => \__( 'No design token recommendations for this plan.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		if ( $eligible === 0 ) {
			return array(
				array(
					'severity' => 'success',
					'message'  => \__( 'All token recommendations have been reviewed.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		return array(
			array(
				'severity' => 'info',
				'message'  => sprintf( \_n( '%d token pending review.', '%d tokens pending review.', $eligible, 'aio-page-builder' ), $eligible ),
				'level'    => 'step',
			),
		);
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'     => array(),
			'column_order'       => self::COLUMN_ORDER,
			'bulk_action_states' => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Apply all tokens', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Deny all', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					'enabled' => false,
					'label'   => \__( 'Clear selection', 'aio-page-builder' ),
				),
				self::BULK_CONTROL_EXECUTE_ALL_REMAINING => array(
					'enabled'        => false,
					'label'          => \__( 'Execute all remaining', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				self::BULK_CONTROL_EXECUTE_SELECTED      => array(
					'enabled'        => false,
					'label'          => \__( 'Execute selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
			),
			'detail_panel'       => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'      => array(),
			'token_set_summary'  => array(
				'groups' => array(),
				'total'  => 0,
			),
		);
	}
}
