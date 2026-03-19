<?php
/**
 * Operational snapshot capture: pre-change and post-change (spec §41.2, §41.3, §38.3, §32.9; Prompt 196).
 *
 * Central service for capturing before/after state around rollback-capable execution.
 * Fails safely; logs capture problems without blocking execution. For replace_page, pre-change
 * capture preserves original page state for rollback and template-replacement traceability.
 * For replace_page and create_page, template metadata is captured in post_change when the
 * handler result includes template execution result (Prompt 197).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Captures pre-change and post-change operational snapshots; persists via repository.
 */
final class Operational_Snapshot_Service {

	/** Action types that support operational snapshot capture in v1 (Prompt 642: page replacement + token only). */
	private const CAPTURE_ACTION_TYPES = array(
		Execution_Action_Types::REPLACE_PAGE,
		Execution_Action_Types::APPLY_TOKEN_SET,
	);

	/** @var Operational_Snapshot_Repository_Interface */
	private $repository;

	/** @var Pre_Change_Snapshot_Builder */
	private $pre_builder;

	/** @var Post_Change_Result_Builder */
	private $post_builder;

	public function __construct(
		Operational_Snapshot_Repository_Interface $repository,
		Pre_Change_Snapshot_Builder $pre_builder,
		Post_Change_Result_Builder $post_builder
	) {
		$this->repository   = $repository;
		$this->pre_builder  = $pre_builder;
		$this->post_builder = $post_builder;
	}

	/**
	 * Whether the action type is rollback-capable and supports pre-change capture.
	 *
	 * @param string $action_type
	 * @return bool
	 */
	public function supports_pre_capture( string $action_type ): bool {
		return in_array( $action_type, self::CAPTURE_ACTION_TYPES, true );
	}

	/**
	 * Captures pre-change state and persists. Returns snapshot_id on success; failure does not throw.
	 *
	 * @param array<string, mixed> $envelope Action envelope.
	 * @return Operational_Snapshot_Result
	 */
	public function capture_pre_change( array $envelope ): Operational_Snapshot_Result {
		$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ]
			: '';
		if ( ! $this->supports_pre_capture( $action_type ) ) {
			return Operational_Snapshot_Result::failure( __( 'Action type does not support pre-change capture.', 'aio-page-builder' ), array( 'unsupported_action' ) );
		}

		$built = $this->pre_builder->build( $envelope );
		if ( $built === null ) {
			return Operational_Snapshot_Result::failure( __( 'Could not build pre-change state.', 'aio-page-builder' ), array( 'build_failed' ) );
		}

		$action_id   = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] )
			? substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ), 0, 40 )
			: 'pre';
		$snapshot_id = 'op-snap-pre-' . $action_id . '-' . gmdate( 'Ymd\THis' ) . '-' . wp_rand( 100, 999 );
		if ( strlen( $snapshot_id ) > Operational_Snapshot_Schema::SNAPSHOT_ID_MAX_LENGTH ) {
			$snapshot_id = substr( $snapshot_id, 0, Operational_Snapshot_Schema::SNAPSHOT_ID_MAX_LENGTH );
		}

		$now          = gmdate( 'c' );
		$actor_ref    = $this->resolve_actor_ref( $envelope );
		$plan_id      = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] : '';
		$plan_item_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';

		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $snapshot_id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => $built['object_family'],
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => $built['target_ref'],
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => $now,
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => $built['pre_change'],
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ?? '',
			Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => $plan_id,
			Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF => $plan_item_id,
			Operational_Snapshot_Schema::FIELD_ACTION_TYPE => $action_type,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS => Operational_Snapshot_Schema::ROLLBACK_STATUS_AVAILABLE,
			Operational_Snapshot_Schema::FIELD_RETENTION   => array(
				'retention_class' => Operational_Snapshot_Schema::RETENTION_CLASS_PLAN_LINKED,
				'retention_notes' => 'Retain until plan archived',
			),
			Operational_Snapshot_Schema::FIELD_PROVENANCE  => array(
				'actor_ref'  => $actor_ref,
				'trigger'    => 'pre_execution',
				'source_ref' => $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ?? '',
			),
		);

		$saved = $this->repository->save( $snapshot );
		if ( ! $saved ) {
			return Operational_Snapshot_Result::failure( __( 'Failed to persist pre-change snapshot.', 'aio-page-builder' ), array( 'storage_failed' ) );
		}
		return Operational_Snapshot_Result::success( $snapshot_id, __( 'Pre-change snapshot captured.', 'aio-page-builder' ), $snapshot );
	}

	/**
	 * Captures post-change result and persists. Links to pre_snapshot_id when provided.
	 *
	 * @param array<string, mixed> $envelope Action envelope.
	 * @param array<string, mixed> $handler_result success, message, artifacts.
	 * @param string               $pre_snapshot_id Optional pre-change snapshot ID to link.
	 * @return Operational_Snapshot_Result
	 */
	public function capture_post_change( array $envelope, array $handler_result, string $pre_snapshot_id = '' ): Operational_Snapshot_Result {
		$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ]
			: '';
		if ( ! in_array( $action_type, self::CAPTURE_ACTION_TYPES, true ) ) {
			return Operational_Snapshot_Result::failure( __( 'Action type does not support post-change capture.', 'aio-page-builder' ), array( 'unsupported_action' ) );
		}

		$built = $this->post_builder->build( $envelope, $handler_result );
		if ( $built === null ) {
			return Operational_Snapshot_Result::failure( __( 'Could not build post-change result.', 'aio-page-builder' ), array( 'build_failed' ) );
		}

		$action_id   = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] )
			? substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ), 0, 40 )
			: 'post';
		$snapshot_id = 'op-snap-post-' . $action_id . '-' . gmdate( 'Ymd\THis' ) . '-' . wp_rand( 100, 999 );
		if ( strlen( $snapshot_id ) > Operational_Snapshot_Schema::SNAPSHOT_ID_MAX_LENGTH ) {
			$snapshot_id = substr( $snapshot_id, 0, Operational_Snapshot_Schema::SNAPSHOT_ID_MAX_LENGTH );
		}

		$now          = gmdate( 'c' );
		$actor_ref    = $this->resolve_actor_ref( $envelope );
		$plan_id      = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] : '';
		$plan_item_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) ? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] : '';

		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $snapshot_id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => $built['object_family'],
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => $built['target_ref'],
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => $now,
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => $built['post_change'],
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ?? '',
			Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => $plan_id,
			Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF => $plan_item_id,
			Operational_Snapshot_Schema::FIELD_ACTION_TYPE => $action_type,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS => Operational_Snapshot_Schema::ROLLBACK_STATUS_AVAILABLE,
			Operational_Snapshot_Schema::FIELD_RETENTION   => array(
				'retention_class' => Operational_Snapshot_Schema::RETENTION_CLASS_PLAN_LINKED,
				'retention_notes' => 'Retain until plan archived',
			),
			Operational_Snapshot_Schema::FIELD_PROVENANCE  => array(
				'actor_ref'  => $actor_ref,
				'trigger'    => 'post_execution',
				'source_ref' => $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] ?? '',
			),
		);
		if ( $pre_snapshot_id !== '' ) {
			$snapshot['pre_snapshot_id'] = $pre_snapshot_id;
		}

		$saved = $this->repository->save( $snapshot );
		if ( ! $saved ) {
			return Operational_Snapshot_Result::failure( __( 'Failed to persist post-change snapshot.', 'aio-page-builder' ), array( 'storage_failed' ) );
		}
		return Operational_Snapshot_Result::success( $snapshot_id, __( 'Post-change snapshot captured.', 'aio-page-builder' ), $snapshot );
	}

	private function resolve_actor_ref( array $envelope ): string {
		$actor = $envelope[ Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT ] ?? array();
		if ( ! is_array( $actor ) ) {
			return '';
		}
		$id = $actor[ Execution_Action_Contract::ACTOR_ACTOR_ID ] ?? null;
		if ( is_string( $id ) && $id !== '' ) {
			return $id;
		}
		$user = \wp_get_current_user();
		if ( $user instanceof \WP_User && $user->ID > 0 ) {
			return 'user:' . (string) $user->ID;
		}
		return '';
	}
}
