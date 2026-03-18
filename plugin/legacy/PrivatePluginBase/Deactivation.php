<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old PrivatePluginBase deactivation. Active: Plugin::deactivate() → Lifecycle_Manager.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Deactivation handler.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation.
 */
final class Deactivation {

	/**
	 * Runs on plugin deactivation.
	 *
	 * @return void
	 */
	public static function run(): void {
		// Flush rewrite rules if plugin registers post types or taxonomies.
		flush_rewrite_rules();
	}
}
