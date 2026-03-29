<?php
/**
 * Derives a crawl readiness phase and user-facing lines from onboarding prefill (no secrets).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

/**
 * Maps stored crawl session fields to onboarding copy and a stable phase key.
 */
final class Onboarding_Crawl_Context_Phase {

	public const PHASE_NONE      = 'none';
	public const PHASE_RUNNING   = 'running';
	public const PHASE_COMPLETED = 'completed';
	public const PHASE_PARTIAL   = 'partial';
	public const PHASE_FAILED    = 'failed';
	public const PHASE_UNKNOWN   = 'unknown';
	public const PHASE_STALE     = 'stale';

	/**
	 * @param array<string, mixed> $prefill Onboarding prefill from {@see Onboarding_Prefill_Service::get_prefill_data()}.
	 * @return array{phase: string, phase_label: string, is_stale: bool, headline: string, detail: string, next_step: string}
	 */
	public static function summarize( array $prefill, ?Settings_Service $settings ): array {
		$run_id = isset( $prefill['latest_crawl_run_id'] ) && is_string( $prefill['latest_crawl_run_id'] )
			? \trim( $prefill['latest_crawl_run_id'] )
			: '';
		if ( $run_id === '' ) {
			return self::pack(
				self::PHASE_NONE,
				false,
				__( 'No crawl recorded yet', 'aio-page-builder' ),
				__( 'Crawl is optional. When you run one, the planner can use discovered pages as context.', 'aio-page-builder' ),
				__( 'Expand the section below to start a crawl or open the full Crawler screen.', 'aio-page-builder' )
			);
		}

		$fs      = isset( $prefill['latest_crawl_final_status'] ) && is_string( $prefill['latest_crawl_final_status'] )
			? \strtolower( \trim( $prefill['latest_crawl_final_status'] ) )
			: '';
		$ended   = isset( $prefill['latest_crawl_ended_at'] ) && is_string( $prefill['latest_crawl_ended_at'] )
			? \trim( $prefill['latest_crawl_ended_at'] )
			: '';
		$started = isset( $prefill['latest_crawl_started_at'] ) && is_string( $prefill['latest_crawl_started_at'] )
			? \trim( $prefill['latest_crawl_started_at'] )
			: '';
		$disc    = isset( $prefill['latest_crawl_total_discovered'] ) ? (int) $prefill['latest_crawl_total_discovered'] : 0;
		$failed  = isset( $prefill['latest_crawl_failed_count'] ) ? (int) $prefill['latest_crawl_failed_count'] : 0;

		$threshold_days = 30;
		if ( $settings !== null ) {
			$main = $settings->get( Option_Names::MAIN_SETTINGS );
			$raw  = isset( $main['onboarding_stale_crawl_warning_days'] ) ? (int) $main['onboarding_stale_crawl_warning_days'] : 0;
			if ( $raw > 0 ) {
				$threshold_days = $raw;
			}
		}

		$end_ts   = $ended !== '' ? \strtotime( $ended ) : false;
		$is_stale = false;
		if ( $end_ts !== false ) {
			$age_seconds = \time() - $end_ts;
			$is_stale    = $age_seconds > ( $threshold_days * 86400 );
		}

		if ( $ended === '' && $started !== '' ) {
			$running = $fs === '' || $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING || $fs === 'unknown';
			if ( $running ) {
				return self::pack(
					self::PHASE_RUNNING,
					false,
					__( 'Crawl in progress or queued', 'aio-page-builder' ),
					sprintf(
						/* translators: %s: crawl run id */
						__( 'Latest run: %s. Refresh this step after the crawler finishes.', 'aio-page-builder' ),
						$run_id
					),
					__( 'Wait for completion, or open Crawler Sessions to inspect status.', 'aio-page-builder' )
				);
			}
		}

		if ( $ended !== '' && $is_stale ) {
			return self::pack(
				self::PHASE_STALE,
				true,
				__( 'Crawl data may be outdated', 'aio-page-builder' ),
				sprintf(
					/* translators: 1: crawl run id, 2: max age in days */
					__( 'Last run %1$s ended more than %2$d days ago. Consider a new crawl if the site changed.', 'aio-page-builder' ),
					$run_id,
					$threshold_days
				),
				__( 'Run a fresh crawl before another planning request if you need current pages.', 'aio-page-builder' )
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_COMPLETED ) {
			return self::pack(
				self::PHASE_COMPLETED,
				false,
				__( 'Latest crawl completed', 'aio-page-builder' ),
				sprintf(
					/* translators: 1: run id, 2: discovered URL count */
					__( 'Run %1$s finished. Discovered URLs: %2$d.', 'aio-page-builder' ),
					$run_id,
					$disc
				),
				__( 'You can continue onboarding; planning will use this context when available.', 'aio-page-builder' )
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_PARTIAL ) {
			return self::pack(
				self::PHASE_PARTIAL,
				false,
				__( 'Latest crawl finished with partial results', 'aio-page-builder' ),
				sprintf(
					/* translators: 1: run id, 2: failed URLs, 3: discovered URLs */
					__( 'Run %1$s: some URLs failed (%2$d failed, %3$d discovered). Review the session for details.', 'aio-page-builder' ),
					$run_id,
					$failed,
					$disc
				),
				__( 'You may retry or fix scope, then continue when ready.', 'aio-page-builder' )
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED ) {
			return self::pack(
				self::PHASE_FAILED,
				false,
				__( 'Latest crawl failed', 'aio-page-builder' ),
				sprintf(
					/* translators: %s: crawl run id */
					__( 'Run %s did not complete successfully. Open Crawler Sessions for the error and retry if offered.', 'aio-page-builder' ),
					$run_id
				),
				__( 'Fix the crawl scope or site access, then retry.', 'aio-page-builder' )
			);
		}

		return self::pack(
			self::PHASE_UNKNOWN,
			false,
			__( 'Crawl status unclear', 'aio-page-builder' ),
			sprintf(
				/* translators: 1: run id, 2: raw status string */
				__( 'Run %1$s: reported status “%2$s”. Open Crawler Sessions for full detail.', 'aio-page-builder' ),
				$run_id,
				$fs !== '' ? $fs : __( 'unknown', 'aio-page-builder' )
			),
			__( 'Refresh after the crawler updates, or retry from the crawler screen.', 'aio-page-builder' )
		);
	}

	/**
	 * Stable shape for UI, telemetry, and data attributes.
	 *
	 * @return array{phase: string, phase_label: string, is_stale: bool, headline: string, detail: string, next_step: string}
	 */
	private static function pack( string $phase, bool $is_stale, string $headline, string $detail, string $next_step ): array {
		return array(
			'phase'       => $phase,
			'phase_label' => self::short_label_for_phase( $phase ),
			'is_stale'    => $is_stale,
			'headline'    => $headline,
			'detail'      => $detail,
			'next_step'   => $next_step,
		);
	}

	/**
	 * Short machine-readable phase name for operators and assistive labels (paired with `phase`).
	 */
	private static function short_label_for_phase( string $phase ): string {
		switch ( $phase ) {
			case self::PHASE_NONE:
				return __( 'No crawl', 'aio-page-builder' );
			case self::PHASE_RUNNING:
				return __( 'Running', 'aio-page-builder' );
			case self::PHASE_COMPLETED:
				return __( 'Completed', 'aio-page-builder' );
			case self::PHASE_PARTIAL:
				return __( 'Partial', 'aio-page-builder' );
			case self::PHASE_FAILED:
				return __( 'Failed', 'aio-page-builder' );
			case self::PHASE_STALE:
				return __( 'Stale', 'aio-page-builder' );
			case self::PHASE_UNKNOWN:
			default:
				return __( 'Unknown', 'aio-page-builder' );
		}
	}
}
