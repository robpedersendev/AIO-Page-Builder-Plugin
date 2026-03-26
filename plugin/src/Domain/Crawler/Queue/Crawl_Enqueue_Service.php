<?php
/**
 * Enqueues crawl runs and retry runs (admin-triggered).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Queue;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Execution\Crawl_Run_Processor;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Payload_Builder;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;

/**
 * Creates crawl sessions with queued status and basic locking to prevent duplicates.
 */
final class Crawl_Enqueue_Service {

	private const LOCK_PREFIX      = 'aio_page_builder_crawl_lock_';
	private const LOCK_TTL_SECONDS = 1800;

	private Crawl_Snapshot_Service $snapshot_service;

	public function __construct( Crawl_Snapshot_Service $snapshot_service ) {
		$this->snapshot_service = $snapshot_service;
	}

	/**
	 * Enqueues a new crawl run for the current site.
	 *
	 * @param array<string, mixed> $settings
	 * @param string               $created_by
	 * @return array{success: bool, crawl_id: string, message: string}
	 */
	public function enqueue_start( array $settings, string $created_by ): array {
		$site_url  = function_exists( 'site_url' ) ? (string) \site_url() : '';
		$site_host = $this->host_from_site_url( $site_url );
		if ( $site_host === '' ) {
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'Site host could not be determined.', 'aio-page-builder' ),
			);
		}
		if ( ! $this->acquire_lock( $site_host ) ) {
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'A crawl is already active or recently queued for this site.', 'aio-page-builder' ),
			);
		}

		$crawl_id = $this->snapshot_service->create_session( $site_host, $settings );
		if ( $crawl_id === '' ) {
			$this->release_lock( $site_host );
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'Failed to queue crawl.', 'aio-page-builder' ),
			);
		}

		$this->augment_session_payload( $crawl_id, $site_url, '', $settings, $created_by );
		$this->schedule_processor( $crawl_id );
		return array(
			'success'  => true,
			'crawl_id' => $crawl_id,
			'message'  => __( 'Crawl queued.', 'aio-page-builder' ),
		);
	}

	/**
	 * Enqueues a retry crawl run from prior snapshot settings (linked by retry_of).
	 *
	 * @param string $prior_crawl_id
	 * @param string $created_by
	 * @return array{success: bool, crawl_id: string, message: string}
	 */
	public function enqueue_retry( string $prior_crawl_id, string $created_by ): array {
		$prior_id = \sanitize_text_field( $prior_crawl_id );
		$prior    = $this->snapshot_service->get_session( $prior_id );
		if ( $prior === null ) {
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'Prior crawl session not found.', 'aio-page-builder' ),
			);
		}
		$site_host = (string) ( $prior[ Crawl_Snapshot_Payload_Builder::SESSION_SITE_HOST ] ?? '' );
		$site_host = \sanitize_text_field( $site_host );
		if ( $site_host === '' ) {
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'Prior crawl session host is missing.', 'aio-page-builder' ),
			);
		}
		if ( ! $this->acquire_lock( $site_host ) ) {
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'A crawl is already active or recently queued for this site.', 'aio-page-builder' ),
			);
		}
		$settings = isset( $prior[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] ) && is_array( $prior[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] )
			? $prior[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ]
			: array();
		$crawl_id = $this->snapshot_service->create_session( $site_host, $settings );
		if ( $crawl_id === '' ) {
			$this->release_lock( $site_host );
			return array(
				'success'  => false,
				'crawl_id' => '',
				'message'  => __( 'Failed to queue retry crawl.', 'aio-page-builder' ),
			);
		}
		$site_url = (string) ( $prior['site_url'] ?? '' );
		$this->augment_session_payload( $crawl_id, $site_url, $prior_id, $settings, $created_by );
		$this->schedule_processor( $crawl_id );
		return array(
			'success'  => true,
			'crawl_id' => $crawl_id,
			'message'  => __( 'Retry queued from prior snapshot settings.', 'aio-page-builder' ),
		);
	}

	private function host_from_site_url( string $site_url ): string {
		$parts = \wp_parse_url( $site_url );
		$host  = is_array( $parts ) && isset( $parts['host'] ) ? (string) $parts['host'] : '';
		return strtolower( trim( $host ) );
	}

	private function augment_session_payload( string $crawl_id, string $site_url, string $retry_of, array $settings, string $created_by ): void {
		$existing = $this->snapshot_service->get_session( $crawl_id );
		if ( $existing === null ) {
			return;
		}
		$existing['site_url'] = $site_url !== '' ? \esc_url_raw( $site_url ) : '';
		$existing['retry_of'] = $retry_of !== '' ? \sanitize_text_field( $retry_of ) : '';
		$prev_settings        = isset( $existing[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] ) && is_array( $existing[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] )
			? $existing[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ]
			: array();
		$existing[ Crawl_Snapshot_Payload_Builder::SESSION_SETTINGS ] = array_merge( $prev_settings, is_array( $settings ) ? $settings : array() );
		$existing['created_by']                                       = \sanitize_text_field( $created_by );
		$existing['created_at']                                       = gmdate( 'c' );
		$existing['crawl_id'] = $crawl_id;
		$option_key           = 'aio_page_builder_crawl_session_' . substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $crawl_id ), 0, 64 );
		\update_option( $option_key, $existing, false );
	}

	/**
	 * Releases the per-site crawl lock after a run finishes (called by Crawl_Run_Processor).
	 *
	 * @param string $site_host Canonical host.
	 * @return void
	 */
	public function release_lock_for_host( string $site_host ): void {
		$host = strtolower( trim( $site_host ) );
		if ( $host === '' ) {
			return;
		}
		$this->release_lock( $host );
	}

	/**
	 * Schedules WP-Cron to execute the crawl processor (spawn_cron when wp-cron is disabled).
	 *
	 * @param string $crawl_id Crawl run id.
	 * @return void
	 */
	private function schedule_processor( string $crawl_id ): void {
		$crawl_id = \sanitize_text_field( $crawl_id );
		if ( $crawl_id === '' || ! \function_exists( 'wp_schedule_single_event' ) ) {
			return;
		}
		\wp_schedule_single_event( \time(), Crawl_Run_Processor::CRON_HOOK, array( $crawl_id ) );
		if ( \defined( 'DISABLE_WP_CRON' ) && \DISABLE_WP_CRON && \function_exists( 'spawn_cron' ) ) {
			\spawn_cron();
		}
	}

	private function acquire_lock( string $site_host ): bool {
		$key = self::LOCK_PREFIX . md5( $site_host );
		$now = time();
		$raw = \get_option( $key, array() );
		if ( is_array( $raw ) && isset( $raw['expires_at'] ) && is_numeric( $raw['expires_at'] ) ) {
			if ( (int) $raw['expires_at'] > $now ) {
				return false;
			}
		}
		$payload = array(
			'site_host'   => $site_host,
			'acquired_at' => $now,
			'expires_at'  => $now + self::LOCK_TTL_SECONDS,
		);
		return \update_option( $key, $payload, false );
	}

	private function release_lock( string $site_host ): void {
		$key = self::LOCK_PREFIX . md5( $site_host );
		\delete_option( $key );
	}
}
