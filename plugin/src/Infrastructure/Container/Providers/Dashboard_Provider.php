<?php
/**
 * Registers Dashboard screen state builder (spec §49.5).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Admin\Dashboard\Dashboard_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers dashboard_state_builder for the Dashboard screen.
 */
final class Dashboard_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'dashboard_state_builder',
			function () use ( $container ): Dashboard_State_Builder {
				return new Dashboard_State_Builder(
					$container->get( 'settings' ),
					$container->has( 'crawl_snapshot_service' ) ? $container->get( 'crawl_snapshot_service' ) : null,
					$container->has( 'ai_run_repository' ) ? $container->get( 'ai_run_repository' ) : null,
					$container->has( 'build_plan_repository' ) ? $container->get( 'build_plan_repository' ) : null,
					$container->has( 'job_queue_repository' ) ? $container->get( 'job_queue_repository' ) : null,
					$container->has( 'assignment_map_service' ) ? $container->get( 'assignment_map_service' ) : null,
					$container->has( 'provider_monthly_spend_service' ) ? $container->get( 'provider_monthly_spend_service' ) : null,
					$container->has( 'provider_pricing_registry' ) ? $container->get( 'provider_pricing_registry' ) : null
				);
			}
		);
	}
}
