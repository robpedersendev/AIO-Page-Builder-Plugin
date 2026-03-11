<?php
/**
 * Registers onboarding domain services: draft, prefill, UI state builder (onboarding-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Draft_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_Prefill_Service;
use AIOPageBuilder\Domain\AI\Onboarding\Onboarding_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

final class Onboarding_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'onboarding_draft_service', function () use ( $container ): Onboarding_Draft_Service {
			return new Onboarding_Draft_Service( $container->get( 'settings' ) );
		} );
		$container->register( 'onboarding_prefill_service', function () use ( $container ): Onboarding_Prefill_Service {
			$crawl = $container->has( 'crawl_snapshot_service' ) ? $container->get( 'crawl_snapshot_service' ) : null;
			return new Onboarding_Prefill_Service(
				$container->get( 'profile_store' ),
				$container->get( 'settings' ),
				$crawl
			);
		} );
		$container->register( 'onboarding_ui_state_builder', function () use ( $container ): Onboarding_UI_State_Builder {
			return new Onboarding_UI_State_Builder(
				$container->get( 'onboarding_draft_service' ),
				$container->get( 'onboarding_prefill_service' )
			);
		} );
	}
}
