<?php
/**
 * Unit tests for Settings_Service: default resolution, get/set, unknown option (spec §9.4).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';

/**
 * Default resolution, typed get/set, unknown option handling.
 */
final class Settings_Service_Test extends TestCase {

	private Settings_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->service                = new Settings_Service();
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	public function test_get_returns_default_when_not_set(): void {
		$value = $this->service->get( Option_Names::MAIN_SETTINGS );
		$this->assertIsArray( $value );
		$this->assertSame( array(), $value );
	}

	public function test_get_default_matches_schema(): void {
		foreach ( Option_Names::all() as $key ) {
			$default = $this->service->get_default( $key );
			$this->assertIsArray( $default, "Default for {$key} must be array" );
			$this->assertSame( $default, $this->service->get( $key ), "Unset option must return default for {$key}" );
		}
	}

	public function test_set_and_get_roundtrip(): void {
		$data = array( 'key' => 'value' );
		$this->service->set( Option_Names::MAIN_SETTINGS, $data );
		$this->assertSame( $data, $this->service->get( Option_Names::MAIN_SETTINGS ) );
	}

	public function test_get_throws_for_unknown_key(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown option key' );
		$this->service->get( 'unknown_key' );
	}

	public function test_set_throws_for_unknown_key(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown option key' );
		$this->service->set( 'unknown_key', array() );
	}

	public function test_get_default_throws_for_unknown_key(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown option key' );
		$this->service->get_default( 'unknown_key' );
	}

	public function test_has_key_returns_true_for_known(): void {
		$this->assertTrue( $this->service->has_key( Option_Names::MAIN_SETTINGS ) );
	}

	public function test_has_key_returns_false_for_unknown(): void {
		$this->assertFalse( $this->service->has_key( 'other_plugin_option' ) );
	}

	public function test_get_returns_stored_array_even_when_nested(): void {
		$data = array( 'nested' => array( 'a' => 1 ) );
		$this->service->set( Option_Names::REPORTING_SETTINGS, $data );
		$this->assertSame( $data, $this->service->get( Option_Names::REPORTING_SETTINGS ) );
	}
}
