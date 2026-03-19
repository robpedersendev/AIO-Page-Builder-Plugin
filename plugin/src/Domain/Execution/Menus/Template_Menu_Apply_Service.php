<?php
/**
 * Template-aware menu apply: hierarchy-sensitive placement and validation (spec §59.10, §1.9.8, §1.9.9; Prompt 207).
 *
 * Applies approved Build Plan menu actions with page-class ordering (top_level, hub, nested_hub, child_detail).
 * Validates menu target; fails visibly when location is missing. Produces navigation_hierarchy_summary
 * and menu_target_validation_result for logging and rollback traceability.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Menus;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Job_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Result;

/**
 * Page class ordering for navigation: top-level first, then hub, nested hub, child/detail.
 */
final class Template_Menu_Apply_Service implements Template_Menu_Apply_Service_Interface {

	/** Map plan menu_context to theme location slug (aligned with Menu_Change_Job_Service). */
	private const CONTEXT_TO_LOCATION = array(
		'header'     => 'primary',
		'footer'     => 'footer',
		'mobile'     => 'mobile',
		'off_canvas' => 'off_canvas',
		'sidebar'    => 'sidebar',
	);

	/** Page class order for hierarchy-aware placement (lower = earlier in menu). */
	private const PAGE_CLASS_ORDER = array(
		'top_level'    => 1,
		'hub'          => 2,
		'nested_hub'   => 3,
		'child_detail' => 4,
	);

	/** @var Menu_Change_Job_Service */
	private $menu_job_service;

	public function __construct( Menu_Change_Job_Service $menu_job_service ) {
		$this->menu_job_service = $menu_job_service;
	}

	/**
	 * Applies template-aware menu changes: validates target, orders by page class, applies with parent/child.
	 *
	 * @param array<string, mixed> $envelope Validated action envelope (target_reference with menu_context, action, items with optional page_class/parent_page_id).
	 * @return Template_Menu_Apply_Result
	 */
	public function apply( array $envelope ): Template_Menu_Apply_Result {
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();

		$menu_context = isset( $target['menu_context'] ) && is_string( $target['menu_context'] ) ? trim( $target['menu_context'] ) : '';
		$action       = isset( $target['action'] ) && is_string( $target['action'] ) ? trim( $target['action'] ) : '';
		$items        = isset( $target['items'] ) && is_array( $target['items'] ) ? $target['items'] : array();

		$validation = $this->validate_menu_target( $menu_context );
		if ( ! empty( $validation['missing_location'] ) || ( isset( $validation['valid'] ) && $validation['valid'] === false ) ) {
			return Template_Menu_Apply_Result::failure(
				__( 'Menu location is not registered or not supported by the theme. Apply failed.', 'aio-page-builder' ),
				array( 'menu_target_validation_failed' ),
				array_merge( $validation, array( 'valid' => false ) ),
				array(
					'items_ordered_by_class' => array(),
					'applied_count'          => 0,
					'warnings'               => array(),
				),
				array()
			);
		}

		$location_slug = $validation['location_slug'] ?? ( self::CONTEXT_TO_LOCATION[ $menu_context ] ?? $menu_context );

		if ( $action !== 'update_existing' && $action !== 'replace' && $action !== 'create' ) {
			return Template_Menu_Apply_Result::failure(
				__( 'Template-aware apply supports only update_existing, replace, or create.', 'aio-page-builder' ),
				array( 'invalid_action' ),
				$validation,
				array(
					'items_ordered_by_class' => array(),
					'applied_count'          => 0,
					'warnings'               => array(),
				),
				array()
			);
		}

		$ordered           = $this->order_items_by_hierarchy( $items );
		$hierarchy_summary = array(
			'items_ordered_by_class' => array_map(
				function ( array $item ): array {
					$out = array(
						'title'     => $item['title'] ?? '',
						'object_id' => $item['object_id'] ?? 0,
					);
					if ( isset( $item['page_class'] ) ) {
						$out['page_class'] = $item['page_class'];
					}
					if ( isset( $item['parent_page_id'] ) ) {
						$out['parent_page_id'] = $item['parent_page_id'];
					}
					return $out;
				},
				$ordered
			),
			'applied_count'          => 0,
			'warnings'               => array(),
		);

		$menu_id = 0;
		if ( $action === 'update_existing' ) {
			$menu_id = $this->resolve_menu_for_context( $menu_context, $target );
			if ( $menu_id <= 0 ) {
				return Template_Menu_Apply_Result::failure(
					__( 'Existing menu could not be resolved for this location.', 'aio-page-builder' ),
					array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ),
					array_merge(
						$validation,
						array(
							'valid'            => true,
							'resolved_menu_id' => 0,
						)
					),
					$hierarchy_summary,
					array()
				);
			}
		} else {
			$menu_name = isset( $target['proposed_menu_name'] ) && is_string( $target['proposed_menu_name'] ) ? trim( $target['proposed_menu_name'] ) : '';
			if ( $menu_name === '' ) {
				$menu_name = __( 'Primary Menu', 'aio-page-builder' );
			}
			$create_result = $this->menu_job_service->run(
				array_merge(
					$envelope,
					array(
						Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array_merge(
							$target,
							array(
								'action'             => $action === 'replace' ? 'replace' : 'create',
								'proposed_menu_name' => $menu_name,
								'items'              => array(),
							)
						),
					)
				)
			);
			if ( ! $create_result->is_success() ) {
				return Template_Menu_Apply_Result::failure(
					$create_result->get_message(),
					$create_result->get_errors(),
					$validation,
					$hierarchy_summary,
					array()
				);
			}
			$menu_id = $create_result->get_menu_id();
		}

		$per_item_status                    = array();
		$applied                            = $this->apply_items_with_hierarchy( $menu_id, $ordered, $per_item_status );
		$hierarchy_summary['applied_count'] = $applied;

		$artifacts = array(
			'action'            => $action,
			'location_assigned' => $location_slug,
			'items_updated'     => $applied,
		);
		$term      = get_term( $menu_id, 'nav_menu' );
		if ( $term instanceof \WP_Term ) {
			$artifacts['menu_name'] = $term->name;
		}

		return Template_Menu_Apply_Result::success(
			$menu_id,
			array_merge(
				$validation,
				array(
					'valid'            => true,
					'resolved_menu_id' => $menu_id,
				)
			),
			$hierarchy_summary,
			$per_item_status,
			$artifacts
		);
	}

	/**
	 * Validates that the menu target (location) is registered. Fails visibly when missing.
	 *
	 * @param string $menu_context
	 * @return array<string, mixed> menu_target_validation_result (valid, location_slug, missing_location).
	 */
	private function validate_menu_target( string $menu_context ): array {
		$location_slug = self::CONTEXT_TO_LOCATION[ $menu_context ] ?? $menu_context;
		$registered    = get_registered_nav_menus();
		if ( ! is_array( $registered ) ) {
			$registered = array();
		}
		$missing = ! isset( $registered[ $location_slug ] );
		return array(
			'valid'            => ! $missing,
			'location_slug'    => $location_slug,
			'missing_location' => $missing,
		);
	}

	/**
	 * Orders items by page_class (top_level, hub, nested_hub, child_detail) and parent.
	 *
	 * @param array<int, array<string, mixed>> $items
	 * @return array<int, array<string, mixed>>
	 */
	private function order_items_by_hierarchy( array $items ): array {
		$with_order = array();
		foreach ( $items as $idx => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$class        = isset( $item['page_class'] ) && is_string( $item['page_class'] ) ? trim( $item['page_class'] ) : 'top_level';
			$order        = self::PAGE_CLASS_ORDER[ $class ] ?? 99;
			$parent_id    = isset( $item['parent_page_id'] ) && is_numeric( $item['parent_page_id'] ) ? (int) $item['parent_page_id'] : 0;
			$with_order[] = array(
				'item'     => $item,
				'order'    => $order,
				'parent'   => $parent_id,
				'class'    => $class,
				'orig_idx' => $idx,
			);
		}
		usort(
			$with_order,
			function ( $a, $b ) {
				if ( $a['order'] !== $b['order'] ) {
					return $a['order'] <=> $b['order'];
				}
				if ( $a['parent'] !== $b['parent'] ) {
					return $a['parent'] <=> $b['parent'];
				}
				return $a['orig_idx'] <=> $b['orig_idx'];
			}
		);
		return array_map(
			function ( array $row ): array {
				return $row['item'];
			},
			$with_order
		);
	}

	/**
	 * Resolves nav menu term_id for context (theme location or target ref).
	 *
	 * @param string               $menu_context
	 * @param array<string, mixed> $target
	 * @return int
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
			if ( is_array( $menus ) ) {
				foreach ( $menus as $menu ) {
					if ( isset( $menu->name ) && $menu->name === $name ) {
						return (int) $menu->term_id;
					}
				}
			}
		}
		return 0;
	}

	/**
	 * Applies menu items with parent/child; tracks per-item status.
	 *
	 * @param int                              $menu_id
	 * @param array<int, array<string, mixed>> $ordered_items
	 * @param list<array<string, mixed>>       $per_item_status Output.
	 * @return int Applied count.
	 */
	private function apply_items_with_hierarchy( int $menu_id, array $ordered_items, array &$per_item_status ): int {
		$object_id_to_menu_item_id = array();
		$position                  = 0;
		$applied                   = 0;
		foreach ( $ordered_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$title          = isset( $item['title'] ) && is_string( $item['title'] ) ? sanitize_text_field( $item['title'] ) : '';
			$url            = isset( $item['url'] ) && is_string( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			$type           = isset( $item['type'] ) && is_string( $item['type'] ) ? $item['type'] : 'page';
			$object_id      = isset( $item['object_id'] ) && is_numeric( $item['object_id'] ) ? (int) $item['object_id'] : 0;
			$parent_page_id = isset( $item['parent_page_id'] ) && is_numeric( $item['parent_page_id'] ) ? (int) $item['parent_page_id'] : 0;

			if ( $title === '' && $url === '' && $object_id <= 0 ) {
				$per_item_status[] = array(
					'status' => 'skipped',
					'reason' => 'missing_title_url_object_id',
				);
				continue;
			}

			$parent_menu_item_id = 0;
			if ( $parent_page_id > 0 && isset( $object_id_to_menu_item_id[ $parent_page_id ] ) ) {
				$parent_menu_item_id = (int) $object_id_to_menu_item_id[ $parent_page_id ];
			}

			$item_data = array(
				'menu-item-position'  => $position,
				'menu-item-type'      => $type === 'page' ? 'post_type' : $type,
				'menu-item-title'     => $title !== '' ? $title : ( ( $url !== '' && $url !== null ) ? $url : (string) $object_id ),
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_menu_item_id,
			);
			if ( $url !== '' ) {
				$item_data['menu-item-url'] = $url;
			}
			if ( $object_id > 0 && in_array( $type, array( 'post_type', 'page' ), true ) ) {
				$item_data['menu-item-object-id'] = $object_id;
				$item_data['menu-item-object']    = $type === 'page' ? 'page' : 'post';
			}

			$menu_item_id = wp_update_nav_menu_item( $menu_id, 0, $item_data );
			if ( is_wp_error( $menu_item_id ) ) {
				$per_item_status[] = array(
					'status'    => 'error',
					'title'     => $title,
					'object_id' => $object_id,
					'error'     => $menu_item_id->get_error_message(),
				);
				++$position;
				continue;
			}
			$menu_item_id = (int) $menu_item_id;
			if ( $object_id > 0 ) {
				$object_id_to_menu_item_id[ $object_id ] = $menu_item_id;
			}
			$per_item_status[] = array(
				'status'       => 'applied',
				'title'        => $title,
				'object_id'    => $object_id,
				'menu_item_id' => $menu_item_id,
			);
			++$position;
			++$applied;
		}
		return $applied;
	}
}
