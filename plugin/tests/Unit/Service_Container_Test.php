<?php
/**
 * Unit tests for Service_Container.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Container/Service_Provider_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';

/**
 * Tests container registration, singleton resolution, and missing-ID behavior.
 */
final class Service_Container_Test extends TestCase {

	public function test_provider_can_register_service(): void {
		$container = new Service_Container();
		$container->register( 'test', function () {
			return new \stdClass();
		} );
		$this->assertTrue( $container->has( 'test' ) );
		$this->assertInstanceOf( \stdClass::class, $container->get( 'test' ) );
	}

	public function test_services_resolve_once_as_singletons(): void {
		$container = new Service_Container();
		$container->register( 'single', function () {
			return new \stdClass();
		} );
		$first  = $container->get( 'single' );
		$second = $container->get( 'single' );
		$this->assertSame( $first, $second );
	}

	public function test_missing_service_id_fails_clearly(): void {
		$container = new Service_Container();
		$this->expectException( \RuntimeException::class );
		$this->expectExceptionMessage( 'Unknown service ID: missing_id' );
		$container->get( 'missing_id' );
	}

	public function test_has_returns_false_for_unregistered_id(): void {
		$container = new Service_Container();
		$this->assertFalse( $container->has( 'nonexistent' ) );
	}
}
