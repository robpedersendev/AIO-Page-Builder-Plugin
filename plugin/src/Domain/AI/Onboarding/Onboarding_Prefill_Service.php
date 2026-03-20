<?php
/**
 * Builds prefill data for onboarding from stored profile, crawl context, and provider config (onboarding-state-machine.md §8). No secrets.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Crawler\Snapshots\Crawl_Snapshot_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;

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

	public function __construct( Profile_Store $profile_store, Settings_Service $settings, ?Crawl_Snapshot_Service $crawl_snapshot_service = null ) {
		$this->profile_store          = $profile_store;
		$this->settings               = $settings;
		$this->crawl_snapshot_service = $crawl_snapshot_service;
	}

	/**
	 * Returns prefill data for the onboarding screen. No secret values.
	 *
	 * @param array<string, mixed>|null $draft Current draft (optional); used to restore crawl_run_id_ref / goal if present.
	 * @return array<string, mixed> Keys: profile (brand_profile, business_profile), current_site_url, crawl_run_ids, latest_crawl_run_id, latest_crawl_session_timestamp, provider_refs.
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
		if ( $this->crawl_snapshot_service !== null && is_string( $latest_crawl_run_id ) && $latest_crawl_run_id !== '' ) {
			$sess = $this->crawl_snapshot_service->get_session( $latest_crawl_run_id );
			if ( is_array( $sess ) ) {
				$ended                          = isset( $sess['ended_at'] ) && is_string( $sess['ended_at'] ) ? \trim( $sess['ended_at'] ) : '';
				$started                        = isset( $sess['started_at'] ) && is_string( $sess['started_at'] ) ? \trim( $sess['started_at'] ) : '';
				$latest_crawl_session_timestamp = $ended !== '' ? $ended : ( $started !== '' ? $started : null );
			}
		}

		$provider_refs = $this->get_provider_refs();

		return array(
			'profile'                        => $profile,
			'current_site_url'               => $current_site_url,
			'crawl_run_ids'                  => $crawl_run_ids,
			'latest_crawl_run_id'            => $latest_crawl_run_id,
			'latest_crawl_session_timestamp' => $latest_crawl_session_timestamp,
			'provider_refs'                  => $provider_refs,
		);
	}

	/**
	 * Provider config: provider_id and credential_state only. No secrets.
	 *
	 * @return array<int, array{provider_id: string, credential_state: string}>
	 */
	private function get_provider_refs(): array {
		$config = $this->settings->get( Option_Names::PROVIDER_CONFIG_REF );
		$refs   = array();
		if ( isset( $config['providers'] ) && is_array( $config['providers'] ) ) {
			foreach ( $config['providers'] as $p ) {
				if ( is_array( $p ) && isset( $p['provider_id'] ) && is_string( $p['provider_id'] ) ) {
					$refs[] = array(
						'provider_id'      => \sanitize_text_field( $p['provider_id'] ),
						'credential_state' => isset( $p['credential_state'] ) && is_string( $p['credential_state'] ) ? $p['credential_state'] : 'absent',
					);
				}
			}
		}
		return $refs;
	}

	/**
	 * Whether at least one provider has credential_state === configured.
	 *
	 * @return bool
	 */
	public function is_provider_ready(): bool {
		$refs = $this->get_provider_refs();
		foreach ( $refs as $ref ) {
			if ( ( $ref['credential_state'] ?? '' ) === 'configured' ) {
				return true;
			}
		}
		return false;
	}
}
