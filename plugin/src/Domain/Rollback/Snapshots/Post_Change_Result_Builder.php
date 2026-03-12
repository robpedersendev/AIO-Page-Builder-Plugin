<?php
/**
 * Builds post-change result_snapshot for operational snapshots (spec §41.3; operational-snapshot-schema.md §6).
 *
 * Object-family-specific: page, menu, token_set. Uses handler_result artifacts.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;

/**
 * Builds post_change block and target_ref/object_family from envelope and handler result.
 */
final class Post_Change_Result_Builder {

	/**
	 * Builds post-change result_snapshot and returns target_ref, object_family, and post_change block; or null if not supported.
	 *
	 * @param array<string, mixed> $envelope Action envelope.
	 * @param array<string, mixed> $handler_result success, message, artifacts.
	 * @return array{target_ref: string, object_family: string, post_change: array<string, mixed>}|null
	 */
	public function build( array $envelope, array $handler_result ): ?array {
		$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ]
			: '';
		$artifacts = isset( $handler_result['artifacts'] ) && is_array( $handler_result['artifacts'] ) ? $handler_result['artifacts'] : array();
		$success   = ! empty( $handler_result['success'] );

		if ( $action_type === Execution_Action_Types::REPLACE_PAGE ) {
			return $this->build_page_post( $artifacts, $success, $handler_result );
		}
		if ( $action_type === Execution_Action_Types::UPDATE_MENU ) {
			return $this->build_menu_post( $artifacts, $success, $handler_result );
		}
		if ( $action_type === Execution_Action_Types::APPLY_TOKEN_SET ) {
			return $this->build_token_set_post( $envelope, $artifacts, $success, $handler_result );
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $artifacts
	 * @param bool                 $success
	 * @param array<string, mixed> $handler_result
	 * @return array{target_ref: string, object_family: string, post_change: array<string, mixed>}|null
	 */
	private function build_page_post( array $artifacts, bool $success, array $handler_result ): ?array {
		$post_id = isset( $artifacts['target_post_id'] ) && is_numeric( $artifacts['target_post_id'] ) ? (int) $artifacts['target_post_id'] : 0;
		if ( $post_id <= 0 ) {
			return null;
		}
		$result_snapshot = array( 'post_id' => $post_id );
		$post = \get_post( $post_id );
		if ( $post instanceof \WP_Post && $post->post_type === 'page' ) {
			$result_snapshot['post_title']   = $post->post_title;
			$result_snapshot['post_name']    = $post->post_name;
			$result_snapshot['post_status']  = $post->post_status;
		}
		if ( isset( $artifacts['superseded_post_id'] ) && (int) $artifacts['superseded_post_id'] > 0 ) {
			$result_snapshot['previous_post_id'] = (int) $artifacts['superseded_post_id'];
		}
		$message = isset( $handler_result['message'] ) && is_string( $handler_result['message'] ) ? $handler_result['message'] : '';
		return array(
			'target_ref'    => (string) $post_id,
			'object_family' => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			'post_change'   => array(
				'captured_at'     => gmdate( 'c' ),
				'result_snapshot' => $result_snapshot,
				'outcome'         => $success ? 'success' : 'failed',
				'message'         => substr( $message, 0, 512 ),
			),
		);
	}

	/**
	 * @param array<string, mixed> $artifacts
	 * @param bool                 $success
	 * @param array<string, mixed> $handler_result
	 * @return array{target_ref: string, object_family: string, post_change: array<string, mixed>}|null
	 */
	private function build_menu_post( array $artifacts, bool $success, array $handler_result ): ?array {
		$menu_id = isset( $artifacts['menu_id'] ) && is_numeric( $artifacts['menu_id'] ) ? (int) $artifacts['menu_id'] : 0;
		if ( $menu_id <= 0 ) {
			return null;
		}
		$result_snapshot = array( 'menu_id' => $menu_id );
		if ( isset( $artifacts['menu_name'] ) ) {
			$result_snapshot['name'] = $artifacts['menu_name'];
		}
		if ( isset( $artifacts['location_assigned'] ) ) {
			$result_snapshot['location'] = $artifacts['location_assigned'];
		}
		$message = isset( $handler_result['message'] ) && is_string( $handler_result['message'] ) ? $handler_result['message'] : '';
		return array(
			'target_ref'    => (string) $menu_id,
			'object_family' => Operational_Snapshot_Schema::OBJECT_FAMILY_MENU,
			'post_change'   => array(
				'captured_at'      => gmdate( 'c' ),
				'result_snapshot'  => $result_snapshot,
				'outcome'          => $success ? 'success' : 'failed',
				'message'          => substr( $message, 0, 512 ),
			),
		);
	}

	/**
	 * @param array<string, mixed> $envelope
	 * @param array<string, mixed> $artifacts
	 * @param bool                 $success
	 * @param array<string, mixed> $handler_result
	 * @return array{target_ref: string, object_family: string, post_change: array<string, mixed>}|null
	 */
	private function build_token_set_post( array $envelope, array $artifacts, bool $success, array $handler_result ): ?array {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();
		$group = isset( $target['token_group'] ) && is_string( $target['token_group'] ) ? trim( $target['token_group'] ) : '';
		$name  = isset( $target['token_name'] ) && is_string( $target['token_name'] ) ? trim( $target['token_name'] ) : '';
		if ( $group === '' || $name === '' ) {
			$group = isset( $artifacts['token_group'] ) && is_string( $artifacts['token_group'] ) ? trim( $artifacts['token_group'] ) : '';
			$name  = isset( $artifacts['token_name'] ) && is_string( $artifacts['token_name'] ) ? trim( $artifacts['token_name'] ) : '';
		}
		if ( $group === '' || $name === '' ) {
			return null;
		}
		$token_set_ref = $group . ':' . $name;
		$result_snapshot = array(
			'token_set_id' => $token_set_ref,
			'tokens'       => array(),
		);
		if ( isset( $artifacts['applied_value'] ) ) {
			$result_snapshot['tokens'][ $name ] = array( 'value' => $artifacts['applied_value'] );
		}
		$message = isset( $handler_result['message'] ) && is_string( $handler_result['message'] ) ? $handler_result['message'] : '';
		return array(
			'target_ref'    => $token_set_ref,
			'object_family' => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
			'post_change'   => array(
				'captured_at'      => gmdate( 'c' ),
				'result_snapshot'  => $result_snapshot,
				'outcome'          => $success ? 'success' : 'failed',
				'message'          => substr( $message, 0, 512 ),
			),
		);
	}
}
