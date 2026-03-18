<?php
/**
 * Step 6 (review, publish, finalization) workspace shell (spec §37, Prompt 076).
 *
 * Renders finalization queue buckets and copy that state publish/preview/conflict reporting
 * are not available in this version. No publish execution.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Finalization;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;

/**
 * Shell-only UI for confirmation (finalization) step. No publish/swap execution.
 */
final class Finalization_Step_UI_Service {

	/** Step index for confirmation (finalization) in canonical step order. */
	public const STEP_INDEX_CONFIRMATION = 7;

	/**
	 * Builds step workspace payload for Step 6 (finalization).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 7.
	 * @param array<string, bool>  $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Unused; detail may show queue summary.
	 * @param array<int, string>   $selected_item_ids Unused.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages, finalization_buckets?, conflict_summary_placeholder?, preview_link_placeholder?
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $step_index !== self::STEP_INDEX_CONFIRMATION ) {
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
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_CONFIRMATION ) {
			return $this->empty_workspace();
		}

		$finalization_buckets         = array(
			'publish_ready' => 0,
			'blocked'       => 0,
			'failed'        => 0,
			'deferred'      => 0,
		);
		$conflict_summary_placeholder = array(
			'count'    => 0,
			'messages' => array(),
		);
		$preview_link_placeholder     = array(
			'url'   => '',
			'label' => \__( 'Preview not available in this version', 'aio-page-builder' ),
		);
		$step_list_rows               = array();
		$column_order                 = array( 'bucket', 'count', 'status' );
		$bulk_states                  = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => false,
				'label'          => \__( 'Publish all', 'aio-page-builder' ),
				'count_eligible' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => false,
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => false,
				'label'          => \__( 'Cancel finalization', 'aio-page-builder' ),
				'count_eligible' => 0,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => false,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
			),
		);
		$detail_panel                 = array(
			'item_id'     => '',
			'sections'    => array(
				array(
					'heading'       => \__( 'Finalization queue', 'aio-page-builder' ),
					'key'           => 'queue',
					'content_lines' => array(
						\__( 'Finalization queue and publish actions are not available in this version.', 'aio-page-builder' ),
						\__( 'Blocked: 0', 'aio-page-builder' ),
						\__( 'Failed: 0', 'aio-page-builder' ),
						\__( 'Deferred: 0', 'aio-page-builder' ),
					),
				),
				array(
					'heading'       => \__( 'Conflicts', 'aio-page-builder' ),
					'key'           => 'conflicts',
					'content_lines' => array( \__( 'Conflict reporting is not available in this version.', 'aio-page-builder' ) ),
				),
			),
			'row_actions' => array(),
		);
		$step_messages                = array(
			array(
				'severity' => 'info',
				'message'  => \__( 'Review approved items and confirm when ready. Execution is not performed in this step.', 'aio-page-builder' ),
				'level'    => 'step',
			),
		);

		$plan_status          = (string) ( $plan_definition[ Build_Plan_Schema::KEY_STATUS ] ?? '' );
		$run_completion_state = '';
		$finalization_summary = null;
		if ( $plan_status === Build_Plan_Schema::STATUS_COMPLETED ) {
			$run_completion_state = isset( $plan_definition['run_completion_state'] ) && is_string( $plan_definition['run_completion_state'] ) ? $plan_definition['run_completion_state'] : '';
			$finalization_summary = isset( $plan_definition['finalization_summary'] ) && is_array( $plan_definition['finalization_summary'] ) ? $plan_definition['finalization_summary'] : ( isset( $plan_definition['completion_summary'] ) && is_array( $plan_definition['completion_summary'] ) ? $plan_definition['completion_summary'] : null );
		}

		return array(
			'step_list_rows'               => $step_list_rows,
			'column_order'                 => $column_order,
			'bulk_action_states'           => $bulk_states,
			'detail_panel'                 => $detail_panel,
			'step_messages'                => $step_messages,
			'finalization_buckets'         => $finalization_buckets,
			'conflict_summary_placeholder' => $conflict_summary_placeholder,
			'preview_link_placeholder'     => $preview_link_placeholder,
			'run_completion_state'         => $run_completion_state,
			'finalization_summary'         => $finalization_summary,
		);
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'               => array(),
			'column_order'                 => array(),
			'bulk_action_states'           => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Publish all', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Cancel finalization', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					'enabled' => false,
					'label'   => \__( 'Clear selection', 'aio-page-builder' ),
				),
			),
			'detail_panel'                 => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'                => array(),
			'finalization_buckets'         => array(
				'publish_ready' => 0,
				'blocked'       => 0,
				'failed'        => 0,
				'deferred'      => 0,
			),
			'conflict_summary_placeholder' => array(
				'count'    => 0,
				'messages' => array(),
			),
			'preview_link_placeholder'     => array(
				'url'   => '',
				'label' => \__( 'Preview not available in this version', 'aio-page-builder' ),
			),
			'run_completion_state'         => '',
			'finalization_summary'         => null,
		);
	}
}
