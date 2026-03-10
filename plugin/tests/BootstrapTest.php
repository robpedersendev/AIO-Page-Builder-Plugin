<?php
/**
 * Bootstrap unit tests.
 *
 * @package PrivatePluginBase
 */

namespace PrivatePluginBase\Tests;

use PHPUnit\Framework\TestCase;
use PrivatePluginBase\Bootstrap;
use PrivatePluginBase\Options;
use PrivatePluginBase\Security\Capabilities;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

/**
 * Tests for plugin bootstrap and core classes.
 */
final class BootstrapTest extends TestCase {

	/**
	 * Asserts that the Bootstrap class exists and is loadable.
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
