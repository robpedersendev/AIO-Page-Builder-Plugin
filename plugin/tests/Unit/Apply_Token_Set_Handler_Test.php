<?php
/**
 * Unit tests for Token_Set_Result, Apply_Token_Set_Handler (spec §35, §40.2, §41.7; Prompt 083).
 *
 * Covers result DTO, handler delegation, invalid target rejection, and example token-set apply result payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Handlers\Apply_Token_Set_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Result;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Token_Set_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Token_Set_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Apply_Token_Set_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';

/**
 * Stub token job service for handler tests.
 */
final class Stub_Token_Set_Job_Service implements Token_Set_Job_Service_Interface {

	/** @var Token_Set_Result */
	public $run_result;

	public function __construct() {
		$this->run_result = Token_Set_Result::failure( 'Stub', array() );
	}

	public function run( array $envelope ): Token_Set_Result {
		return $this->run_result;
	}
}

final class Apply_Token_Set_Handler_Test extends TestCase {

	/** Example token-set apply execution result payload (spec §35, §41.7). */
	public static function example_token_set_apply_result_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Token value applied.',
			'artifacts' => array(
				'token_group'        => 'color',
				'token_name'         => 'primary',
				'applied_value'      => '#2563eb',
				'previous_value_ref' => array( 'value' => '#1e40af' ),
				'snapshot_ref'       => 'snap_tok_dtr_0_20250311T120000',
			),
		);
	}

	public function test_token_set_result_success_to_handler_result(): void {
		$result = Token_Set_Result::success( 'color', 'primary', '#2563eb', '#1e40af', 'snap_1' );
		$this->assertTrue( $result->is_success() );
		$out = $result->to_handler_result();
		$this->assertSame( 'color', $out['artifacts']['token_group'] ?? '' );
		$this->assertSame( 'primary', $out['artifacts']['token_name'] ?? '' );
		$this->assertArrayHasKey( 'previous_value_ref', $out['artifacts'] );
		$this->assertSame( 'snap_1', $out['artifacts']['snapshot_ref'] ?? '' );
	}

	public function test_token_set_result_failure_has_errors(): void {
		$result = Token_Set_Result::failure( 'Invalid token group.', array( 'invalid_token_group' ) );
		$this->assertFalse( $result->is_success() );
		$this->assertContains( 'invalid_token_group', $result->get_errors() );
	}

	public function test_apply_token_set_handler_delegates_to_job_service(): void {
		$stub = new Stub_Token_Set_Job_Service();
		$stub->run_result = Token_Set_Result::success( 'spacing', 'unit', '0.25rem', null, 'snap_tok' );
		$handler = new Apply_Token_Set_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'token_group'    => 'spacing',
				'token_name'     => 'unit',
				'proposed_value' => '0.25rem',
			),
			'snapshot_ref' => 'snap_tok',
		);
		$out = $handler->execute( $envelope );
		$this->assertTrue( $out['success'] );
		$this->assertSame( 'spacing', $out['artifacts']['token_group'] ?? '' );
		$this->assertSame( '0.25rem', $out['artifacts']['applied_value'] ?? '' );
	}

	public function test_apply_token_set_handler_returns_failure_on_invalid_target(): void {
		$stub = new Stub_Token_Set_Job_Service();
		$stub->run_result = Token_Set_Result::failure( 'Missing token group or name.', array( Execution_Action_Contract::ERROR_INVALID_ENVELOPE ) );
		$handler = new Apply_Token_Set_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(),
		);
		$out = $handler->execute( $envelope );
		$this->assertFalse( $out['success'] );
		$this->assertSame( array( Execution_Action_Contract::ERROR_INVALID_ENVELOPE ), $out['errors'] ?? array() );
	}

	public function test_example_token_set_apply_result_payload_has_required_keys(): void {
		$payload = self::example_token_set_apply_result_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'artifacts', $payload );
		$this->assertArrayHasKey( 'token_group', $payload['artifacts'] );
		$this->assertArrayHasKey( 'token_name', $payload['artifacts'] );
		$this->assertArrayHasKey( 'applied_value', $payload['artifacts'] );
		$this->assertArrayHasKey( 'previous_value_ref', $payload['artifacts'] );
		$this->assertArrayHasKey( 'snapshot_ref', $payload['artifacts'] );
		$this->assertTrue( $payload['success'] );
	}
}
