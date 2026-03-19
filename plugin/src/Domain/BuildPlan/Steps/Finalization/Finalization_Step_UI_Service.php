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
 * Finalization step UI: surfaces publish readiness, conflicts, and completion summary (spec §37).
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

		$counts         = $this->count_items_by_outcome( $plan_definition );
		$conflicts      = $this->detect_conflicts( $plan_definition );
		$conflict_count = count( $conflicts );

		$completion_summary = null;
		if ( isset( $plan_definition['completion_summary'] ) && is_array( $plan_definition['completion_summary'] ) ) {
			$completion_summary = $plan_definition['completion_summary'];
		}

		$finalization_buckets = array(
			'publish_ready' => (int) ( $completion_summary['published'] ?? 0 ),
			'blocked'       => (int) ( $completion_summary['blocked'] ?? $conflict_count ),
			'failed'        => (int) ( $completion_summary['failed'] ?? $counts['failed'] ),
			'deferred'      => (int) ( $counts['pending'] + $counts['approved'] ),
		);
		$conflict_summary = array(
			'count'    => $conflict_count,
			'messages' => array_values(
				array_slice(
					array_map(
						static function ( array $c ): string {
							$msg = isset( $c['message'] ) ? (string) $c['message'] : '';
							if ( $msg !== '' ) {
								return $msg;
							}
							$type = isset( $c['type'] ) ? (string) $c['type'] : 'conflict';
							return $type;
						},
						$conflicts
					),
					0,
					5
				)
			),
		);
		$preview_link = array(
			'url'   => '',
			'label' => \__( 'View logs & rollback history', 'aio-page-builder' ),
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
						sprintf( \__( 'Publish-ready: %d', 'aio-page-builder' ), (int) $finalization_buckets['publish_ready'] ),
						sprintf( \__( 'Blocked: %d', 'aio-page-builder' ), (int) $finalization_buckets['blocked'] ),
						sprintf( \__( 'Failed: %d', 'aio-page-builder' ), (int) $finalization_buckets['failed'] ),
						sprintf( \__( 'Deferred: %d', 'aio-page-builder' ), (int) $finalization_buckets['deferred'] ),
					),
				),
				array(
					'heading'       => \__( 'Conflicts', 'aio-page-builder' ),
					'key'           => 'conflicts',
					'content_lines' => $conflict_count > 0
						? array_merge(
							array( sprintf( \__( 'Conflicts detected: %d', 'aio-page-builder' ), (int) $conflict_count ) ),
							array_slice( $conflict_summary['messages'], 0, 3 )
						)
						: array( \__( 'No conflicts detected in the current plan state.', 'aio-page-builder' ) ),
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
			'conflict_summary'             => $conflict_summary,
			'preview_link'                 => $preview_link,
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
			'conflict_summary'             => array(
				'count'    => 0,
				'messages' => array(),
			),
			'preview_link'                 => array(
				'url'   => '',
				'label' => \__( 'View logs & rollback history', 'aio-page-builder' ),
			),
			'run_completion_state'         => '',
			'finalization_summary'         => null,
		);
	}

	/**
	 * @param array<string, mixed> $definition
	 * @return array{pending: int, approved: int, completed: int, rejected: int, failed: int}
	 */
	private function count_items_by_outcome( array $definition ): array {
		$out   = array(
			'pending'   => 0,
			'approved'  => 0,
			'completed' => 0,
			'rejected'  => 0,
			'failed'    => 0,
		);
		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				if ( $status === 'completed' ) {
					++$out['completed'];
				} elseif ( $status === 'rejected' ) {
					++$out['rejected'];
				} elseif ( $status === 'failed' ) {
					++$out['failed'];
				} elseif ( $status === 'approved' || $status === 'in_progress' ) {
					++$out['approved'];
				} else {
					++$out['pending'];
				}
			}
		}
		return $out;
	}

	/**
	 * Detects conflicts that block finalization (slug collisions across completed items).
	 *
	 * @param array<string, mixed> $definition
	 * @return array<int, array<string, mixed>>
	 */
	private function detect_conflicts( array $definition ): array {
		$conflicts = array();
		$slugs     = array();
		$steps     = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		foreach ( $steps as $step ) {
			if ( ! is_array( $step ) ) {
				continue;
			}
			$items = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
				? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
				: array();
			foreach ( $items as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}
				$status = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? '' );
				if ( $status !== 'completed' ) {
					continue;
				}
				$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
				$slug    = isset( $payload['page_slug_candidate'] ) && is_string( $payload['page_slug_candidate'] ) ? trim( $payload['page_slug_candidate'] ) : '';
				if ( $slug === '' && isset( $payload['proposed_slug'] ) && is_string( $payload['proposed_slug'] ) ) {
					$slug = trim( $payload['proposed_slug'] );
				}
				if ( $slug === '' && isset( $payload['target_slug'] ) && is_string( $payload['target_slug'] ) ) {
					$slug = trim( $payload['target_slug'] );
				}
				if ( $slug === '' ) {
					continue;
				}
				if ( isset( $slugs[ $slug ] ) ) {
					$conflicts[] = array(
						'type'    => 'slug_conflict',
						'slug'    => $slug,
						'message' => __( 'Duplicate slug in plan.', 'aio-page-builder' ),
					);
				}
				$slugs[ $slug ] = true;
			}
		}
		return $conflicts;
	}
}
