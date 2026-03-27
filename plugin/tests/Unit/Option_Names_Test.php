<?php
/**
 * Unit tests for Option_Names: stable keys, prefix, validation (spec §9.4, §62.3).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';

/**
 * Option name constants and schema coverage.
 */
final class Option_Names_Test extends TestCase {

	public function test_all_keys_use_namespaced_prefix(): void {
		foreach ( Option_Names::all() as $key ) {
			$this->assertStringStartsWith( 'aio_page_builder_', $key, "Option key must be namespaced: {$key}" );
		}
	}

	public function test_all_returns_all_option_keys(): void {
		$all = Option_Names::all();
		$this->assertCount( 8, $all );
		$this->assertContains( Option_Names::MAIN_SETTINGS, $all );
		$this->assertContains( Option_Names::VERSION_MARKERS, $all );
		$this->assertContains( Option_Names::REPORTING_SETTINGS, $all );
		$this->assertContains( Option_Names::DEPENDENCY_NOTICE_DISMISSALS, $all );
		$this->assertContains( Option_Names::UNINSTALL_PREFS, $all );
		$this->assertContains( Option_Names::PROVIDER_CONFIG_REF, $all );
		$this->assertContains( Option_Names::PROFILE_CURRENT, $all );
		$this->assertContains( Option_Names::ONBOARDING_TELEMETRY_AGGREGATE, $all );
	}

	public function test_is_valid_accepts_all_constants(): void {
		foreach ( Option_Names::all() as $key ) {
			$this->assertTrue( Option_Names::is_valid( $key ), "Key should be valid: {$key}" );
		}
	}

	public function test_is_valid_rejects_unknown(): void {
		$this->assertFalse( Option_Names::is_valid( 'unknown_option' ) );
		$this->assertFalse( Option_Names::is_valid( '' ) );
	}
}
