<?php
/**
 * Reporting service scaffold.
 *
 * Isolated in its own domain. Core plugin behavior must not depend on this.
 * See docs/standards/REPORTING_EXCEPTION.md.
 *
 * @package PrivatePluginBase\Reporting
 */

declare(strict_types=1);

namespace PrivatePluginBase\Reporting;

defined( 'ABSPATH' ) || exit;

/**
 * Outbound operational reporting service.
 *
 * Scaffold only. No payloads sent. When implemented, must have:
 * schema definitions, redaction rules, retry rules, timeout rules, audit logs.
 * Failure must never take down core plugin behavior.
 */
final class Service {

	/**
	 * Registers reporting hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Scaffold: no hooks registered. Future implementations may add.
		// Possible additions: install notification on activation, heartbeat via wp-cron, diagnostics on demand.
	}
}
