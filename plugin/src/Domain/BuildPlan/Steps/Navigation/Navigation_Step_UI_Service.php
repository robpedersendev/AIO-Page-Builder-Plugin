<?php
/**
 * Step 3 (navigation) UI payload builder (spec §34.1–34.10).
 *
 * Filters to menu_change items, builds row summaries and detail via Navigation_Detail_Builder,
 * current-vs-proposed comparison, diff and validation summaries, bulk approve/deny. Does not apply menus.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Navigation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;

/**
 * Produces step_list_rows, column_order, bulk_action_states, detail_panel, step_messages,
 * navigation_comparison, validation_summary for Step 3 (navigation) only.
 */
final class Navigation_Step_UI_Service {

	/** Step 3 column order per spec §34.3–34.7: context, action, current/proposed name, diff hint. */
	public const COLUMN_ORDER = array(
		'menu_context',
		'action',
		'current_menu_name',
		'proposed_menu_name',
		'diff_summary',
	);

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	/** @var Navigation_Detail_Builder */
	private $detail_builder;

	/** @var Navigation_Bulk_Action_Service */
	private $bulk_action_service;

	public function __construct(
		Build_Plan_Row_Action_Resolver $row_action_resolver,
		Navigation_Detail_Builder $detail_builder,
		Navigation_Bulk_Action_Service $bulk_action_service
	) {
		$this->row_action_resolver = $row_action_resolver;
		$this->detail_builder      = $detail_builder;
		$this->bulk_action_service = $bulk_action_service;
	}

	/**
	 * Builds full step workspace payload for Step 3 (navigation).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 4 for navigation.
	 * @param array<string, bool>  $capabilities can_approve, can_execute, can_view_artifacts.
	 * @param string|null          $selected_item_id Item id for detail panel.
	 * @param array<int, string>   $selected_item_ids Item ids for bulk selection.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages, navigation_comparison?, validation_summary?
	 */
	public function build_workspace(
		array $plan_definition,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $step_index !== Navigation_Bulk_Action_Service::STEP_INDEX_NAVIGATION ) {
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
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_NAVIGATION ) {
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

		$eligibility    = $this->bulk_action_service->get_bulk_eligibility( $plan_definition );
		$pending_count  = $eligibility['approve_all_eligible'];
		$selected_count = count( array_intersect( $selected_item_ids, array_column( $rows, 'item_id' ) ) );

		$bulk_states = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Apply All Navigation Changes', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $selected_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
				'count_selected' => $selected_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Deny All Navigation Changes', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
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
				$detail_panel['sections']    = $this->detail_builder->build_sections( $selected_item );
				$detail_panel['row_actions'] = $this->row_action_resolver->resolve( $selected_item, $capabilities );
			}
		}

		$step_messages         = $this->step_messages( count( $rows ), $eligible_count );
		$navigation_comparison = $this->build_navigation_comparison( $items );
		$validation_summary    = $this->build_validation_summary( $items );

		return array(
			'step_list_rows'        => $rows,
			'column_order'          => self::COLUMN_ORDER,
			'bulk_action_states'    => $bulk_states,
			'detail_panel'          => $detail_panel,
			'step_messages'         => $step_messages,
			'navigation_comparison' => $navigation_comparison,
			'validation_summary'    => $validation_summary,
		);
	}

	/**
	 * Returns only menu_change items from the step.
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
			// * Include both menu_change (update/rename/replace) and menu_new (net-new creation, v2).
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE
				&& $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW ) {
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
			$val = $payload[ $key ] ?? $item[ $key ] ?? '';
			if ( $key === 'proposed_menu_name' && $val === '' ) {
				$val = $payload['menu_name'] ?? '';
			}
			if ( $key === 'current_menu_name' && $val === ''
				&& \strtolower( (string) ( $payload['action'] ?? '' ) ) === 'create' ) {
				$val = \__( '(none — new menu)', 'aio-page-builder' );
			}
			if ( $key === 'diff_summary' ) {
				if ( $val === '' || ( is_array( $val ) && $val === array() ) ) {
					$val = $this->format_items_column_preview( $payload['items'] ?? array() );
				} elseif ( is_array( $val ) ) {
					$val = implode( '; ', array_slice( array_map( 'strval', $val ), 0, 3 ) );
				}
			} elseif ( is_array( $val ) ) {
				$val = implode( '; ', array_slice( array_map( 'strval', $val ), 0, 3 ) );
			}
			$cols[ $key ] = is_string( $val ) ? $val : (string) \wp_json_encode( $val );
		}
		return $cols;
	}

	/**
	 * Short preview for the Items column when diff_summary is empty (e.g. net-new menu with structured items).
	 *
	 * @param mixed $items Payload items list.
	 */
	private function format_items_column_preview( $items ): string {
		if ( ! is_array( $items ) || $items === array() ) {
			return '';
		}
		$n       = count( $items );
		$preview = array();
		foreach ( array_slice( $items, 0, 3 ) as $entry ) {
			if ( is_string( $entry ) ) {
				$label = $entry;
			} elseif ( is_array( $entry ) ) {
				$label = (string) ( $entry['label'] ?? $entry['title'] ?? $entry['page_ref'] ?? '' );
			} else {
				$label = (string) $entry;
			}
			if ( $label !== '' ) {
				$preview[] = $label;
			}
		}
		if ( $preview === array() ) {
			return sprintf( /* translators: %d: menu item count */ \__( '%d item(s)', 'aio-page-builder' ), $n );
		}
		$more = $n > count( $preview ) ? '…' : '';
		return sprintf(
			/* translators: 1: item count, 2: comma-separated labels, 3: ellipsis if truncated */
			\__( '%1$d: %2$s%3$s', 'aio-page-builder' ),
			$n,
			implode( ', ', $preview ),
			$more
		);
	}

	/**
	 * Builds current-vs-proposed navigation comparison for the step (spec §34.1).
	 *
	 * @param array<int, array<string, mixed>> $items Eligible menu_change items.
	 * @return array<string, mixed> context_key => array(current, proposed, diff_hint).
	 */
	private function build_navigation_comparison( array $items ): array {
		$comparison = array();
		foreach ( $items as $item ) {
			$payload  = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$context  = (string) ( $payload['menu_context'] ?? 'default' );
			$current  = $payload['current_menu_name'] ?? $payload['current_structure'] ?? '';
			$proposed = (string) ( $payload['proposed_menu_name'] ?? $payload['menu_name'] ?? '' );
			$diff     = $payload['diff_summary'] ?? $payload['differences'] ?? array();
			if ( ! is_array( $diff ) ) {
				$diff = array();
			}
			$comparison[ $context ] = array(
				'current'   => $current,
				'proposed'  => $proposed,
				'diff_hint' => array_slice( array_map( 'strval', $diff ), 0, 5 ),
			);
		}
		return $comparison;
	}

	/**
	 * Builds validation summary from all items' validation_messages (spec §34.10).
	 *
	 * @param array<int, array<string, mixed>> $items Eligible menu_change items.
	 * @return array{valid: bool, messages: array<int, string>}
	 */
	private function build_validation_summary( array $items ): array {
		$messages = array();
		foreach ( $items as $item ) {
			$payload = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$vals    = $payload['validation_messages'] ?? $payload['validation_errors'] ?? array();
			if ( ! is_array( $vals ) ) {
				$vals = array( $vals );
			}
			foreach ( $vals as $m ) {
				$messages[] = is_string( $m ) ? $m : (string) \wp_json_encode( $m );
			}
		}
		return array(
			'valid'    => empty( $messages ),
			'messages' => $messages,
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
					'message'  => \__( 'No navigation changes were recommended for this step.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		if ( $eligible === 0 ) {
			return array(
				array(
					'severity' => 'success',
					'message'  => \__( 'All navigation changes in this step have been reviewed.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		return array(
			array(
				'severity' => 'info',
				'message'  => sprintf( /* translators: %d: number of navigation changes */ \_n( '%d navigation change pending review.', '%d navigation changes pending review.', $eligible, 'aio-page-builder' ), $eligible ),
				'level'    => 'step',
			),
		);
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'        => array(),
			'column_order'          => self::COLUMN_ORDER,
			'bulk_action_states'    => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Apply All Navigation Changes', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Apply to selected', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Deny All Navigation Changes', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_CLEAR_SELECTION => array(
					'enabled' => false,
					'label'   => \__( 'Clear selection', 'aio-page-builder' ),
				),
			),
			'detail_panel'          => array(
				'item_id'     => '',
				'sections'    => array(),
				'row_actions' => array(),
			),
			'step_messages'         => array(),
			'navigation_comparison' => array(),
			'validation_summary'    => array(
				'valid'    => true,
				'messages' => array(),
			),
		);
	}
}
