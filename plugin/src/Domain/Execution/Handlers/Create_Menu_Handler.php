<?php
/**
 * Execution handler for create_menu actions (v2-scope-backlog.md §2).
 *
 * Creates a net-new WordPress nav menu, optionally assigns it to a registered theme location,
 * and optionally seeds it with initial menu items. Distinct from Apply_Menu_Change_Handler
 * (UPDATE_MENU), which handles rename/replace/update_existing flows. No service dependency
 * required: the mutation is a sequence of native WordPress nav-menu API calls.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Handlers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Executor\Execution_Handler_Interface;

/**
 * Handler for create_menu action type. Calls wp_create_nav_menu() then optionally assigns
 * the new menu to a theme location and seeds initial nav-menu items.
 */
final class Create_Menu_Handler implements Execution_Handler_Interface {

	/**
	 * Executes the menu creation. Envelope has been validated by Single_Action_Executor.
	 *
	 * target_reference must contain:
	 *   - menu_name (string, non-empty): name passed to wp_create_nav_menu().
	 * target_reference may contain:
	 *   - theme_location (string): registered theme location slug; skipped if not registered.
	 *   - items (array): seed items; each may have title, url, type, object_id.
	 *
	 * @param array<string, mixed> $envelope Governed action envelope.
	 * @return array<string, mixed> success, message, artifacts (menu_id, menu_name, location_assigned, items_applied, ?location_skipped_reason).
	 */
	public function execute( array $envelope ): array {
		$target         = is_array( $envelope['target_reference'] ?? null ) ? $envelope['target_reference'] : array();
		$menu_name      = isset( $target['menu_name'] ) && is_string( $target['menu_name'] ) ? \trim( $target['menu_name'] ) : '';
		$theme_location = isset( $target['theme_location'] ) && is_string( $target['theme_location'] ) ? \trim( $target['theme_location'] ) : '';
		$items          = isset( $target['items'] ) && is_array( $target['items'] ) ? $target['items'] : array();

		if ( $menu_name === '' ) {
			return array(
				'success' => false,
				'message' => \__( 'Menu creation failed: menu_name is missing or empty.', 'aio-page-builder' ),
				'errors'  => array( 'menu_name_required' ),
			);
		}

		$created = \wp_create_nav_menu( $menu_name );

		if ( \is_wp_error( $created ) ) {
			return array(
				'success' => false,
				/* translators: %s: error message from wp_create_nav_menu */
				'message' => \sprintf( \__( 'Menu creation failed: %s', 'aio-page-builder' ), $created->get_error_message() ),
				'errors'  => array( 'wp_create_nav_menu_error' ),
			);
		}

		$menu_id = (int) $created;
		if ( $menu_id <= 0 ) {
			return array(
				'success' => false,
				'message' => \__( 'Menu creation failed: wp_create_nav_menu returned an invalid menu ID.', 'aio-page-builder' ),
				'errors'  => array( 'invalid_menu_id' ),
			);
		}

		$location_assigned       = false;
		$location_skipped_reason = '';

		if ( $theme_location !== '' ) {
			$registered_locations = \get_registered_nav_menus();
			if ( is_array( $registered_locations ) && isset( $registered_locations[ $theme_location ] ) ) {
				$this->assign_to_location( $menu_id, $theme_location );
				$location_assigned = true;
			} else {
				// * Location not registered in this theme; skip silently and surface in artifacts.
				$location_skipped_reason = 'not_registered';
			}
		}

		$items_applied = $this->apply_items( $menu_id, $items );

		$artifacts = array(
			'menu_id'           => $menu_id,
			'menu_name'         => $menu_name,
			'location_assigned' => $location_assigned,
			'items_applied'     => $items_applied,
		);
		if ( $location_skipped_reason !== '' ) {
			$artifacts['location_skipped_reason'] = $location_skipped_reason;
		}
		if ( $theme_location !== '' ) {
			$artifacts['theme_location'] = $theme_location;
		}

		return array(
			'success'   => true,
			/* translators: 1: menu name 2: menu term ID */
			'message'   => \sprintf(
				\__( 'Menu "%1$s" created (term ID %2$d).', 'aio-page-builder' ),
				$menu_name,
				$menu_id
			),
			'artifacts' => $artifacts,
		);
	}

	/**
	 * Assigns an existing nav menu to a theme location via theme_mod.
	 *
	 * @param int    $menu_id       Nav menu term ID.
	 * @param string $location_slug Registered theme location slug.
	 * @return void
	 */
	private function assign_to_location( int $menu_id, string $location_slug ): void {
		$locations = \get_theme_mod( 'nav_menu_locations' );
		if ( ! is_array( $locations ) ) {
			$locations = array();
		}
		$locations[ $location_slug ] = $menu_id;
		\set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Seeds the new menu with initial items. Skips items with no identifiable content.
	 *
	 * @param int                            $menu_id Nav menu term ID.
	 * @param array<int, array<string,mixed>> $items   Seed item descriptors.
	 * @return int Number of items successfully added.
	 */
	private function apply_items( int $menu_id, array $items ): int {
		$count    = 0;
		$position = 0;
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title     = isset( $item['title'] ) && is_string( $item['title'] ) ? \sanitize_text_field( $item['title'] ) : '';
			$url       = isset( $item['url'] ) && is_string( $item['url'] ) ? \esc_url_raw( $item['url'] ) : '';
			$type      = isset( $item['type'] ) && is_string( $item['type'] ) ? $item['type'] : 'custom';
			$object_id = isset( $item['object_id'] ) && is_numeric( $item['object_id'] ) ? (int) $item['object_id'] : 0;

			if ( $title === '' && $url === '' && $object_id <= 0 ) {
				continue;
			}

			$item_data = array(
				'menu-item-position' => $position,
				'menu-item-type'     => $type,
				'menu-item-title'    => $title !== '' ? $title : ( $url !== '' ? $url : (string) $object_id ),
				'menu-item-status'   => 'publish',
			);
			if ( $url !== '' ) {
				$item_data['menu-item-url'] = $url;
			}
			if ( $object_id > 0 && in_array( $type, array( 'post_type', 'page' ), true ) ) {
				$item_data['menu-item-object-id'] = $object_id;
				$item_data['menu-item-object']    = $type === 'page' ? 'page' : 'post';
			}

			$result = \wp_update_nav_menu_item( $menu_id, 0, $item_data );
			if ( ! \is_wp_error( $result ) && $result > 0 ) {
				++$count;
			}
			++$position;
		}
		return $count;
	}
}
