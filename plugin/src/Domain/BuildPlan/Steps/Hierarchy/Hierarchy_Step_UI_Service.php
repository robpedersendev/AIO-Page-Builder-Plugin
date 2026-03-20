<?php
/**
 * Hierarchy step UI service (v2-scope-backlog.md §1).
 *
 * Provides step index constant and produces the step payload for the Build Plan workspace.
 * Renders ITEM_TYPE_HIERARCHY_ASSIGNMENT items (executable) and ITEM_TYPE_HIERARCHY_NOTE
 * items (advisory-only) with appropriate row actions.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Steps\Hierarchy;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\BuildPlan\UI\Build_Plan_Row_Action_Resolver;

/**
 * Step 3 — Hierarchy. Emits ITEM_TYPE_HIERARCHY_ASSIGNMENT items for executable reassignments
 * and ITEM_TYPE_HIERARCHY_NOTE items for advisory/unresolvable dependencies.
 */
final class Hierarchy_Step_UI_Service {

	/** Step index for the hierarchy step in the Build Plan steps array. */
	public const STEP_INDEX_HIERARCHY = 3;

	/** @var Build_Plan_Row_Action_Resolver */
	private $row_action_resolver;

	public function __construct( Build_Plan_Row_Action_Resolver $row_action_resolver ) {
		$this->row_action_resolver = $row_action_resolver;
	}

	/**
	 * Produces the step UI payload for the hierarchy step.
	 *
	 * @param int                  $step_index       Step array index; must be STEP_INDEX_HIERARCHY.
	 * @param array<string, mixed> $definition       Full plan definition.
	 * @param array<string, bool>  $capabilities     Capability flags: can_approve, can_execute, can_view_artifacts.
	 * @return array<string, mixed>|null Null if step_index does not match.
	 */
	public function build_step_payload( int $step_index, array $definition, array $capabilities = array() ): ?array {
		if ( $step_index !== self::STEP_INDEX_HIERARCHY ) {
			return null;
		}

		$steps = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] )
			? $definition[ Build_Plan_Schema::KEY_STEPS ]
			: array();

		$step  = $steps[ self::STEP_INDEX_HIERARCHY ] ?? null;
		if ( ! is_array( $step ) ) {
			return null;
		}

		$items       = isset( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] ) && is_array( $step[ Build_Plan_Item_Schema::KEY_ITEMS ] )
			? $step[ Build_Plan_Item_Schema::KEY_ITEMS ]
			: array();
		$step_rows   = array();
		$exec_count  = 0;
		$done_count  = 0;
		$advisory_count = 0;

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$status    = (string) ( $item['status'] ?? Build_Plan_Item_Statuses::PENDING );
			$payload   = is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ?? null )
				? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ]
				: array();

			$row_actions = $this->row_action_resolver->resolve( $item, $capabilities );

			if ( $item_type === Build_Plan_Item_Schema::ITEM_TYPE_HIERARCHY_ASSIGNMENT ) {
				if ( $status === Build_Plan_Item_Statuses::COMPLETED ) {
					++$done_count;
				} elseif ( $status === Build_Plan_Item_Statuses::APPROVED ) {
					++$exec_count;
				}
			} else {
				++$advisory_count;
			}

			$step_rows[] = array(
				'item_id'      => (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' ),
				'item_type'    => $item_type,
				'status'       => $status,
				'page_id'      => isset( $payload['page_id'] ) ? (int) $payload['page_id'] : null,
				'parent_page_id' => isset( $payload['parent_page_id'] ) ? (int) $payload['parent_page_id'] : null,
				'note'         => isset( $payload['note'] ) ? (string) $payload['note'] : '',
				'row_actions'  => $row_actions,
			);
		}

		return array(
			'step_index'      => self::STEP_INDEX_HIERARCHY,
			'step_rows'       => $step_rows,
			'executable_count' => $exec_count,
			'completed_count' => $done_count,
			'advisory_count'  => $advisory_count,
			'column_order'    => array( 'page_id', 'parent_page_id', 'status', 'note' ),
		);
	}
}
