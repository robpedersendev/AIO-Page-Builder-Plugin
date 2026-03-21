<?php
/**
 * Minimal WordPress stubs for Create_Menu_Handler integration tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Execution\Handlers;

if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_create_nav_menu' ) ) {
	/**
	 * @param string $name Menu name.
	 * @return int
	 */
	function wp_create_nav_menu( string $name ) {
		return 99;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_registered_nav_menus' ) ) {
	/**
	 * @return array<string, string>
	 */
	function get_registered_nav_menus(): array {
		return array();
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\get_theme_mod' ) ) {
	/**
	 * @param string $name Mod name.
	 * @return array<string, mixed>
	 */
	function get_theme_mod( string $name ): array {
		return array();
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\set_theme_mod' ) ) {
	/**
	 * @param string               $name  Mod name.
	 * @param array<string, mixed> $value Value.
	 * @return void
	 */
	function set_theme_mod( string $name, array $value ): void {
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\wp_update_nav_menu_item' ) ) {
	/**
	 * @param int                  $id      Menu ID.
	 * @param int                  $item_id Item ID.
	 * @param array<string, mixed> $data    Data.
	 * @return int
	 */
	function wp_update_nav_menu_item( int $id, int $item_id, array $data ): int {
		return 1;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\is_wp_error' ) ) {
	/**
	 * @param mixed $thing Value.
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return false;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\sanitize_text_field' ) ) {
	/**
	 * @param string $s String.
	 * @return string
	 */
	function sanitize_text_field( string $s ): string {
		return $s;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\esc_url_raw' ) ) {
	/**
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( string $url ): string {
		return $url;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Domain\Execution\Handlers\__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}
