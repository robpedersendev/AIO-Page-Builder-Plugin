<?php
/**
 * Execution handler for assign_page_hierarchy actions (v2-scope-backlog.md §1).
 *
 * Reassigns a page's post_parent to the specified parent page ID.
 * Called only for ITEM_TYPE_HIERARCHY_ASSIGNMENT items with status approved;
 * envelope has already passed Single_Action_Executor validation and dependency checks.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;

/**
 * Handler for assign_page_hierarchy action type. Updates post_parent via wp_update_post.
 * Requires no injected service: the mutation is a single native WordPress call.
 */
final class Assign_Page_Hierarchy_Handler implements Execution_Handler_Interface {

	/**
	 * Executes the hierarchy assignment. Envelope has been validated by Single_Action_Executor.
	 *
	 * target_reference must contain:
	 *   - page_id (int > 0): the page whose post_parent will be updated.
	 *   - parent_page_id (int >= 0): new parent (0 = top-level).
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return array<string, mixed> success, message, artifacts (page_id, old_parent, new_parent, ?no_op).
	 */
	public function execute( array $envelope ): array {
		$target         = is_array( $envelope['target_reference'] ?? null ) ? $envelope['target_reference'] : array();
		$page_id        = isset( $target['page_id'] ) ? (int) $target['page_id'] : 0;
		$parent_page_id = isset( $target['parent_page_id'] ) ? (int) $target['parent_page_id'] : -1;

		if ( $page_id <= 0 ) {
			return array(
				'success' => false,
				'message' => \__( 'Hierarchy assignment failed: page_id is missing or invalid.', 'aio-page-builder' ),
				'errors'  => array( 'page_id_required' ),
			);
		}

		if ( $parent_page_id < 0 ) {
			return array(
				'success' => false,
				'message' => \__( 'Hierarchy assignment failed: parent_page_id is missing or invalid.', 'aio-page-builder' ),
				'errors'  => array( 'parent_page_id_required' ),
			);
		}

		$page = \get_post( $page_id );
		if ( ! $page instanceof \WP_Post ) {
			return array(
				'success' => false,
				/* translators: %d: page ID */
				'message' => \sprintf( \__( 'Hierarchy assignment failed: page %d not found.', 'aio-page-builder' ), $page_id ),
				'errors'  => array( 'page_not_found' ),
			);
		}

		if ( $parent_page_id > 0 && ! \get_post( $parent_page_id ) instanceof \WP_Post ) {
			return array(
				'success' => false,
				/* translators: %d: parent page ID */
				'message' => \sprintf( \__( 'Hierarchy assignment failed: parent page %d not found.', 'aio-page-builder' ), $parent_page_id ),
				'errors'  => array( 'parent_not_found' ),
			);
		}

		$old_parent = (int) $page->post_parent;

		// * No-op: parent already matches; return success without writing to avoid unnecessary revision.
		if ( $old_parent === $parent_page_id ) {
			return array(
				'success'   => true,
				'message'   => \__( 'Hierarchy already matches; no change needed.', 'aio-page-builder' ),
				'artifacts' => array(
					'page_id'    => $page_id,
					'old_parent' => $old_parent,
					'new_parent' => $parent_page_id,
					'no_op'      => true,
				),
			);
		}

		$update_result = \wp_update_post(
			array(
				'ID'          => $page_id,
				'post_parent' => $parent_page_id,
			),
			true
		);

		if ( \is_wp_error( $update_result ) ) {
			return array(
				'success' => false,
				/* translators: %s: error message from wp_update_post */
				'message' => \sprintf( \__( 'Hierarchy assignment failed: %s', 'aio-page-builder' ), $update_result->get_error_message() ),
				'errors'  => array( 'wp_update_post_error' ),
			);
		}

		if ( 0 === $update_result ) {
			return array(
				'success' => false,
				'message' => \__( 'Hierarchy assignment failed: wp_update_post returned 0.', 'aio-page-builder' ),
				'errors'  => array( 'wp_update_post_zero' ),
			);
		}

		return array(
			'success'   => true,
			/* translators: 1: page ID 2: old parent ID 3: new parent ID */
			'message'   => \sprintf(
				\__( 'Page %1$d post_parent changed from %2$d to %3$d.', 'aio-page-builder' ),
				$page_id,
				$old_parent,
				$parent_page_id
			),
			'artifacts' => array(
				'page_id'    => $page_id,
				'old_parent' => $old_parent,
				'new_parent' => $parent_page_id,
			),
		);
	}
}
