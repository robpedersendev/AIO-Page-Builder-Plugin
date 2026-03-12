<?php
/**
 * Builds pre-change state_snapshot for operational snapshots (spec §41.2; operational-snapshot-schema.md §5).
 *
 * Object-family-specific: page, menu, token_set. No capture logic for hierarchy or build_plan_transition in this builder.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Rollback\Snapshots;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service;

/**
 * Builds pre_change block and target_ref/object_family from envelope for rollback-capable action types.
 */
final class Pre_Change_Snapshot_Builder {

	/**
	 * Builds pre-change state_snapshot and returns target_ref, object_family, and pre_change block; or null if not supported/failed.
	 *
	 * @param array<string, mixed> $envelope Action envelope.
	 * @return array{target_ref: string, object_family: string, pre_change: array<string, mixed>}|null
	 */
	public function build( array $envelope ): ?array {
		$action_type = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ]
			: '';
		$target = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();

		if ( $action_type === Execution_Action_Types::REPLACE_PAGE ) {
			return $this->build_page_pre( $target );
		}
		if ( $action_type === Execution_Action_Types::UPDATE_MENU ) {
			return $this->build_menu_pre( $target );
		}
		if ( $action_type === Execution_Action_Types::APPLY_TOKEN_SET ) {
			return $this->build_token_set_pre( $target );
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $target
	 * @return array{target_ref: string, object_family: string, pre_change: array<string, mixed>}|null
	 */
	private function build_page_pre( array $target ): ?array {
		$post_id = $this->resolve_page_id( $target );
		if ( $post_id <= 0 ) {
			return null;
		}
		$post = \get_post( $post_id );
		if ( ! $post instanceof \WP_Post || $post->post_type !== 'page' ) {
			return null;
		}
		$content_hash = '';
		if ( ! empty( $post->post_content ) ) {
			$content_hash = 'sha256:' . hash( 'sha256', $post->post_content );
		}
		$excerpt = \wp_trim_words( \wp_strip_all_tags( $post->post_content ), 50 );
		if ( strlen( $excerpt ) > 500 ) {
			$excerpt = substr( $excerpt, 0, 497 ) . '...';
		}
		$state_snapshot = array(
			'post_id'       => $post_id,
			'post_title'    => $post->post_title,
			'post_name'     => $post->post_name,
			'post_status'   => $post->post_status,
			'post_type'     => $post->post_type,
			'content_hash'  => $content_hash,
			'excerpt'       => $excerpt,
		);
		$now = gmdate( 'c' );
		return array(
			'target_ref'     => (string) $post_id,
			'object_family'  => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			'pre_change'     => array(
				'captured_at'     => $now,
				'state_snapshot'  => $state_snapshot,
			),
		);
	}

	/**
	 * @param array<string, mixed> $target
	 * @return array{target_ref: string, object_family: string, pre_change: array<string, mixed>}|null
	 */
	private function build_menu_pre( array $target ): ?array {
		$menu_id = $this->resolve_menu_id( $target );
		if ( $menu_id <= 0 ) {
			return null;
		}
		$menu = \wp_get_nav_menu_object( $menu_id );
		if ( ! $menu instanceof \WP_Term ) {
			return null;
		}
		$locations = \get_nav_menu_locations();
		$location  = '';
		foreach ( $locations as $loc => $term_id ) {
			if ( (int) $term_id === (int) $menu_id ) {
				$location = $loc;
				break;
			}
		}
		$items = \wp_get_nav_menu_items( $menu_id );
		$item_list = array();
		if ( is_array( $items ) ) {
			foreach ( $items as $item ) {
				if ( ! $item instanceof \WP_Post ) {
					continue;
				}
				$item_list[] = array(
					'id'     => $item->ID,
					'title'  => $item->title,
					'url'    => $item->url,
					'parent' => (int) $item->menu_item_parent,
					'order'  => (int) $item->menu_order,
				);
			}
		}
		$state_snapshot = array(
			'menu_id'   => (int) $menu->term_id,
			'name'      => $menu->name,
			'location'  => $location,
			'items'     => $item_list,
		);
		$now = gmdate( 'c' );
		return array(
			'target_ref'    => (string) $menu_id,
			'object_family' => Operational_Snapshot_Schema::OBJECT_FAMILY_MENU,
			'pre_change'    => array(
				'captured_at'    => $now,
				'state_snapshot' => $state_snapshot,
			),
		);
	}

	/**
	 * @param array<string, mixed> $target
	 * @return array{target_ref: string, object_family: string, pre_change: array<string, mixed>}|null
	 */
	private function build_token_set_pre( array $target ): ?array {
		$group = isset( $target['token_group'] ) && is_string( $target['token_group'] ) ? trim( $target['token_group'] ) : '';
		$name  = isset( $target['token_name'] ) && is_string( $target['token_name'] ) ? trim( $target['token_name'] ) : '';
		if ( $group === '' || $name === '' ) {
			return null;
		}
		$store = \get_option( Token_Set_Job_Service::OPTION_APPLIED_TOKENS, array() );
		if ( ! is_array( $store ) ) {
			$store = array();
		}
		$tokens = array();
		if ( isset( $store[ $group ] ) && is_array( $store[ $group ] ) ) {
			foreach ( $store[ $group ] as $k => $v ) {
				$tokens[ $k ] = array( 'value' => $v );
			}
		}
		$state_snapshot = array(
			'token_set_id' => $group . ':' . $name,
			'tokens'       => $tokens,
		);
		$now = gmdate( 'c' );
		return array(
			'target_ref'    => $group . ':' . $name,
			'object_family' => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
			'pre_change'    => array(
				'captured_at'    => $now,
				'state_snapshot' => $state_snapshot,
			),
		);
	}

	private function resolve_page_id( array $target ): int {
		if ( isset( $target['page_ref'] ) && is_array( $target['page_ref'] ) && isset( $target['page_ref']['value'] ) ) {
			$id = (int) $target['page_ref']['value'];
			if ( $id > 0 ) {
				$post = \get_post( $id );
				if ( $post instanceof \WP_Post && $post->post_type === 'page' ) {
					return $id;
				}
			}
		}
		$slug = isset( $target['current_page_slug'] ) && is_string( $target['current_page_slug'] ) ? trim( $target['current_page_slug'] ) : '';
		if ( $slug === '' && isset( $target['current_page_url'] ) && is_string( $target['current_page_url'] ) ) {
			$parsed = \wp_parse_url( $target['current_page_url'] );
			$path   = isset( $parsed['path'] ) ? trim( (string) $parsed['path'], '/' ) : '';
			if ( $path !== '' ) {
				$slug = $path;
			}
		}
		if ( $slug === '' ) {
			return 0;
		}
		$page = \get_page_by_path( $slug, \OBJECT, 'page' );
		return $page instanceof \WP_Post ? (int) $page->ID : 0;
	}

	private function resolve_menu_id( array $target ): int {
		$context = isset( $target['menu_context'] ) && is_string( $target['menu_context'] ) ? trim( $target['menu_context'] ) : '';
		if ( $context === '' ) {
			return 0;
		}
		$locations = \get_nav_menu_locations();
		$slug = array( 'header' => 'primary', 'footer' => 'footer', 'mobile' => 'mobile', 'off_canvas' => 'off_canvas', 'sidebar' => 'sidebar' )[ $context ] ?? $context;
		$term_id = isset( $locations[ $slug ] ) ? $locations[ $slug ] : null;
		if ( $term_id !== null && (int) $term_id > 0 ) {
			return (int) $term_id;
		}
		if ( isset( $target['existing_menu_id'] ) && is_numeric( $target['existing_menu_id'] ) ) {
			return (int) $target['existing_menu_id'];
		}
		return 0;
	}
}
