<?php
/**
 * Unit tests for legacy PrivatePluginBase classes (quarantined in plugin/legacy/).
 *
 * These classes are NOT loaded by the active plugin. Entry is aio-page-builder.php → AIOPageBuilder\Bootstrap\Plugin.
 * This test loads legacy files explicitly from legacy/ for regression only.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests;

use PHPUnit\Framework\TestCase;
use PrivatePluginBase\Bootstrap;
use PrivatePluginBase\Options;
use PrivatePluginBase\Security\Capabilities;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__ );
$legacy_root = $plugin_root . '/legacy';
// Load legacy PrivatePluginBase classes from quarantine (not used by active plugin).
require_once $legacy_root . '/PrivatePluginBase/Options.php';
require_once $legacy_root . '/PrivatePluginBase/Security/Capabilities.php';
require_once $legacy_root . '/PrivatePluginBase/Activation.php';
require_once $legacy_root . '/PrivatePluginBase/Deactivation.php';
require_once $legacy_root . '/PrivatePluginBase/Admin/Settings/Page.php';
require_once $legacy_root . '/PrivatePluginBase/Admin/Menu.php';
require_once $legacy_root . '/PrivatePluginBase/Settings/Registrar.php';
require_once $legacy_root . '/PrivatePluginBase/Reporting/Service.php';
require_once $legacy_root . '/PrivatePluginBase/Diagnostics/Logger.php';
require_once $legacy_root . '/PrivatePluginBase/Rest/NamespaceController.php';
require_once $legacy_root . '/PrivatePluginBase/Bootstrap.php';

/**
 * Tests for legacy PrivatePluginBase bootstrap and core classes (legacy/ only).
 */
final class BootstrapTest extends TestCase {

	/**
	 * Asserts that the legacy Bootstrap class exists and is loadable.
	 *
	 * @return void
	 */
	public function test_bootstrap_class_exists(): void {
		$this->assertTrue( class_exists( 'PrivatePluginBase\\Bootstrap' ) );
	}

	/**
	 * Asserts that the Options class exists.
	 *
	 * @return void
	 */
	public function test_options_class_exists(): void {
		$this->assertTrue( class_exists( Options::class ) );
	}

	/**
	 * Asserts that the Capabilities class has the expected constant.
	 *
	 * @return void
	 */
	public function test_capabilities_manage_constant_defined(): void {
		$this->assertSame( 'manage_private_plugin_base', Capabilities::MANAGE );
	}
}
