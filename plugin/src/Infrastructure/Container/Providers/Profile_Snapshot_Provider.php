<?php
/**
 * Registers profile snapshot domain services (v2-scope-backlog.md §3).
 *
 * Services registered:
 * - profile_snapshot_repository   → Profile_Snapshot_Repository (table aio_profile_snapshots)
 * - profile_snapshot_factory      → Profile_Snapshot_Factory
 * - profile_snapshot_diff_service → Profile_Snapshot_Diff_Service
 * - profile_snapshot_capture_service → Profile_Snapshot_Capture_Service (registers WP hooks)
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Capture_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Diff_Service;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Factory;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Helper;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Snapshot_Repository;
use AIOPageBuilder\Domain\Storage\Profile\Profile_Store;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers all profile snapshot infrastructure services and lifecycle hooks.
 */
final class Profile_Snapshot_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'profile_snapshot_repository',
			function (): Profile_Snapshot_Repository {
				global $wpdb;
				return new Profile_Snapshot_Repository( $wpdb );
			}
		);

		$container->register(
			'profile_snapshot_factory',
			function (): Profile_Snapshot_Factory {
				return new Profile_Snapshot_Factory( new Profile_Snapshot_Helper() );
			}
		);

		$container->register(
			'profile_snapshot_diff_service',
			function (): Profile_Snapshot_Diff_Service {
				return new Profile_Snapshot_Diff_Service();
			}
		);

		$container->register(
			'profile_snapshot_capture_service',
			function () use ( $container ): Profile_Snapshot_Capture_Service {
				/** @var Profile_Snapshot_Repository $repo */
				$repo = $container->get( 'profile_snapshot_repository' );
				/** @var Profile_Snapshot_Factory $factory */
				$factory = $container->get( 'profile_snapshot_factory' );
				$service = new Profile_Snapshot_Capture_Service( $factory, $repo );
				$service->register_hooks();

				// * Wire container-aware onboarding completion hook here so the capture service
				// * does not need a Service_Container dependency.
				\add_action(
					'aio_pb_onboarding_run_completed',
					static function ( string $run_id ) use ( $container, $service ): void {
						if ( ! $container->has( 'profile_store' ) ) {
							return;
						}
						$store = $container->get( 'profile_store' );
						if ( $store instanceof Profile_Store ) {
							$service->on_onboarding_run_completed( $store, $run_id );
						}
					},
					10,
					1
				);

				return $service;
			}
		);
	}
}
