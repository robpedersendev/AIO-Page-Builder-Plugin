<?php
/**
 * Unit tests for Template_Library_Upgrade_Helper (Prompt 202; spec §53.3, §53.4, §58.2, §58.5).
 *
 * Covers: run() return shape, first-run records registry_schema, retry-safe idempotent second run,
 * already-set leaves state unchanged. Uses bootstrap get_option/update_option stubs.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Migrations\Template_Library_Upgrade_Helper;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Migrations/Template_Library_Upgrade_Helper.php';

final class Template_Library_Upgrade_Helper_Test extends TestCase {

	private const VERSION_MARKERS_KEY = Option_Names::VERSION_MARKERS;

	public function setUp(): void {
		parent::setUp();
		if ( ! isset( $GLOBALS['_aio_test_options'] ) ) {
			$GLOBALS['_aio_test_options'] = array();
		}
	}

	public function tearDown(): void {
		unset( $GLOBALS['_aio_test_options'][ self::VERSION_MARKERS_KEY ] );
		parent::tearDown();
	}

	private function get_helper(): Template_Library_Upgrade_Helper {
		$settings = new Settings_Service();
		return new Template_Library_Upgrade_Helper( $settings );
	}

	public function test_run_returns_expected_keys(): void {
		$helper = $this->get_helper();
		$result = $helper->run();
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'validated', $result );
		$this->assertArrayHasKey( 'registry_schema_recorded', $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertTrue( $result['validated'] );
		$this->assertIsBool( $result['registry_schema_recorded'] );
		$this->assertIsString( $result['message'] );
	}

	public function test_first_run_when_registry_schema_missing_records_and_returns_recorded_true(): void {
		$GLOBALS['_aio_test_options'][ self::VERSION_MARKERS_KEY ] = array();
		$helper = $this->get_helper();
		$result = $helper->run();
		$this->assertTrue( $result['registry_schema_recorded'], 'First run should record registry_schema' );
		$this->assertStringContainsString( 'recorded', $result['message'] );
		$stored = \get_option( self::VERSION_MARKERS_KEY, array() );
		$this->assertArrayHasKey( 'registry_schema', $stored );
		$this->assertNotEmpty( $stored['registry_schema'] );
	}

	public function test_second_run_is_retry_safe_returns_recorded_false(): void {
		$GLOBALS['_aio_test_options'][ self::VERSION_MARKERS_KEY ] = array();
		$helper = $this->get_helper();
		$helper->run();
		$result2 = $helper->run();
		$this->assertFalse( $result2['registry_schema_recorded'], 'Second run must not report recorded (retry-safe)' );
		$this->assertTrue( $result2['validated'] );
		$this->assertStringContainsString( 'already set', $result2['message'] );
	}

	public function test_when_registry_schema_already_set_run_does_not_change_and_returns_recorded_false(): void {
		$existing = '2';
		$GLOBALS['_aio_test_options'][ self::VERSION_MARKERS_KEY ] = array( 'registry_schema' => $existing );
		$helper = $this->get_helper();
		$result = $helper->run();
		$this->assertFalse( $result['registry_schema_recorded'] );
		$this->assertStringContainsString( 'already set', $result['message'] );
		$stored = \get_option( self::VERSION_MARKERS_KEY, array() );
		$this->assertSame( $existing, $stored['registry_schema'] ?? '' );
	}

	public function test_when_registry_schema_is_zero_first_run_records(): void {
		$GLOBALS['_aio_test_options'][ self::VERSION_MARKERS_KEY ] = array( 'registry_schema' => '0' );
		$helper = $this->get_helper();
		$result = $helper->run();
		$this->assertTrue( $result['registry_schema_recorded'] );
		$stored = \get_option( self::VERSION_MARKERS_KEY, array() );
		$this->assertNotSame( '0', $stored['registry_schema'] ?? '' );
	}
}
