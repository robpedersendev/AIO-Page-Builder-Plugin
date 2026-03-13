<?php
/**
 * Registers provider failover policy service (spec §25.1, §29.6, Prompt 119).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers provider_failover_service. Depends on settings and provider_capability_resolver.
 */
final class AI_Failover_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register( 'provider_failover_service', function () use ( $container ): Provider_Failover_Service {
			return new Provider_Failover_Service(
				$container->get( 'settings' ),
				$container->get( 'provider_capability_resolver' )
			);
		} );
	}
}
