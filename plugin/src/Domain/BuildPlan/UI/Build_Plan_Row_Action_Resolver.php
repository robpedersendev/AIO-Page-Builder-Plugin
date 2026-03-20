<?php
/**
 * Resolves row-level actions for Build Plan items (spec §31.7, build-plan-admin-ia-contract §9).
 *
 * Returns which actions are visible and enabled for a given item status and user capabilities.
 * No step-specific business logic; uses state machine and capability flags only.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;

/**
 * Builds row_actions payload per row: array of { action_id, label, enabled, url? }.
 * Action visibility and enablement follow state machine; capability flags gate approve/deny/execute.
 */
final class Build_Plan_Row_Action_Resolver {

	/** Action id: view detail. */
	public const ACTION_VIEW_DETAIL = 'view_detail';

	/** Action id: approve. */
	public const ACTION_APPROVE = 'approve';

	/** Action id: deny. */
	public const ACTION_DENY = 'deny';

	/** Action id: execute. */
	public const ACTION_EXECUTE = 'execute';

	/** Action id: retry. */
	public const ACTION_RETRY = 'retry';

	/** Action id: view diff. */
	public const ACTION_VIEW_DIFF = 'view_diff';

	/** Action id: view dependencies. */
	public const ACTION_VIEW_DEPENDENCIES = 'view_dependencies';

	/**
	 * Resolves row actions for one item. Caller supplies capability flags (e.g. can_approve, can_execute).
	 *
	 * @param array<string, mixed> $item Raw plan item (item_id, status, item_type, payload, ...).
	 * @param array<string, bool>  $capabilities Map: can_approve, can_execute, can_view_artifacts. All default false if omitted.
	 * @return array<int, array<string, mixed>> List of action descriptors: action_id, label, enabled, url (optional).
	 */
	public function resolve( array $item, array $capabilities = array() ): array {
		$status             = (string) ( $item['status'] ?? Build_Plan_Item_Statuses::PENDING );
		$item_type          = (string) ( $item['item_type'] ?? '' );
		$can_approve        = ! empty( $capabilities['can_approve'] );
		$can_execute        = ! empty( $capabilities['can_execute'] );
		$can_view_artifacts = ! empty( $capabilities['can_view_artifacts'] );

		$actions = array();

		$actions[] = array(
			'action_id' => self::ACTION_VIEW_DETAIL,
			'label'     => \__( 'View detail', 'aio-page-builder' ),
			'enabled'   => true,
		);

		$can_review = Build_Plan_Item_Statuses::can_transition_review( $status, Build_Plan_Item_Statuses::APPROVED );
		$actions[]  = array(
			'action_id' => self::ACTION_APPROVE,
			'label'     => \__( 'Approve', 'aio-page-builder' ),
			'enabled'   => $can_approve && $can_review,
		);

		$can_deny  = Build_Plan_Item_Statuses::can_transition_review( $status, Build_Plan_Item_Statuses::REJECTED );
		$actions[] = array(
			'action_id' => self::ACTION_DENY,
			'label'     => \__( 'Deny', 'aio-page-builder' ),
			'enabled'   => $can_approve && $can_deny,
		);

		// * Execute/retry are available for token, hierarchy_assignment, and menu_new items.
		// * ITEM_TYPE_HIERARCHY_NOTE is advisory-only. ITEM_TYPE_MENU_CHANGE uses UPDATE_MENU (approve/deny only in v1).
		$supports_execution = in_array(
			$item_type,
			array(
				Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
				Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT,
				Build_Plan_Item_Schema::ITEM_TYPE_MENU_NEW,
			),
			true
		);
		if ( $supports_execution ) {
			$can_run = $status === Build_Plan_Item_Statuses::APPROVED
				&& Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::APPROVED, Build_Plan_Item_Statuses::IN_PROGRESS );
			$actions[] = array(
				'action_id' => self::ACTION_EXECUTE,
				'label'     => \__( 'Execute', 'aio-page-builder' ),
				'enabled'   => $can_execute && $can_run,
			);

			$can_retry = $status === Build_Plan_Item_Statuses::FAILED
				&& Build_Plan_Item_Statuses::can_transition_execution( Build_Plan_Item_Statuses::FAILED, Build_Plan_Item_Statuses::IN_PROGRESS );
			$actions[] = array(
				'action_id' => self::ACTION_RETRY,
				'label'     => \__( 'Retry', 'aio-page-builder' ),
				'enabled'   => $can_execute && $can_retry,
			);
		}

		$actions[] = array(
			'action_id' => self::ACTION_VIEW_DIFF,
			'label'     => \__( 'View diff', 'aio-page-builder' ),
			'enabled'   => false,
		);

		$actions[] = array(
			'action_id' => self::ACTION_VIEW_DEPENDENCIES,
			'label'     => \__( 'View dependencies', 'aio-page-builder' ),
			'enabled'   => true,
		);

		return $actions;
	}
}
