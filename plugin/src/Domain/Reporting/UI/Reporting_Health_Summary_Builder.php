<?php
/**
 * Builds reporting health summary for admin monitoring (spec §46.10, §49.11).
 *
 * Surfaces recent reporting failures, last successful heartbeat, and degraded state.
 * Redacted; no secrets. Used by Queue & Logs screen.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\UI;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Builds a small summary payload for reporting health display.
 */
final class Reporting_Health_Summary_Builder {

	private const RECENT_FAILURES_WINDOW = 30;

	/**
	 * Builds reporting health summary from options (REPORTING_LOG, HEARTBEAT_STATE, ERROR_REPORT_STATE).
	 *
	 * @return array{
	 *   recent_failures_count: int,
	 *   last_heartbeat_month: string,
	 *   reporting_degraded: bool,
	 *   summary_message: string,
	 *   failed_events_by_type: array<string, int>
	 * }
	 */
	public function build(): array {
		$log = \get_option( Option_Names::REPORTING_LOG, array() );
		if ( ! is_array( $log ) ) {
			$log = array();
		}
		$recent_failures = 0;
		$failed_by_type  = array();
		$cutoff          = gmdate( 'Y-m-d\TH:i:s\Z', strtotime( '-' . self::RECENT_FAILURES_WINDOW . ' days' ) );
		foreach ( $log as $entry ) {
			if ( ! is_array( $entry ) || ( (string) ( $entry['delivery_status'] ?? '' ) ) !== 'failed' ) {
				continue;
			}
			$attempted = (string) ( $entry['attempted_at'] ?? '' );
			if ( $attempted !== '' && $attempted < $cutoff ) {
				continue;
			}
			++$recent_failures;
			$type                    = (string) ( $entry['event_type'] ?? 'unknown' );
			$failed_by_type[ $type ] = ( $failed_by_type[ $type ] ?? 0 ) + 1;
		}

		$heartbeat_state = \get_option( Option_Names::HEARTBEAT_STATE, array() );
		$last_heartbeat  = '';
		if ( is_array( $heartbeat_state ) && isset( $heartbeat_state['last_successful_month'] ) ) {
			$last_heartbeat = (string) $heartbeat_state['last_successful_month'];
		}

		$current_ym      = gmdate( 'Y-m' );
		$degraded        = $recent_failures > 0 || ( $last_heartbeat !== '' && $last_heartbeat < $current_ym );
		if ( $recent_failures > 0 ) {
			$summary_message = sprintf(
				/* translators: %d: number of failed outbound reporting deliveries in the last 30 days */
				\__( '%d reporting delivery failure(s) in the last 30 days.', 'aio-page-builder' ),
				$recent_failures
			);
		} elseif ( $last_heartbeat === $current_ym ) {
			$summary_message = \__( 'No reporting delivery failures in the last 30 days. Heartbeat recorded for this calendar month.', 'aio-page-builder' );
		} else {
			$summary_message = \__( 'No reporting delivery failures in the last 30 days. No successful heartbeat recorded for this calendar month yet.', 'aio-page-builder' );
		}

		return array(
			'recent_failures_count' => $recent_failures,
			'last_heartbeat_month'  => $last_heartbeat,
			'reporting_degraded'    => $degraded,
			'summary_message'       => $summary_message,
			'failed_events_by_type' => $failed_by_type,
		);
	}
}
