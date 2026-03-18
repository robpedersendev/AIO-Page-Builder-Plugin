<?php
/**
 * Unit tests for Option_Store: typed get/set for main, reporting, dismissals, uninstall (spec §9.4).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Option_Store;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Option_Store.php';

/**
 * Default reads on fresh install; roundtrip for each option root.
 */
final class Option_Store_Test extends TestCase {

	private Settings_Service $settings;
	private Option_Store $store;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_test_options'] = array();
		$this->settings               = new Settings_Service();
		$this->store                  = new Option_Store( $this->settings );
	}

	protected function tearDown(): void {
		$GLOBALS['_aio_test_options'] = array();
		parent::tearDown();
	}

	public function test_get_main_settings_returns_empty_on_fresh_install(): void {
		$this->assertSame( array(), $this->store->get_main_settings() );
	}

	public function test_get_reporting_settings_returns_empty_on_fresh_install(): void {
		$this->assertSame( array(), $this->store->get_reporting_settings() );
	}

	public function test_get_dependency_dismissals_returns_empty_on_fresh_install(): void {
		$this->assertSame( array(), $this->store->get_dependency_dismissals() );
	}

	public function test_get_uninstall_prefs_returns_empty_on_fresh_install(): void {
		$this->assertSame( array(), $this->store->get_uninstall_prefs() );
	}

	public function test_main_settings_roundtrip(): void {
		$data = array( 'enabled' => true );
		$this->store->set_main_settings( $data );
		$this->assertSame( $data, $this->store->get_main_settings() );
	}

	public function test_reporting_settings_roundtrip(): void {
		$data = array( 'disclosure_accepted' => false );
		$this->store->set_reporting_settings( $data );
		$this->assertSame( $data, $this->store->get_reporting_settings() );
	}

	public function test_dependency_dismissals_roundtrip(): void {
		$data = array( 'php_version' => true );
		$this->store->set_dependency_dismissals( $data );
		$this->assertSame( $data, $this->store->get_dependency_dismissals() );
	}

	public function test_uninstall_prefs_roundtrip(): void {
		$data = array( 'remove_data' => false );
		$this->store->set_uninstall_prefs( $data );
		$this->assertSame( $data, $this->store->get_uninstall_prefs() );
	}

	public function test_option_roots_are_stable(): void {
		$this->store->set_main_settings( array( 'x' => 1 ) );
		$this->assertArrayHasKey( Option_Names::MAIN_SETTINGS, $GLOBALS['_aio_test_options'] );
	}
}
