<?php
/**
 * Registers task-scoped AI provider router (spec §25.1).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Routing\AI_Provider_Router_Interface;
use AIOPageBuilder\Domain\AI\Routing\Default_AI_Provider_Router;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers ai_provider_router after Config_Provider (settings).
 */
final class AI_Router_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'ai_provider_router',
			function () use ( $container ): AI_Provider_Router_Interface {
				return new Default_AI_Provider_Router( $container->get( 'settings' ) );
			}
		);
	}
}
