<?php
/**
 * Registers provider driver base services: request context builder, error normalizer, capability resolver (spec §25, §43.13).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers normalized request/response helpers and capability resolution. No concrete vendor drivers.
 */
final class AI_Provider_Base_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'provider_response_normalizer',
			function (): Provider_Response_Normalizer {
				return new Provider_Response_Normalizer();
			}
		);
		$container->register(
			'provider_error_normalizer',
			function () use ( $container ): Provider_Error_Normalizer {
				return new Provider_Error_Normalizer( $container->get( 'provider_response_normalizer' ) );
			}
		);
		$container->register(
			'provider_request_context_builder',
			function (): Provider_Request_Context_Builder {
				return new Provider_Request_Context_Builder();
			}
		);
		$container->register(
			'provider_capability_resolver',
			function (): Provider_Capability_Resolver {
				return new Provider_Capability_Resolver();
			}
		);
	}
}
