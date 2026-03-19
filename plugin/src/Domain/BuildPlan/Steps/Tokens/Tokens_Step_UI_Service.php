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

final class Tokens_Step_UI_Service {

	/** Step index for design tokens in canonical step order. */
	public const STEP_INDEX_DESIGN_TOKENS = 5;

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

	/** @var array<string, array<string, string>>|null */
	private ?array $current_tokens_cache = null;

	public function __construct( Build_Plan_Row_Action_Resolver $row_action_resolver, Global_Style_Settings_Repository $global_style_settings_repository ) {
		$this->row_action_resolver = $row_action_resolver;
		$this->global_style_settings_repository = $global_style_settings_repository;
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
		$steps_raw = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step      = $steps_raw[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return $this->empty_workspace();
		}
		$step_type = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS ) {
			return $this->empty_workspace();
		}

		$items          = $this->eligible_items_from_step( $step );
		$rows           = array();
		$eligible_count = 0;
		foreach ( $items as $item ) {
			$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $item_id === '' ) {
				continue;
			}
			$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
			if ( $status === Build_Plan_Item_Statuses::PENDING ) {
				++$eligible_count;
			}
			$row_actions = $this->row_action_resolver->resolve( $item, $capabilities );
			$rows[]      = array(
				Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
				Step_Item_List_Component::ROW_KEY_STATUS  => $status,
				Step_Item_List_Component::ROW_KEY_STATUS_BADGE => $this->status_to_badge( $status ),
				Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => $this->summary_columns_for_item( $item ),
				Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
				Step_Item_List_Component::ROW_KEY_IS_SELECTED => in_array( $item_id, $selected_item_ids, true ),
			);
		}

		$bulk_states       = $this->bulk_states( $eligible_count, $capabilities, $selected_item_ids, $rows );
		$detail_panel      = $this->build_detail_panel( $items, $selected_item_id, $capabilities );
		$step_messages     = $this->step_messages( count( $rows ), $eligible_count );
		$token_set_summary = $this->build_token_set_summary( $items );

		return array(
			'step_list_rows'         => $rows,
			'column_order'           => self::COLUMN_ORDER,
			'bulk_action_states'     => $bulk_states,
			'detail_panel'           => $detail_panel,
			'step_messages'          => $step_messages,
			'token_set_summary'      => $token_set_summary,
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
		$group = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? $payload['token_group'] : '';
		$name  = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? $payload['token_name'] : '';
		$curr  = $this->current_value_for_token( $group, $name );
		$cols  = array();
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
			$payload                     = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
			$proposed_val                = $payload['proposed_value'] ?? null;
			$proposed_str                = is_scalar( $proposed_val ) ? (string) $proposed_val : \wp_json_encode( $proposed_val );
			$group                        = isset( $payload['token_group'] ) && is_string( $payload['token_group'] ) ? $payload['token_group'] : '';
			$name                         = isset( $payload['token_name'] ) && is_string( $payload['token_name'] ) ? $payload['token_name'] : '';
			$current_str                  = $this->current_value_for_token( $group, $name );
			$has_current                  = $current_str !== '';
			$detail_panel['sections']    = array(
				array(
					'heading'       => \__( 'Token', 'aio-page-builder' ),
					'key'           => 'token',
					'content_lines' => array(
						\__( 'Group:', 'aio-page-builder' ) . ' ' . (string) ( $payload['token_group'] ?? '' ),
						\__( 'Name:', 'aio-page-builder' ) . ' ' . (string) ( $payload['token_name'] ?? '' ),
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
					'heading'       => \__( 'Revert / history', 'aio-page-builder' ),
					'key'           => 'revert_history',
					'content_lines' => array(
						\__( 'After execution, token changes are traceable and reversible via the plan rollback workflow.', 'aio-page-builder' ),
					),
				),
			);
			$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $item, $capabilities );
			break;
		}
		return $detail_panel;
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

	private function bulk_states( int $eligible_count, array $capabilities, array $selected_item_ids, array $rows ): array {
		$selected_count = count( array_intersect( $selected_item_ids, array_column( $rows, 'item_id' ) ) );
		$can_approve    = ! empty( $capabilities['can_approve'] );
		$apply_all_ok   = $can_approve && $eligible_count > 0;
		$apply_sel_ok   = $can_approve && $selected_count > 0;
		$deny_all_ok    = $can_approve && $eligible_count > 0;

		return array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $apply_all_ok,
				'label'          => \__( 'Apply all tokens', 'aio-page-builder' ),
				'count_eligible' => $eligible_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $apply_sel_ok,
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => $selected_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $deny_all_ok,
				'label'          => \__( 'Deny all', 'aio-page-builder' ),
				'count_eligible' => $eligible_count,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => $selected_count > 0,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
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
			'step_list_rows'         => array(),
			'column_order'           => self::COLUMN_ORDER,
			'bulk_action_states'     => array(
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
			),
			'detail_panel'           => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'          => array(),
			'token_set_summary'      => array(
				'groups' => array(),
				'total'  => 0,
			),
		);
	}
}
