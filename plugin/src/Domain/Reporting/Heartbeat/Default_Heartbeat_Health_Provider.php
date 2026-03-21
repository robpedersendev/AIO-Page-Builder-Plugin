<?php
/**
 * Default health provider: returns empty/zero/healthy when no diagnostics source is available.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Heartbeat;

defined( 'ABSPATH' ) || exit;

/**
 * Provides safe defaults for heartbeat health fields.
 */
final class Default_Heartbeat_Health_Provider implements Heartbeat_Health_Provider_Interface {

	/** @var array<string> Valid health summary values. */
	private const HEALTH_VALUES = array( 'healthy', 'warning', 'degraded', 'critical' );

	public function get_health_data(): array {
		return array(
			'last_successful_ai_run_at'               => '',
			'last_successful_build_plan_execution_at' => '',
			'current_health_summary'                  => self::HEALTH_VALUES[0],
			'current_queue_warning_count'             => 0,
			'current_unresolved_critical_error_count' => 0,
		);
	}
}
