<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old PrivatePluginBase activation. Active: AIOPageBuilder\Bootstrap\Plugin::activate() → Lifecycle_Manager.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Activation handler.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation.
 */
final class Activation {

	/**
	 * Runs on plugin activation.
	 *
	 * @return void
	 */
	public static function run(): void {
		Security\Capabilities::add_to_administrator();
		Options::set_version();
	}
}
