<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old reporting scaffold. Active plugin uses AIOPageBuilder reporting domain.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Reporting service scaffold.
 *
 * @package PrivatePluginBase\Reporting
 */

declare(strict_types=1);

namespace PrivatePluginBase\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Outbound operational reporting service.
 *
 * Scaffold only. No payloads sent.
 */
final class Service {

	/**
	 * Registers reporting hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Scaffold: no hooks registered.
	}
}
