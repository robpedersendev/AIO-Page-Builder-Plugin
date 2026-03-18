<?php
/**
 * Rollback handler for token-set apply actions (spec §38.5, §41.9).
 *
 * Restores token values from pre_change.state_snapshot to the applied-tokens option.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Handler_Interface;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Result;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Restores design token set from pre-change snapshot.
 */
final class Rollback_Token_Set_Handler implements Rollback_Handler_Interface {

	/**
	 * Restores token values from pre state_snapshot into OPTION_APPLIED_TOKENS (group => [ name => value ]).
	 *
	 * @param array<string, mixed> $pre_snapshot
	 * @param array<string, mixed> $post_snapshot
	 * @param array<string, mixed> $context
	 * @return Rollback_Result
	 */
	public function execute( array $pre_snapshot, array $post_snapshot, array $context = array() ): Rollback_Result {
		$job_id     = isset( $context['job_id'] ) && is_string( $context['job_id'] ) ? $context['job_id'] : '';
		$target_ref = (string) ( $pre_snapshot[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? '' );
		$pre_block  = $pre_snapshot[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] ?? null;
		if ( ! is_array( $pre_block ) ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Pre-change snapshot block missing.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				'',
				'',
				array( 'code' => 'missing_pre_block' )
			);
		}
		$state = $pre_block['state_snapshot'] ?? null;
		if ( ! is_array( $state ) ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Pre-change state_snapshot missing.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				'',
				'',
				array( 'code' => 'missing_state_snapshot' )
			);
		}
		$tokens       = isset( $state['tokens'] ) && is_array( $state['tokens'] ) ? $state['tokens'] : array();
		$token_set_id = isset( $state['token_set_id'] ) ? (string) $state['token_set_id'] : $target_ref;
		if ( strpos( $token_set_id, ':' ) === false ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Invalid token_set_id in snapshot.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				'',
				'',
				array( 'code' => 'invalid_token_set_id' )
			);
		}
		$parts = explode( ':', $token_set_id, 2 );
		$group = trim( $parts[0] ?? '' );
		if ( $group === '' ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Missing token group in snapshot.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				'',
				'',
				array( 'code' => 'missing_group' )
			);
		}
		$store = \get_option( Token_Set_Job_Service::OPTION_APPLIED_TOKENS, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		if ( ! isset( $store[ $group ] ) || ! is_array( $store[ $group ] ) ) {
			$store[ $group ] = array();
		}
		$restored = array();
		foreach ( $tokens as $name => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$value                    = isset( $entry['value'] ) ? $entry['value'] : null;
			$store[ $group ][ $name ] = $value;
			$restored[ $name ]        = $value;
		}
		$updated = \update_option( Token_Set_Job_Service::OPTION_APPLIED_TOKENS, $store );
		if ( ! $updated ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Failed to persist restored token values.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				__( 'Retry or restore manually.', 'aio-page-builder' ),
				'',
				array( 'code' => 'storage_failed' )
			);
		}
		return Rollback_Result::success(
			$job_id,
			$target_ref,
			$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
			$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
			'',
			array(
				'token_set_id' => $token_set_id,
				'restored'     => $restored,
			)
		);
	}
}
