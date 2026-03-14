<?php
/**
 * Unit tests for Template_Page_Replacement_Service (spec §32, §32.9; Prompt 196).
 *
 * Covers snapshot_ref preservation, replacement_trace_record in artifacts, template_replacement_execution_result,
 * failure-safe status reporting.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Jobs\Replace_Page_Result;
use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Replacement_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Replace_Page_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Replace_Page_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Replacement_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Replacement_Service.php';

/**
 * Stub replace-page job service returning configurable result.
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

final class Template_Page_Replacement_Service_Test extends TestCase {

	public function test_run_enriches_success_result_with_template_replacement_execution_result(): void {
		$stub = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::success( 202, 'tpl_services_hub', 3, 'op-snap-pre-123', 101 );
		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->with( 'tpl_services_hub' )->willReturn( array( 'template_family' => 'services' ) );

		$service = new Template_Page_Replacement_Service( $stub, $repo );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'template_key' => 'tpl_services_hub' ),
		);
		$result = $service->run( $envelope );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 202, $result->get_target_post_id() );
		$this->assertSame( 101, $result->get_superseded_post_id() );
		$this->assertSame( 'op-snap-pre-123', $result->get_snapshot_ref() );
		$artifacts = $result->get_artifacts();
		$this->assertArrayHasKey( 'template_replacement_execution_result', $artifacts );
		$this->assertArrayHasKey( 'replacement_trace_record', $artifacts );
		$exec = $artifacts['template_replacement_execution_result'];
		$this->assertTrue( $exec['success'] );
		$this->assertSame( 202, $exec['target_post_id'] );
		$this->assertSame( 101, $exec['superseded_post_id'] );
		$this->assertSame( 'tpl_services_hub', $exec['template_key'] );
		$this->assertSame( 'services', $exec['template_family'] );
		$trace = $artifacts['replacement_trace_record'];
		$this->assertSame( 101, $trace['original_post_id'] );
		$this->assertSame( 202, $trace['new_post_id'] );
		$this->assertSame( 'private', $trace['archive_status'] );
		$this->assertSame( 'op-snap-pre-123', $trace['snapshot_pre_id'] );
	}

	public function test_run_rebuild_in_place_trace_has_in_place_archive_status(): void {
		$stub = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::success( 99, 'tpl_hub', 1, 'op-snap-pre-99', 0 );
		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->willReturn( array() );

		$service = new Template_Page_Replacement_Service( $stub, $repo );
		$result = $service->run( array() );

		$trace = $result->get_artifacts()['replacement_trace_record'];
		$this->assertSame( 'in_place', $trace['archive_status'] );
		$this->assertSame( 99, $trace['original_post_id'] );
		$this->assertSame( 99, $trace['new_post_id'] );
	}

	public function test_run_failure_includes_template_replacement_execution_result_with_errors(): void {
		$stub = new Stub_Replace_Page_Job_Service();
		$stub->run_result = Replace_Page_Result::failure( 'Pre-change snapshot required but not provided.', array( 'snapshot_required' ), 'op-snap-missing' );
		$repo = $this->createMock( Page_Template_Repository::class );

		$service = new Template_Page_Replacement_Service( $stub, $repo );
		$result = $service->run( array( Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'template_key' => 'tpl_missing' ) ) );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'op-snap-missing', $result->get_snapshot_ref() );
		$exec = $result->get_artifacts()['template_replacement_execution_result'];
		$this->assertFalse( $exec['success'] );
		$this->assertSame( array( 'snapshot_required' ), $exec['errors'] );
		$this->assertSame( 'tpl_missing', $exec['template_key'] );
		$this->assertSame( array(), $result->get_artifacts()['replacement_trace_record'] );
	}
}
