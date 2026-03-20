<?php
/**
 * Integration tests: both provider drivers compute cost_usd when pricing exists.
 *
 * Uses a mock HTTP server via provider base_url override and WordPress function stubs.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Integration\Domain\AI;

use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Pricing_Registry;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Additional_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Drivers\Concrete_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\wp_remote_post' ) ) {
	/** @return array */
	function wp_remote_post( string $url, array $args = array() ): array {
		return $GLOBALS['__driver_cost_test_mock_response'] ?? array();
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\wp_remote_retrieve_response_code' ) ) {
	/** @return int|string */
	function wp_remote_retrieve_response_code( $response ) {
		return $response['code'] ?? 0;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\wp_remote_retrieve_body' ) ) {
	/** @return string */
	function wp_remote_retrieve_body( $response ): string {
		return $response['body'] ?? '';
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\is_wp_error' ) ) {
	/** @return bool */
	function is_wp_error( $thing ): bool {
		return false;
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\wp_json_encode' ) ) {
	/** @return string */
	function wp_json_encode( $data ): string {
		return json_encode( $data );
	}
}
if ( ! function_exists( 'AIOPageBuilder\Tests\Integration\Domain\AI\__' ) ) {
	/** @return string */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

// Minimal secret store stub.
class Stub_Secret_Store_Cost implements \AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface {
	public function get_credential_for_provider( string $provider_id ): ?string {
		return 'test-key';
	}
	public function get_credential_state( string $provider_id ): string {
		return \AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface::STATE_CONFIGURED;
	}
	public function set_credential( string $provider_id, string $value ): bool {
		return true;
	}
	public function has_credential( string $provider_id ): bool {
		return true;
	}
	public function delete_credential( string $provider_id ): bool {
		return true;
	}
}

/**
 * @covers \AIOPageBuilder\Domain\AI\Providers\Drivers\Concrete_AI_Provider_Driver
 * @covers \AIOPageBuilder\Domain\AI\Providers\Drivers\Additional_AI_Provider_Driver
 */
final class Driver_Cost_Computation_Test extends TestCase {

	private Provider_Cost_Calculator $calculator;

	protected function setUp(): void {
		$this->calculator = new Provider_Cost_Calculator( new Provider_Pricing_Registry() );
	}

	private function build_openai_driver(): Concrete_AI_Provider_Driver {
		return new Concrete_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			new Stub_Secret_Store_Cost(),
			'http://mock.local',
			$this->calculator
		);
	}

	private function build_anthropic_driver(): Additional_AI_Provider_Driver {
		return new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			new Stub_Secret_Store_Cost(),
			'http://mock.local',
			$this->calculator
		);
	}

	public function test_openai_driver_computes_cost_usd_for_known_model(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'choices' => array( array( 'message' => array( 'content' => 'hello' ) ) ),
				'usage'   => array( 'prompt_tokens' => 1000, 'completion_tokens' => 500, 'total_tokens' => 1500 ),
				'model'   => 'gpt-4o',
				'id'      => 'chatcmpl-test',
			) ),
		);

		$driver   = $this->build_openai_driver();
		$result   = $driver->request( array(
			'request_id'    => 'req-1',
			'model'         => 'gpt-4o',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertTrue( $result['success'] );
		$this->assertNotNull( $result['usage']['cost_usd'] );
		$this->assertIsFloat( $result['usage']['cost_usd'] );
		$this->assertGreaterThan( 0.0, $result['usage']['cost_usd'] );
		// Exact value: (1000 * 0.0000025) + (500 * 0.00001) = 0.0075.
		$this->assertEqualsWithDelta( 0.0075, $result['usage']['cost_usd'], 1.0e-8 );
	}

	public function test_openai_driver_leaves_cost_usd_null_when_no_calculator(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'choices' => array( array( 'message' => array( 'content' => 'hello' ) ) ),
				'usage'   => array( 'prompt_tokens' => 1000, 'completion_tokens' => 500, 'total_tokens' => 1500 ),
				'model'   => 'gpt-4o',
			) ),
		);

		$driver = new Concrete_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			new Stub_Secret_Store_Cost(),
			'http://mock.local'
			// No calculator — cost_usd should remain null.
		);
		$result = $driver->request( array(
			'request_id'    => 'req-2',
			'model'         => 'gpt-4o',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertTrue( $result['success'] );
		$this->assertNull( $result['usage']['cost_usd'] );
	}

	public function test_openai_driver_leaves_cost_usd_null_for_unknown_model(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'choices' => array( array( 'message' => array( 'content' => 'hello' ) ) ),
				'usage'   => array( 'prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150 ),
				'model'   => 'gpt-unknown-future',
			) ),
		);

		$driver = $this->build_openai_driver();
		$result = $driver->request( array(
			'request_id'    => 'req-3',
			'model'         => 'gpt-unknown-future',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertTrue( $result['success'] );
		$this->assertNull( $result['usage']['cost_usd'] );
	}

	public function test_anthropic_driver_computes_cost_usd_for_known_model(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'content'     => array( array( 'text' => 'hello', 'type' => 'text' ) ),
				'usage'       => array( 'input_tokens' => 2000, 'output_tokens' => 1000 ),
				'model'       => 'claude-sonnet-4-20250514',
				'id'          => 'msg-test',
				'stop_reason' => 'end_turn',
			) ),
		);

		$driver = $this->build_anthropic_driver();
		$result = $driver->request( array(
			'request_id'    => 'req-4',
			'model'         => 'claude-sonnet-4-20250514',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertTrue( $result['success'] );
		$this->assertNotNull( $result['usage']['cost_usd'] );
		// (2000 * 0.000003) + (1000 * 0.000015) = 0.006 + 0.015 = 0.021.
		$this->assertEqualsWithDelta( 0.021, $result['usage']['cost_usd'], 1.0e-8 );
	}

	public function test_anthropic_driver_leaves_cost_usd_null_when_no_calculator(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'content'     => array( array( 'text' => 'hello', 'type' => 'text' ) ),
				'usage'       => array( 'input_tokens' => 500, 'output_tokens' => 200 ),
				'model'       => 'claude-sonnet-4-20250514',
			) ),
		);

		$driver = new Additional_AI_Provider_Driver(
			new Provider_Error_Normalizer(),
			new Provider_Response_Normalizer(),
			new Stub_Secret_Store_Cost(),
			'http://mock.local'
		);
		$result = $driver->request( array(
			'request_id'    => 'req-5',
			'model'         => 'claude-sonnet-4-20250514',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertTrue( $result['success'] );
		$this->assertNull( $result['usage']['cost_usd'] );
	}

	public function test_token_counts_unaffected_by_calculator(): void {
		$GLOBALS['__driver_cost_test_mock_response'] = array(
			'code' => 200,
			'body' => json_encode( array(
				'choices' => array( array( 'message' => array( 'content' => 'hello' ) ) ),
				'usage'   => array( 'prompt_tokens' => 777, 'completion_tokens' => 333, 'total_tokens' => 1110 ),
				'model'   => 'gpt-4o',
			) ),
		);

		$driver = $this->build_openai_driver();
		$result = $driver->request( array(
			'request_id'    => 'req-6',
			'model'         => 'gpt-4o',
			'user_message'  => 'test',
			'system_prompt' => '',
		) );

		$this->assertSame( 777, $result['usage']['prompt_tokens'] );
		$this->assertSame( 333, $result['usage']['completion_tokens'] );
		$this->assertSame( 1110, $result['usage']['total_tokens'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['__driver_cost_test_mock_response'] );
	}
}
