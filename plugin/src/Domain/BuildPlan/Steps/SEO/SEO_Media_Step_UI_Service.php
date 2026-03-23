<?php
/**
 * Step 5 (SEO, meta, media) workspace UI (spec §36, Prompt 076).
 *
 * Renders SEO/meta/media recommendation rows with review controls.
 * This step is recommendation-only in v1: it records accepted SEO/meta/media
 * guidance as advisory plan data and does not include a direct write execution
 * path for SEO/meta/media changes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\SEO;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;

final class SEO_Media_Step_UI_Service {

	/** Step index for SEO in canonical step order. */
	public const STEP_INDEX_SEO = 6;

	/** Column order per spec §36. */
	public const COLUMN_ORDER = array(
		'target_page_title_or_url',
		'action_type',
		'current',
		'proposed',
		'confidence',
	);

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	public function __construct( Build_Plan_Row_Action_Resolver $row_action_resolver ) {
		$this->row_action_resolver = $row_action_resolver;
	}

	/**
	 * Builds step workspace payload for Step 5 (SEO / meta / media).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 6.
	 * @param array<string, bool>  $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Item id for detail panel.
	 * @param array<int, string>   $selected_item_ids Item ids for bulk selection.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $step_index !== self::STEP_INDEX_SEO ) {
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
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_SEO ) {
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
			$payload     = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
			$rows[]      = array(
				Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
				Step_Item_List_Component::ROW_KEY_STATUS  => $status,
				Step_Item_List_Component::ROW_KEY_STATUS_BADGE => $this->status_to_badge( $status ),
				Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => $this->summary_columns_for_item( $item ),
				Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
				Step_Item_List_Component::ROW_KEY_IS_SELECTED => in_array( $item_id, $selected_item_ids, true ),
			);
		}

		$bulk_states   = $this->bulk_states( $eligible_count, $capabilities, $selected_item_ids, $rows );
		$detail_panel  = $this->build_detail_panel( $items, $selected_item_id, $capabilities );
		$step_messages = $this->step_messages( count( $rows ), $eligible_count );
		if ( count( $rows ) > 0 ) {
			array_unshift(
				$step_messages,
				array(
					'severity' => 'info',
					'message'  => \__( 'SEO/meta/media recommendations are advisory only in this version. Approving items records them in plan artifacts; no direct write execution path exists for this step.', 'aio-page-builder' ),
					'level'    => 'step',
				)
			);
		}
		return array(
			'step_list_rows'     => $rows,
			'column_order'       => self::COLUMN_ORDER,
			'bulk_action_states' => $bulk_states,
			'detail_panel'       => $detail_panel,
			'step_messages'      => $step_messages,
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
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) !== Build_Plan_Item_Schema::ITEM_TYPE_SEO ) {
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
			if ( $key === 'target_page_title_or_url' ) {
				$val          = isset( $payload['target_page_title_or_url'] ) ? (string) ( $payload['target_page_title_or_url'] ) : '';
				$cols[ $key ] = $val !== '' ? $val : '—';
				continue;
			}
			if ( $key === 'action_type' ) {
				$cols[ $key ] = \__( 'Advisory recommendation (no direct write)', 'aio-page-builder' );
				continue;
			}
			if ( $key === 'current' || $key === 'proposed' ) {
				$cols[ $key ] = '—';
				continue;
			}
			if ( $key === 'confidence' ) {
				$val          = isset( $payload['confidence'] ) ? (string) ( $payload['confidence'] ) : '';
				$cols[ $key ] = $val !== '' ? $val : '—';
				continue;
			}
			$val          = $payload[ $key ] ?? null;
			$cols[ $key ] = is_string( $val ) && $val !== '' ? $val : '—';
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
			$payload                        = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
				$target                     = (string) ( $payload['target_page_title_or_url'] ?? '—' );
				$confidence                 = (string) ( $payload['confidence'] ?? '' );
				$confidence_display         = $confidence !== '' ? $confidence : '—';
				$advisory_storage_line      = \__( 'Stored in Build Plan artifacts as advisory SEO guidance.', 'aio-page-builder' );
				$no_direct_write_advisory   = \__( 'This step does not include first-party adapter writes for SEO/meta/media in v1.', 'aio-page-builder' );
				$no_direct_write_capability = \__( 'Approved items are review records only; apply the guidance through supported SEO plugins or manual integration.', 'aio-page-builder' );
			$detail_panel['sections']       = array(
				array(
					'heading'       => \__( 'Target', 'aio-page-builder' ),
					'key'           => 'target',
					'content_lines' => array( \esc_html( $target ) ),
				),
				array(
					'heading'       => \__( 'SEO guidance (advisory)', 'aio-page-builder' ),
					'key'           => 'recommendations',
					'content_lines' => array(
						$advisory_storage_line,
						\__( 'Recommendation purpose: title/meta/schema guidance and media direction are guidance-only in this version.', 'aio-page-builder' ),
						\__( 'Execution posture: no direct write execution exists for this step in v1.', 'aio-page-builder' ),
						\__( 'Dependency notes: interoperability-first; no generic SEO plugin mutation is performed by this step.', 'aio-page-builder' ),
					),
				),
				array(
					'heading'       => \__( 'Confidence', 'aio-page-builder' ),
					'key'           => 'confidence',
					'content_lines' => array( \esc_html( \sprintf( '%s: %s', \__( 'Confidence', 'aio-page-builder' ), $confidence_display ) ) ),
				),
				array(
					'heading'       => \__( 'Advisory status', 'aio-page-builder' ),
					'key'           => 'advisory_status',
					'content_lines' => array(
						$no_direct_write_advisory,
						$no_direct_write_capability,
					),
				),
			);
			if ( (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING ) === Build_Plan_Item_Statuses::APPROVED ) {
				$detail_panel['sections'][0]['content_lines']   = array( \esc_html( $target ) );
				$detail_panel['sections'][1]['content_lines'][] = \__( 'Status: APPROVED (recorded for advisory review / manual integration).', 'aio-page-builder' );
			}
			$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $item, $capabilities );
			break;
		}
		return $detail_panel;
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
				'label'          => \__( 'Approve all SEO recommendations', 'aio-page-builder' ),
				'count_eligible' => $eligible_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $apply_sel_ok,
				'label'          => \__( 'Approve selected SEO recommendations', 'aio-page-builder' ),
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
					'message'  => \__( 'No SEO/meta/media recommendations for this plan.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		if ( $eligible === 0 ) {
			return array(
				array(
					'severity' => 'success',
					'message'  => \__( 'All SEO recommendations have been reviewed.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		return array(
			array(
				'severity' => 'info',
				'message'  => sprintf( /* translators: %d: number of SEO items */ \_n( '%d SEO item pending review.', '%d SEO items pending review.', $eligible, 'aio-page-builder' ), $eligible ),
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
					'label'          => \__( 'Approve all SEO recommendations', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Approve selected SEO recommendations', 'aio-page-builder' ),
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
			'detail_panel'       => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'      => array(),
		);
	}
}
