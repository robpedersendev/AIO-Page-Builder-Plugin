<?php
/**
 * Unit tests for Provider_Connection_Test_Result (spec §49.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Providers\Drivers\Provider_Connection_Test_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Providers/Drivers/Provider_Connection_Test_Result.php';

final class Provider_Connection_Test_Result_Test extends TestCase {

	public function test_success_result_to_array_has_stable_shape(): void {
		$result = new Provider_Connection_Test_Result(
			true,
			'openai',
			'gpt-4o',
			null,
			'2025-07-15T12:00:00+00:00',
			'Connection successful.'
		);
		$arr = $result->to_array();
		$this->assertTrue( $arr['success'] );
		$this->assertSame( 'openai', $arr['provider_id'] );
		$this->assertSame( 'gpt-4o', $arr['model_used'] );
		$this->assertNull( $arr['normalized_error'] );
		$this->assertSame( '2025-07-15T12:00:00+00:00', $arr['tested_at'] );
		$this->assertSame( 'Connection successful.', $arr['user_message'] );
	}

	public function test_failure_result_to_array_includes_normalized_error(): void {
		$error = array(
			'category'      => 'auth_failure',
			'user_message'  => 'AI service authentication failed.',
			'internal_code'  => 'auth_failure',
			'provider_raw'   => null,
			'retry_posture'  => 'no_retry',
		);
		$result = new Provider_Connection_Test_Result(
			false,
			'openai',
			'gpt-4o',
			$error,
			'2025-07-15T12:00:00+00:00',
			'AI service authentication failed.'
		);
		$arr = $result->to_array();
		$this->assertFalse( $arr['success'] );
		$this->assertSame( $error, $arr['normalized_error'] );
	}

	public function test_from_array_roundtrip(): void {
		$data = array(
			'success'          => true,
			'provider_id'      => 'openai',
			'model_used'       => 'gpt-4o-mini',
			'normalized_error' => null,
			'tested_at'        => '2025-07-15T13:00:00+00:00',
			'user_message'     => 'OK',
		);
		$result = Provider_Connection_Test_Result::from_array( $data );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'openai', $result->get_provider_id() );
		$this->assertSame( 'gpt-4o-mini', $result->get_model_used() );
		$this->assertNull( $result->get_normalized_error() );
		$this->assertSame( $data['tested_at'], $result->get_tested_at() );
		$this->assertSame( 'OK', $result->get_user_message() );
		$this->assertEquals( $data, $result->to_array() );
	}

	public function test_from_array_failure_with_error(): void {
		$error = array(
			'category'      => 'rate_limit',
			'user_message'  => 'The AI service is temporarily busy.',
			'internal_code'  => 'rate_limit',
			'provider_raw'   => null,
			'retry_posture'  => 'retry_with_backoff',
		);
		$data = array(
			'success'          => false,
			'provider_id'      => 'openai',
			'model_used'       => 'gpt-4o',
			'normalized_error' => $error,
			'tested_at'        => '2025-07-15T14:00:00+00:00',
			'user_message'     => 'The AI service is temporarily busy.',
		);
		$result = Provider_Connection_Test_Result::from_array( $data );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( $error, $result->get_normalized_error() );
	}
}
