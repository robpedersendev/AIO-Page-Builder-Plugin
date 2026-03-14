<?php
/**
 * Unit tests for Bulk_Template_Page_Build_Result (spec §33.6, §33.9, §33.10; Prompt 195).
 *
 * Covers to_array shape, example_payload, slug_collisions, retry_eligible_item_ids, partial_failure.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Pages\Bulk_Template_Page_Build_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Pages/Bulk_Template_Page_Build_Result.php';

final class Bulk_Template_Page_Build_Result_Test extends TestCase {

	public function test_example_payload_has_required_keys(): void {
		$payload = Bulk_Template_Page_Build_Result::example_payload();
		$this->assertArrayHasKey( 'plan_id', $payload );
		$this->assertArrayHasKey( 'batch_id', $payload );
		$this->assertArrayHasKey( 'status', $payload );
		$this->assertArrayHasKey( 'job_refs', $payload );
		$this->assertArrayHasKey( 'item_results', $payload );
		$this->assertArrayHasKey( 'slug_collisions', $payload );
		$this->assertArrayHasKey( 'completed_count', $payload );
		$this->assertArrayHasKey( 'failed_count', $payload );
		$this->assertArrayHasKey( 'refused_count', $payload );
		$this->assertArrayHasKey( 'partial_failure', $payload );
		$this->assertArrayHasKey( 'retry_eligible_item_ids', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertArrayHasKey( 'bulk_plan_snapshot', $payload );
		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_PARTIAL, $payload['status'] );
		$this->assertTrue( $payload['partial_failure'] );
	}

	public function test_result_to_array_includes_slug_collisions_and_retry_eligible(): void {
		$result = new Bulk_Template_Page_Build_Result(
			'plan_1',
			'batch_1',
			Bulk_Template_Page_Build_Result::STATUS_PARTIAL,
			array( 'job_1' ),
			array(
				'item_1' => array(
					'status' => 'completed',
					'job_ref' => 'job_1',
					'post_id' => 10,
					'template_key' => 'tpl_hub',
					'slug_conflict' => false,
					'failure_reason' => '',
					'retry_eligible' => false,
				),
				'item_2' => array(
					'status' => 'refused',
					'job_ref' => '',
					'post_id' => 0,
					'template_key' => 'tpl_child',
					'slug_conflict' => true,
					'failure_reason' => 'Slug conflict.',
					'retry_eligible' => false,
				),
			),
			array( 'item_2', 'about-us' ),
			1,
			0,
			1,
			true,
			array(),
			'1 completed. 1 refused.',
			array( 'envelope_count' => 2 )
		);
		$arr = $result->to_array();
		$this->assertSame( 'plan_1', $arr['plan_id'] );
		$this->assertSame( 'batch_1', $arr['batch_id'] );
		$this->assertSame( array( 'item_2', 'about-us' ), $arr['slug_collisions'] );
		$this->assertSame( 1, $arr['completed_count'] );
		$this->assertSame( 1, $arr['refused_count'] );
		$this->assertTrue( $arr['partial_failure'] );
		$this->assertArrayHasKey( 'item_1', $arr['item_results'] );
		$this->assertSame( 10, $arr['item_results']['item_1']['post_id'] );
		$this->assertTrue( $arr['item_results']['item_2']['slug_conflict'] );
	}

	public function test_result_getters(): void {
		$result = new Bulk_Template_Page_Build_Result(
			'p1',
			'b1',
			Bulk_Template_Page_Build_Result::STATUS_COMPLETED,
			array(),
			array(),
			array( 'dup-slug' ),
			5,
			0,
			0,
			false,
			array( 'item_3' ),
			'Done.',
			array()
		);
		$this->assertSame( 'p1', $result->get_plan_id() );
		$this->assertSame( 'b1', $result->get_batch_id() );
		$this->assertSame( Bulk_Template_Page_Build_Result::STATUS_COMPLETED, $result->get_status() );
		$this->assertSame( array( 'dup-slug' ), $result->get_slug_collisions() );
		$this->assertSame( 5, $result->get_completed_count() );
		$this->assertSame( array( 'item_3' ), $result->get_retry_eligible_item_ids() );
		$this->assertFalse( $result->is_partial_failure() );
	}
}
