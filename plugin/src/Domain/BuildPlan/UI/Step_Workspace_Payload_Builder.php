<?php
/**
 * Builds step workspace UI payload: step_list_rows, bulk_action_states, detail_panel, step_messages (spec §31.5–31.10).
 *
 * Consumes plan definition and step index; produces payloads for Step_Item_List_Component,
 * Bulk_Action_Bar_Component, Detail_Panel_Component, Step_Message_Component. Step-specific
 * column and section content are pluggable via optional callables; defaults are generic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;

/**
 * Produces step_list_rows, column_order, bulk_action_states, detail_panel (sections + row_actions), step_messages.
 */
final class Step_Workspace_Payload_Builder {

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	/** Default column order when step does not define one. */
	private const DEFAULT_COLUMN_ORDER = array( 'title', 'action_type', 'rationale' );

	public function __construct( Build_Plan_Row_Action_Resolver $row_action_resolver ) {
		$this->row_action_resolver = $row_action_resolver;
	}

	/**
	 * Builds full step workspace payload for an actionable step.
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Step index in steps array.
	 * @param array<string, bool>   $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Item id for detail panel; null if none.
	 * @param array<int, string>    $selected_item_ids Item ids for bulk "apply to selected".
	 * @return array<string, mixed> Keys: step_list_rows, column_order, bulk_action_states, detail_panel, step_messages.
	 */
	public function build(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		$steps_raw = isset( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $plan_definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $plan_definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step = $steps_raw[ $step_index ] ?? null;
		if ( ! is_array( $step ) ) {
			return $this->empty_workspace();
		}

		$step_type  = (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' );
		$items      = $this->actionable_items_from_step( $step );
		$column_order = self::DEFAULT_COLUMN_ORDER;

		$rows = array();
		$eligible_count = 0;
		foreach ( $items as $item ) {
			$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $item_id === '' ) {
				continue;
			}
			$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
			if ( ! Build_Plan_Item_Statuses::is_terminal( $status ) && $status === Build_Plan_Item_Statuses::PENDING ) {
				++$eligible_count;
			}
			$row_actions = $this->row_action_resolver->resolve( $item, $capabilities );
			$rows[] = array(
				'item_id'          => $item_id,
				'status'           => $status,
				'status_badge'     => $this->item_status_to_badge( $status ),
				'summary_columns'  => $this->summary_columns_for_item( $item, $step_type ),
				'row_actions'      => $row_actions,
				'is_selected'      => in_array( $item_id, $selected_item_ids, true ),
			);
		}

		$selected_count = count( array_intersect( $selected_item_ids, array_column( $rows, 'item_id' ) ) );
		$bulk_states = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $eligible_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Apply to all eligible', 'aio-page-builder' ),
				'count_eligible' => $eligible_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $selected_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => $selected_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $eligible_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Deny all eligible', 'aio-page-builder' ),
				'count_eligible' => $eligible_count,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => $selected_count > 0,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
			),
		);

		$detail_panel = array(
			'item_id'     => $selected_item_id ?? '',
			'sections'    => array(),
			'row_actions' => array(),
		);
		if ( $selected_item_id !== null && $selected_item_id !== '' ) {
			$selected_item = null;
			foreach ( $items as $item ) {
				if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ) === $selected_item_id ) {
					$selected_item = $item;
					break;
				}
			}
			if ( $selected_item !== null ) {
				$detail_panel['sections']    = $this->detail_sections_for_item( $selected_item );
				$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $selected_item, $capabilities );
			}
		}

		$step_messages = $this->step_messages_for_count( count( $rows ), $eligible_count, $step_type );

		return array(
			'step_list_rows'     => $rows,
			'column_order'       => $column_order,
			'bulk_action_states' => $bulk_states,
			'detail_panel'       => $detail_panel,
			'step_messages'      => $step_messages,
		);
	}

	/**
	 * Returns items that are actionable (excludes overview/hierarchy/confirmation notes).
	 *
	 * @param array<string, mixed> $step
	 * @return array<int, array<string, mixed>>
	 */
	private function actionable_items_from_step( array $step ): array {
		$items_raw = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$skip_types = array(
			Build_Plan_Item_Schema::ITEM_TYPE_OVERVIEW_NOTE,
			Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_NOTE,
			Build_Plan_Item_Schema::ITEM_TYPE_CONFIRMATION,
		);
		$out = array();
		foreach ( $items_raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			if ( in_array( $item_type, $skip_types, true ) ) {
				continue;
			}
			$out[] = $item;
		}
		return $out;
	}

	private function item_status_to_badge( string $status ): string {
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

	/**
	 * Builds summary_columns for one item. Generic: pulls from payload keys matching column names.
	 *
	 * @param array<string, mixed> $item
	 * @param string               $step_type
	 * @return array<string, string>
	 */
	private function summary_columns_for_item( array $item, string $step_type ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$columns = array();
		foreach ( self::DEFAULT_COLUMN_ORDER as $key ) {
			$val = $payload[ $key ] ?? $item[ $key ] ?? '';
			$columns[ $key ] = is_string( $val ) ? $val : (string) \wp_json_encode( $val );
		}
		if ( trim( implode( '', $columns ) ) === '' ) {
			$columns['title'] = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
		}
		return $columns;
	}

	/**
	 * Builds detail_panel sections for one item. Generic: heading + content from payload.
	 *
	 * @param array<string, mixed> $item
	 * @return array<int, array<string, mixed>>
	 */
	private function detail_sections_for_item( array $item ): array {
		$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
			? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
			: array();
		$sections = array();
		$sections[] = array(
			'heading' => \__( 'Details', 'aio-page-builder' ),
			'key'     => 'details',
			'content' => '<dl class="aio-detail-dl">' . $this->payload_to_dl( $payload ) . '</dl>',
		);
		$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
		$status  = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
		$sections[] = array(
			'heading' => \__( 'Status', 'aio-page-builder' ),
			'key'     => 'status',
			'content_lines' => array( $item_id, $status ),
		);
		return $sections;
	}

	private function payload_to_dl( array $payload ): string {
		$out = '';
		foreach ( $payload as $k => $v ) {
			if ( is_array( $v ) || is_object( $v ) ) {
				$v = \wp_json_encode( $v );
			}
			$out .= '<dt>' . \esc_html( (string) $k ) . '</dt><dd>' . \esc_html( (string) $v ) . '</dd>';
		}
		return $out;
	}

	private function step_messages_for_count( int $total, int $eligible, string $step_type ): array {
		$messages = array();
		if ( $total === 0 ) {
			$messages[] = array(
				'severity' => 'info',
				'message'  => \__( 'No recommendations were generated for this step.', 'aio-page-builder' ),
				'level'    => 'step',
			);
		} elseif ( $eligible === 0 ) {
			$messages[] = array(
				'severity' => 'success',
				'message'  => \__( 'All recommendations in this step have already been resolved.', 'aio-page-builder' ),
				'level'    => 'step',
			);
		} else {
			$messages[] = array(
				'severity' => 'info',
				'message'  => sprintf( \_n( '%d item pending review.', '%d items pending review.', $eligible, 'aio-page-builder' ), $eligible ),
				'level'    => 'step',
			);
		}
		return $messages;
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'     => array(),
			'column_order'       => self::DEFAULT_COLUMN_ORDER,
			'bulk_action_states' => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array( 'enabled' => false, 'label' => \__( 'Apply to all eligible', 'aio-page-builder' ), 'count_eligible' => 0 ),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array( 'enabled' => false, 'label' => \__( 'Apply to selected', 'aio-page-builder' ), 'count_selected' => 0 ),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array( 'enabled' => false, 'label' => \__( 'Deny all eligible', 'aio-page-builder' ), 'count_eligible' => 0 ),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array( 'enabled' => false, 'label' => \__( 'Clear selection', 'aio-page-builder' ) ),
			),
			'detail_panel'       => array( 'item_id' => '', 'sections' => array(), 'row_actions' => array() ),
			'step_messages'      => array(),
		);
	}
}
