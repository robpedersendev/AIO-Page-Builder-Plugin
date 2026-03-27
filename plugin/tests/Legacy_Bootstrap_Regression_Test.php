<?php
/**
 * Regression tests for quarantined legacy PrivatePluginBase classes.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests;

use PHPUnit\Framework\TestCase;
use PrivatePluginBase\Options;
use PrivatePluginBase\Security\Capabilities;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__ );
$legacy_root = $plugin_root . '/legacy';

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
 * Keeps the quarantined legacy tree loadable while making the active plugin test authoritative.
 */
final class Legacy_Bootstrap_Regression_Test extends TestCase {

	/**
	 * The legacy bootstrap class remains loadable from the quarantined tree.
	 *
	 * @return void
	 */
	public function test_legacy_bootstrap_class_exists(): void {
		$this->assertTrue( class_exists( 'PrivatePluginBase\\Bootstrap' ) );
	}

	/**
	 * The legacy options class should remain loadable for regression purposes.
	 *
	 * @return void
	 */
	public function test_legacy_options_class_exists(): void {
		$this->assertTrue( class_exists( Options::class ) );
	}

	/**
	 * The legacy capabilities constant should remain stable.
	 *
	 * @return void
	 */
	public function test_legacy_capabilities_constant_remains_stable(): void {
		$this->assertSame( 'manage_private_plugin_base', Capabilities::MANAGE );
	}
}
