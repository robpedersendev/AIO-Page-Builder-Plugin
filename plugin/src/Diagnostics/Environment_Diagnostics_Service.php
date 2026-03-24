<?php
/**
 * Environment-facing diagnostics for live preview and related subsystems.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Diagnostics;

use AIOPageBuilder\Frontend\Template_Live_Preview_Ticket_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Summarizes live preview hardening and storage health for support screens.
 */
final class Environment_Diagnostics_Service {

	/**
	 * @return array<string, string|bool>
	 */
	public static function build_live_preview_snapshot(): array {
		return array(
			'live_preview_hardening_enabled' => true,
			'ticket_storage_healthy'         => Template_Live_Preview_Ticket_Service::probe_storage_health(),
			'default_shell_mode'             => Template_Live_Preview_Ticket_Service::SHELL_MINIMAL,
			'preview_security_headers'       => true,
		);
	}
}
