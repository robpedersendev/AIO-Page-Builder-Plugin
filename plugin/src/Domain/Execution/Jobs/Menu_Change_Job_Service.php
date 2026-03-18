<?php
/**
 * Menu/navigation change execution (spec §34, §40.2; Prompt 083).
 *
 * Governed create/rename/replace/update_existing with location assignment.
 * Validates menu_context and target state; uses WordPress nav menu API.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Jobs;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;

/**
 * Applies approved menu changes via WordPress nav menu API.
 */
final class Menu_Change_Job_Service implements Menu_Change_Job_Service_Interface {

	/** Map plan menu_context to theme location slug (spec §34). */
	private const CONTEXT_TO_LOCATION = array(
		'header'     => 'primary',
		'footer'     => 'footer',
		'mobile'     => 'mobile',
		'off_canvas' => 'off_canvas',
		'sidebar'    => 'sidebar',
	);

	/**
	 * Runs the menu change flow. Validates target; creates/updates menu and assigns location.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference with menu_context, action, proposed_menu_name, items).
	 * @return Menu_Change_Result
	 */
	public function run( array $envelope ): Menu_Change_Result {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();

		$menu_context = isset( $target['menu_context'] ) && is_string( $target['menu_context'] ) ? trim( $target['menu_context'] ) : '';
		$action       = isset( $target['action'] ) && is_string( $target['action'] ) ? trim( $target['action'] ) : '';
		$menu_name    = isset( $target['proposed_menu_name'] ) && is_string( $target['proposed_menu_name'] ) ? trim( $target['proposed_menu_name'] ) : '';
		$items        = isset( $target['items'] ) && is_array( $target['items'] ) ? $target['items'] : array();

		if ( $menu_context === '' ) {
			return Menu_Change_Result::failure( __( 'Missing menu context.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_INVALID_ENVELOPE ) );
		}
		if ( $action === '' || ! in_array( $action, array( 'create', 'rename', 'replace', 'update_existing' ), true ) ) {
			return Menu_Change_Result::failure( __( 'Invalid or missing menu action.', 'aio-page-builder' ), array( 'invalid_action' ) );
		}
		if ( $menu_name === '' && in_array( $action, array( 'create', 'replace' ), true ) ) {
			return Menu_Change_Result::failure( __( 'Proposed menu name is required for create/replace.', 'aio-page-builder' ), array( 'invalid_target' ) );
		}

		$location_slug = self::CONTEXT_TO_LOCATION[ $menu_context ] ?? $menu_context;

		if ( $action === 'create' ) {
			return $this->do_create( $menu_name, $location_slug, $items );
		}
		if ( $action === 'rename' ) {
			$existing_id = $this->resolve_menu_for_context( $menu_context, $target );
			if ( $existing_id <= 0 ) {
				return Menu_Change_Result::failure( __( 'Menu to rename could not be resolved.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
			}
			return $this->do_rename( $existing_id, $menu_name ?: null );
		}
		if ( $action === 'replace' ) {
			return $this->do_replace( $menu_name, $location_slug, $items, $menu_context );
		}
		if ( $action === 'update_existing' ) {
			$existing_id = $this->resolve_menu_for_context( $menu_context, $target );
			if ( $existing_id <= 0 ) {
				return Menu_Change_Result::failure( __( 'Existing menu could not be resolved.', 'aio-page-builder' ), array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
			}
			return $this->do_update_items( $existing_id, $items );
		}

		return Menu_Change_Result::failure( __( 'Unsupported menu action.', 'aio-page-builder' ), array( 'invalid_action' ) );
	}

	/**
	 * Create new menu and assign to location.
	 *
	 * @param string            $menu_name
	 * @param string            $location_slug
	 * @param array<int, mixed> $items
	 * @return Menu_Change_Result
	 */
	private function do_create( string $menu_name, string $location_slug, array $items ): Menu_Change_Result {
		$created = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $created ) ) {
			return Menu_Change_Result::failure(
				$created->get_error_message(),
				array( 'menu_create_failed' )
			);
		}
		$menu_id = (int) $created;
		$this->assign_menu_to_location( $menu_id, $location_slug );
		$this->apply_menu_items( $menu_id, $items );
		return Menu_Change_Result::success( $menu_id, 'create', $menu_name, $location_slug );
	}

	/**
	 * Rename existing menu.
	 *
	 * @param int         $menu_id
	 * @param string|null $new_name
	 * @return Menu_Change_Result
	 */
	private function do_rename( int $menu_id, ?string $new_name ): Menu_Change_Result {
		$term         = get_term( $menu_id, 'nav_menu' );
		$current_name = ( $term instanceof \WP_Term ) ? $term->name : '';
		$name         = $new_name !== null && $new_name !== '' ? $new_name : $current_name;
		$updated      = wp_update_nav_menu_object( $menu_id, array( 'menu-name' => $name ) );
		if ( is_wp_error( $updated ) ) {
			return Menu_Change_Result::failure( $updated->get_error_message(), array( 'menu_update_failed' ) );
		}
		return Menu_Change_Result::success( $menu_id, 'rename', $name, '' );
	}

	/**
	 * Replace: create new menu, assign to location, clear previous assignment for that location.
	 *
	 * @param string            $menu_name
	 * @param string            $location_slug
	 * @param array<int, mixed> $items
	 * @param string            $menu_context
	 * @return Menu_Change_Result
	 */
	private function do_replace( string $menu_name, string $location_slug, array $items, string $menu_context ): Menu_Change_Result {
		$created = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $created ) ) {
			return Menu_Change_Result::failure( $created->get_error_message(), array( 'menu_create_failed' ) );
		}
		$menu_id = (int) $created;
		$this->assign_menu_to_location( $menu_id, $location_slug );
		$this->apply_menu_items( $menu_id, $items );
		return Menu_Change_Result::success( $menu_id, 'replace', $menu_name, $location_slug );
	}

	/**
	 * Update items on existing menu.
	 *
	 * @param int               $menu_id
	 * @param array<int, mixed> $items
	 * @return Menu_Change_Result
	 */
	private function do_update_items( int $menu_id, array $items ): Menu_Change_Result {
		$term = get_term( $menu_id, 'nav_menu' );
		$name = ( $term instanceof \WP_Term ) ? $term->name : '';
		$this->apply_menu_items( $menu_id, $items );
		return Menu_Change_Result::success( $menu_id, 'update_existing', $name, '', array( 'items_updated' => count( $items ) ) );
	}

	/**
	 * Assign menu to theme location.
	 *
	 * @param int    $menu_id
	 * @param string $location_slug
	 */
	private function assign_menu_to_location( int $menu_id, string $location_slug ): void {
		if ( $location_slug === '' ) {
			return;
		}
		$locations = get_theme_mod( 'nav_menu_locations' );
		if ( ! is_array( $locations ) ) {
			$locations = array();
		}
		$locations[ $location_slug ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 * Add/update menu items. Each item: title, url, type (custom/page/post), object_id (optional).
	 *
	 * @param int               $menu_id
	 * @param array<int, mixed> $items
	 */
	private function apply_menu_items( int $menu_id, array $items ): void {
		$position = 0;
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title     = isset( $item['title'] ) && is_string( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$url       = isset( $item['url'] ) && is_string( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			$type      = isset( $item['type'] ) && is_string( $item['type'] ) ? $item['type'] : 'custom';
			$object_id = isset( $item['object_id'] ) && is_numeric( $item['object_id'] ) ? (int) $item['object_id'] : 0;
			if ( $title === '' && $url === '' && $object_id <= 0 ) {
				continue;
			}
			$item_data = array(
				'menu-item-position' => $position,
				'menu-item-type'     => $type,
				'menu-item-title'    => $title !== '' ? $title : ( $url ?: (string) $object_id ),
				'menu-item-status'   => 'publish',
			);
			if ( $url !== '' ) {
				$item_data['menu-item-url'] = $url;
			}
			if ( $object_id > 0 && in_array( $type, array( 'post_type', 'page' ), true ) ) {
				$item_data['menu-item-object-id'] = $object_id;
				$item_data['menu-item-object']    = $type === 'page' ? 'page' : 'post';
			}
			wp_update_nav_menu_item( $menu_id, 0, $item_data );
			++$position;
		}
	}

	/**
	 * Resolve nav menu term_id for a context (from current theme location or target ref).
	 *
	 * @param string               $menu_context
	 * @param array<string, mixed> $target
	 * @return int 0 if not found.
	 */
	private function resolve_menu_for_context( string $menu_context, array $target ): int {
		$location_slug = self::CONTEXT_TO_LOCATION[ $menu_context ] ?? $menu_context;
		$locations     = get_theme_mod( 'nav_menu_locations' );
		if ( is_array( $locations ) && isset( $locations[ $location_slug ] ) && (int) $locations[ $location_slug ] > 0 ) {
			return (int) $locations[ $location_slug ];
		}
		if ( isset( $target['existing_menu_id'] ) && is_numeric( $target['existing_menu_id'] ) ) {
			$id   = (int) $target['existing_menu_id'];
			$term = get_term( $id, 'nav_menu' );
			if ( $term instanceof \WP_Term ) {
				return $id;
			}
		}
		$name = isset( $target['existing_menu_name'] ) && is_string( $target['existing_menu_name'] ) ? trim( $target['existing_menu_name'] ) : '';
		if ( $name !== '' ) {
			$menus = wp_get_nav_menus();
			foreach ( $menus as $menu ) {
				if ( isset( $menu->name ) && $menu->name === $name ) {
					return (int) $menu->term_id;
				}
			}
		}
		return 0;
	}
}
