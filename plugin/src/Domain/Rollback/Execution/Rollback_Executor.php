<?php
/**
 * Rollback execution: revalidate eligibility, dispatch to handler, record outcome (spec §38.5, §41.9, §59.11).
 *
 * Does not run inline from UI; invoked from queue job processor after confirmation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Execution;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Rollback\Execution\Handlers\Rollback_Page_Replacement_Handler;
use AIOPageBuilder\Domain\Rollback\Execution\Handlers\Rollback_Token_Set_Handler;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;

/**
 * Revalidates eligibility, loads snapshots, dispatches to typed handler, marks snapshot used on success.
 */
final class Rollback_Executor {

	/** @var Rollback_Eligibility_Service */
	private Rollback_Eligibility_Service $eligibility_service;

	/** @var Operational_Snapshot_Repository_Interface */
	private Operational_Snapshot_Repository_Interface $repository;

	/** @var array<string, Rollback_Handler_Interface> */
	private array $handlers = array();

	public function __construct(
		Rollback_Eligibility_Service $eligibility_service,
		Operational_Snapshot_Repository_Interface $repository
	) {
		$this->eligibility_service = $eligibility_service;
		$this->repository          = $repository;
		$this->register_default_handlers();
	}

	private function register_default_handlers(): void {
		$this->handlers[ Execution_Action_Types::REPLACE_PAGE ]  = new Rollback_Page_Replacement_Handler();
		$this->handlers[ Execution_Action_Types::APPLY_TOKEN_SET ] = new Rollback_Token_Set_Handler();
	}

	/**
	 * Registers a handler for an action type (e.g. replace_page, apply_token_set). Navigation not supported by default.
	 *
	 * @param string                        $action_type
	 * @param Rollback_Handler_Interface $handler
	 * @return void
	 */
	public function register_handler( string $action_type, Rollback_Handler_Interface $handler ): void {
		$this->handlers[ $action_type ] = $handler;
	}

	/**
	 * Executes rollback for the given job payload. Revalidates eligibility first; dispatches to handler; marks snapshot used on success.
	 *
	 * @param array<string, mixed> $payload Must contain pre_snapshot_id, post_snapshot_id, rollback_handler_key; optional job_id, build_plan_ref, plan_item_ref.
	 * @return Rollback_Result
	 */
	public function execute( array $payload ): Rollback_Result {
		$job_id            = isset( $payload['job_id'] ) && is_string( $payload['job_id'] ) ? $payload['job_id'] : '';
		$pre_snapshot_id   = isset( $payload['pre_snapshot_id'] ) && is_string( $payload['pre_snapshot_id'] ) ? trim( $payload['pre_snapshot_id'] ) : '';
		$post_snapshot_id  = isset( $payload['post_snapshot_id'] ) && is_string( $payload['post_snapshot_id'] ) ? trim( $payload['post_snapshot_id'] ) : '';
		$handler_key       = isset( $payload['rollback_handler_key'] ) && is_string( $payload['rollback_handler_key'] ) ? trim( $payload['rollback_handler_key'] ) : '';
		$target_ref        = isset( $payload['target_ref'] ) && is_string( $payload['target_ref'] ) ? $payload['target_ref'] : '';

		if ( $pre_snapshot_id === '' || $post_snapshot_id === '' ) {
			return Rollback_Result::ineligible(
				$job_id,
				$target_ref,
				__( 'Missing pre or post snapshot ID.', 'aio-page-builder' ),
				$pre_snapshot_id,
				$post_snapshot_id,
				__( 'Provide valid snapshot references.', 'aio-page-builder' ),
				array( 'code' => 'missing_snapshot_ids' )
			);
		}

		$eligibility = $this->eligibility_service->evaluate( $pre_snapshot_id, $post_snapshot_id, array( 'skip_permission_check' => true ) );
		if ( ! $eligibility->is_eligible() ) {
			$reasons = $eligibility->get_blocking_reasons();
			$reason  = implode( ', ', $reasons );
			return Rollback_Result::ineligible(
				$job_id,
				$eligibility->get_rollback_handler_key() !== '' ? $eligibility->get_rollback_handler_key() : $target_ref,
				$eligibility->get_message() . ' (' . $reason . ')',
				$pre_snapshot_id,
				$post_snapshot_id,
				__( 'Do not retry until eligibility is restored.', 'aio-page-builder' ),
				array( 'blocking_reasons' => $reasons )
			);
		}

		if ( $handler_key === '' ) {
			$handler_key = $eligibility->get_rollback_handler_key();
		}
		if ( $handler_key === '' || ! isset( $this->handlers[ $handler_key ] ) ) {
			return Rollback_Result::ineligible(
				$job_id,
				$target_ref,
				__( 'No rollback handler for this action type.', 'aio-page-builder' ),
				$pre_snapshot_id,
				$post_snapshot_id,
				__( 'Unsupported rollback family.', 'aio-page-builder' ),
				array( 'code' => 'no_handler', 'handler_key' => $handler_key )
			);
		}

		$pre_snapshot  = $this->repository->get_by_id( $pre_snapshot_id );
		$post_snapshot = $this->repository->get_by_id( $post_snapshot_id );
		if ( $pre_snapshot === null || $post_snapshot === null ) {
			return Rollback_Result::ineligible(
				$job_id,
				$target_ref,
				__( 'Snapshot no longer available.', 'aio-page-builder' ),
				$pre_snapshot_id,
				$post_snapshot_id,
				'',
				array( 'code' => 'snapshot_gone' )
			);
		}

		$context = array_merge(
			array( 'job_id' => $job_id ),
			$payload
		);
		$handler = $this->handlers[ $handler_key ];
		$result  = $handler->execute( $pre_snapshot, $post_snapshot, $context );

		if ( $result->is_success() ) {
			$this->mark_snapshot_used( $pre_snapshot_id );
		}

		return $result;
	}

	private function mark_snapshot_used( string $pre_snapshot_id ): void {
		$snap = $this->repository->get_by_id( $pre_snapshot_id );
		if ( $snap === null ) {
			return;
		}
		$snap[ Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS ] = Operational_Snapshot_Schema::ROLLBACK_STATUS_USED;
		$this->repository->save( $snap );
	}
}
