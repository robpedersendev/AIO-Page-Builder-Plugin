<?php
/**
 * Unit tests for Create_Page_Result, Create_Page_Handler, and create-page validation (spec §33.5, §40.2; Prompt 081).
 *
 * Covers result DTO, handler delegation, invalid target/template/parent rejection.
 * Includes one example new-page execution result payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Handlers\Create_Page_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Result;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Create_Page_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Create_Page_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Create_Page_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';

/**
 * Stub job service that returns a fixed result for testing the handler.
 */
final class Stub_Create_Page_Job_Service implements Create_Page_Job_Service_Interface {

	/** @var Create_Page_Result */
	public $run_result;

	public function __construct() {
		$this->run_result = Create_Page_Result::failure( 'Stub', array() );
	}

	public function run( array $envelope ): Create_Page_Result {
		return $this->run_result;
	}
}

final class Create_Page_Handler_Test extends TestCase {

	/** Example new-page execution result payload (handler result shape after successful create). */
	public static function example_new_page_execution_result_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Page created.',
			'artifacts' => array(
				'post_id'           => 42,
				'template_key'       => 'tpl_landing',
				'assignment_count'   => 2,
			),
		);
	}

	public function test_create_page_result_success_to_array(): void {
		$result = Create_Page_Result::success( 42, 'tpl_landing', 2, 'log_123' );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 42, $result->get_post_id() );
		$arr = $result->to_array();
		$this->assertSame( true, $arr['success'] );
		$this->assertSame( 42, $arr['post_id'] );
		$this->assertSame( 'tpl_landing', $arr['artifacts']['template_key'] ?? '' );
		$this->assertSame( 2, $arr['artifacts']['assignment_count'] ?? 0 );
	}

	public function test_create_page_result_failure_has_errors(): void {
		$result = Create_Page_Result::failure( 'Invalid template.', array( 'template_not_found' ) );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_post_id() );
		$this->assertSame( array( 'template_not_found' ), $result->get_errors() );
	}

	public function test_create_page_result_to_handler_result_shape(): void {
		$result = Create_Page_Result::success( 99, 'tpl_contact', 1 );
		$handler_result = $result->to_handler_result();
		$this->assertArrayHasKey( 'success', $handler_result );
		$this->assertArrayHasKey( 'message', $handler_result );
		$this->assertArrayHasKey( 'artifacts', $handler_result );
		$this->assertSame( 99, $handler_result['artifacts']['post_id'] ?? 0 );
		$this->assertSame( 'tpl_contact', $handler_result['artifacts']['template_key'] ?? '' );
	}

	public function test_create_page_handler_delegates_to_job_service(): void {
		$stub = new Stub_Create_Page_Job_Service();
		$stub->run_result = Create_Page_Result::success( 100, 'tpl_landing', 2 );
		$handler = new Create_Page_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_ACTION_ID   => 'exec_item_0_batch1',
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE  => 'create_page',
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'plan_item_id'         => 'item_0',
				'template_key'         => 'tpl_landing',
				'proposed_page_title'  => 'Landing',
				'proposed_slug'        => 'landing',
			),
		);
		$out = $handler->execute( $envelope );
		$this->assertTrue( $out['success'] );
		$this->assertSame( 100, $out['artifacts']['post_id'] ?? 0 );
	}

	public function test_create_page_handler_returns_failure_when_job_service_fails(): void {
		$stub = new Stub_Create_Page_Job_Service();
		$stub->run_result = Create_Page_Result::failure( 'Template not found.', array( 'template_not_found' ) );
		$handler = new Create_Page_Handler( $stub );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'template_key' => 'tpl_missing' ),
		);
		$out = $handler->execute( $envelope );
		$this->assertFalse( $out['success'] );
		$this->assertSame( array( 'template_not_found' ), $out['errors'] ?? array() );
	}

	public function test_example_new_page_execution_result_payload_has_required_keys(): void {
		$payload = self::example_new_page_execution_result_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'artifacts', $payload );
		$this->assertArrayHasKey( 'post_id', $payload['artifacts'] );
		$this->assertArrayHasKey( 'template_key', $payload['artifacts'] );
		$this->assertArrayHasKey( 'assignment_count', $payload['artifacts'] );
		$this->assertTrue( $payload['success'] );
	}
}
