<?php
/**
 * Rollback handler for page replacement actions (spec §38.5, §41.9).
 *
 * Restores post_title, post_name, post_status from pre_change.state_snapshot.
 * Does not restore post_content (not captured in snapshot).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Handler_Interface;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Result;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;

/**
 * Restores page identity and status from pre-change snapshot.
 */
final class Rollback_Page_Replacement_Handler implements Rollback_Handler_Interface {

	/**
	 * Restores post_title, post_name, post_status for the target page from pre state_snapshot.
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
				__( 'Check snapshot integrity.', 'aio-page-builder' ),
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
		$post_id = isset( $state['post_id'] ) ? (int) $state['post_id'] : (int) $target_ref;
		if ( $post_id <= 0 ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Invalid post ID in snapshot.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				'',
				'',
				array( 'code' => 'invalid_post_id' )
			);
		}
		$post = \get_post( $post_id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'page' ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				__( 'Target page no longer exists.', 'aio-page-builder' ),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				__( 'Rollback cannot be applied.', 'aio-page-builder' ),
				'',
				array( 'code' => 'target_missing' )
			);
		}
		$title   = isset( $state['post_title'] ) ? (string) $state['post_title'] : $post->post_title;
		$name    = isset( $state['post_name'] ) ? (string) $state['post_name'] : $post->post_name;
		$status  = isset( $state['post_status'] ) ? (string) $state['post_status'] : $post->post_status;
		$updated = \wp_update_post(
			array(
				'ID'          => $post_id,
				'post_title'  => $title,
				'post_name'   => $name,
				'post_status' => $status,
			),
			true
		);
		if ( \is_wp_error( $updated ) ) {
			return Rollback_Result::failed(
				$job_id,
				$target_ref,
				$updated->get_error_message(),
				false,
				$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
				__( 'Retry or restore manually.', 'aio-page-builder' ),
				'',
				array( 'code' => 'wp_update_post_error' )
			);
		}
		return Rollback_Result::success(
			$job_id,
			$target_ref,
			$pre_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
			$post_snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '',
			'',
			array(
				'restored_title'  => $title,
				'restored_slug'   => $name,
				'restored_status' => $status,
			)
		);
	}
}
