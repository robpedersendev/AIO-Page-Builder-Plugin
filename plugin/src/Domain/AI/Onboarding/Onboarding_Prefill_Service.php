<?php
/**
 * Builds prefill data for onboarding from stored profile, crawl context, and provider config (onboarding-state-machine.md §8). No secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use AIOPageBuilder\Support\Logging\Named_Debug_Log;
use AIOPageBuilder\Support\Logging\Named_Debug_Log_Event;

/**
 * Assembles profile, crawl refs, and provider readiness for onboarding UI. Traceable to stored sources only.
 */
final class Onboarding_Prefill_Service {

	/** @var Profile_Store */
	private Profile_Store $profile_store;

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Crawl_Snapshot_Service|null */
	private $crawl_snapshot_service;

	/** @var Provider_Secret_Store_Interface */
	private Provider_Secret_Store_Interface $secret_store;

	public function __construct(
		Profile_Store $profile_store,
		Settings_Service $settings,
		?Crawl_Snapshot_Service $crawl_snapshot_service,
		Provider_Secret_Store_Interface $secret_store
	) {
		$this->profile_store          = $profile_store;
		$this->settings               = $settings;
		$this->crawl_snapshot_service = $crawl_snapshot_service;
		$this->secret_store           = $secret_store;
	}

	/**
	 * Returns prefill data for the onboarding screen. No secret values.
	 *
	 * @param array<string, mixed>|null $draft Current draft (optional); used to restore crawl_run_id_ref / goal if present.
	 * @return array<string, mixed> Keys: profile, current_site_url, crawl_run_ids, latest_crawl_run_id, latest_crawl_session_timestamp, latest_crawl_final_status, latest_crawl_started_at, latest_crawl_ended_at, latest_crawl_total_discovered, latest_crawl_failed_count, provider_refs.
	 */
	public function get_prefill_data( ?array $draft = null ): array {
		$profile          = $this->profile_store->get_full_profile();
		$business         = $profile['business_profile'] ?? array();
		$current_site_url = isset( $business['current_site_url'] ) && is_string( $business['current_site_url'] ) ? $business['current_site_url'] : '';

		$crawl_run_ids       = array();
		$latest_crawl_run_id = null;
		if ( $this->crawl_snapshot_service !== null ) {
			$sessions = $this->crawl_snapshot_service->list_sessions( 20 );
			foreach ( $sessions as $session ) {
				$run_id = isset( $session['crawl_run_id'] ) && is_string( $session['crawl_run_id'] ) ? $session['crawl_run_id'] : null;
				if ( $run_id !== null ) {
					$crawl_run_ids[] = $run_id;
					if ( $latest_crawl_run_id === null ) {
						$latest_crawl_run_id = $run_id;
					}
				}
			}
		}
		if ( $draft !== null && ! empty( $draft['crawl_run_id_ref'] ) ) {
			$latest_crawl_run_id = $draft['crawl_run_id_ref'];
		}

		$latest_crawl_session_timestamp = null;
		$latest_crawl_final_status      = null;
		$latest_crawl_started_at        = null;
		$latest_crawl_ended_at          = null;
		$latest_crawl_total_discovered  = 0;
		$latest_crawl_failed_count      = 0;
		if ( $this->crawl_snapshot_service !== null && is_string( $latest_crawl_run_id ) && $latest_crawl_run_id !== '' ) {
			$sess = $this->crawl_snapshot_service->get_session( $latest_crawl_run_id );
			if ( is_array( $sess ) ) {
				$ended   = isset( $sess['ended_at'] ) && is_string( $sess['ended_at'] ) ? \trim( $sess['ended_at'] ) : '';
				$started = isset( $sess['started_at'] ) && is_string( $sess['started_at'] ) ? \trim( $sess['started_at'] ) : '';
				$latest_crawl_session_timestamp = $ended !== '' ? $ended : ( $started !== '' ? $started : null );
				$latest_crawl_started_at        = $started !== '' ? $started : null;
				$latest_crawl_ended_at          = $ended !== '' ? $ended : null;
				$latest_crawl_final_status      = isset( $sess['final_status'] ) && is_string( $sess['final_status'] ) ? \trim( $sess['final_status'] ) : '';
				$latest_crawl_total_discovered  = isset( $sess['total_discovered'] ) ? (int) $sess['total_discovered'] : 0;
				$latest_crawl_failed_count      = isset( $sess['failed_count'] ) ? (int) $sess['failed_count'] : 0;
			}
		}

		$provider_refs = $this->get_provider_refs();
		Named_Debug_Log::event(
			Named_Debug_Log_Event::ONBOARDING_PREFILL_BUILT,
			'crawl_runs=' . (string) count( $crawl_run_ids ) . ' provider_refs=' . (string) count( $provider_refs )
		);

		return array(
			'profile'                        => $profile,
			'current_site_url'               => $current_site_url,
			'crawl_run_ids'                  => $crawl_run_ids,
			'latest_crawl_run_id'            => $latest_crawl_run_id,
			'latest_crawl_session_timestamp' => $latest_crawl_session_timestamp,
			'latest_crawl_final_status'      => $latest_crawl_final_status,
			'latest_crawl_started_at'        => $latest_crawl_started_at,
			'latest_crawl_ended_at'          => $latest_crawl_ended_at,
			'latest_crawl_total_discovered'  => $latest_crawl_total_discovered,
			'latest_crawl_failed_count'      => $latest_crawl_failed_count,
			'provider_refs'                  => $provider_refs,
		);
	}

	/**
	 * Provider rows: provider_id and credential_state from the segregated secret store (authoritative) merged with config ref.
	 * Safe for display; no secret values.
	 *
	 * @return array<int, array{provider_id: string, credential_state: string}>
	 */
	private function get_provider_refs(): array {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		$ids    = array();
		if ( isset( $config['providers'] ) && is_array( $config['providers'] ) ) {
			foreach ( $config['providers'] as $p ) {
				if ( is_array( $p ) && isset( $p['provider_id'] ) && is_string( $p['provider_id'] ) ) {
					$ids[] = \sanitize_key( $p['provider_id'] );
				}
			}
		}
		$defaults = Provider_Capability_Resolver::get_known_provider_ids();
		$merged   = array_unique( array_merge( $defaults, $ids ) );
		$refs     = array();
		foreach ( array_values( $merged ) as $provider_id ) {
			if ( $provider_id === '' ) {
				continue;
			}
			$refs[] = array(
				'provider_id'      => $provider_id,
				'credential_state' => $this->secret_store->get_credential_state( $provider_id ),
			);
		}
		return $refs;
	}

	/**
	 * Whether at least one provider has a stored credential or legacy configured flag (immediate recognition after save).
	 *
	 * @return bool
	 */
	public function is_provider_ready(): bool {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		if ( isset( $config['providers'] ) && is_array( $config['providers'] ) ) {
			foreach ( $config['providers'] as $p ) {
				if ( is_array( $p ) && isset( $p['provider_id'] ) && is_string( $p['provider_id'] ) ) {
					$pid = \sanitize_key( $p['provider_id'] );
					if ( $pid !== '' && $this->secret_store->has_credential( $pid ) ) {
						return true;
					}
				}
			}
		}
		foreach ( Provider_Capability_Resolver::get_known_provider_ids() as $pid ) {
			if ( $this->secret_store->has_credential( $pid ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * First provider id that has a stored credential, for planning runs.
	 *
	 * @return string|null
	 */
	public function get_first_ready_provider_id(): ?string {
		foreach ( $this->get_provider_refs() as $ref ) {
			$pid = isset( $ref['provider_id'] ) ? (string) $ref['provider_id'] : '';
			if ( $pid !== '' && $this->secret_store->has_credential( $pid ) ) {
				return $pid;
			}
		}
		return null;
	}
}
