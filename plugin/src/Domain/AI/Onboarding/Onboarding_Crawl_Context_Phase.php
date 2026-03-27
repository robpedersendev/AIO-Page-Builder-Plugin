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

	public const PHASE_NONE       = 'none';
	public const PHASE_RUNNING    = 'running';
	public const PHASE_COMPLETED  = 'completed';
	public const PHASE_PARTIAL    = 'partial';
	public const PHASE_FAILED     = 'failed';
	public const PHASE_UNKNOWN    = 'unknown';
	public const PHASE_STALE      = 'stale';

	/**
	 * @param array<string, mixed> $prefill Onboarding prefill from {@see Onboarding_Prefill_Service::get_prefill_data()}.
	 * @return array{phase: string, is_stale: bool, headline: string, detail: string, next_step: string}
	 */
	public static function summarize( array $prefill, ?Settings_Service $settings ): array {
		$run_id = isset( $prefill['latest_crawl_run_id'] ) && is_string( $prefill['latest_crawl_run_id'] )
			? \trim( $prefill['latest_crawl_run_id'] )
			: '';
		if ( $run_id === '' ) {
			return array(
				'phase'     => self::PHASE_NONE,
				'is_stale'  => false,
				'headline'  => __( 'No crawl recorded yet', 'aio-page-builder' ),
				'detail'    => __( 'Crawl is optional. When you run one, the planner can use discovered pages as context.', 'aio-page-builder' ),
				'next_step' => __( 'Expand the section below to start a crawl or open the full Crawler screen.', 'aio-page-builder' ),
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

		$end_ts = $ended !== '' ? \strtotime( $ended ) : false;
		$is_stale = false;
		if ( $end_ts !== false ) {
			$age_seconds = \time() - $end_ts;
			$is_stale    = $age_seconds > ( $threshold_days * 86400 );
		}

		if ( $ended === '' && $started !== '' ) {
			$running = $fs === '' || $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_RUNNING || $fs === 'unknown';
			if ( $running ) {
				return array(
					'phase'     => self::PHASE_RUNNING,
					'is_stale'  => false,
					'headline'  => __( 'Crawl in progress or queued', 'aio-page-builder' ),
					'detail'    => sprintf(
						/* translators: %s: crawl run id */
						__( 'Latest run: %s. Refresh this step after the crawler finishes.', 'aio-page-builder' ),
						$run_id
					),
					'next_step' => __( 'Wait for completion, or open Crawler Sessions to inspect status.', 'aio-page-builder' ),
				);
			}
		}

		if ( $ended !== '' && $is_stale ) {
			return array(
				'phase'     => self::PHASE_STALE,
				'is_stale'  => true,
				'headline'  => __( 'Crawl data may be outdated', 'aio-page-builder' ),
				'detail'    => sprintf(
					/* translators: 1: crawl run id, 2: max age in days */
					__( 'Last run %1$s ended more than %2$d days ago. Consider a new crawl if the site changed.', 'aio-page-builder' ),
					$run_id,
					$threshold_days
				),
				'next_step' => __( 'Run a fresh crawl before another planning request if you need current pages.', 'aio-page-builder' ),
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_COMPLETED ) {
			return array(
				'phase'     => self::PHASE_COMPLETED,
				'is_stale'  => false,
				'headline'  => __( 'Latest crawl completed', 'aio-page-builder' ),
				'detail'    => sprintf(
					/* translators: 1: run id, 2: discovered URL count */
					__( 'Run %1$s finished. Discovered URLs: %2$d.', 'aio-page-builder' ),
					$run_id,
					$disc
				),
				'next_step' => __( 'You can continue onboarding; planning will use this context when available.', 'aio-page-builder' ),
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_PARTIAL ) {
			return array(
				'phase'     => self::PHASE_PARTIAL,
				'is_stale'  => false,
				'headline'  => __( 'Latest crawl finished with partial results', 'aio-page-builder' ),
				'detail'    => sprintf(
					/* translators: 1: run id, 2: failed URLs, 3: discovered URLs */
					__( 'Run %1$s: some URLs failed (%2$d failed, %3$d discovered). Review the session for details.', 'aio-page-builder' ),
					$run_id,
					$failed,
					$disc
				),
				'next_step' => __( 'You may retry or fix scope, then continue when ready.', 'aio-page-builder' ),
			);
		}

		if ( $fs === Crawl_Snapshot_Payload_Builder::SESSION_STATUS_FAILED ) {
			return array(
				'phase'     => self::PHASE_FAILED,
				'is_stale'  => false,
				'headline'  => __( 'Latest crawl failed', 'aio-page-builder' ),
				'detail'    => sprintf(
					/* translators: %s: crawl run id */
					__( 'Run %s did not complete successfully. Open Crawler Sessions for the error and retry if offered.', 'aio-page-builder' ),
					$run_id
				),
				'next_step' => __( 'Fix the crawl scope or site access, then retry.', 'aio-page-builder' ),
			);
		}

		return array(
			'phase'     => self::PHASE_UNKNOWN,
			'is_stale'  => false,
			'headline'  => __( 'Crawl status unclear', 'aio-page-builder' ),
			'detail'    => sprintf(
				/* translators: 1: run id, 2: raw status string */
				__( 'Run %1$s: reported status “%2$s”. Open Crawler Sessions for full detail.', 'aio-page-builder' ),
				$run_id,
				$fs !== '' ? $fs : __( 'unknown', 'aio-page-builder' )
			),
			'next_step' => __( 'Refresh after the crawler updates, or retry from the crawler screen.', 'aio-page-builder' ),
		);
	}
}
