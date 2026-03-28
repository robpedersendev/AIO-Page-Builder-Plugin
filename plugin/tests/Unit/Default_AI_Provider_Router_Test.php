<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Routing\AI_Routing_Task;
use AIOPageBuilder\Domain\AI\Routing\Default_AI_Provider_Router;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Default_AI_Provider_Router_Test extends TestCase {

	public function test_resolves_preferred_provider_when_no_task_override(): void {
		$settings = new Settings_Service();
		$settings->set(
			Option_Names::PROVIDER_CONFIG_REF,
			array(
				'primary_provider_id' => 'anthropic',
			)
		);
		$router = new Default_AI_Provider_Router( $settings );
		$route  = $router->resolve_route(
			AI_Routing_Task::ONBOARDING_PLANNING,
			array( 'preferred_provider_id' => 'openai' )
		);
		$this->assertTrue( $route->is_valid() );
		$this->assertSame( 'openai', $route->get_primary_provider_id() );
	}

	public function test_task_override_wins_over_preferred(): void {
		$settings = new Settings_Service();
		$settings->set(
			Option_Names::PROVIDER_CONFIG_REF,
			array(
				'task_routing' => array(
					AI_Routing_Task::ONBOARDING_PLANNING => array(
						'provider_id' => 'anthropic',
						'model'       => 'claude-sonnet-4-20250514',
					),
				),
			)
		);
		$router = new Default_AI_Provider_Router( $settings );
		$route  = $router->resolve_route(
			AI_Routing_Task::ONBOARDING_PLANNING,
			array( 'preferred_provider_id' => 'openai' )
		);
		$this->assertTrue( $route->is_valid() );
		$this->assertSame( 'anthropic', $route->get_primary_provider_id() );
		$this->assertSame( 'claude-sonnet-4-20250514', $route->get_primary_model_override() );
	}

	public function test_invalid_provider_id_returns_invalid_route(): void {
		$settings = new Settings_Service();
		$settings->set(
			Option_Names::PROVIDER_CONFIG_REF,
			array(
				'task_routing' => array(
					AI_Routing_Task::TEMPLATE_LAB_REPAIR => array(
						'provider_id' => 'unknown_vendor',
					),
				),
			)
		);
		$router = new Default_AI_Provider_Router( $settings );
		$route  = $router->resolve_route( AI_Routing_Task::TEMPLATE_LAB_REPAIR, array() );
		$this->assertFalse( $route->is_valid() );
	}
}
