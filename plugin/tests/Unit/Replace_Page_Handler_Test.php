<?php
/**
 * Unit tests for Replace_Page_Result, Replace_Page_Handler (spec §32.9, §40.2, §41.2; Prompt 082).
 *
 * Covers result DTO (snapshot_ref, superseded refs), handler delegation, and example
 * replace-page execution result payload with snapshot reference fields.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Handlers\Replace_Page_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Result;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Job_Service_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Replace_Page_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Replace_Page_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Replace_Page_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';

/**
 * Stub job service that returns a fixed Replace_Page_Result for testing the handler.
 */
final class Stub_Replace_Page_Job_Service implements Replace_Page_Job_Service_Interface {

	/** @var Replace_Page_Result */
	public $run_result;

	public function __construct() {
		$this->run_result = Replace_Page_Result::failure( 'Stub', array(), '' );
	}

	public function run( array $envelope ): Replace_Page_Result {
		return $this->run_result;
	}
}

final class Replace_Page_Handler_Test extends TestCase {

	/** Example replace-page execution result payload with snapshot_ref and optional superseded_page_ref (spec §41.2). */
	public static function example_replace_page_execution_result_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Page updated or replaced.',
			'artifacts' => array(
				'target_post_id'   => 1001,
				'snapshot_ref'     => 'snap_pre_epc_0_20250311T120000',
				'template_key'     => 'tpl_landing',
				'assignment_count' => 2,
			),
		);
	}

	/** Example replace (new page + archive) result with superseded_page_ref. */
	public static function example_replace_page_with_superseded_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Page updated or replaced.',
			'artifacts' => array(
				'target_post_id'      => 1002,
				'snapshot_ref'        => 'snap_pre_epc_1_20250311T120001',
				'template_key'        => 'tpl_contact',
				'assignment_count'    => 1,
				'superseded_post_id'  => 1001,
				'superseded_page_ref' => array(
					'type'  => 'post_id',
					'value' => '1001',
				),
			),
		);
	}

	public function test_replace_page_result_success_to_array(): void {
		$result = Replace_Page_Result::success( 1001, 'tpl_landing', 2, 'snap_123', 0 );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 1001, $result->get_target_post_id() );
		$this->assertSame( 'snap_123', $result->get_snapshot_ref() );
		$this->assertSame( 0, $result->get_superseded_post_id() );
		$arr = $result->to_array();
		$this->assertSame( true, $arr['success'] );
		$this->assertSame( 'snap_123', $arr['snapshot_ref'] );
		$this->assertSame( 'tpl_landing', $arr['artifacts']['template_key'] ?? '' );
	}

	public function test_replace_page_result_success_with_superseded(): void {
		$result = Replace_Page_Result::success( 1002, 'tpl_contact', 1, 'snap_456', 1001 );
		$this->assertSame( 1001, $result->get_superseded_post_id() );
		$artifacts = $result->get_artifacts();
		$this->assertArrayHasKey( 'superseded_page_ref', $artifacts );
		$this->assertSame(
			array(
				'type'  => 'post_id',
				'value' => '1001',
			),
			$artifacts['superseded_page_ref']
		);
	}

	public function test_replace_page_result_failure_includes_snapshot_ref(): void {
		$result = Replace_Page_Result::failure( 'Target page could not be resolved.', array( 'target_not_found' ), 'snap_pre_0' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_target_post_id() );
		$this->assertSame( 'snap_pre_0', $result->get_snapshot_ref() );
		$this->assertSame( array( 'target_not_found' ), $result->get_errors() );
	}

	public function test_replace_page_result_to_handler_result_has_snapshot_ref(): void {
		$result         = Replace_Page_Result::success( 99, 'tpl_contact', 1, 'snap_xyz', 0 );
		$handler_result = $result->to_handler_result();
		$this->assertArrayHasKey( 'artifacts', $handler_result );
		$this->assertSame( 'snap_xyz', $handler_result['artifacts']['snapshot_ref'] ?? '' );
		$this->assertSame( 99, $handler_result['artifacts']['target_post_id'] ?? 0 );
	}

	public function test_replace_page_handler_delegates_to_job_service(): void {
		$stub             = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::success( 1001, 'tpl_landing', 2, 'snap_pre_0', 0 );
		$handler          = new Replace_Page_Handler( $stub );
		$envelope         = array(
			Execution_Action_Contract::ENVELOPE_ACTION_ID => 'exec_epc_0_batch1',
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => 'replace_page',
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'plan_item_id'   => 'epc_0',
				'target_post_id' => 1001,
				'template_key'   => 'tpl_landing',
				'action'         => 'rebuild_from_template',
				'snapshot_ref'   => 'snap_pre_0',
			),
		);
		$out              = $handler->execute( $envelope );
		$this->assertTrue( $out['success'] );
		$this->assertSame( 1001, $out['artifacts']['target_post_id'] ?? 0 );
		$this->assertSame( 'snap_pre_0', $out['artifacts']['snapshot_ref'] ?? '' );
	}

	public function test_replace_page_handler_returns_failure_when_job_service_fails(): void {
		$stub             = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::failure( 'Target page could not be resolved.', array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ), 'snap_pre_0' );
		$handler          = new Replace_Page_Handler( $stub );
		$envelope         = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'current_page_url' => '/missing-page/' ),
			'snapshot_ref' => 'snap_pre_0',
		);
		$out              = $handler->execute( $envelope );
		$this->assertFalse( $out['success'] );
		$this->assertSame( array( Execution_Action_Contract::ERROR_TARGET_NOT_FOUND ), $out['errors'] ?? array() );
	}

	public function test_replace_page_handler_returns_failure_when_snapshot_required_but_not_provided(): void {
		$stub             = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::failure( 'Pre-change snapshot required but not provided.', array( 'snapshot_required' ), '' );
		$handler          = new Replace_Page_Handler( $stub );
		$envelope         = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'target_post_id' => 1,
				'template_key'   => 'tpl_landing',
			),
			'snapshot_required' => true,
			'snapshot_ref'      => '',
		);
		$out              = $handler->execute( $envelope );
		$this->assertFalse( $out['success'] );
		$this->assertContains( 'snapshot_required', $out['errors'] ?? array() );
	}

	public function test_example_replace_page_execution_result_payload_has_snapshot_ref(): void {
		$payload = self::example_replace_page_execution_result_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'artifacts', $payload );
		$this->assertArrayHasKey( 'snapshot_ref', $payload['artifacts'] );
		$this->assertSame( 'snap_pre_epc_0_20250311T120000', $payload['artifacts']['snapshot_ref'] );
		$this->assertArrayHasKey( 'target_post_id', $payload['artifacts'] );
		$this->assertArrayHasKey( 'template_key', $payload['artifacts'] );
		$this->assertTrue( $payload['success'] );
	}

	public function test_example_replace_with_superseded_has_superseded_page_ref(): void {
		$payload = self::example_replace_page_with_superseded_payload();
		$this->assertSame( 1002, $payload['artifacts']['target_post_id'] );
		$this->assertSame( 1001, $payload['artifacts']['superseded_post_id'] );
		$this->assertSame(
			array(
				'type'  => 'post_id',
				'value' => '1001',
			),
			$payload['artifacts']['superseded_page_ref']
		);
		$this->assertNotEmpty( $payload['artifacts']['snapshot_ref'] );
	}
}
