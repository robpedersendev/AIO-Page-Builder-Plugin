<?php
/**
 * Unit tests for Provider_Response_Normalizer: error category mapping, response shapes (spec §25.11, ai-provider-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Providers/Provider_Response_Normalizer.php';

final class Provider_Response_Normalizer_Test extends TestCase {

	private function normalizer(): Provider_Response_Normalizer {
		return new Provider_Response_Normalizer();
	}

	public function test_normalize_error_returns_stable_shape(): void {
		$n = $this->normalizer();
		$err = $n->normalize_error( Provider_Response_Normalizer::ERROR_RATE_LIMIT, 'Rate limit exceeded' );
		$this->assertSame( Provider_Response_Normalizer::ERROR_RATE_LIMIT, $err['category'] );
		$this->assertSame( Provider_Response_Normalizer::RETRY_WITH_BACKOFF, $err['retry_posture'] );
		$this->assertArrayHasKey( 'user_message', $err );
		$this->assertSame( 'Rate limit exceeded', $err['provider_raw'] );
	}

	public function test_auth_failure_is_no_retry(): void {
		$n = $this->normalizer();
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $n->get_retry_posture( Provider_Response_Normalizer::ERROR_AUTH_FAILURE ) );
		$err = $n->normalize_error( Provider_Response_Normalizer::ERROR_AUTH_FAILURE );
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $err['retry_posture'] );
	}

	public function test_validation_failure_is_no_retry(): void {
		$n = $this->normalizer();
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $n->get_retry_posture( Provider_Response_Normalizer::ERROR_VALIDATION_FAILURE ) );
	}

	public function test_timeout_is_retry_with_backoff(): void {
		$n = $this->normalizer();
		$this->assertSame( Provider_Response_Normalizer::RETRY_WITH_BACKOFF, $n->get_retry_posture( Provider_Response_Normalizer::ERROR_TIMEOUT ) );
	}

	public function test_malformed_response_is_retry_once(): void {
		$n = $this->normalizer();
		$this->assertSame( Provider_Response_Normalizer::RETRY_ONCE, $n->get_retry_posture( Provider_Response_Normalizer::ERROR_MALFORMED_RESPONSE ) );
	}

	public function test_build_error_response_has_required_keys(): void {
		$n = $this->normalizer();
		$res = $n->build_error_response( 'req_1', 'openai', 'gpt-4o', Provider_Response_Normalizer::ERROR_TIMEOUT, 'Request timed out' );
		$this->assertFalse( $res['success'] );
		$this->assertSame( 'req_1', $res['request_id'] );
		$this->assertSame( 'openai', $res['provider_id'] );
		$this->assertSame( 'gpt-4o', $res['model_used'] );
		$this->assertNull( $res['structured_payload'] );
		$this->assertIsArray( $res['normalized_error'] );
		$this->assertSame( Provider_Response_Normalizer::ERROR_TIMEOUT, $res['normalized_error']['category'] );
	}

	public function test_build_success_response_has_required_keys(): void {
		$n = $this->normalizer();
		$payload = array( 'version' => '1', 'sections' => array() );
		$usage   = array( 'prompt_tokens' => 100, 'completion_tokens' => 50, 'total_tokens' => 150, 'cost_placeholder' => null );
		$res = $n->build_success_response( 'req_2', 'openai', 'gpt-4o', $payload, $usage );
		$this->assertTrue( $res['success'] );
		$this->assertSame( 'req_2', $res['request_id'] );
		$this->assertSame( $payload, $res['structured_payload'] );
		$this->assertSame( $usage, $res['usage'] );
		$this->assertNull( $res['normalized_error'] );
	}

	/** Unknown category defaults to no_retry and generic user message. */
	public function test_unknown_category_defaults(): void {
		$n = $this->normalizer();
		$this->assertSame( Provider_Response_Normalizer::RETRY_NO_RETRY, $n->get_retry_posture( 'unknown_code' ) );
		$err = $n->normalize_error( 'unknown_code' );
		$this->assertSame( 'no_retry', $err['retry_posture'] );
		$this->assertStringContainsString( 'unexpected', strtolower( $err['user_message'] ) );
	}
}
