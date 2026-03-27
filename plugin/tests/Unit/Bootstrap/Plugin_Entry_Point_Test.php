<?php
/**
 * Entry-point regression coverage for packaged installs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Bootstrap;

use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Verifies the active plugin entry file remains installable without Composer's
 * generated vendor autoloader in production ZIPs.
 */
final class Plugin_Entry_Point_Test extends TestCase {

	/**
	 * @return void
	 */
	public function test_main_plugin_file_does_not_unconditionally_require_vendor_autoload(): void {
		$plugin_root = dirname( __DIR__, 3 );
		$main_file   = $plugin_root . '/aio-page-builder.php';

		$this->assertFileExists( $main_file );

		$contents = (string) file_get_contents( $main_file );

		$this->assertStringContainsString( 'Internal_Autoloader', $contents );
		$this->assertStringContainsString( 'is_readable( $vendor_autoload )', $contents );
		$this->assertStringNotContainsString( "require_once __DIR__ . '/vendor/autoload.php';", $contents );
	}

	/**
	 * @return void
	 */
	public function test_internal_autoloader_loads_project_classes_without_composer(): void {
		$plugin_root     = dirname( __DIR__, 3 );
		$autoloader_file = $plugin_root . '/src/Bootstrap/Internal_Autoloader.php';
		$constants_class = 'AIOPageBuilder\\Bootstrap\\Constants';

		$this->assertFileExists( $autoloader_file );

		require_once $autoloader_file;

		\AIOPageBuilder\Bootstrap\Internal_Autoloader::register();

		$this->assertTrue( class_exists( $constants_class ) );
	}
}
