<?php
/**
 * Unit tests for the active plugin entry point.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests;

use AIOPageBuilder\Bootstrap\Constants;
use AIOPageBuilder\Bootstrap\Plugin;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Verifies the shipped plugin entry point, not the quarantined legacy tree.
 */
final class BootstrapTest extends TestCase {

	/**
	 * Loads the active plugin entry point once for the test class.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		require_once dirname( __DIR__ ) . '/aio-page-builder.php';
	}

	/**
	 * The active plugin bootstrap classes should be available.
	 *
	 * @return void
	 */
	public function test_active_bootstrap_classes_exist(): void {
		$this->assertTrue( class_exists( Constants::class ) );
		$this->assertTrue( class_exists( Plugin::class ) );
	}

	/**
	 * The main plugin file must register activation and deactivation hooks.
	 *
	 * @return void
	 */
	public function test_lifecycle_hooks_are_registered_from_main_plugin_file(): void {
		// * Must match __FILE__ resolution inside aio-page-builder.php (register_*_hook keys).
		$plugin_file = realpath( dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'aio-page-builder.php' );
		$this->assertIsString( $plugin_file );

		$this->assertSame(
			array( Plugin::class, 'activate' ),
			$GLOBALS['_aio_activation_hooks'][ $plugin_file ] ?? null
		);

		$this->assertSame(
			array( Plugin::class, 'deactivate' ),
			$GLOBALS['_aio_deactivation_hooks'][ $plugin_file ] ?? null
		);
	}

	/**
	 * The runtime bootstrap should be attached to plugins_loaded.
	 *
	 * @return void
	 */
	public function test_plugins_loaded_bootstrap_action_is_registered(): void {
		$this->assertSame( 0, has_action( 'plugins_loaded', array( Plugin::class, 'bootstrap' ) ) );
	}

	/**
	 * Constants should resolve plugin identity from the active entry point.
	 *
	 * @return void
	 */
	public function test_constants_resolve_active_plugin_identity(): void {
		$this->assertSame( '0.1.0', Constants::plugin_version() );
		$this->assertStringEndsWith( '/plugin/aio-page-builder.php', str_replace( '\\', '/', Constants::plugin_file() ) );
	}
}
