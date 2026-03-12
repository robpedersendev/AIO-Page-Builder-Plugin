<?php
/**
 * Unit tests for Menu_Change_Result, Apply_Menu_Change_Handler (spec §34, §40.2; Prompt 083).
 *
 * Covers result DTO, handler delegation, invalid target rejection, and example menu-change result payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Handlers\Apply_Menu_Change_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Result;
use AIOPageBuilder\Domain\Execution\Jobs\Menu_Change_Job_Service_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Menu_Change_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Menu_Change_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Apply_Menu_Change_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';

/**
 * Stub menu job service for handler tests.
 */
final class Stub_Menu_Change_Job_Service implements Menu_Change_Job_Service_Interface {

	/** @var Menu_Change_Result */
	public $run_result;

	public function __construct() {
		$this->run_result = Menu_Change_Result::failure( 'Stub', array() );
	}

	public function run( array $envelope ): Menu_Change_Result {
		return $this->run_result;
	}
}

final class Apply_Menu_Change_Handler_Test extends TestCase {

	/** Example menu-change execution result payload (spec §34). */
	public static function example_menu_change_result_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Menu change applied.',
			'artifacts' => array(
				'menu_id'            => 42,
				'action'             => 'create',
				'menu_name'          => 'Main Navigation',
				'location_assigned'  => 'primary',
			),
		);
	}

	public function test_menu_change_result_success_to_handler_result(): void {
		$result = Menu_Change_Result::success( 42, 'create', 'Main Nav', 'primary' );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 42, $result->get_menu_id() );
		$out = $result->to_handler_result();
		$this->assertSame( 42, $out['artifacts']['menu_id'] ?? 0 );
		$this->assertSame( 'primary', $out['artifacts']['location_assigned'] ?? '' );
	}

	public function test_menu_change_result_failure_has_errors(): void {
		$result = Menu_Change_Result::failure( 'Menu to rename could not be resolved.', array( 'target_not_found' ) );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_menu_id() );
		$this->assertContains( 'target_not_found', $result->get_errors() );
	}

	public function test_apply_menu_change_handler_delegates_to_job_service(): void {
		$stub = new Stub_Menu_Change_Job_Service();
		$stub->run_result = Menu_Change_Result::success( 99, 'create', 'Footer Menu', 'footer' );
		$handler = new Apply_Menu_Change_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'menu_context'        => 'header',
				'action'              => 'create',
				'proposed_menu_name'  => 'Footer Menu',
				'items'               => array(),
			),
		);
		$out = $handler->execute( $envelope );
		$this->assertTrue( $out['success'] );
		$this->assertSame( 99, $out['artifacts']['menu_id'] ?? 0 );
		$this->assertSame( 'footer', $out['artifacts']['location_assigned'] ?? '' );
	}

	public function test_apply_menu_change_handler_returns_failure_on_invalid_target(): void {
		$stub = new Stub_Menu_Change_Job_Service();
		$stub->run_result = Menu_Change_Result::failure( 'Menu to rename could not be resolved.', array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ) );
		$handler = new Apply_Menu_Change_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'menu_context' => 'header', 'action' => 'rename' ),
		);
		$out = $handler->execute( $envelope );
		$this->assertFalse( $out['success'] );
		$this->assertSame( array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ), $out['errors'] ?? array() );
	}

	public function test_example_menu_change_result_payload_has_required_keys(): void {
		$payload = self::example_menu_change_result_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'artifacts', $payload );
		$this->assertArrayHasKey( 'menu_id', $payload['artifacts'] );
		$this->assertArrayHasKey( 'action', $payload['artifacts'] );
		$this->assertArrayHasKey( 'location_assigned', $payload['artifacts'] );
		$this->assertTrue( $payload['success'] );
	}
}
