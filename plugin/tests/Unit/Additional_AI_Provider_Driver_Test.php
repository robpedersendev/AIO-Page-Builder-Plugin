<?php
/**
 * Unit tests for Additional (Anthropic) AI provider driver: capability exposure, normalized error mapping,
 * request without credential, validator-compatible response envelope (spec §25, §49.9, Prompt 118).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Drivers\Additional_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Additional_Provider_Capability_Profile;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Result;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Error_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Secrets/Provider_Secret_Store_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/AI_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Abstract_AI_Provider_Driver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Additional_Provider_Capability_Profile.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Additional_AI_Provider_Driver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Result.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Service.php';

/**
 * In-memory secret store for additional-driver tests.
 */
final class Additional_Driver_Test_Secret_Store implements Provider_Secret_Store_Interface {
	private array $creds = array();
	private array $states = array();

	public function get_credential_for_provider( string $provider_id ): ?string {
		return $this->creds[ $provider_id ] ?? null;
	}

	public function get_credential_state( string $provider_id ): string {
		return $this->states[ $provider_id ] ?? self::STATE_ABSENT;
	}

	public function has_credential( string $provider_id ): bool {
		return isset( $this->creds[ $provider_id ] );
	}

	public function set_credential( string $provider_id, string $value ): bool {
		$this->creds[ $provider_id ]   = $value;
		$this->states[ $provider_id ] = self::STATE_CONFIGURED;
		return true;
	}

	public function delete_credential( string $provider_id ): bool {
		$had = isset( $this->creds[ $provider_id ] );
		unset( $this->creds[ $provider_id ], $this->states[ $provider_id ] );
		return $had;
	}
}

final class Additional_AI_Provider_Driver_Test extends TestCase {

	public function test_capability_profile_returns_anthropic_provider_id(): void {
		$cap = Additional_Provider_Capability_Profile::get_capabilities();
		$this->assertSame( 'anthropic', $cap['provider_id'] );
		$this->assertTrue( $cap['structured_output_supported'] );
		$this->assertIsArray( $cap['models'] );
		$this->assertNotEmpty( $cap['models'] );
	}

	public function test_capability_profile_models_have_required_keys(): void {
		$cap   = Additional_Provider_Capability_Profile::get_capabilities();
		$models = $cap['models'];
		foreach ( $models as $m ) {
			$this->assertArrayHasKey( 'id', $m );
			$this->assertArrayHasKey( 'supports_structured_output', $m );
			$this->assertArrayHasKey( 'default_for_planning', $m );
		}
	}

	public function test_driver_get_provider_id_returns_anthropic(): void {
		$store  = new Additional_Driver_Test_Secret_Store();
		$driver = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$this->assertSame( 'anthropic', $driver->get_provider_id() );
	}

	public function test_driver_get_capabilities_exposes_metadata(): void {
		$store  = new Additional_Driver_Test_Secret_Store();
		$driver = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$cap = $driver->get_capabilities();
		$this->assertSame( 'anthropic', $cap['provider_id'] );
		$this->assertTrue( $cap['structured_output_supported'] );
		$this->assertGreaterThan( 0, $cap['max_context_tokens'] );
	}

	public function test_validator_compatible_success_response_envelope_has_required_keys(): void {
		$normalizer = new Provider_Response_Normalizer();
		$payload   = array( 'steps' => array(), 'schema_ref' => 'aio/build-plan-draft-v1' );
		$usage     = array( 'prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30 );
		$response  = $normalizer->build_success_response(
			'req-1',
			'anthropic',
			'claude-sonnet-4-20250514',
			$payload,
			$usage,
			null
		);
		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'request_id', $response );
		$this->assertArrayHasKey( 'provider_id', $response );
		$this->assertArrayHasKey( 'model_used', $response );
		$this->assertArrayHasKey( 'structured_payload', $response );
		$this->assertArrayHasKey( 'usage', $response );
		$this->assertSame( 'anthropic', $response['provider_id'] );
		$this->assertSame( $payload, $response['structured_payload'] );
		$this->assertNull( $response['normalized_error'] );
	}

	public function test_driver_request_without_credential_returns_normalized_auth_error(): void {
		$store  = new Additional_Driver_Test_Secret_Store();
		$driver = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$request = array(
			'request_id'     => 'test-req-1',
			'model'           => 'claude-sonnet-4-20250514',
			'system_prompt'   => '',
			'user_message'   => 'Hi',
		);
		$response = $driver->request( $request );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'test-req-1', $response['request_id'] );
		$this->assertSame( 'anthropic', $response['provider_id'] );
		$this->assertArrayHasKey( 'normalized_error', $response );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $response['normalized_error']['category'] );
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $response['normalized_error']['retry_posture'] );
	}

	public function test_driver_supports_structured_output(): void {
		$store  = new Additional_Driver_Test_Secret_Store();
		$driver = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$this->assertTrue( $driver->supports_structured_output( 'aio/build-plan-draft-v1' ) );
	}

	public function test_capability_resolver_resolve_default_model_for_anthropic(): void {
		$store    = new Additional_Driver_Test_Secret_Store();
		$driver   = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$resolver = new Provider_Capability_Resolver();
		$model    = $resolver->resolve_default_model_for_planning( $driver, 'aio/build-plan-draft-v1' );
		$this->assertNotNull( $model );
		$this->assertSame( 'claude-sonnet-4-20250514', $model );
	}

	public function test_capability_resolver_known_provider_ids_includes_anthropic(): void {
		$ids = Provider_Capability_Resolver::get_known_provider_ids();
		$this->assertContains( 'openai', $ids );
		$this->assertContains( 'anthropic', $ids );
	}

	public function test_anthropic_connection_test_without_credential_returns_normalized_auth_error(): void {
		$store   = new Additional_Driver_Test_Secret_Store();
		$driver  = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			$store
		);
		$settings = new Settings_Service();
		$service  = new Provider_Connection_Test_Service(
			new Provider_Request_Context_Builder(),
			new Provider_Capability_Resolver(),
			$settings
		);

		$result = $service->run_test( $driver );

		$this->assertInstanceOf( Provider_Connection_Test_Result::class, $result );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'anthropic', $result->get_provider_id() );
		$this->assertNotNull( $result->get_normalized_error() );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $result->get_normalized_error()['category'] );
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $result->get_normalized_error()['retry_posture'] );
		$this->assertNull( $service->get_last_successful_use( 'anthropic' ) );
	}

	/**
	 * Example normalized connection-test result for Anthropic (success case). Contract shape for UI/persistence.
	 */
	public function test_example_normalized_connection_test_result_anthropic_success(): void {
		$result = new Provider_Connection_Test_Result(
			true,
			'anthropic',
			'claude-sonnet-4-20250514',
			null,
			'2025-07-15T14:30:00+00:00',
			'Connection successful.'
		);
		$arr = $result->to_array();

		$this->assertTrue( $arr['success'] );
		$this->assertSame( 'anthropic', $arr['provider_id'] );
		$this->assertSame( 'claude-sonnet-4-20250514', $arr['model_used'] );
		$this->assertNull( $arr['normalized_error'] );
		$this->assertSame( '2025-07-15T14:30:00+00:00', $arr['tested_at'] );
		$this->assertSame( 'Connection successful.', $arr['user_message'] );

		$restored = Provider_Connection_Test_Result::from_array( $arr );
		$this->assertTrue( $restored->is_success() );
		$this->assertSame( 'anthropic', $restored->get_provider_id() );
	}

	/**
	 * Example normalized connection-test result for Anthropic (failure case). Contract shape for UI/persistence.
	 */
	public function test_example_normalized_connection_test_result_anthropic_failure(): void {
		$normalized_error = array(
			'category'      => Provider_Response_Normalizer::ERROR_AUTH_FAILURE,
			'user_message'  => 'AI service authentication failed. Check your settings.',
			'internal_code' => Provider_Response_Normalizer::ERROR_AUTH_FAILURE,
			'provider_raw'   => null,
			'retry_posture'  => Provider_Response_Normalizer::RETRY_NO_RETRY,
		);
		$result = new Provider_Connection_Test_Result(
			false,
			'anthropic',
			'claude-sonnet-4-20250514',
			$normalized_error,
			'2025-07-15T14:35:00+00:00',
			'AI service authentication failed. Check your settings.'
		);
		$arr = $result->to_array();

		$this->assertFalse( $arr['success'] );
		$this->assertSame( 'anthropic', $arr['provider_id'] );
		$this->assertSame( 'claude-sonnet-4-20250514', $arr['model_used'] );
		$this->assertSame( $normalized_error, $arr['normalized_error'] );
		$this->assertSame( '2025-07-15T14:35:00+00:00', $arr['tested_at'] );

		$restored = Provider_Connection_Test_Result::from_array( $arr );
		$this->assertFalse( $restored->is_success() );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $restored->get_normalized_error()['category'] );
	}
}
