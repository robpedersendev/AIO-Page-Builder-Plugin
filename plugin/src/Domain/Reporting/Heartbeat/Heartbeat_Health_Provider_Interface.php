<?php
/**
 * Provides health/status data for heartbeat payload (spec §46.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

/**
 * Returns last successful run timestamps and current health counts (redaction-safe).
 */
interface Heartbeat_Health_Provider_Interface {

	/**
	 * Returns data for heartbeat payload. No secrets.
	 *
	 * @return array{
	 *   last_successful_ai_run_at: string,
	 *   last_successful_build_plan_execution_at: string,
	 *   current_health_summary: string,
	 *   current_queue_warning_count: int,
	 *   current_unresolved_critical_error_count: int
	 * }
	 */
	public function get_health_data(): array;
}
