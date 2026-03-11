<?php
/**
 * Unit tests for AI_Provider_Interface: stub implementation and contract conformance (spec §25.1, ai-provider-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\AI_Provider_Interface;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Providers/AI_Provider_Interface.php';
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';

/**
 * Stub provider that returns normalized error for every request. Used to verify interface contract.
 */
final class Stub_Provider_For_Contract implements AI_Provider_Interface {

	public function get_provider_id(): string {
		return 'stub';
	}

	public function get_capabilities(): array {
		return array(
			'provider_id'                  => 'stub',
			'structured_output_supported'  => true,
			'file_attachment_supported'    => false,
			'max_context_tokens'           => 4096,
			'models'                       => array( array( 'id' => 'stub-model', 'supports_structured_output' => true, 'default_for_planning' => true ) ),
		);
	}

	public function request( array $request ): array {
		$normalizer = new Provider_Response_Normalizer();
		return $normalizer->build_error_response(
			(string) ( $request['request_id'] ?? 'unknown' ),
			$this->get_provider_id(),
			(string) ( $request['model'] ?? 'stub-model' ),
			Provider_Response_Normalizer::ERROR_UNSUPPORTED_FEATURE,
			'Stub does not perform real requests'
		);
	}

	public function supports_structured_output( string $schema_ref ): bool {
		return $schema_ref !== '';
	}
}

final class AI_Provider_Interface_Test extends TestCase {

	public function test_stub_provider_implements_interface(): void {
		$provider = new Stub_Provider_For_Contract();
		$this->assertInstanceOf( AI_Provider_Interface::class, $provider );
	}

	public function test_stub_get_provider_id_returns_stable_id(): void {
		$provider = new Stub_Provider_For_Contract();
		$this->assertSame( 'stub', $provider->get_provider_id() );
	}

	public function test_stub_get_capabilities_returns_contract_shape(): void {
		$provider = new Stub_Provider_For_Contract();
		$cap = $provider->get_capabilities();
		$this->assertSame( 'stub', $cap['provider_id'] );
		$this->assertArrayHasKey( 'structured_output_supported', $cap );
		$this->assertArrayHasKey( 'models', $cap );
	}

	public function test_stub_request_returns_normalized_response_shape(): void {
		$provider = new Stub_Provider_For_Contract();
		$request  = array(
			'request_id'   => 'req_test',
			'model'        => 'stub-model',
			'system_prompt' => 'Test',
			'user_message' => 'Test',
		);
		$response = $provider->request( $request );
		$this->assertArrayHasKey( 'request_id', $response );
		$this->assertArrayHasKey( 'success', $response );
		$this->assertArrayHasKey( 'provider_id', $response );
		$this->assertArrayHasKey( 'model_used', $response );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'req_test', $response['request_id'] );
		$this->assertIsArray( $response['normalized_error'] );
	}

	public function test_stub_supports_structured_output(): void {
		$provider = new Stub_Provider_For_Contract();
		$this->assertTrue( $provider->supports_structured_output( 'aio/build-plan-draft-v1' ) );
	}
}
