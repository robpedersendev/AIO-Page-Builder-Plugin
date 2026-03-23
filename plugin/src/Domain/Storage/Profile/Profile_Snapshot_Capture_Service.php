<?php
/**
 * Hooks into profile-write lifecycle events and persists snapshots (v2-scope-backlog.md §3).
 *
 * Listens to:
 * - aio_pb_brand_profile_merged    → source = brand_profile_merge
 * - aio_pb_business_profile_merged → source = business_profile_merge
 * - aio_pb_onboarding_run_completed → source = onboarding_completion (receives Profile_Store)
 *
 * All captures are best-effort: failures are logged but never propagate to callers.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Profile;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Support\Logging\Internal_Debug_Log;

/**
 * Registers WordPress action hooks to trigger snapshot capture on profile lifecycle events.
 */
final class Profile_Snapshot_Capture_Service {

	/** @var Profile_Snapshot_Factory */
	private Profile_Snapshot_Factory $factory;

	/** @var Profile_Snapshot_Repository_Interface */
	private Profile_Snapshot_Repository_Interface $repository;

	public function __construct(
		Profile_Snapshot_Factory $factory,
		Profile_Snapshot_Repository_Interface $repository
	) {
		$this->factory    = $factory;
		$this->repository = $repository;
	}

	/**
	 * Registers brand/business profile-merge hooks. The onboarding_run_completed hook is
	 * wired by Profile_Snapshot_Provider so it can resolve the profile_store from the container.
	 *
	 * @return void
	 */
	public function register_hooks(): void {
		\add_action( 'aio_pb_brand_profile_merged', array( $this, 'on_brand_profile_merged' ), 10, 1 );
		\add_action( 'aio_pb_business_profile_merged', array( $this, 'on_business_profile_merged' ), 10, 1 );
	}

	/**
	 * Captures a snapshot after brand profile is merged.
	 *
	 * @param Profile_Store_Interface $store
	 * @return void
	 */
	public function on_brand_profile_merged( Profile_Store_Interface $store ): void {
		$this->capture( $store, 'brand_profile_merge', 'other', '' );
	}

	/**
	 * Captures a snapshot after business profile is merged.
	 *
	 * @param Profile_Store_Interface $store
	 * @return void
	 */
	public function on_business_profile_merged( Profile_Store_Interface $store ): void {
		$this->capture( $store, 'business_profile_merge', 'other', '' );
	}

	/**
	 * Captures a snapshot after a successful onboarding AI run.
	 *
	 * @param Profile_Store_Interface $store   Current profile store.
	 * @param string                  $run_ref Optional AI run reference for scope_id.
	 * @return void
	 */
	public function on_onboarding_run_completed( Profile_Store_Interface $store, string $run_ref = '' ): void {
		$this->capture( $store, 'onboarding_completion', 'onboarding_session', $run_ref );
	}

	/**
	 * Builds and persists a snapshot. Logs any error, never throws.
	 *
	 * @param Profile_Store_Interface $store
	 * @param string                  $source
	 * @param string                  $scope_type
	 * @param string                  $scope_id
	 * @return void
	 */
	private function capture( Profile_Store_Interface $store, string $source, string $scope_type, string $scope_id ): void {
		try {
			$snapshot = $this->factory->build( $store, $source, $scope_type, $scope_id );
			$saved    = $this->repository->save( $snapshot );
			$payload  = \wp_json_encode(
				array(
					'event'       => 'profile_snapshot_captured',
					'snapshot_id' => $snapshot->snapshot_id,
					'source'      => $source,
					'scope_type'  => $scope_type,
					'scope_id'    => $scope_id,
					'saved'       => $saved,
				)
			);
			Internal_Debug_Log::line( false !== $payload ? $payload : 'json_encode_failed' );
		} catch ( \Throwable $e ) {
			Internal_Debug_Log::line( 'profile_snapshot_capture_failed source=' . $source . ' error=' . $e->getMessage() );
		}
	}
}
