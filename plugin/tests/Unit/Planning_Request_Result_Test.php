<?php
/**
 * Unit tests for Planning_Request_Result (spec §49.8, §59.8).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Onboarding\Planning_Request_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/AI/Onboarding/Planning_Request_Result.php';

final class Planning_Request_Result_Test extends TestCase {

	public function test_success_result_to_array_has_stable_shape(): void {
		$result = new Planning_Request_Result(
			true,
			Planning_Request_Result::STATUS_SUCCESS,
			'aio-run-123',
			456,
			'AI plan generated successfully.',
			null,
			null,
			null
		);
		$arr = $result->to_array();
		$this->assertTrue( $arr['success'] );
		$this->assertSame( Planning_Request_Result::STATUS_SUCCESS, $arr['status'] );
		$this->assertSame( 'aio-run-123', $arr['run_id'] );
		$this->assertSame( 456, $arr['run_post_id'] );
		$this->assertSame( 'AI plan generated successfully.', $arr['user_message'] );
		$this->assertNull( $arr['validation_report'] );
		$this->assertNull( $arr['normalized_error'] );
		$this->assertNull( $arr['blocking_reason'] );
	}

	public function test_validation_failed_result_to_array(): void {
		$report = array( 'final_validation_state' => 'failed', 'record_validation_results' => array() );
		$result = new Planning_Request_Result(
			false,
			Planning_Request_Result::STATUS_VALIDATION_FAILED,
			'aio-run-789',
			101,
			'The AI response could not be validated.',
			$report,
			null,
			null
		);
		$arr = $result->to_array();
		$this->assertFalse( $arr['success'] );
		$this->assertSame( Planning_Request_Result::STATUS_VALIDATION_FAILED, $arr['status'] );
		$this->assertSame( $report, $arr['validation_report'] );
		$this->assertNull( $arr['normalized_error'] );
	}

	public function test_provider_failed_result_to_array(): void {
		$error = array(
			'category'      => 'auth_failure',
			'user_message'  => 'AI service authentication failed.',
			'internal_code'  => 'auth_failure',
			'provider_raw'   => null,
			'retry_posture'  => 'no_retry',
		);
		$result = new Planning_Request_Result(
			false,
			Planning_Request_Result::STATUS_PROVIDER_FAILED,
			'aio-run-999',
			202,
			'AI service authentication failed.',
			null,
			$error,
			null
		);
		$arr = $result->to_array();
		$this->assertFalse( $arr['success'] );
		$this->assertSame( $error, $arr['normalized_error'] );
	}

	public function test_blocked_result_has_blocking_reason(): void {
		$result = new Planning_Request_Result(
			false,
			Planning_Request_Result::STATUS_BLOCKED,
			'',
			0,
			'Configure an AI provider before submitting.',
			null,
			null,
			'provider_not_ready'
		);
		$this->assertSame( 'provider_not_ready', $result->get_blocking_reason() );
		$this->assertSame( '', $result->get_run_id() );
		$this->assertSame( 0, $result->get_run_post_id() );
	}

	public function test_ui_safe_payload_has_no_secret_keys(): void {
		$result = new Planning_Request_Result(
			true,
			Planning_Request_Result::STATUS_SUCCESS,
			'run-1',
			1,
			'Done.',
			null,
			null,
			null
		);
		$arr = $result->to_array();
		$forbidden = array( 'api_key', 'secret', 'token', 'password', 'credential' );
		foreach ( $forbidden as $key ) {
			$this->assertArrayNotHasKey( $key, $arr );
		}
		$this->assertArrayHasKey( 'success', $arr );
		$this->assertArrayHasKey( 'status', $arr );
		$this->assertArrayHasKey( 'user_message', $arr );
	}
}
