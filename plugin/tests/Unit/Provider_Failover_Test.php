<?php
/**
 * Unit tests for provider failover policy, result, and service (spec §25.1, §29.6, Prompt 119).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Failover\Failover_Result;
use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Policy;
use AIOPageBuilder\Domain\AI\Providers\Failover\Provider_Failover_Service;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Provider_Failover_Policy.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Failover_Result.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Failover/Provider_Failover_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';

final class Provider_Failover_Test extends TestCase {

	public function test_policy_disabled_returns_can_attempt_fallback_false(): void {
		$policy = Provider_Failover_Policy::disabled( 'openai' );
		$this->assertFalse( $policy->is_enabled() );
		$this->assertFalse( $policy->can_attempt_fallback( 'openai', 0 ) );
	}

	public function test_policy_from_config_enabled_with_fallback(): void {
		$config = array(
			'enabled'              => true,
			'fallback_provider_id' => 'anthropic',
			'eligible_categories'  => array( Provider_Response_Normalizer::ERROR_RATE_LIMIT, Provider_Response_Normalizer::ERROR_TIMEOUT ),
		);
		$policy = Provider_Failover_Policy::from_config( $config, 'openai' );
		$this->assertTrue( $policy->is_enabled() );
		$this->assertSame( 'openai', $policy->get_primary_provider_id() );
		$this->assertSame( 'anthropic', $policy->get_fallback_provider_id() );
		$this->assertTrue( $policy->can_attempt_fallback( 'openai', 0 ) );
		$this->assertFalse( $policy->can_attempt_fallback( 'anthropic', 0 ) );
		$this->assertTrue( $policy->is_eligible_category( Provider_Response_Normalizer::ERROR_RATE_LIMIT ) );
		$this->assertFalse( $policy->is_eligible_category( Provider_Response_Normalizer::ERROR_AUTH_FAILURE ) );
	}

	public function test_policy_from_config_same_primary_and_fallback_disables(): void {
		$config = array(
			'enabled'              => true,
			'fallback_provider_id' => 'openai',
		);
		$policy = Provider_Failover_Policy::from_config( $config, 'openai' );
		$this->assertFalse( $policy->is_enabled() );
	}

	public function test_failover_result_primary_success_metadata_has_effective_provider(): void {
		$snapshot = array(
			'enabled'               => false,
			'primary_provider_id'   => 'openai',
			'fallback_provider_id'  => '',
			'eligible_categories'   => array(),
			'max_fallback_attempts' => 0,
		);
		$result   = Failover_Result::primary_success( 'openai', 'gpt-4o', $snapshot );
		$this->assertTrue( $result->used_primary() );
		$this->assertSame( 'openai', $result->get_effective_provider_id() );
		$meta = $result->to_run_metadata();
		$this->assertArrayHasKey( 'effective_provider_used', $meta );
		$this->assertSame( 'openai', $meta['effective_provider_used']['provider_id'] ?? '' );
		$this->assertSame( 'gpt-4o', $meta['effective_provider_used']['model_used'] ?? '' );
		$this->assertArrayHasKey( 'failover_attempt', $meta );
		$this->assertCount( 1, $meta['failover_attempt'] );
	}

	public function test_failover_result_primary_failure_no_fallback_metadata(): void {
		$snapshot = array(
			'enabled'               => false,
			'primary_provider_id'   => 'openai',
			'fallback_provider_id'  => '',
			'eligible_categories'   => array(),
			'max_fallback_attempts' => 0,
		);
		$result   = Failover_Result::primary_failure_no_fallback( 'openai', 'gpt-4o', 'rate_limit', $snapshot );
		$this->assertFalse( $result->used_primary() );
		$meta = $result->to_run_metadata();
		$this->assertArrayHasKey( 'effective_provider_used', $meta );
		$this->assertArrayHasKey( 'failover_attempt', $meta );
		$this->assertSame( 'rate_limit', ( $meta['failover_attempt'][0]['category'] ?? '' ) );
	}

	public function test_failover_result_fallback_success_has_fallback_reference_and_effective_provider(): void {
		$snapshot = array(
			'enabled'               => true,
			'primary_provider_id'   => 'openai',
			'fallback_provider_id'  => 'anthropic',
			'eligible_categories'   => array(),
			'max_fallback_attempts' => 1,
		);
		$attempts = array(
			array(
				'provider_id'  => 'openai',
				'model_used'   => 'gpt-4o',
				'category'     => 'rate_limit',
				'attempted_at' => '2025-01-01T00:00:00Z',
			),
			array(
				'provider_id'  => 'anthropic',
				'model_used'   => 'claude-3-5-sonnet',
				'category'     => 'success',
				'attempted_at' => '2025-01-01T00:00:01Z',
			),
		);
		$result   = Failover_Result::fallback_success( 'anthropic', 'claude-3-5-sonnet', $attempts, $snapshot );
		$this->assertFalse( $result->used_primary() );
		$this->assertSame( 'anthropic', $result->get_effective_provider_id() );
		$meta = $result->to_run_metadata();
		$this->assertArrayHasKey( 'fallback_provider_reference', $meta );
		$this->assertSame( 'anthropic', $meta['fallback_provider_reference']['provider_id'] ?? '' );
		$this->assertSame( 'anthropic', $meta['effective_provider_used']['provider_id'] ?? '' );
		$this->assertCount( 2, $meta['failover_attempt'] );
	}

	public function test_service_get_policy_returns_disabled_when_no_failover_config(): void {
		$settings = new Settings_Service();
		$settings->set( Option_Names::PROVIDER_CONFIG_REF, array() );
		$service = new Provider_Failover_Service( $settings, new Provider_Capability_Resolver() );
		$policy  = $service->get_policy_for_primary( 'openai' );
		$this->assertFalse( $policy->is_enabled() );
	}

	public function test_service_try_fallback_ineligible_category_returns_primary_response_and_no_fallback_result(): void {
		$settings = new Settings_Service();
		$settings->set( Option_Names::PROVIDER_CONFIG_REF, array() );
		$service          = new Provider_Failover_Service( $settings, new Provider_Capability_Resolver() );
		$policy           = Provider_Failover_Policy::disabled( 'openai' );
		$primary_response = array(
			'success'          => false,
			'normalized_error' => array(
				'category'      => Provider_Response_Normalizer::ERROR_AUTH_FAILURE,
				'user_message'  => 'Auth failed',
				'internal_code' => 'auth_failure',
				'provider_raw'  => null,
				'retry_posture' => 'no_retry',
			),
		);
		$request          = array(
			'request_id'    => 'req-1',
			'model'         => 'gpt-4o',
			'system_prompt' => '',
			'user_message'  => '',
		);
		$container        = new Service_Container();
		$bag              = $service->try_fallback( $policy, 'openai', 'gpt-4o', $primary_response, $request, 'aio/build-plan-draft-v1', $container );
		$this->assertSame( $primary_response, $bag['response'] );
		$this->assertInstanceOf( Failover_Result::class, $bag['result'] );
		$this->assertFalse( $bag['result']->used_primary() );
		$this->assertSame( 'openai', $bag['result']->get_effective_provider_id() );
	}

	public function test_service_try_fallback_eligible_but_no_fallback_driver_returns_primary_response(): void {
		$policy           = new Provider_Failover_Policy( true, 'openai', 'nonexistent', array( Provider_Response_Normalizer::ERROR_RATE_LIMIT ), 1 );
		$settings         = new Settings_Service();
		$service          = new Provider_Failover_Service( $settings, new Provider_Capability_Resolver() );
		$primary_response = array(
			'success'          => false,
			'normalized_error' => array(
				'category'      => Provider_Response_Normalizer::ERROR_RATE_LIMIT,
				'user_message'  => 'Rate limited',
				'internal_code' => 'rate_limit',
				'provider_raw'  => null,
				'retry_posture' => 'retry_with_backoff',
			),
		);
		$request          = array(
			'request_id'    => 'req-1',
			'model'         => 'gpt-4o',
			'system_prompt' => '',
			'user_message'  => '',
		);
		$container        = new Service_Container();
		$bag              = $service->try_fallback( $policy, 'openai', 'gpt-4o', $primary_response, $request, 'aio/build-plan-draft-v1', $container );
		$this->assertSame( $primary_response, $bag['response'] );
		$this->assertSame( 'openai', $bag['result']->get_effective_provider_id() );
		$this->assertCount( 1, $bag['result']->get_attempts() );
	}

	public function test_persisted_metadata_example_failover_result_payload(): void {
		$snapshot = array(
			'enabled'               => true,
			'primary_provider_id'   => 'openai',
			'fallback_provider_id'  => 'anthropic',
			'eligible_categories'   => array( 'rate_limit', 'timeout' ),
			'max_fallback_attempts' => 1,
		);
		$attempts = array(
			array(
				'provider_id'  => 'openai',
				'model_used'   => 'gpt-4o',
				'category'     => 'rate_limit',
				'attempted_at' => '2025-03-12T10:00:00Z',
			),
			array(
				'provider_id'  => 'anthropic',
				'model_used'   => 'claude-3-5-sonnet-20241022',
				'category'     => 'success',
				'attempted_at' => '2025-03-12T10:00:05Z',
			),
		);
		$result   = Failover_Result::fallback_success( 'anthropic', 'claude-3-5-sonnet-20241022', $attempts, $snapshot );
		$meta     = $result->to_run_metadata();
		$this->assertArrayHasKey( 'failover_policy', $meta );
		$this->assertArrayHasKey( 'failover_attempt', $meta );
		$this->assertArrayHasKey( 'fallback_provider_reference', $meta );
		$this->assertArrayHasKey( 'effective_provider_used', $meta );
		$this->assertSame( 'anthropic', $meta['effective_provider_used']['provider_id'] );
		$this->assertSame( 'claude-3-5-sonnet-20241022', $meta['effective_provider_used']['model_used'] );
	}
}
