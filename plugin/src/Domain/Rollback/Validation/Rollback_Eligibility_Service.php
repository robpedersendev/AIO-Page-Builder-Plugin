<?php
/**
 * Rollback eligibility validation (spec §38.4, §38.5, §41.8, §41.9, §59.11).
 *
 * Evaluates whether a historical action can be safely offered for rollback. Does not execute rollback.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Validation;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Determines rollback eligibility from snapshot presence, handler support, target resolution,
 * newer-change conflicts, and permission. Returns explicit eligibility result with blockers and warnings.
 */
final class Rollback_Eligibility_Service {

	/** Action types that have a rollback handler (spec §38.4). */
	private const ROLLBACK_ACTION_TYPES = array(
		Execution_Action_Types::REPLACE_PAGE,
		Execution_Action_Types::UPDATE_MENU,
		Execution_Action_Types::APPLY_TOKEN_SET,
	);

	/** @var Operational_Snapshot_Repository_Interface */
	private Operational_Snapshot_Repository_Interface $repository;

	public function __construct( Operational_Snapshot_Repository_Interface $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Evaluates whether the action identified by pre/post snapshots is eligible for rollback.
	 *
	 * @param string               $pre_snapshot_id  Pre-change snapshot ID.
	 * @param string               $post_snapshot_id Post-change snapshot ID.
	 * @param array<string, mixed> $options Optional: skip_permission_check (bool), user_id (int).
	 * @return Rollback_Eligibility_Result
	 */
	public function evaluate( string $pre_snapshot_id, string $post_snapshot_id, array $options = array() ): Rollback_Eligibility_Result {
		$pre_snapshot_id  = trim( $pre_snapshot_id );
		$post_snapshot_id = trim( $post_snapshot_id );
		$skip_permission  = ! empty( $options['skip_permission_check'] );
		$required_cap     = Capabilities::EXECUTE_ROLLBACKS;

		$pre = $this->repository->get_by_id( $pre_snapshot_id );
		if ( $pre === null ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::PRE_SNAPSHOT_MISSING ),
				'',
				$pre_snapshot_id,
				$post_snapshot_id,
				'',
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap ),
				__( 'Pre-change snapshot not found.', 'aio-page-builder' )
			);
		}

		$post = $this->repository->get_by_id( $post_snapshot_id );
		if ( $post === null ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::POST_SNAPSHOT_MISSING ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				(string) ( $pre[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' ),
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap ),
				__( 'Post-change snapshot not found.', 'aio-page-builder' )
			);
		}

		$execution_ref = (string) ( $post[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? $pre[ Operational_Snapshot_Schema::FIELD_EXECUTION_REF ] ?? '' );

		$pre_type  = isset( $pre[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ) ? (string) $pre[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] : '';
		$post_type = isset( $post[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ) ? (string) $post[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] : '';
		if ( $pre_type !== Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::PRE_SNAPSHOT_TYPE_INVALID ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}
		if ( $post_type !== Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::POST_SNAPSHOT_TYPE_INVALID ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}

		$status = isset( $pre[ Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS ] ) ? (string) $pre[ Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS ] : '';
		if ( $status === Operational_Snapshot_Schema::ROLLBACK_STATUS_USED ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::SNAPSHOT_USED ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}
		if ( $status === Operational_Snapshot_Schema::ROLLBACK_STATUS_EXPIRED ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::SNAPSHOT_EXPIRED ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}
		if ( $status === Operational_Snapshot_Schema::ROLLBACK_STATUS_INVALIDATED ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::SNAPSHOT_INVALIDATED ),
				$this->action_type_from_snapshot( $pre ),
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}

		$action_type = $this->action_type_from_snapshot( $pre );
		if ( $action_type === '' || ! in_array( $action_type, self::ROLLBACK_ACTION_TYPES, true ) ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::NO_HANDLER_FOR_ACTION_TYPE ),
				$action_type,
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_UNKNOWN,
				array(),
				array( $required_cap )
			);
		}

		$target_ref    = (string) ( $pre[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? '' );
		$object_family = (string) ( $pre[ Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY ] ?? '' );
		$target_state  = $this->resolve_target( $target_ref, $object_family );
		if ( $target_state !== Rollback_Eligibility_Result::TARGET_RESOLVED ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::TARGET_UNRESOLVABLE ),
				$action_type,
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				$target_state,
				array(),
				array( $required_cap )
			);
		}

		$pre_created_at = $this->snapshot_created_at( $pre );
		$for_target     = $this->repository->list_snapshot_created_times_for_target( $target_ref );
		foreach ( $for_target as $sid => $ts ) {
			if ( $sid === $pre_snapshot_id || $sid === $post_snapshot_id ) {
				continue;
			}
			if ( $ts > $pre_created_at ) {
				return Rollback_Eligibility_Result::ineligible(
					array( Rollback_Blocking_Reasons::NEWER_CHANGE_CONFLICT ),
					$action_type,
					$pre_snapshot_id,
					$post_snapshot_id,
					$execution_ref,
					Rollback_Eligibility_Result::TARGET_RESOLVED,
					array(),
					array( $required_cap ),
					__( 'A later change to this target exists; rollback would conflict.', 'aio-page-builder' )
				);
			}
		}

		if ( ! $skip_permission && ! \current_user_can( $required_cap ) ) {
			return Rollback_Eligibility_Result::ineligible(
				array( Rollback_Blocking_Reasons::PERMISSION_DENIED ),
				$action_type,
				$pre_snapshot_id,
				$post_snapshot_id,
				$execution_ref,
				Rollback_Eligibility_Result::TARGET_RESOLVED,
				array(),
				array( $required_cap )
			);
		}

		return Rollback_Eligibility_Result::eligible(
			$action_type,
			$pre_snapshot_id,
			$post_snapshot_id,
			$execution_ref,
			array(),
			array( $required_cap )
		);
	}

	/**
	 * @param array<string, mixed> $snapshot
	 * @return string
	 */
	private function action_type_from_snapshot( array $snapshot ): string {
		$action = isset( $snapshot[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ] ) ? (string) $snapshot[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ] : '';
		return $action;
	}

	/**
	 * @param array<string, mixed> $snapshot
	 * @return int
	 */
	private function snapshot_created_at( array $snapshot ): int {
		$created = isset( $snapshot[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snapshot[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
			? $snapshot[ Operational_Snapshot_Schema::FIELD_CREATED_AT ]
			: '';
		return $created !== '' ? strtotime( $created ) : 0;
	}

	/**
	 * Resolves target object existence. Returns TARGET_RESOLVED, TARGET_MISSING, or TARGET_INVALID.
	 *
	 * @param string $target_ref
	 * @param string $object_family
	 * @return string
	 */
	private function resolve_target( string $target_ref, string $object_family ): string {
		if ( $target_ref === '' ) {
			return Rollback_Eligibility_Result::TARGET_MISSING;
		}
		switch ( $object_family ) {
			case Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE:
				$post_id = (int) $target_ref;
				if ( $post_id <= 0 ) {
					return Rollback_Eligibility_Result::TARGET_MISSING;
				}
				$post = \get_post( $post_id );
				return ( $post instanceof \WP_Post && $post->post_type === 'page' ) ? Rollback_Eligibility_Result::TARGET_RESOLVED : Rollback_Eligibility_Result::TARGET_MISSING;
			case Operational_Snapshot_Schema::OBJECT_FAMILY_MENU:
				$term_id = (int) $target_ref;
				if ( $term_id <= 0 ) {
					return Rollback_Eligibility_Result::TARGET_MISSING;
				}
				$term = \get_term( $term_id, 'nav_menu' );
				return ( $term instanceof \WP_Term ) ? Rollback_Eligibility_Result::TARGET_RESOLVED : Rollback_Eligibility_Result::TARGET_MISSING;
			case Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET:
				$store = \get_option( Token_Set_Job_Service::OPTION_APPLIED_TOKENS, array() );
				if ( ! is_array( $store ) ) {
					return Rollback_Eligibility_Result::TARGET_MISSING;
				}
				if ( strpos( $target_ref, ':' ) !== false ) {
					$parts = explode( ':', $target_ref, 2 );
					$group = trim( $parts[0] ?? '' );
					return isset( $store[ $group ] ) && is_array( $store[ $group ] ) ? Rollback_Eligibility_Result::TARGET_RESOLVED : Rollback_Eligibility_Result::TARGET_MISSING;
				}
				return Rollback_Eligibility_Result::TARGET_RESOLVED;
			default:
				return Rollback_Eligibility_Result::TARGET_MISSING;
		}
	}
}
