<?php
/**
 * Unit tests for provider driver base: error normalizer, capability resolver, request context builder, stub driver (spec §25, §43.13).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Capability_Resolver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Request_Context_Builder;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Stub_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Error_Normalizer.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Request_Context_Builder.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Capability_Resolver.php';
require_once $plugin_root . '/src/Domain/AI/Secrets/Provider_Secret_Store_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/AI_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Abstract_AI_Provider_Driver.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Stub_AI_Provider_Driver.php';

/**
 * In-memory secret store for tests. No secrets in logs.
 */
final class Driver_Test_Secret_Store implements Provider_Secret_Store_Interface {
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

final class Provider_Driver_Base_Test extends TestCase {

	public function test_error_normalizer_maps_401_to_auth_failure(): void {
		$n = new Provider_Error_Normalizer();
		$cat = $n->map_to_category( 401, null, null );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $cat );
	}

	public function test_error_normalizer_maps_429_to_rate_limit(): void {
		$n = new Provider_Error_Normalizer();
		$this->assertSame( Provider_Response_Normalizer::ERROR_RATE_LIMIT, $n->map_to_category( 429 ) );
	}

	public function test_error_normalizer_maps_504_to_timeout(): void {
		$n = new Provider_Error_Normalizer();
		$this->assertSame( Provider_Response_Normalizer::ERROR_TIMEOUT, $n->map_to_category( 504 ) );
	}

	public function test_error_normalizer_maps_5xx_to_provider_error(): void {
		$n = new Provider_Error_Normalizer();
		$this->assertSame( Provider_Response_Normalizer::ERROR_PROVIDER_ERROR, $n->map_to_category( 502 ) );
	}

	public function test_error_normalizer_map_exception_connection_to_network_error(): void {
		$n = new Provider_Error_Normalizer();
		$e = new \RuntimeException( 'Connection refused' );
		$this->assertSame( Provider_Response_Normalizer::ERROR_NETWORK_ERROR, $n->map_exception_to_category( $e ) );
	}

	public function test_error_normalizer_build_error_response_has_normalized_envelope(): void {
		$n = new Provider_Error_Normalizer();
		$res = $n->build_error_response( 'req-1', 'openai', 'gpt-4o', Provider_Response_Normalizer::ERROR_RATE_LIMIT, 'Rate limit exceeded' );
		$this->assertFalse( $res['success'] );
		$this->assertSame( 'req-1', $res['request_id'] );
		$this->assertSame( 'openai', $res['provider_id'] );
		$this->assertSame( 'gpt-4o', $res['model_used'] );
		$this->assertNotNull( $res['normalized_error'] );
		$this->assertSame( Provider_Response_Normalizer::ERROR_RATE_LIMIT, $res['normalized_error']['category'] );
		$this->assertSame( Provider_Response_Normalizer::RETRY_WITH_BACKOFF, $res['normalized_error']['retry_posture'] );
		$this->assertNull( $res['structured_payload'] );
	}

	public function test_redact_provider_message_strips_sk_prefix(): void {
		$n = new Provider_Error_Normalizer();
		$redacted = $n->redact_provider_message( 'Invalid key: sk-abc123def456ghi789jqxyz012345' );
		$this->assertStringNotContainsString( 'sk-abc', $redacted ?? '' );
		$this->assertStringContainsString( '[REDACTED]', $redacted ?? '' );
	}

	public function test_safe_failure_when_credentials_absent(): void {
		$store = new Driver_Test_Secret_Store();
		$driver = new Stub_AI_Provider_Driver( $store, array(), array( 'structured_payload' => array() ) );
		$request = array( 'request_id' => 'r1', 'model' => 'stub-model', 'system_prompt' => 'Hi', 'user_message' => 'Hi' );
		$response = $driver->request( $request );
		$this->assertFalse( $response['success'] );
		$this->assertSame( Provider_Response_Normalizer::ERROR_AUTH_FAILURE, $response['normalized_error']['category'] );
		$this->assertNull( $response['structured_payload'] );
	}

	public function test_capability_resolver_returns_driver_capabilities(): void {
		$store = new Driver_Test_Secret_Store();
		$store->set_credential( 'stub', 'test-key' );
		$driver = new Stub_AI_Provider_Driver( $store );
		$resolver = new Provider_Capability_Resolver();
		$cap = $resolver->get_capabilities( $driver );
		$this->assertSame( 'stub', $cap['provider_id'] );
		$this->assertTrue( $cap['structured_output_supported'] );
		$this->assertArrayHasKey( 'models', $cap );
	}

	public function test_capability_resolver_supports_schema(): void {
		$store = new Driver_Test_Secret_Store();
		$driver = new Stub_AI_Provider_Driver( $store );
		$resolver = new Provider_Capability_Resolver();
		$this->assertTrue( $resolver->supports_schema( $driver, 'aio/build-plan-draft-v1' ) );
	}

	public function test_capability_resolver_resolve_default_model_for_planning(): void {
		$store = new Driver_Test_Secret_Store();
		$driver = new Stub_AI_Provider_Driver( $store );
		$resolver = new Provider_Capability_Resolver();
		$model = $resolver->resolve_default_model_for_planning( $driver, 'aio/build-plan-draft-v1' );
		$this->assertSame( 'stub-model', $model );
	}

	public function test_request_context_builder_produces_normalized_request(): void {
		$builder = new Provider_Request_Context_Builder();
		$ctx = $builder->build( 'req-1', 'gpt-4o', 'You are a planner.', 'Analyze this.', array(
			'structured_output_schema_ref' => 'aio/build-plan-draft-v1',
			'max_tokens' => 4096,
			'timeout_seconds' => 60,
		) );
		$this->assertSame( 'req-1', $ctx['request_id'] );
		$this->assertSame( 'gpt-4o', $ctx['model'] );
		$this->assertSame( 'You are a planner.', $ctx['system_prompt'] );
		$this->assertSame( 'Analyze this.', $ctx['user_message'] );
		$this->assertSame( 'aio/build-plan-draft-v1', $ctx['structured_output_schema_ref'] );
		$this->assertSame( 4096, $ctx['max_tokens'] );
		$this->assertSame( 60, $ctx['timeout_seconds'] );
	}

	/** Example: normalized driver success envelope (contract §4). */
	public function test_normalized_driver_success_envelope_shape(): void {
		$store = new Driver_Test_Secret_Store();
		$store->set_credential( 'stub', 'key' );
		$driver = new Stub_AI_Provider_Driver( $store );
		$driver->set_success_result( array(
			'structured_payload' => array( 'schema_version' => '1', 'run_summary' => array() ),
			'usage' => array( 'prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150, 'cost_placeholder' => null ),
		) );
		$request = array( 'request_id' => 'req-ok', 'model' => 'stub-model', 'system_prompt' => '', 'user_message' => '' );
		$response = $driver->request( $request );
		$this->assertTrue( $response['success'] );
		$this->assertSame( 'req-ok', $response['request_id'] );
		$this->assertSame( 'stub', $response['provider_id'] );
		$this->assertSame( 'stub-model', $response['model_used'] );
		$this->assertIsArray( $response['structured_payload'] );
		$this->assertSame( '1', $response['structured_payload']['schema_version'] ?? '' );
		$this->assertSame( 100, $response['usage']['prompt_tokens'] ?? 0 );
		$this->assertNull( $response['normalized_error'] );
	}

	/** Example: normalized provider error envelope (contract §5). */
	public function test_normalized_provider_error_envelope_shape(): void {
		$store = new Driver_Test_Secret_Store();
		$store->set_credential( 'stub', 'key' );
		$driver = new Stub_AI_Provider_Driver( $store );
		$driver->set_error_result( 429, 'rate_limit_exceeded', 'Rate limit exceeded' );
		$request = array( 'request_id' => 'req-err', 'model' => 'stub-model', 'system_prompt' => '', 'user_message' => '' );
		$response = $driver->request( $request );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'req-err', $response['request_id'] );
		$this->assertSame( 'stub', $response['provider_id'] );
		$this->assertNull( $response['structured_payload'] );
		$this->assertIsArray( $response['normalized_error'] );
		$this->assertSame( Provider_Response_Normalizer::ERROR_RATE_LIMIT, $response['normalized_error']['category'] );
		$this->assertSame( Provider_Response_Normalizer::RETRY_WITH_BACKOFF, $response['normalized_error']['retry_posture'] );
		$this->assertArrayHasKey( 'user_message', $response['normalized_error'] );
	}

	public function test_validator_handoff_shape_response_has_required_keys(): void {
		$store = new Driver_Test_Secret_Store();
		$store->set_credential( 'stub', 'key' );
		$driver = new Stub_AI_Provider_Driver( $store );
		$driver->set_success_result( array( 'structured_payload' => array( 'schema_version' => '1' ) ) );
		$request = array( 'request_id' => 'r', 'model' => 'm', 'system_prompt' => '', 'user_message' => '' );
		$response = $driver->request( $request );
		$this->assertArrayHasKey( 'request_id', $response );
		$this->assertArrayHasKey( 'success', $response );
		$this->assertArrayHasKey( 'structured_payload', $response );
		$this->assertArrayHasKey( 'provider_id', $response );
		$this->assertArrayHasKey( 'model_used', $response );
		$this->assertArrayHasKey( 'usage', $response );
		$this->assertArrayHasKey( 'normalized_error', $response );
		if ( $response['success'] ) {
			$this->assertNotNull( $response['structured_payload'] );
			$this->assertNull( $response['normalized_error'] );
		} else {
			$this->assertNotNull( $response['normalized_error'] );
			$this->assertArrayHasKey( 'category', $response['normalized_error'] );
			$this->assertArrayHasKey( 'retry_posture', $response['normalized_error'] );
		}
	}

	public function test_build_from_segments_concatenates_system_and_user(): void {
		$builder = new Provider_Request_Context_Builder();
		$ctx = $builder->build_from_segments( 'r2', 'gpt-4o', array( 'system_base' => 'You are helpful.' ), array( 'planning' => 'Plan this.' ), array() );
		$this->assertSame( 'You are helpful.', $ctx['system_prompt'] );
		$this->assertSame( 'Plan this.', $ctx['user_message'] );
		$this->assertSame( 'r2', $ctx['request_id'] );
	}
}
