<?php
/**
 * Shared admin hub tab navigation (query args, URLs, nav-tab output).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Admin;

use AIOPageBuilder\Infrastructure\Config\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Helpers for tabbed hub screens under a single admin.php?page= slug.
 */
final class Admin_Screen_Hub {

	public const QUERY_TAB    = 'aio_tab';
	public const QUERY_SUBTAB = 'aio_subtab';

	/**
	 * AI workspace hub: tab id => capability (order: providers, ai_runs, experiments — keep sync with render_ai_workspace_hub).
	 *
	 * @return array<string, string>
	 */
	public static function ai_workspace_tab_caps(): array {
		return array(
			'ai_runs'     => Capabilities::VIEW_AI_RUNS,
			'providers'   => Capabilities::MANAGE_AI_PROVIDERS,
			'experiments' => Capabilities::MANAGE_AI_PROVIDERS,
		);
	}

	/**
	 * Resolves a tab key from the request with a whitelist.
	 *
	 * @param string   $default Default tab key.
	 * @param string[] $allowed Allowed tab keys.
	 * @return string
	 */
	public static function current_tab( string $default, array $allowed ): string {
		if ( ! isset( $_GET[ self::QUERY_TAB ] ) ) {
			return $default;
		}
		$tab = \sanitize_key( (string) \wp_unslash( $_GET[ self::QUERY_TAB ] ) );
		return \in_array( $tab, $allowed, true ) ? $tab : $default;
	}

	/**
	 * Resolves a sub-tab key (second-level nav) with a whitelist.
	 *
	 * @param string   $default Default sub-tab key.
	 * @param string[] $allowed Allowed keys.
	 * @return string
	 */
	public static function current_subtab( string $default, array $allowed ): string {
		if ( ! isset( $_GET[ self::QUERY_SUBTAB ] ) ) {
			return $default;
		}
		$tab = \sanitize_key( (string) \wp_unslash( $_GET[ self::QUERY_SUBTAB ] ) );
		return \in_array( $tab, $allowed, true ) ? $tab : $default;
	}

	/**
	 * Builds an admin URL for a hub tab.
	 *
	 * @param string $page_slug Registered admin page slug.
	 * @param string $tab       Tab key.
	 * @param array  $extra     Extra query args (sanitized values).
	 * @return string
	 */
	public static function tab_url( string $page_slug, string $tab, array $extra = array() ): string {
		$args = \array_merge(
			array(
				'page'          => $page_slug,
				self::QUERY_TAB => $tab,
			),
			$extra
		);
		return \add_query_arg( $args, \admin_url( 'admin.php' ) );
	}

	/**
	 * Builds a URL including a sub-tab key.
	 *
	 * @param string $page_slug Registered admin page slug.
	 * @param string $tab       Primary tab key.
	 * @param string $subtab    Secondary tab key.
	 * @param array  $extra     Extra query args.
	 * @return string
	 */
	public static function subtab_url( string $page_slug, string $tab, string $subtab, array $extra = array() ): string {
		$args = \array_merge(
			array(
				'page'             => $page_slug,
				self::QUERY_TAB    => $tab,
				self::QUERY_SUBTAB => $subtab,
			),
			$extra
		);
		return \add_query_arg( $args, \admin_url( 'admin.php' ) );
	}

	/**
	 * Renders a WordPress nav-tab row for tabs the current user may access.
	 *
	 * @param string                                           $page_slug Hub page slug.
	 * @param array<string, array{label: string, cap: string}> $tabs Tab definitions keyed by tab id.
	 * @param string                                           $current   Active tab id.
	 * @param callable(string): bool|null                      $user_can_tab Optional cap check; defaults to Capabilities::current_user_can_for_route.
	 * @return void
	 */
	public static function render_nav_tabs( string $page_slug, array $tabs, string $current, ?callable $user_can_tab = null ): void {
		$can = $user_can_tab ?? static function ( string $cap ): bool {
			return \AIOPageBuilder\Infrastructure\Config\Capabilities::current_user_can_for_route( $cap );
		};
		echo '<h2 class="nav-tab-wrapper aio-nav-tab-wrapper">';
		foreach ( $tabs as $key => $info ) {
			if ( ! $can( $info['cap'] ) ) {
				continue;
			}
			$active = ( $current === $key ) ? ' nav-tab-active' : '';
			echo '<a href="' . \esc_url( self::tab_url( $page_slug, $key ) ) . '" class="nav-tab' . \esc_attr( $active ) . '">' . \esc_html( $info['label'] ) . '</a>';
		}
		echo '</h2>';
	}

	/**
	 * Renders a secondary nav row (nested tabs) below the primary row.
	 *
	 * @param string                                                          $page_slug Hub page slug.
	 * @param string                                                          $tab       Primary tab id (fixed in URLs).
	 * @param array<string, array{label: string, cap: string, hint?: string}> $tabs Sub-tab definitions; optional hint shown under the row for the active tab.
	 * @param string                                                          $current   Active sub-tab id.
	 * @param callable(string): bool|null                                     $user_can_tab Optional cap check; defaults to Capabilities::current_user_can_for_route.
	 * @param array<string, scalar|\Stringable>                               $extra_query_args Merged into every subtab link (e.g. run_id for detail views).
	 * @return void
	 */
	public static function render_subnav_tabs( string $page_slug, string $tab, array $tabs, string $current, ?callable $user_can_tab = null, array $extra_query_args = array() ): void {
		$can = $user_can_tab ?? static function ( string $cap ): bool {
			return \AIOPageBuilder\Infrastructure\Config\Capabilities::current_user_can_for_route( $cap );
		};
		echo '<h3 class="nav-tab-wrapper aio-nav-subtab-wrapper" style="margin-top:0.5em;padding-top:0.5em;border-top:1px solid #c3c4c7;">';
		foreach ( $tabs as $key => $info ) {
			if ( ! $can( $info['cap'] ) ) {
				continue;
			}
			$active = ( $current === $key ) ? ' nav-tab-active' : '';
			$hint   = isset( $info['hint'] ) && is_string( $info['hint'] ) ? $info['hint'] : '';
			echo '<a href="' . \esc_url( self::subtab_url( $page_slug, $tab, $key, $extra_query_args ) ) . '" class="nav-tab' . \esc_attr( $active ) . '"';
			if ( $hint !== '' ) {
				echo ' title="' . \esc_attr( $hint ) . '"';
			}
			echo '>' . \esc_html( $info['label'] ) . '</a>';
		}
		echo '</h3>';
		if ( isset( $tabs[ $current ] ) && is_array( $tabs[ $current ] ) && $can( $tabs[ $current ]['cap'] )
			&& isset( $tabs[ $current ]['hint'] ) && is_string( $tabs[ $current ]['hint'] ) && $tabs[ $current ]['hint'] !== '' ) {
			echo '<p class="description aio-hub-subtab-hint">' . \esc_html( $tabs[ $current ]['hint'] ) . '</p>';
		}
	}

	/**
	 * Picks the first tab key the user may access, or the default if none.
	 *
	 * @param string                            $default Default tab key.
	 * @param array<string, array{cap: string}> $tabs Tab definitions keyed by id (label only for display elsewhere).
	 * @return string
	 */
	public static function first_accessible_tab( string $default, array $tabs ): string {
		foreach ( $tabs as $key => $info ) {
			if ( \AIOPageBuilder\Infrastructure\Config\Capabilities::current_user_can_for_route( $info['cap'] ) ) {
				return $key;
			}
		}
		return $default;
	}
}
