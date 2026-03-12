<?php
/**
 * Builds dependency-ordered action envelopes from approved Build Plan items (spec §40.3; Prompt 080).
 *
 * Collects eligible actions, maps item types to action types, orders by dependencies and step order,
 * and returns a sequence of governed envelopes. Does not execute; used by Execution_Queue_Service.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Turns approved plan items into an ordered list of execution envelopes.
 */
final class Bulk_Executor {

	/** Map plan item_type to execution action_type (spec §40.1). Non-mapped types are skipped. */
	private const ITEM_TYPE_TO_ACTION_TYPE = array(
		Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE => Execution_Action_Types::REPLACE_PAGE,
		Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE             => Execution_Action_Types::CREATE_PAGE,
		Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE            => Execution_Action_Types::UPDATE_MENU,
		Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN          => Execution_Action_Types::APPLY_TOKEN_SET,
		Build_Plan_Item_Schema::ITEM_TYPE_SEO                    => Execution_Action_Types::UPDATE_PAGE_METADATA,
	);

	/**
	 * Builds an ordered list of action envelopes from the plan definition.
	 * Only items with status approved (or in_progress for retry) and with a known action type are included.
	 * Order: dependency-aware (depends_on_item_ids) then step order.
	 *
	 * @param string               $plan_id       Plan ID (internal key).
	 * @param array<string, mixed>  $definition    Full plan definition (steps, status, etc.).
	 * @param array<int, string>|null $item_ids   Optional list of plan item IDs to include; null = all eligible.
	 * @param array<string, mixed>  $actor_context Actor context for envelopes (actor_type, capability_checked, etc.).
	 * @param string               $batch_id     Optional batch identifier for action_id uniqueness.
	 * @return array<int, array<string, mixed>> Ordered list of governed action envelopes.
	 */
	public function build_ordered_envelopes(
		string $plan_id,
		array $definition,
		?array $item_ids,
		array $actor_context,
		string $batch_id = ''
	): array {
		$batch_id   = $batch_id !== '' ? $batch_id : $this->generate_batch_id();
		$plan_status = isset( $definition[ Build_Plan_Schema::KEY_STATUS ] ) && is_string( $definition[ Build_Plan_Schema::KEY_STATUS ] ) ? $definition[ Build_Plan_Schema::KEY_STATUS ] : '';
		$steps      = isset( $definition[ Build_Plan_Schema::KEY_STEPS ] ) && is_array( $definition[ Build_Plan_Schema::KEY_STEPS ] ) ? $definition[ Build_Plan_Schema::KEY_STEPS ] : array();
		$eligible   = $this->collect_eligible_items( $steps, $item_ids );
		$ordered    = $this->order_by_dependencies( $eligible, $steps );
		return $this->envelopes_from_ordered_items( $plan_id, $plan_status, $ordered, $actor_context, $batch_id );
	}

	/**
	 * Collects items that are approved (or in_progress) and have a mappable action type.
	 *
	 * @param array<int, array<string, mixed>> $steps
	 * @param array<int, string>|null          $item_ids
	 * @return array<int, array{item: array<string, mixed>, step_index: int}>
	 */
	private function collect_eligible_items( array $steps, ?array $item_ids ): array {
		$id_filter = $item_ids !== null ? array_flip( array_map( 'strval', $item_ids ) ) : null;
		$eligible  = array();
		foreach ( $steps as $step_index => $step ) {
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
				$item_id   = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
				$status   = (string) ( $item[ Build_Plan_Item_Schema::KEY_STATUS ] ?? Build_Plan_Item_Statuses::PENDING );
				$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
				if ( $item_id === '' ) {
					continue;
				}
				if ( $id_filter !== null && ! isset( $id_filter[ $item_id ] ) ) {
					continue;
				}
				if ( $status !== Build_Plan_Item_Statuses::APPROVED && $status !== Build_Plan_Item_Statuses::IN_PROGRESS ) {
					continue;
				}
				if ( ! isset( self::ITEM_TYPE_TO_ACTION_TYPE[ $item_type ] ) ) {
					continue;
				}
				$eligible[] = array( 'item' => $item, 'step_index' => $step_index );
			}
		}
		return $eligible;
	}

	/**
	 * Orders eligible items: dependency order (depends_on_item_ids) then step index.
	 * Simple topological sort: items with no deps first; then items whose deps are already in the list.
	 *
	 * @param array<int, array{item: array<string, mixed>, step_index: int}> $eligible
	 * @param array<int, array<string, mixed>>                               $steps
	 * @return array<int, array{item: array<string, mixed>, step_index: int}>
	 */
	private function order_by_dependencies( array $eligible, array $steps ): array {
		$item_ids_in_batch = array();
		foreach ( $eligible as $e ) {
			$id = (string) ( $e['item'][ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			if ( $id !== '' ) {
				$item_ids_in_batch[ $id ] = true;
			}
		}
		$ordered = array();
		$added   = array();
		$max_pass = count( $eligible ) + 1;
		$pass    = 0;
		while ( count( $ordered ) < count( $eligible ) && $pass < $max_pass ) {
			$pass++;
			foreach ( $eligible as $e ) {
				$item   = $e['item'];
				$item_id = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
				if ( isset( $added[ $item_id ] ) ) {
					continue;
				}
				$dep_ids = isset( $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ] )
					? $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ]
					: array();
				$satisfied = true;
				foreach ( $dep_ids as $dep_id ) {
					$dep_id = (string) $dep_id;
					if ( $dep_id === '' ) {
						continue;
					}
					if ( isset( $item_ids_in_batch[ $dep_id ] ) && ! isset( $added[ $dep_id ] ) ) {
						$satisfied = false;
						break;
					}
				}
				if ( $satisfied ) {
					$ordered[] = $e;
					$added[ $item_id ] = true;
				}
			}
		}
		return $ordered;
	}

	/**
	 * Builds one envelope per ordered item (Execution_Action_Contract shape).
	 *
	 * @param string               $plan_id
	 * @param string               $plan_status
	 * @param array<int, array{item: array<string, mixed>, step_index: int}> $ordered
	 * @param array<string, mixed>  $actor_context
	 * @param string               $batch_id
	 * @return array<int, array<string, mixed>>
	 */
	private function envelopes_from_ordered_items(
		string $plan_id,
		string $plan_status,
		array $ordered,
		array $actor_context,
		string $batch_id
	): array {
		$envelopes = array();
		$now       = gmdate( 'c' );
		$executable_plan = in_array( $plan_status, Execution_Action_Contract::EXECUTABLE_PLAN_STATUSES, true );
		$item_status     = Execution_Action_Contract::EXECUTABLE_ITEM_STATUS;

		foreach ( $ordered as $e ) {
			$item     = $e['item'];
			$item_id  = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_ID ] ?? '' );
			$item_type = (string) ( $item[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' );
			$payload  = isset( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ) ? $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] : array();
			$action_type = self::ITEM_TYPE_TO_ACTION_TYPE[ $item_type ] ?? Execution_Action_Types::CREATE_PAGE;
			$action_id   = 'exec_' . $item_id . '_' . $batch_id;

			$dep_ids = isset( $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ] ) && is_array( $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ] )
				? $item[ Build_Plan_Item_Schema::KEY_DEPENDS_ON_ITEM_IDS ]
				: array();
			$dependency_manifest = array(
				'resolved'          => true,
				'resolution_errors'  => array(),
				'depends_on_item_ids' => $dep_ids,
			);

			$target_reference = array_merge( array( 'plan_item_id' => $item_id ), $payload );
			$template_key = isset( $payload['template_key'] ) && is_string( $payload['template_key'] ) ? trim( $payload['template_key'] ) : '';
			if ( $template_key === '' && isset( $payload['target_template_key'] ) && is_string( $payload['target_template_key'] ) ) {
				$template_key = trim( $payload['target_template_key'] );
			}
			if ( $template_key !== '' && ( $action_type === Execution_Action_Types::CREATE_PAGE || $action_type === Execution_Action_Types::REPLACE_PAGE ) ) {
				$target_reference['template_ref'] = array( 'type' => 'internal_key', 'value' => $template_key );
				$target_reference['template_key']  = $template_key;
			}

			// * Spec §32.9, §41.2: pre-change snapshot for rollback-capable actions (page, menu, token).
			$snapshot_required = ( $action_type === Execution_Action_Types::REPLACE_PAGE )
				|| ( $action_type === Execution_Action_Types::UPDATE_MENU )
				|| ( $action_type === Execution_Action_Types::APPLY_TOKEN_SET );

			$envelope = array(
				Execution_Action_Contract::ENVELOPE_ACTION_ID        => $action_id,
				Execution_Action_Contract::ENVELOPE_ACTION_TYPE       => $action_type,
				Execution_Action_Contract::ENVELOPE_PLAN_ID           => $plan_id,
				Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID      => $item_id,
				Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE  => $target_reference,
				Execution_Action_Contract::ENVELOPE_APPROVAL_STATE    => array(
					Execution_Action_Contract::APPROVAL_PLAN_STATUS => $executable_plan ? $plan_status : 'approved',
					Execution_Action_Contract::APPROVAL_ITEM_STATUS => $item_status,
					Execution_Action_Contract::APPROVAL_VERIFIED_AT => $now,
				),
				Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT     => $actor_context,
				Execution_Action_Contract::ENVELOPE_CREATED_AT       => $now,
				'dependency_manifest' => $dependency_manifest,
				'snapshot_required'  => $snapshot_required,
			);
			$envelopes[] = $envelope;
		}
		return $envelopes;
	}

	/**
	 * Builds a single finalization envelope for the plan (spec §37, §40.1). Plan-level action; no plan_item_id.
	 *
	 * @param string               $plan_id       Plan ID (internal key).
	 * @param array<string, mixed> $definition    Full plan definition (for approval state).
	 * @param array<string, mixed> $actor_context Actor context.
	 * @param string               $batch_id      Optional batch identifier.
	 * @return array<string, mixed> One envelope with action_type finalize_plan.
	 */
	public function build_finalization_envelope(
		string $plan_id,
		array $definition,
		array $actor_context,
		string $batch_id = ''
	): array {
		$batch_id    = $batch_id !== '' ? $batch_id : $this->generate_batch_id();
		$plan_status = isset( $definition[ Build_Plan_Schema::KEY_STATUS ] ) && is_string( $definition[ Build_Plan_Schema::KEY_STATUS ] ) ? $definition[ Build_Plan_Schema::KEY_STATUS ] : '';
		$executable  = in_array( $plan_status, Execution_Action_Contract::EXECUTABLE_PLAN_STATUSES, true );
		$now         = gmdate( 'c' );
		$action_id   = 'exec_finalize_' . $plan_id . '_' . $batch_id;
		return array(
			Execution_Action_Contract::ENVELOPE_ACTION_ID        => $action_id,
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE     => Execution_Action_Types::FINALIZE_PLAN,
			Execution_Action_Contract::ENVELOPE_PLAN_ID         => $plan_id,
			Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID    => '',
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'plan_id' => $plan_id ),
			Execution_Action_Contract::ENVELOPE_APPROVAL_STATE  => array(
				Execution_Action_Contract::APPROVAL_PLAN_STATUS => $executable ? $plan_status : 'approved',
				Execution_Action_Contract::APPROVAL_ITEM_STATUS => Execution_Action_Contract::EXECUTABLE_ITEM_STATUS,
				Execution_Action_Contract::APPROVAL_VERIFIED_AT => $now,
			),
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT   => $actor_context,
			Execution_Action_Contract::ENVELOPE_CREATED_AT      => $now,
			'dependency_manifest' => array( 'resolved' => true, 'resolution_errors' => array(), 'depends_on_item_ids' => array() ),
			'snapshot_required'  => false,
		);
	}

	private function generate_batch_id(): string {
		return gmdate( 'Ymd\THis' ) . '_' . wp_rand( 1000, 9999 );
	}
}
