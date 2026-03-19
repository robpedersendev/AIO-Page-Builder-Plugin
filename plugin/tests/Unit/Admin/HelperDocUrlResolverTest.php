<?php
/**
 * Unit tests for Helper_Doc_Url_Resolver.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Services\Helper_Doc_Url_Resolver;
use AIOPageBuilder\Domain\Registries\Docs\Documentation_Registry_Lookup_Interface;
use AIOPageBuilder\Infrastructure\AdminRouting\Admin_Router;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );
defined( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES' ) || define( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES', dirname( __DIR__ ) . '/../fixtures/wp-plugin-api-stub.php' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/tests/fixtures/wp-plugin-api-stub.php';
require_once $plugin_root . '/src/Infrastructure/AdminRouting/Admin_Router.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Documentation_Registry_Lookup_Interface.php';
require_once $plugin_root . '/src/Admin/Services/Helper_Doc_Url_Resolver.php';

final class HelperDocUrlResolverTest extends TestCase {

	public function test_resolve_returns_unavailable_when_registry_missing(): void {
		$registry  = new class() implements Documentation_Registry_Lookup_Interface {
			public function get_by_section_key( string $section_key ): ?array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
				return null;
			}
			public function get_by_id( string $documentation_id ): ?array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
				return null;
			}
		};
		$router    = new Admin_Router();
		$resolver  = new Helper_Doc_Url_Resolver( $registry, $router );
		$resolved  = $resolver->resolve( 'hero_intro', '1', null );

		$this->assertFalse( $resolved['available'] );
		$this->assertSame( '', $resolved['url'] );
		$this->assertSame( Helper_Doc_Url_Resolver::UNAVAILABLE_MESSAGE, $resolved['message'] );
	}

	public function test_resolve_returns_url_when_registry_has_section_doc(): void {
		$registry = new class() implements Documentation_Registry_Lookup_Interface {
			public function get_by_id( string $documentation_id ): ?array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
				return null;
			}
			public function get_by_section_key( string $section_key ): ?array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
				return array( 'documentation_id' => 'doc-helper-hero-intro' );
			}
		};
		$router   = new Admin_Router();
		$resolver = new Helper_Doc_Url_Resolver( $registry, $router );
		$resolved = $resolver->resolve( 'hero_intro', '1', null );

		$this->assertTrue( $resolved['available'] );
		$this->assertNotSame( '', $resolved['url'] );
		$this->assertStringContainsString( 'admin.php', $resolved['url'] );
		$this->assertStringContainsString( 'aio-page-builder-documentation-detail', $resolved['url'] );
	}
}

