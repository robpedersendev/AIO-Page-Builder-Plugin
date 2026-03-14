<?php
/**
 * Unit tests for finalization flow (spec §37, §40.10; Prompt 084).
 *
 * Covers Finalization_Result, Finalization_Job_Service (publish-ready validation, conflict detection,
 * completion summary), Finalize_Plan_Handler, Bulk_Executor::build_finalization_envelope, and
 * example finalization result payload with completion summary counts.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Schema;
use AIOPageBuilder\Domain\BuildPlan\Statuses\Build_Plan_Item_Statuses;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Handlers\Finalize_Plan_Handler;
use AIOPageBuilder\Domain\Execution\Jobs\Finalization_Job_Service;
use AIOPageBuilder\Domain\Execution\Jobs\Finalization_Result;
use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Service;
use AIOPageBuilder\Domain\Execution\Queue\Bulk_Executor;
use AIOPageBuilder\Domain\Storage\Repositories\Build_Plan_Repository_Interface;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Statuses/Build_Plan_Item_Statuses.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Build_Plan_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Finalization_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Finalization_Job_Service.php';
require_once $plugin_root . '/src/Domain/Execution/Handlers/Finalize_Plan_Handler.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Bulk_Executor.php';
require_once $plugin_root . '/src/Domain/Execution/Finalize/Template_Finalization_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Finalize/Template_Finalization_Service.php';

/**
 * Stub Build Plan Repository for finalization tests (implements Build_Plan_Repository_Interface).
 */
final class Stub_Build_Plan_Repository_Finalization implements Build_Plan_Repository_Interface {

	/** @var array<string, mixed>|null */
	public $get_by_key_return = null;

	/** @var array<string, mixed> */
	public $get_plan_definition_return = array();

	/** @var bool */
	public $save_plan_definition_return = true;

	/** @var array{post_id: int, definition: array<string, mixed>}|null Last save call. */
	public $last_save = null;

	public function get_by_key( string $key ): ?array {
		return $this->get_by_key_return;
	}

	public function get_plan_definition( int $post_id ): array {
		return $this->get_plan_definition_return;
	}

	public function save_plan_definition( int $post_id, array $definition ): bool {
		$this->last_save = array( 'post_id' => $post_id, 'definition' => $definition );
		return $this->save_plan_definition_return;
	}
}

final class Finalization_Flow_Test extends TestCase {

	/**
	 * Example finalization result payload with completion summary counts (spec §37.7).
	 *
	 * @return array<string, mixed>
	 */
	public static function example_finalization_result_payload(): array {
		return array(
			'success'   => true,
			'message'   => 'Plan finalized.',
			'artifacts' => array(
				'completion_summary' => array(
					'published'                        => 2,
					'completed_without_publication'    => 1,
					'blocked'                          => 0,
					'denied'                           => 0,
					'failed'                           => 0,
				),
				'conflicts'         => array(),
				'finalized_at'      => '2025-03-11T14:30:00+00:00',
				'actor_ref'         => 'user:1',
			),
		);
	}

	public function test_finalization_result_success_has_completion_summary(): void {
		$summary = array(
			'published'                        => 1,
			'completed_without_publication'    => 2,
			'blocked'                          => 0,
			'denied'                           => 0,
			'failed'                           => 0,
		);
		$result = Finalization_Result::success( '2025-03-11T12:00:00+00:00', $summary, array(), 'user:1' );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( $summary, $result->get_artifacts()['completion_summary'] );
		$this->assertSame( '2025-03-11T12:00:00+00:00', $result->get_artifacts()['finalized_at'] );
		$out = $result->to_handler_result();
		$this->assertTrue( $out['success'] );
		$this->assertSame( $summary, $out['artifacts']['completion_summary'] );
	}

	public function test_finalization_result_failure_includes_errors_and_artifacts(): void {
		$result = Finalization_Result::failure(
			'Conflicts detected; finalization blocked.',
			array( 'conflicts_block' ),
			array(
				'completion_summary' => array( 'published' => 0, 'completed_without_publication' => 2, 'blocked' => 1, 'denied' => 0, 'failed' => 0 ),
				'conflicts'         => array( array( 'type' => 'slug_conflict', 'slug' => 'about', 'message' => 'Duplicate slug in plan.' ) ),
			)
		);
		$this->assertFalse( $result->is_success() );
		$this->assertContains( 'conflicts_block', $result->get_errors() );
		$this->assertSame( 1, ( $result->get_artifacts()['completion_summary']['blocked'] ?? 0 ) );
	}

	public function test_finalization_job_service_rejects_missing_plan_id(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$svc  = new Finalization_Job_Service( $repo );
		$env  = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => '',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array(),
		);
		$result = $svc->run( $env );
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Missing plan ID', $result->get_message() );
	}

	public function test_finalization_job_service_rejects_plan_not_found(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = null;
		$svc  = new Finalization_Job_Service( $repo );
		$env  = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-missing',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array(),
		);
		$result = $svc->run( $env );
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'not found', $result->get_message() );
	}

	public function test_finalization_job_service_rejects_plan_not_executable(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array(
			Build_Plan_Schema::KEY_STATUS => 'draft',
			Build_Plan_Schema::KEY_STEPS  => array(),
		);
		$svc = new Finalization_Job_Service( $repo );
		$env = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-draft',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array(),
		);
		$result = $svc->run( $env );
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'not in an executable state', $result->get_message() );
	}

	public function test_finalization_job_service_blocks_on_slug_conflict(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array(
			Build_Plan_Schema::KEY_STATUS => Build_Plan_Schema::STATUS_APPROVED,
			Build_Plan_Schema::KEY_STEPS  => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item-1',
							Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::COMPLETED,
							Build_Plan_Item_Schema::KEY_PAYLOAD   => array( 'page_slug_candidate' => 'about-us' ),
						),
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item-2',
							Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::COMPLETED,
							Build_Plan_Item_Schema::KEY_PAYLOAD   => array( 'page_slug_candidate' => 'about-us' ),
						),
					),
				),
			),
		);
		$svc = new Finalization_Job_Service( $repo );
		$env = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-conflict',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array( Execution_Action_Contract::ACTOR_ACTOR_ID => 'user:0' ),
		);
		$result = $svc->run( $env );
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Conflicts detected', $result->get_message() );
		$artifacts = $result->get_artifacts();
		$this->assertNotEmpty( $artifacts['conflicts'] );
		$this->assertSame( 1, $artifacts['completion_summary']['blocked'] ?? 0 );
		$this->assertNull( $repo->last_save );
	}

	public function test_finalization_job_service_success_updates_plan_and_returns_summary(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array(
			Build_Plan_Schema::KEY_STATUS => Build_Plan_Schema::STATUS_APPROVED,
			Build_Plan_Schema::KEY_STEPS  => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item-1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_DESIGN_TOKEN,
							Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::COMPLETED,
						),
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item-2',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_MENU_CHANGE,
							Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::REJECTED,
						),
					),
				),
			),
		);
		$svc = new Finalization_Job_Service( $repo );
		$env = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-ok',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array( Execution_Action_Contract::ACTOR_ACTOR_ID => 'user:42' ),
		);
		$result = $svc->run( $env );
		$this->assertTrue( $result->is_success() );
		$summary = $result->get_artifacts()['completion_summary'];
		$this->assertSame( 0, $summary['published'] );
		$this->assertSame( 1, $summary['completed_without_publication'] );
		$this->assertSame( 0, $summary['blocked'] );
		$this->assertSame( 1, $summary['denied'] );
		$this->assertSame( 0, $summary['failed'] );
		$this->assertNotNull( $repo->last_save );
		$this->assertSame( 1, $repo->last_save['post_id'] );
		$def = $repo->last_save['definition'];
		$this->assertSame( Build_Plan_Schema::STATUS_COMPLETED, $def[ Build_Plan_Schema::KEY_STATUS ] ?? '' );
		$this->assertArrayHasKey( Build_Plan_Schema::KEY_COMPLETED_AT, $def );
		$this->assertArrayHasKey( 'finalization_history', $def );
		$this->assertSame( 'user:42', $result->get_artifacts()['actor_ref'] );
	}

	public function test_finalize_plan_handler_delegates_to_job_service(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array(
			Build_Plan_Schema::KEY_STATUS => Build_Plan_Schema::STATUS_APPROVED,
			Build_Plan_Schema::KEY_STEPS  => array(),
		);
		$svc    = new Finalization_Job_Service( $repo );
		$handler = new Finalize_Plan_Handler( $svc );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-handler',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array( Execution_Action_Contract::ACTOR_ACTOR_ID => 'user:0' ),
		);
		$out = $handler->execute( $envelope );
		$this->assertTrue( $out['success'] );
		$this->assertArrayHasKey( 'completion_summary', $out['artifacts'] );
		$this->assertArrayHasKey( 'finalized_at', $out['artifacts'] );
	}

	public function test_bulk_executor_build_finalization_envelope_returns_plan_level_envelope(): void {
		$executor = new Bulk_Executor();
		$definition = array(
			Build_Plan_Schema::KEY_STATUS => Build_Plan_Schema::STATUS_APPROVED,
			Build_Plan_Schema::KEY_STEPS  => array(),
		);
		$actor = array( Execution_Action_Contract::ACTOR_ACTOR_ID => 'user:1', Execution_Action_Contract::ACTOR_CAPABILITY_CHECKED => 'edit_posts' );
		$env = $executor->build_finalization_envelope( 'plan-123', $definition, $actor, 'batch-1' );
		$this->assertSame( Execution_Action_Types::FINALIZE_PLAN, $env[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] );
		$this->assertSame( 'plan-123', $env[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] );
		$this->assertSame( '', $env[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] );
		$this->assertSame( $actor, $env[ Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT ] );
		$this->assertStringContainsString( 'finalize', $env[ Execution_Action_Contract::ENVELOPE_ACTION_ID ] );
	}

	public function test_example_finalization_payload_has_required_completion_summary_keys(): void {
		$payload = self::example_finalization_result_payload();
		$this->assertTrue( $payload['success'] );
		$summary = $payload['artifacts']['completion_summary'];
		$this->assertArrayHasKey( 'published', $summary );
		$this->assertArrayHasKey( 'completed_without_publication', $summary );
		$this->assertArrayHasKey( 'blocked', $summary );
		$this->assertArrayHasKey( 'denied', $summary );
		$this->assertArrayHasKey( 'failed', $summary );
		$this->assertSame( 2, $summary['published'] );
		$this->assertSame( 1, $summary['completed_without_publication'] );
		$this->assertSame( 0, $summary['blocked'] );
	}

	/** With Template_Finalization_Service injected, success artifacts include finalization_summary and run_completion_state (Prompt 208). */
	public function test_finalization_job_service_with_template_service_includes_finalization_summary(): void {
		$repo = new Stub_Build_Plan_Repository_Finalization();
		$repo->get_by_key_return = array( 'id' => 1 );
		$repo->get_plan_definition_return = array(
			Build_Plan_Schema::KEY_STATUS => Build_Plan_Schema::STATUS_APPROVED,
			Build_Plan_Schema::KEY_STEPS  => array(
				array(
					Build_Plan_Item_Schema::KEY_ITEMS => array(
						array(
							Build_Plan_Item_Schema::KEY_ITEM_ID   => 'item-1',
							Build_Plan_Item_Schema::KEY_ITEM_TYPE => Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE,
							Build_Plan_Item_Schema::KEY_STATUS     => Build_Plan_Item_Statuses::COMPLETED,
						),
					),
				),
			),
		);
		$template_svc = new Template_Finalization_Service();
		$svc = new Finalization_Job_Service( $repo, $template_svc );
		$env = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'plan-tpl',
			Execution_Action_Contract::ENVELOPE_ACTOR_CONTEXT => array( Execution_Action_Contract::ACTOR_ACTOR_ID => 'user:1' ),
		);
		$result = $svc->run( $env );
		$this->assertTrue( $result->is_success() );
		$artifacts = $result->get_artifacts();
		$this->assertArrayHasKey( 'finalization_summary', $artifacts );
		$this->assertArrayHasKey( 'run_completion_state', $artifacts );
		$this->assertArrayHasKey( 'template_execution_closure_record', $artifacts );
		$this->assertSame( 'complete', $artifacts['run_completion_state'] );
		$this->assertSame( 1, $artifacts['finalization_summary']['created'] ?? 0 );
		$def = $repo->last_save['definition'];
		$this->assertArrayHasKey( 'run_completion_state', $def );
		$this->assertSame( 'complete', $def['run_completion_state'] );
	}
}
