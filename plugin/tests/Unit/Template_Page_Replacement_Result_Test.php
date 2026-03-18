<?php
/**
 * Unit tests for Template_Page_Replacement_Result (spec §32.9, §59.11; Prompt 196).
 *
 * Covers to_array, replacement_trace_record, example_payload, success/failure factories.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Replacement_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Replacement_Result.php';

final class Template_Page_Replacement_Result_Test extends TestCase {

	public function test_example_payload_has_required_keys(): void {
		$payload = Template_Page_Replacement_Result::example_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'target_post_id', $payload );
		$this->assertArrayHasKey( 'superseded_post_id', $payload );
		$this->assertArrayHasKey( 'snapshot_ref', $payload );
		$this->assertArrayHasKey( 'template_key', $payload );
		$this->assertArrayHasKey( 'template_family', $payload );
		$this->assertArrayHasKey( 'replacement_trace_record', $payload );
		$this->assertArrayHasKey( 'field_assignment_count', $payload );
		$this->assertArrayHasKey( 'warnings', $payload );
		$this->assertArrayHasKey( 'errors', $payload );
		$this->assertArrayHasKey( 'message', $payload );
		$this->assertTrue( $payload['success'] );
		$this->assertSame( 202, $payload['target_post_id'] );
		$this->assertSame( 101, $payload['superseded_post_id'] );
		$trace = $payload['replacement_trace_record'];
		$this->assertSame( 101, $trace['original_post_id'] );
		$this->assertSame( 202, $trace['new_post_id'] );
		$this->assertSame( 'private', $trace['archive_status'] );
	}

	public function test_success_result_to_array_includes_trace_record(): void {
		$trace  = array(
			'original_post_id' => 50,
			'new_post_id'      => 51,
			'archive_status'   => 'in_place',
			'template_key'     => 'tpl_hub',
			'snapshot_pre_id'  => 'op-snap-pre-1',
		);
		$result = Template_Page_Replacement_Result::success( 51, 50, 'op-snap-pre-1', 'tpl_hub', 'services', $trace, 2, array() );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 51, $result->get_target_post_id() );
		$this->assertSame( 50, $result->get_superseded_post_id() );
		$this->assertSame( $trace, $result->get_replacement_trace_record() );
		$arr = $result->to_array();
		$this->assertSame( $trace, $arr['replacement_trace_record'] );
		$this->assertSame( 'services', $arr['template_family'] );
	}

	public function test_failure_result_has_errors_and_snapshot_ref(): void {
		$result = Template_Page_Replacement_Result::failure( 'Pre-change snapshot required but not provided.', array( 'snapshot_required' ), 'op-snap-1', '' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_target_post_id() );
		$this->assertSame( 'op-snap-1', $result->get_snapshot_ref() );
		$this->assertSame( array( 'snapshot_required' ), $result->get_errors() );
		$arr = $result->to_array();
		$this->assertSame( array(), $arr['replacement_trace_record'] );
	}
}
