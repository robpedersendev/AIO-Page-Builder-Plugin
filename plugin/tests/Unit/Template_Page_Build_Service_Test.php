<?php
/**
 * Unit tests for Template_Page_Build_Service (spec §33.5, §17.7, §33.9; Prompt 194).
 *
 * Covers template-driven new-page build enrichment: template_build_execution_result in artifacts,
 * hierarchy assignment recorded, template_family/one_pager/section_count, failure path, per-item result shape.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Result;
use AIOPageBuilder\Domain\Execution\Jobs\Create_Page_Job_Service_Interface;
use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Build_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Create_Page_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Create_Page_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Build_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Build_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';

/**
 * Stub job service returning configurable Create_Page_Result.
 */
final class Stub_Template_Build_Job_Service implements Create_Page_Job_Service_Interface {

	/** @var Create_Page_Result */
	public $run_result;

	public function __construct() {
		$this->run_result = Create_Page_Result::failure( 'Stub', array() );
	}

	public function run( array $envelope ): Create_Page_Result {
		return $this->run_result;
	}
}

final class Template_Page_Build_Service_Test extends TestCase {

	public function test_run_enriches_success_result_with_template_build_execution_result(): void {
		$stub_job = new Stub_Template_Build_Job_Service();
		$stub_job->run_result = Create_Page_Result::success( 100, 'tpl_hub', 2, '' );

		$template_def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY   => 'tpl_hub',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'sec_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0 ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'sec_cta', Page_Template_Schema::SECTION_ITEM_POSITION => 1 ),
			),
			Page_Template_Schema::FIELD_ONE_PAGER     => array( 'doc_ref' => 'one-pager-hub' ),
			'template_family'                         => 'services',
			'template_category_class'                => 'hub',
		);
		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->with( 'tpl_hub' )->willReturn( $template_def );

		$service = new Template_Page_Build_Service( $stub_job, $repo );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'template_key'         => 'tpl_hub',
				'proposed_page_title'  => 'Hub',
				'parent_post_id'       => 5,
			),
		);
		$result = $service->run( $envelope );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 100, $result->get_post_id() );
		$artifacts = $result->get_artifacts();
		$this->assertArrayHasKey( 'template_build_execution_result', $artifacts );
		$tbr = $artifacts['template_build_execution_result'];
		$this->assertSame( true, $tbr['success'] );
		$this->assertSame( 100, $tbr['post_id'] );
		$this->assertSame( 'tpl_hub', $tbr['template_key'] );
		$this->assertSame( 'services', $tbr['template_family'] );
		$this->assertSame( 'hub', $tbr['template_category_class'] );
		$this->assertSame( true, $tbr['hierarchy_applied'] );
		$this->assertSame( 5, $tbr['parent_post_id'] );
		$this->assertSame( true, $tbr['one_pager_available'] );
		$this->assertSame( 2, $tbr['section_count'] );
		$this->assertSame( 2, $tbr['field_assignment_count'] );
	}

	public function test_run_failure_includes_template_build_execution_result_with_errors(): void {
		$stub_job = new Stub_Template_Build_Job_Service();
		$stub_job->run_result = Create_Page_Result::failure( 'Page template not found.', array( 'template_not_found' ) );

		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->willReturn( null );

		$service = new Template_Page_Build_Service( $stub_job, $repo );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'template_key' => 'tpl_missing' ),
		);
		$result = $service->run( $envelope );

		$this->assertFalse( $result->is_success() );
		$artifacts = $result->get_artifacts();
		$this->assertArrayHasKey( 'template_build_execution_result', $artifacts );
		$tbr = $artifacts['template_build_execution_result'];
		$this->assertSame( false, $tbr['success'] );
		$this->assertSame( array( 'template_not_found' ), $tbr['errors'] );
		$this->assertSame( 'tpl_missing', $tbr['template_key'] );
	}

	public function test_run_resolves_parent_from_parent_ref(): void {
		$stub_job = new Stub_Template_Build_Job_Service();
		$stub_job->run_result = Create_Page_Result::success( 50, 'tpl_child', 1 );

		$template_def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY => 'tpl_child',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(),
			'template_family' => 'products',
		);
		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->willReturn( $template_def );

		$service = new Template_Page_Build_Service( $stub_job, $repo );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'template_key'        => 'tpl_child',
				'proposed_page_title' => 'Child',
				'parent_ref'          => array( 'value' => '7' ),
			),
		);
		$result = $service->run( $envelope );

		$tbr = $result->get_artifacts()['template_build_execution_result'];
		$this->assertSame( true, $tbr['hierarchy_applied'] );
		$this->assertSame( 7, $tbr['parent_post_id'] );
	}

	public function test_run_no_parent_sets_hierarchy_applied_false(): void {
		$stub_job = new Stub_Template_Build_Job_Service();
		$stub_job->run_result = Create_Page_Result::success( 60, 'tpl_top', 0 );

		$repo = $this->createMock( Page_Template_Repository::class );
		$repo->method( 'get_definition_by_key' )->willReturn( array( 'template_family' => 'top_level', Page_Template_Schema::FIELD_ORDERED_SECTIONS => array() ) );

		$service = new Template_Page_Build_Service( $stub_job, $repo );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'template_key' => 'tpl_top', 'proposed_page_title' => 'Top' ),
		);
		$result = $service->run( $envelope );

		$tbr = $result->get_artifacts()['template_build_execution_result'];
		$this->assertSame( false, $tbr['hierarchy_applied'] );
		$this->assertSame( 0, $tbr['parent_post_id'] );
	}
}
