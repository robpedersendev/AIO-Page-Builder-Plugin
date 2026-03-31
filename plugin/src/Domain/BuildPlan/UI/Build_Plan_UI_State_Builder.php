<?php
/**
 * Builds UI state payload for the Build Plan shell (spec §31.4, build-plan-admin-ia-contract.md §5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Admin\ViewModels\BuildPlan\Subtype_Build_Plan_Explanation_View_Model;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Steps\ExistingPageUpdates\Existing_Page_Updates_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Finalization\Finalization_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\History\History_Rollback_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Navigation\Navigation_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\NewPageCreation\New_Page_Creation_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\SEO\SEO_Media_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Steps\Tokens\Tokens_Step_UI_Service;
use AIOPageBuilder\Domain\BuildPlan\Generation\Build_Plan_Empty_Definition_Repair_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Consumes plan_id; loads plan via repository; returns structured payload for context rail and shell.
 * No raw repository calls in screen templates; screen uses this payload only.
 * Optional Step_Workspace_Payload_Builder enables build_step_workspace() for actionable steps.
 * New-pages step detail panel includes template rationale (Build_Plan_Template_Explanation_Builder) when registered (Prompt 190).
 * New-pages step rows are enriched with family/hierarchy grouping and template links via New_Page_Template_Recommendation_Builder when registered (Prompt 192).
 * Existing-page step rows are enriched with template-change and replacement-reason summaries via Existing_Page_Template_Change_Builder when registered (Prompt 193).
 */
final class Build_Plan_UI_State_Builder {

	/** @var Build_Plan_Repository */
	private $repository;

	/** @var Build_Plan_Stepper_Builder */
	private $stepper_builder;

	/** @var Step_Workspace_Payload_Builder|null */
	private $step_workspace_builder;

	/** @var Existing_Page_Updates_UI_Service|null */
	private $existing_page_updates_ui_service;

	/** @var New_Page_Creation_UI_Service|null */
	private $new_page_creation_ui_service;

	/** @var Navigation_Step_UI_Service|null */
	private $navigation_step_ui_service;

	/** @var Tokens_Step_UI_Service|null */
	private $tokens_step_ui_service;

	/** @var SEO_Media_Step_UI_Service|null */
	private $seo_media_step_ui_service;

	/** @var Finalization_Step_UI_Service|null */
	private $finalization_step_ui_service;

	/** @var History_Rollback_Step_UI_Service|null */
	private $history_rollback_step_ui_service;

	/** @var Build_Plan_Empty_Definition_Repair_Service|null */
	private $empty_definition_repair;

	public function __construct(
		Build_Plan_Repository $repository,
		Build_Plan_Stepper_Builder $stepper_builder,
		?Step_Workspace_Payload_Builder $step_workspace_builder = null,
		?Existing_Page_Updates_UI_Service $existing_page_updates_ui_service = null,
		?New_Page_Creation_UI_Service $new_page_creation_ui_service = null,
		?Navigation_Step_UI_Service $navigation_step_ui_service = null,
		?Tokens_Step_UI_Service $tokens_step_ui_service = null,
		?SEO_Media_Step_UI_Service $seo_media_step_ui_service = null,
		?Finalization_Step_UI_Service $finalization_step_ui_service = null,
		?History_Rollback_Step_UI_Service $history_rollback_step_ui_service = null,
		?Build_Plan_Empty_Definition_Repair_Service $empty_definition_repair = null
	) {
		$this->repository                       = $repository;
		$this->stepper_builder                  = $stepper_builder;
		$this->step_workspace_builder           = $step_workspace_builder;
		$this->existing_page_updates_ui_service = $existing_page_updates_ui_service;
		$this->new_page_creation_ui_service     = $new_page_creation_ui_service;
		$this->navigation_step_ui_service       = $navigation_step_ui_service;
		$this->tokens_step_ui_service           = $tokens_step_ui_service;
		$this->seo_media_step_ui_service        = $seo_media_step_ui_service;
		$this->finalization_step_ui_service     = $finalization_step_ui_service;
		$this->history_rollback_step_ui_service = $history_rollback_step_ui_service;
		$this->empty_definition_repair          = $empty_definition_repair;
	}

	/**
	 * Builds full UI state for the workspace shell. Returns null if plan not found.
	 *
	 * @param string $plan_id Plan ID (e.g. UUID or internal_key).
	 * @return array<string, mixed>|null Payload with context_rail, stepper_steps, plan_definition, plan_post_id; null if not found.
	 */
	public function build( string $plan_id ): ?array {
		$plan_id = \sanitize_text_field( $plan_id );
		if ( $plan_id === '' ) {
			return null;
		}
		$record = $this->repository->get_by_key( $plan_id );
		if ( $record === null && is_numeric( $plan_id ) ) {
			$record = $this->repository->get_by_id( (int) $plan_id );
		}
		if ( $record === null ) {
			return null;
		}
		$post_id = (int) ( $record['id'] ?? 0 );
		// * Prefer meta-backed definition: flattened merge in post_to_record is skipped when stored JSON is empty, which would otherwise treat post row fields as the "definition" and yield an empty stepper.
		$from_meta      = $post_id > 0 ? $this->repository->get_plan_definition( $post_id ) : array();
		$pre_steps_n    = isset( $from_meta[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $from_meta[ Build_Plan_Schema::KEY_STEPS ] )
			? count( $from_meta[ Build_Plan_Schema::KEY_STEPS ] )
			: -1;
		$repair_invoked = false;
		if ( $this->empty_definition_repair !== null && Build_Plan_Empty_Definition_Repair_Service::definition_lacks_steps( $from_meta ) ) {
			$lookup         = (string) ( $record['internal_key'] ?? $plan_id );
			$repair_invoked = $this->empty_definition_repair->repair_if_needed( $post_id, $lookup );
			$from_meta      = $post_id > 0 ? $this->repository->get_plan_definition( $post_id ) : array();
		}
		$post_repair_steps_n = isset( $from_meta[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $from_meta[ Build_Plan_Schema::KEY_STEPS ] )
			? count( $from_meta[ Build_Plan_Schema::KEY_STEPS ] )
			: -1;
		if ( $from_meta !== array() ) {
			$definition = $from_meta;
		} else {
			$definition = isset( $record['plan_definition'] ) && is_array( $record['plan_definition'] ) ? $record['plan_definition'] : $record;
		}
		$plan_id_from_def = (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? $record['internal_key'] ?? $plan_id );
		$stepper_steps    = $this->stepper_builder->build( $definition );
		$context_rail     = $this->build_context_rail( $definition, $stepper_steps );
		if ( (string) ( $context_rail['plan_id'] ?? '' ) === '' && $plan_id_from_def !== '' ) {
			$context_rail['plan_id'] = $plan_id_from_def;
		}

		if ( Named_Debug_Log::build_plan_meta_trace_enabled() && $post_id > 0 ) {
			Named_Debug_Log::event(
				Named_Debug_Log_Event::BP_PLAN_DEFINITION_UI_STATE_AFTER_LOAD,
				'plan_post_id=' . (string) $post_id
				. ' plan_lookup=' . $plan_id
				. ' pre_meta_steps_n=' . (string) $pre_steps_n
				. ' post_repair_meta_steps_n=' . (string) $post_repair_steps_n
				. ' repair_invoked=' . ( $repair_invoked ? '1' : '0' )
				. ' stepper_n=' . (string) count( $stepper_steps )
				. ' def_root_steps_n=' . (string) ( isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] ) ? count( $definition[ Build_Plan_Schema::KEY_STEPS ] ) : -1 )
			);
		}

		return array(
			'plan_id'         => $plan_id_from_def,
			'plan_post_id'    => (int) ( $record['id'] ?? 0 ),
			'plan_definition' => $definition,
			'context_rail'    => $context_rail,
			'stepper_steps'   => $stepper_steps,
		);
	}

	/**
	 * Builds step workspace payload for an actionable step (list rows, bulk bar, detail panel, messages).
	 * Returns empty payload when plan not found or step_workspace_builder not set.
	 *
	 * @param string              $plan_id Plan ID.
	 * @param int                 $step_index Step index in stepper.
	 * @param array<string, bool> $capabilities can_approve, can_execute, can_view_artifacts (from current user).
	 * @param string|null         $selected_item_id Item id for detail panel.
	 * @param array<int, string>  $selected_item_ids Item ids for bulk selection.
	 * @return array<string, mixed> step_list_rows, column_order, bulk_action_states, detail_panel, step_messages.
	 */
	public function build_step_workspace(
		string $plan_id,
		int $step_index,
		array $capabilities,
		?string $selected_item_id = null,
		array $selected_item_ids = array()
	): array {
		if ( $this->step_workspace_builder === null ) {
			return array(
				'step_list_rows'     => array(),
				'column_order'       => array(),
				'bulk_action_states' => array(),
				'detail_panel'       => array(
					'item_id'     => '',
					'sections'    => array(),
					'row_actions' => array(),
				),
				'step_messages'      => array(),
			);
		}
		$state = $this->build( $plan_id );
		if ( $state === null ) {
			return $this->empty_step_workspace( $capabilities, $selected_item_id, $selected_item_ids );
		}
		$definition = $state['plan_definition'] ?? array();
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();
		$step       = $steps[ $step_index ] ?? null;
		$step_type  = is_array( $step ) ? (string) ( $step[ Build_Plan_Item_Schema::KEY_STEP_TYPE ] ?? '' ) : '';
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_EXISTING_PAGE_CHANGES && $this->existing_page_updates_ui_service !== null ) {
			return $this->existing_page_updates_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_NEW_PAGES && $this->new_page_creation_ui_service !== null ) {
			return $this->new_page_creation_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_NAVIGATION && $this->navigation_step_ui_service !== null ) {
			return $this->navigation_step_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_DESIGN_TOKENS && $this->tokens_step_ui_service !== null ) {
			return $this->tokens_step_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_SEO && $this->seo_media_step_ui_service !== null ) {
			return $this->seo_media_step_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_CONFIRMATION && $this->finalization_step_ui_service !== null ) {
			return $this->finalization_step_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $step_type === Build_Plan_Schema::STEP_TYPE_LOGS_ROLLBACK && $this->history_rollback_step_ui_service !== null ) {
			return $this->history_rollback_step_ui_service->build_workspace( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		if ( $this->step_workspace_builder !== null ) {
			return $this->step_workspace_builder->build( $definition, $step_index, $capabilities, $selected_item_id, $selected_item_ids );
		}
		return $this->empty_step_workspace( $capabilities, $selected_item_id, $selected_item_ids );
	}

	/**
	 * Returns empty step workspace shape when no builder available or plan not found.
	 *
	 * @param array<string, bool> $capabilities
	 * @param string|null         $selected_item_id
	 * @param array<int, string>  $selected_item_ids
	 * @return array<string, mixed>
	 */
	private function empty_step_workspace( array $capabilities, ?string $selected_item_id, array $selected_item_ids ): array {
		if ( $this->step_workspace_builder === null ) {
			return array(
				'step_list_rows'     => array(),
				'column_order'       => array(),
				'bulk_action_states' => array(),
				'detail_panel'       => array(
					'item_id'     => '',
					'sections'    => array(),
					'row_actions' => array(),
				),
				'step_messages'      => array(),
			);
		}
		return $this->step_workspace_builder->build( array(), 0, $capabilities, $selected_item_id, $selected_item_ids );
	}

	/**
	 * Context rail fields per IA contract §5.
	 *
	 * @param array<string, mixed>             $definition
	 * @param array<int, array<string, mixed>> $stepper_steps
	 * @return array<string, mixed>
	 */
	private function build_context_rail( array $definition, array $stepper_steps ): array {
		$unresolved_by_step = array();
		foreach ( $stepper_steps as $s ) {
			$step_type                        = (string) ( $s['step_type'] ?? '' );
			$unresolved_by_step[ $step_type ] = (int) ( $s['unresolved_count'] ?? 0 );
		}
		$warnings         = isset( $definition[ Build_Plan_Schema::KEY_WARNINGS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_WARNINGS ] )
			? $definition[ Build_Plan_Schema::KEY_WARNINGS ]
			: array();
		$warnings_summary = array_slice( $warnings, 0, 5 );
		if ( count( $warnings ) > 5 ) {
			$warnings_summary[] = array( 'message' => sprintf( /* translators: %d: additional warning count */ __( '+%d more', 'aio-page-builder' ), count( $warnings ) - 5 ) );
		}

		$subtype_context = Subtype_Build_Plan_Explanation_View_Model::from_plan_definition( $definition );

		return array(
			'plan_title'                  => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_TITLE ] ?? '' ),
			'plan_id'                     => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_ID ] ?? '' ),
			'plan_lineage_id'             => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_LINEAGE_ID ] ?? '' ),
			'plan_version_label'          => (string) ( $definition[ Build_Plan_Schema::KEY_PLAN_VERSION_LABEL ] ?? '' ),
			'version_purpose_description' => (string) ( $definition[ Build_Plan_Schema::KEY_VERSION_PURPOSE_DESCRIPTION ] ?? '' ),
			'estimated_ai_cost_usd_note'  => (string) ( $definition[ Build_Plan_Schema::KEY_ESTIMATED_AI_COST_USD_NOTE ] ?? '' ),
			'ai_run_ref'                  => (string) ( $definition[ Build_Plan_Schema::KEY_AI_RUN_REF ] ?? '' ),
			'normalized_output_ref'       => (string) ( $definition[ Build_Plan_Schema::KEY_NORMALIZED_OUTPUT_REF ] ?? '' ),
			'plan_status'                 => (string) ( $definition[ Build_Plan_Schema::KEY_STATUS ] ?? '' ),
			'site_purpose_summary'        => (string) ( $definition[ Build_Plan_Schema::KEY_SITE_PURPOSE_SUMMARY ] ?? '' ),
			'site_flow_summary'           => (string) ( $definition[ Build_Plan_Schema::KEY_SITE_FLOW_SUMMARY ] ?? '' ),
			'unresolved_counts_by_step'   => $unresolved_by_step,
			'warnings_summary'            => $warnings_summary,
			'subtype_context'             => $subtype_context,
		);
	}
}
