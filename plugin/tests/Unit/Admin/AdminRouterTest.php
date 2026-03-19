<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string {
		return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( array $args, string $url ): string {
		$q = http_build_query( $args );
		return rtrim( $url, '?' ) . '?' . $q;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', $key ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( string $text ): string {
		return trim( preg_replace( '/[\\r\\n\\t]+/', ' ', $text ) );
	}
}

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Infrastructure/AdminRouting/Admin_Router.php';

final class AdminRouterTest extends TestCase {

	public function test_named_route_generates_admin_url(): void {
		$router = new Admin_Router();
		$url    = $router->url( 'documentation_detail', array( 'doc_id' => 'doc-helper-cta_contact_01' ) );
		$this->assertStringContainsString( 'admin.php?', $url );
		$this->assertStringContainsString( 'page=aio-page-builder-documentation-detail', $url );
		$this->assertStringContainsString( 'doc_id=doc-helper-cta_contact_01', $url );
	}
}

