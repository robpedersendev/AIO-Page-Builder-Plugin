<?php
/**
 * Admin router for named routes and URL generation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\AdminRouting;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;

/**
 * Named route registry for admin.php?page=... URLs.
 */
final class Admin_Router {

	/** @var array<string, array{page: string, capability: string, args: array<string, string>}> */
	private array $routes = array();

	public function __construct() {
		$this->register_defaults();
	}

	/**
	 * Registers a named route.
	 *
	 * @param string               $name Route name.
	 * @param string               $page Admin page slug (value of `page` query arg).
	 * @param string               $capability Required capability for access.
	 * @param array<string, string> $arg_types Arg name => type ("key"|"text"|"int"|"bool"|"raw").
	 * @return void
	 */
	public function register_route( string $name, string $page, string $capability, array $arg_types = array() ): void {
		$name = sanitize_key( $name );
		$page = sanitize_key( $page );
		if ( $name === '' || $page === '' ) {
			return;
		}
		$this->routes[ $name ] = array(
			'page'       => $page,
			'capability' => $capability,
			'args'       => $arg_types,
		);
	}

	/**
	 * Returns route metadata.
	 *
	 * @param string $name
	 * @return array{page: string, capability: string, args: array<string, string>}|null
	 */
	public function get_route( string $name ): ?array {
		$name = sanitize_key( $name );
		return $this->routes[ $name ] ?? null;
	}

	/**
	 * Builds an admin URL for a named route.
	 *
	 * @param string               $name Route name.
	 * @param array<string, mixed> $args Query args for this route.
	 * @return string
	 */
	public function url( string $name, array $args = array() ): string {
		$route = $this->get_route( $name );
		if ( $route === null ) {
			return '';
		}
		$normalized = $this->normalize_args( $route['args'], $args );
		$normalized['page'] = $route['page'];
		return add_query_arg( $normalized, admin_url( 'admin.php' ) );
	}

	/**
	 * Returns true if current user can access the route.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function current_user_can_access( string $name ): bool {
		$route = $this->get_route( $name );
		if ( $route === null ) {
			return false;
		}
		return current_user_can( (string) $route['capability'] );
	}

	/**
	 * Normalizes query args using an allowlist and per-arg type rules.
	 *
	 * @param array<string, string> $arg_types
	 * @param array<string, mixed>  $args
	 * @return array<string, string>
	 */
	public function normalize_args( array $arg_types, array $args ): array {
		$out = array();
		foreach ( $arg_types as $key => $type ) {
			if ( ! array_key_exists( $key, $args ) ) {
				continue;
			}
			$val = $args[ $key ];
			switch ( $type ) {
				case 'int':
					$out[ $key ] = (string) ( is_numeric( $val ) ? (int) $val : 0 );
					break;
				case 'bool':
					$out[ $key ] = ! empty( $val ) ? '1' : '0';
					break;
				case 'key':
					$out[ $key ] = sanitize_key( (string) $val );
					break;
				case 'text':
					$out[ $key ] = sanitize_text_field( (string) $val );
					break;
				case 'raw':
				default:
					$out[ $key ] = (string) $val;
					break;
			}
			if ( $out[ $key ] === '' ) {
				unset( $out[ $key ] );
			}
		}
		return $out;
	}

	private function register_defaults(): void {
		$this->register_route( 'dashboard', 'aio-page-builder-dashboard', Capabilities::MANAGE_SETTINGS );
		$this->register_route( 'section_template_detail', 'aio-page-builder-section-template-detail', Capabilities::MANAGE_SECTION_TEMPLATES, array( 'section' => 'key', 'purpose_family' => 'key', 'reduced_motion' => 'bool' ) );
		$this->register_route( 'page_template_detail', 'aio-page-builder-page-template-detail', Capabilities::MANAGE_PAGE_TEMPLATES, array( 'template' => 'key', 'purpose_family' => 'key', 'reduced_motion' => 'bool' ) );
		$this->register_route( 'template_compare', 'aio-page-builder-template-compare', Capabilities::MANAGE_PAGE_TEMPLATES, array( 'type' => 'key' ) );
		$this->register_route( 'documentation_detail', 'aio-page-builder-documentation-detail', Capabilities::MANAGE_SECTION_TEMPLATES, array( 'doc_id' => 'text', 'section' => 'key' ) );
		$this->register_route( 'build_plan_workspace', 'aio-page-builder-build-plans', Capabilities::VIEW_BUILD_PLANS, array( 'plan_id' => 'text', 'step' => 'int', 'detail' => 'text' ) );
	}
}

