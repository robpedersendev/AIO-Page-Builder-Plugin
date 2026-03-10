<?php
/**
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
