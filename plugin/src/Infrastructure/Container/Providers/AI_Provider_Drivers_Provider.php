<?php
/**
 * Registers concrete AI provider driver(s), connection test service, AI Providers UI state builder,
 * pricing registry, cost calculator, and spend cap budget services (spec §25, §49.9, v2-scope-backlog.md §4).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Infrastructure\Container\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Budget\Provider_Monthly_Spend_Service;
use AIOPageBuilder\Domain\AI\Budget\Provider_Spend_Cap_Settings;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Additional_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Concrete_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Domain\AI\Secrets\Option_Based_Provider_Secret_Store;
use AIOPageBuilder\Domain\AI\UI\AI_Provider_Admin_Capability_Summary_Builder;
use AIOPageBuilder\Domain\AI\UI\AI_Providers_UI_State_Builder;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Container\Service_Provider_Interface;

/**
 * Registers OpenAI and Anthropic provider drivers, provider secret store, connection test service,
 * AI Providers screen state builder, pricing registry, cost calculator, and budget services.
 */
final class AI_Provider_Drivers_Provider implements Service_Provider_Interface {

	/** @inheritdoc */
	public function register( Service_Container $container ): void {
		$container->register(
			'provider_secret_store',
			function (): Option_Based_Provider_Secret_Store {
				return new Option_Based_Provider_Secret_Store();
			}
		);

		$container->register(
			'provider_pricing_registry',
			function (): Provider_Pricing_Registry {
				return new Provider_Pricing_Registry();
			}
		);

		$container->register(
			'provider_cost_calculator',
			function () use ( $container ): Provider_Cost_Calculator {
				return new Provider_Cost_Calculator(
					$container->get( 'provider_pricing_registry' )
				);
			}
		);

		$container->register(
			'provider_spend_cap_settings',
			function (): Provider_Spend_Cap_Settings {
				return new Provider_Spend_Cap_Settings();
			}
		);

		$container->register(
			'provider_monthly_spend_service',
			function () use ( $container ): Provider_Monthly_Spend_Service {
				return new Provider_Monthly_Spend_Service(
					$container->get( 'provider_spend_cap_settings' )
				);
			}
		);

		$container->register(
			'openai_provider_driver',
			function () use ( $container ): Concrete_AI_Provider_Driver {
				return new Concrete_AI_Provider_Driver(
					$container->get( 'provider_error_normalizer' ),
					$container->get( 'provider_response_normalizer' ),
					$container->get( 'provider_secret_store' ),
					Concrete_AI_Provider_Driver::API_BASE_DEFAULT,
					$container->get( 'provider_cost_calculator' )
				);
			}
		);

		$container->register(
			'anthropic_provider_driver',
			function () use ( $container ): Additional_AI_Provider_Driver {
				return new Additional_AI_Provider_Driver(
					$container->get( 'provider_error_normalizer' ),
					$container->get( 'provider_response_normalizer' ),
					$container->get( 'provider_secret_store' ),
					Additional_AI_Provider_Driver::API_BASE_DEFAULT,
					$container->get( 'provider_cost_calculator' )
				);
			}
		);

		$container->register(
			'provider_connection_test_service',
			function () use ( $container ): Provider_Connection_Test_Service {
				return new Provider_Connection_Test_Service(
					$container->get( 'provider_request_context_builder' ),
					$container->get( 'provider_capability_resolver' ),
					$container->get( 'settings' )
				);
			}
		);

		$container->register(
			'ai_providers_ui_state_builder',
			function () use ( $container ): AI_Providers_UI_State_Builder {
				return new AI_Providers_UI_State_Builder(
					$container->get( 'provider_connection_test_service' ),
					$container->get( 'provider_secret_store' ),
					$container->get( 'provider_capability_resolver' ),
					$container->get( 'settings' ),
					$container
				);
			}
		);

		$container->register(
			'ai_provider_capability_summary_builder',
			function () use ( $container ): AI_Provider_Admin_Capability_Summary_Builder {
				return new AI_Provider_Admin_Capability_Summary_Builder(
					$container,
					$container->get( 'provider_secret_store' )
				);
			}
		);
	}
}
