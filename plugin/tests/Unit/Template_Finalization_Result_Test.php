<?php
/**
 * Unit tests for Template_Finalization_Result (spec §59.10; Prompt 208).
 *
 * Covers to_payload(), run_completion_state constants, and example finalization summary payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Finalize\Template_Finalization_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Finalize/Template_Finalization_Result.php';

final class Template_Finalization_Result_Test extends TestCase {

	/** Example finalization summary payload (spec §59.10; Prompt 208). */
	public static function example_finalization_summary_payload(): array {
		return array(
			'finalization_summary'              => array(
				'created'                       => 3,
				'replaced'                      => 1,
				'updated'                       => 2,
				'skipped'                       => 0,
				'failed'                        => 0,
				'pending'                       => 0,
				'published'                     => 2,
				'completed_without_publication' => 4,
				'blocked'                       => 0,
				'denied'                        => 0,
			),
			'template_execution_closure_record' => array(
				array(
					'plan_item_id'    => 'item-1',
					'item_type'       => 'new_page',
					'action_taken'    => 'create',
					'template_key'    => 'tpl_services_hub',
					'template_family' => 'services',
					'post_id'         => 10,
					'one_pager_ref'   => 'doc/one-pager-services-hub',
				),
				array(
					'plan_item_id' => 'item-2',
					'item_type'    => 'existing_page_change',
					'action_taken' => 'replace',
					'template_key' => 'tpl_about',
					'post_id'      => 11,
				),
			),
			'run_completion_state'              => Template_Finalization_Result::RUN_STATE_COMPLETE,
			'one_pager_retention_summary'       => array(
				'tpl_services_hub' => array(
					'count'          => 2,
					'one_pager_refs' => array( 'doc/one-pager-services-hub' ),
				),
			),
		);
	}

	public function test_to_payload_includes_all_stable_keys(): void {
		$summary = array(
			'created'  => 1,
			'replaced' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'failed'   => 0,
			'pending'  => 0,
			'blocked'  => 0,
			'denied'   => 0,
		);
		$closure = array(
			array(
				'plan_item_id' => 'i1',
				'action_taken' => 'create',
			),
		);
		$result  = new Template_Finalization_Result( $summary, $closure, Template_Finalization_Result::RUN_STATE_COMPLETE, array() );
		$payload = $result->to_payload();
		$this->assertArrayHasKey( 'finalization_summary', $payload );
		$this->assertArrayHasKey( 'template_execution_closure_record', $payload );
		$this->assertArrayHasKey( 'run_completion_state', $payload );
		$this->assertArrayHasKey( 'one_pager_retention_summary', $payload );
		$this->assertSame( $summary, $payload['finalization_summary'] );
		$this->assertSame( $closure, $payload['template_execution_closure_record'] );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_COMPLETE, $payload['run_completion_state'] );
	}

	public function test_run_completion_state_constants(): void {
		$this->assertSame( 'complete', Template_Finalization_Result::RUN_STATE_COMPLETE );
		$this->assertSame( 'warning', Template_Finalization_Result::RUN_STATE_WARNING );
		$this->assertSame( 'partial', Template_Finalization_Result::RUN_STATE_PARTIAL );
		$this->assertSame( 'failed', Template_Finalization_Result::RUN_STATE_FAILED );
	}

	public function test_example_finalization_summary_payload_has_required_keys(): void {
		$payload = self::example_finalization_summary_payload();
		$this->assertArrayHasKey( 'finalization_summary', $payload );
		$this->assertArrayHasKey( 'template_execution_closure_record', $payload );
		$this->assertArrayHasKey( 'run_completion_state', $payload );
		$this->assertArrayHasKey( 'one_pager_retention_summary', $payload );
		$this->assertArrayHasKey( 'created', $payload['finalization_summary'] );
		$this->assertArrayHasKey( 'replaced', $payload['finalization_summary'] );
		$this->assertArrayHasKey( 'updated', $payload['finalization_summary'] );
		$this->assertArrayHasKey( 'skipped', $payload['finalization_summary'] );
		$this->assertArrayHasKey( 'failed', $payload['finalization_summary'] );
		$this->assertSame( Template_Finalization_Result::RUN_STATE_COMPLETE, $payload['run_completion_state'] );
	}
}
