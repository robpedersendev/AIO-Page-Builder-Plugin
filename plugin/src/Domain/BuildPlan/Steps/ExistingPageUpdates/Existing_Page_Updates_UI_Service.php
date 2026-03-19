<?php
/**
 * Step 1 (existing page updates) UI payload builder (spec §32.1–32.9).
 *
 * Filters to eligible existing_page_change items, builds row summaries and detail
 * via Existing_Page_Update_Detail_Builder, bulk eligibility, and snapshot_required markers.
 * Does not execute page mutations.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Existing_Page_Template_Change_Builder;

/**
 * Produces step_list_rows, column_order, bulk_action_states, detail_panel, step_messages for Step 1 only.
 */
final class Existing_Page_Updates_UI_Service {

	/** Step 1 column order per spec §32.3, Prompt 193: add template_links for compare/detail. */
	public const COLUMN_ORDER = array(
		'current_page_title',
		'current_page_url',
		'action',
		'target_template',
		'risk_level',
	);

	/** Minimum confidence for item to appear (spec §32.2: sufficient confidence). */
	private const MIN_CONFIDENCE_EXCLUDE = 'low';

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	/** @var Existing_Page_Update_Detail_Builder */
	private $detail_builder;

	/** @var Existing_Page_Update_Bulk_Action_Service */
	private $bulk_action_service;

	/** @var Existing_Page_Template_Change_Builder|null */
	private $template_change_builder;

	public function __construct(
		Build_Plan_Row_Action_Resolver $row_action_resolver,
		Existing_Page_Update_Detail_Builder $detail_builder,
		Existing_Page_Update_Bulk_Action_Service $bulk_action_service,
		?Existing_Page_Template_Change_Builder $template_change_builder = null
	) {
		$this->row_action_resolver     = $row_action_resolver;
		$this->detail_builder          = $detail_builder;
		$this->bulk_action_service     = $bulk_action_service;
		$this->template_change_builder = $template_change_builder;
	}

	/**
	 * Builds full step workspace payload for Step 1 (existing_page_changes).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 1 for existing_page_changes.
	 * @param array<string, bool>  $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Item id for detail panel.
	 * @param array<int, string>   $selected_item_ids Item ids for bulk selection.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages.
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $step_index !== Existing_Page_Update_Bulk_Action_Service::STEP_INDEX_EXISTING_PAGE_CHANGES ) {
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
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES ) {
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
			$row_actions     = $this->row_action_resolver->resolve( $item, $capabilities );
			$summary_columns = $this->summary_columns_for_item( $item );
			$row             = array(
				Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
				Step_Item_List_Component::ROW_KEY_STATUS  => $status,
				Step_Item_List_Component::ROW_KEY_STATUS_BADGE => $this->status_to_badge( $status ),
				Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => $summary_columns,
				Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
				Step_Item_List_Component::ROW_KEY_IS_SELECTED => in_array( $item_id, $selected_item_ids, true ),
				'snapshot_required'                       => true,
			);
			if ( $this->template_change_builder !== null ) {
				$change           = $this->template_change_builder->build_for_item( $item );
				$template_summary = $change[ Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY ];
				$row[ Existing_Page_Template_Change_Builder::KEY_EXISTING_PAGE_TEMPLATE_CHANGE_SUMMARY ] = $template_summary;
				$row[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ]            = $change[ Existing_Page_Template_Change_Builder::KEY_REPLACEMENT_REASON_SUMMARY ];
				$row['summary_columns']['template_links'] = '';
				if ( ! empty( $template_summary['template_key'] ) && ( $row['summary_columns']['target_template'] ?? '' ) === '' ) {
					$row['summary_columns']['target_template'] = $template_summary['template_key'];
				}
			}
			$rows[] = $row;
		}

		$eligibility    = $this->bulk_action_service->get_bulk_eligibility( $plan_definition );
		$pending_count  = $eligibility['approve_all_eligible'];
		$selected_count = count( array_intersect( $selected_item_ids, array_column( $rows, 'item_id' ) ) );

		$bulk_states = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Make All Updates', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => $selected_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Deny All Updates', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
				'enabled' => $pending_count > 0,
				'label'   => \__( 'Clear selection', 'aio-page-builder' ),
			),
		);

		$detail_panel = array(
			'item_id'           => $selected_item_id ?? '',
			'sections'          => array(),
			'row_actions'       => array(),
			'snapshot_required' => true,
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
				$plan_id                     = (string) ( $plan_definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' );
				$context                     = array( 'plan_definition' => $plan_definition );
				$detail_panel['sections']    = $this->detail_builder->build_sections( $selected_item, $plan_id, $context );
				$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $selected_item, $capabilities );
			}
		}

		$step_messages = $this->step_messages( count( $rows ), $eligible_count );

		return array(
			'step_list_rows'     => $rows,
			'column_order'       => self::COLUMN_ORDER,
			'bulk_action_states' => $bulk_states,
			'detail_panel'       => $detail_panel,
			'step_messages'      => $step_messages,
		);
	}

	/**
	 * Returns only existing_page_change items with sufficient confidence (spec §32.2).
	 *
	 * @param array<string, mixed> $step
	 * @return array<int, array<string, mixed>>
	 */
	private function eligible_items_from_step( array $step ): array {
		$items_raw = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$out       = array();
		foreach ( $items_raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE ) {
				continue;
			}
			$payload    = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$confidence = (string) ( $payload['confidence'] ?? 'medium' );
			if ( $confidence === self::MIN_CONFIDENCE_EXCLUDE ) {
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
		$cols    = array();
		foreach ( self::COLUMN_ORDER as $key ) {
			$val          = $payload[ $key ] ?? $item[ $key ] ?? '';
			$cols[ $key ] = is_string( $val ) ? $val : (string) \wp_json_encode( $val );
		}
		return $cols;
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
					'message'  => \__( 'No recommendations were generated for this step.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		if ( $eligible === 0 ) {
			return array(
				array(
					'severity' => 'success',
					'message'  => \__( 'All recommendations in this step have already been resolved.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		return array(
			array(
				'severity' => 'info',
				'message'  => sprintf( \_n( '%d item pending review.', '%d items pending review.', $eligible, 'aio-page-builder' ), $eligible ),
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
					'label'          => \__( 'Make All Updates', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Deny All Updates', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					'enabled' => false,
					'label'   => \__( 'Clear selection', 'aio-page-builder' ),
				),
			),
			'detail_panel'       => array(
				'item_id'           => '',
				'sections'          => array(),
				'row_actions'       => array(),
				'snapshot_required' => true,
			),
			'step_messages'      => array(),
		);
	}
}
