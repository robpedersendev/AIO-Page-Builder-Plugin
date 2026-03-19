<?php
/**
 * Step 2 (new page creation) UI payload builder (spec §33.2–33.10).
 *
 * Filters to eligible new_page items, builds row summaries and detail via
 * New_Page_Creation_Detail_Builder, bulk eligibility (Build All / Build Selected), dependency
 * and post-build placeholder display. Does not execute page creation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Bulk_Action_Bar_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\Components\Step_Item_List_Component;
use AIOPageBuilder\Domain\BuildPlan\UI\New_Page_Template_Recommendation_Builder;
use AIOPageBuilder\Domain\Industry\Docs\Industry_Compliance_Warning_Resolver;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;

/**
 * Produces step_list_rows, column_order, bulk_action_states, detail_panel, step_messages for Step 2 only.
 */
final class New_Page_Creation_UI_Service {

	/** Step 2 column order per spec §33.3, Prompt 192: title, slug, purpose, template, template links, hierarchy, page-type, confidence. */
	public const COLUMN_ORDER = array(
		'proposed_page_title',
		'proposed_slug',
		'purpose',
		'template_key',
		'template_links',
		'hierarchy_position',
		'page_type',
		'confidence',
	);

	/** Minimum confidence for item to appear (spec §33.2: exclude low). */
	private const MIN_CONFIDENCE_EXCLUDE = 'low';

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	/** @var New_Page_Creation_Detail_Builder */
	private $detail_builder;

	/** @var New_Page_Creation_Bulk_Action_Service */
	private $bulk_action_service;

	/** @var New_Page_Template_Recommendation_Builder|null */
	private $recommendation_builder;

	/** @var Industry_Profile_Repository|null Optional; used for Build Plan compliance caution context (Prompt 407). */
	private $profile_repository;

	/** @var Industry_Compliance_Warning_Resolver|null Optional; used for Build Plan compliance caution surfacing (Prompt 407). */
	private $compliance_warning_resolver;

	public function __construct(
		Build_Plan_Row_Action_Resolver $row_action_resolver,
		New_Page_Creation_Detail_Builder $detail_builder,
		New_Page_Creation_Bulk_Action_Service $bulk_action_service,
		?New_Page_Template_Recommendation_Builder $recommendation_builder = null,
		?Industry_Profile_Repository $profile_repository = null,
		?Industry_Compliance_Warning_Resolver $compliance_warning_resolver = null
	) {
		$this->row_action_resolver         = $row_action_resolver;
		$this->detail_builder              = $detail_builder;
		$this->bulk_action_service         = $bulk_action_service;
		$this->recommendation_builder      = $recommendation_builder;
		$this->profile_repository          = $profile_repository;
		$this->compliance_warning_resolver = $compliance_warning_resolver;
	}

	/**
	 * Builds full step workspace payload for Step 2 (new_pages).
	 *
	 * @param array<string, mixed> $plan_definition Plan root.
	 * @param int                  $step_index Must be 2 for new_pages.
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
		if ( $step_index !== New_Page_Creation_Bulk_Action_Service::STEP_INDEX_NEW_PAGES ) {
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
		if ( $step_type !== Build_Plan_Schema::STEP_TYPE_NEW_PAGES ) {
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
			$payload         = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();
			$summary_columns = $this->summary_columns_for_item( $item );
			$row             = array(
				Step_Item_List_Component::ROW_KEY_ITEM_ID => $item_id,
				Step_Item_List_Component::ROW_KEY_STATUS  => $status,
				Step_Item_List_Component::ROW_KEY_STATUS_BADGE => $this->status_to_badge( $status ),
				Step_Item_List_Component::ROW_KEY_SUMMARY_COLUMNS => $summary_columns,
				Step_Item_List_Component::ROW_KEY_ROW_ACTIONS => $row_actions,
				Step_Item_List_Component::ROW_KEY_IS_SELECTED => in_array( $item_id, $selected_item_ids, true ),
				'dependency_validation'                   => $this->dependency_validation_summary( $payload ),
				'post_build_status'                       => (string) ( $payload['post_build_status'] ?? '' ),
			);
			if ( $this->recommendation_builder !== null ) {
				$recommendation = $this->recommendation_builder->build_for_item( $item );
				$row[ New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY ] = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_PROPOSED_TEMPLATE_SUMMARY ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY ] = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_HIERARCHY_CONTEXT_SUMMARY ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_TEMPLATE_SELECTION_REASON ] = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_TEMPLATE_SELECTION_REASON ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL ]               = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_GROUP_HIERARCHY_ROLE ]      = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_GROUP_HIERARCHY_ROLE ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_GROUP_TEMPLATE_FAMILY ]     = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_GROUP_TEMPLATE_FAMILY ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_DEPENDENCY_WARNINGS ]       = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_DEPENDENCY_WARNINGS ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_DEPRECATION_AWARE ]         = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_DEPRECATION_AWARE ];
				$row[ New_Page_Template_Recommendation_Builder::KEY_CONFIDENCE_NOTE ]           = $recommendation[ New_Page_Template_Recommendation_Builder::KEY_CONFIDENCE_NOTE ];
				$row['summary_columns']['template_links']                                       = ''; // Filled by Screen with detail/compare URLs.
			}
			$rows[] = $row;
		}
		if ( $this->recommendation_builder !== null && ! empty( $rows ) ) {
			$rows = $this->sort_rows_by_group( $rows );
		}

		$eligibility    = $this->bulk_action_service->get_bulk_eligibility( $plan_definition );
		$pending_count  = $eligibility['build_all_eligible'];
		$deny_eligible  = $eligibility['deny_all_eligible'] ?? 0;
		$selected_count = count( array_intersect( $selected_item_ids, array_column( $rows, 'item_id' ) ) );

		$bulk_states = array(
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
				'enabled'        => $pending_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Build All Pages', 'aio-page-builder' ),
				'count_eligible' => $pending_count,
			),
			Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
				'enabled'        => $selected_count > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Build Selected Pages', 'aio-page-builder' ),
				'count_selected' => $selected_count,
			),
			Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
				'enabled'        => $deny_eligible > 0 && ! empty( $capabilities['can_approve'] ),
				'label'          => \__( 'Deny All New Pages', 'aio-page-builder' ),
				'count_eligible' => $deny_eligible,
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
				$plan_id = (string) ( $plan_definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' );
				$context = array();
				if ( $this->profile_repository !== null && $this->compliance_warning_resolver !== null ) {
					$profile = $this->profile_repository->get_profile();
					$primary = isset( $profile['primary_industry_key'] ) && is_string( $profile['primary_industry_key'] ) ? trim( $profile['primary_industry_key'] ) : '';
					if ( $primary !== '' ) {
						$context['primary_industry_key']        = $primary;
						$context['compliance_warning_resolver'] = $this->compliance_warning_resolver;
					}
				}
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
	 * Returns only new_page items with sufficient confidence (spec §33.2).
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
			if ( $item_type !== Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE ) {
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

	private function dependency_validation_summary( array $payload ): array {
		$reasons = $payload['dependency_blocking_reasons'] ?? $payload['blocking_reasons'] ?? array();
		if ( ! is_array( $reasons ) ) {
			return array(
				'blocking' => false,
				'messages' => array(),
			);
		}
		return array(
			'blocking' => ! empty( $reasons ),
			'messages' => array_map(
				function ( $r ) {
					return is_string( $r ) ? $r : (string) \wp_json_encode( $r );
				},
				$reasons
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
					'message'  => \__( 'No new page recommendations were generated for this step.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		if ( $eligible === 0 ) {
			return array(
				array(
					'severity' => 'success',
					'message'  => \__( 'All new page items in this step have been marked for build or resolved.', 'aio-page-builder' ),
					'level'    => 'step',
				),
			);
		}
		$retry_note = \__( 'Retry and recovery options are shown in the detail panel for failed or blocked items.', 'aio-page-builder' );
		return array(
			array(
				'severity' => 'info',
				'message'  => sprintf( \_n( '%d new page pending review.', '%d new pages pending review.', $eligible, 'aio-page-builder' ), $eligible ),
				'level'    => 'step',
			),
			array(
				'severity' => 'info',
				'message'  => $retry_note,
				'level'    => 'step',
			),
		);
	}

	/**
	 * Sorts rows by group_label then item_id for scannable hierarchy/family grouping (Prompt 192).
	 *
	 * @param array<int, array<string, mixed>> $rows
	 * @return array<int, array<string, mixed>>
	 */
	private function sort_rows_by_group( array $rows ): array {
		usort(
			$rows,
			function ( array $a, array $b ): int {
				$label_a = (string) ( $a[ New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL ] ?? '' );
				$label_b = (string) ( $b[ New_Page_Template_Recommendation_Builder::KEY_GROUP_LABEL ] ?? '' );
				$cmp     = strcmp( $label_a, $label_b );
				if ( $cmp !== 0 ) {
					return $cmp;
				}
				$id_a = (string) ( $a[ Step_Item_List_Component::ROW_KEY_ITEM_ID ] ?? '' );
				$id_b = (string) ( $b[ Step_Item_List_Component::ROW_KEY_ITEM_ID ] ?? '' );
				return strcmp( $id_a, $id_b );
			}
		);
		return array_values( $rows );
	}

	private function empty_workspace(): array {
		return array(
			'step_list_rows'     => array(),
			'column_order'       => self::COLUMN_ORDER,
			'bulk_action_states' => array(
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Build All Pages', 'aio-page-builder' ),
					'count_eligible' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_APPLY_TO_SELECTED => array(
					'enabled'        => false,
					'label'          => \__( 'Build Selected Pages', 'aio-page-builder' ),
					'count_selected' => 0,
				),
				Bulk_Action_Bar_Component::CONTROL_DENY_ALL => array(
					'enabled'        => false,
					'label'          => \__( 'Deny All', 'aio-page-builder' ),
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
