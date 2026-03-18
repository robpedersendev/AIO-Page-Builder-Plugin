<?php
/**
 * Unit tests for Template_Menu_Apply_Result (spec §59.10; Prompt 207).
 *
 * Covers success/failure factories, to_menu_apply_execution_result,
 * to_handler_result, and example menu-apply execution result payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Menus\Template_Menu_Apply_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Menus/Template_Menu_Apply_Result.php';

final class Template_Menu_Apply_Result_Test extends TestCase {

	/** Example menu-apply execution result payload (spec §59.10; Prompt 207). */
	public static function example_menu_apply_execution_result_payload(): array {
		$validation = array(
			'valid'            => true,
			'location_slug'    => 'primary',
			'missing_location' => false,
			'resolved_menu_id' => 10,
		);
		$hierarchy  = array(
			'items_ordered_by_class' => array(
				array(
					'title'      => 'Home',
					'object_id'  => 1,
					'page_class' => 'top_level',
				),
				array(
					'title'      => 'Services',
					'object_id'  => 2,
					'page_class' => 'hub',
				),
				array(
					'title'          => 'Consulting',
					'object_id'      => 3,
					'page_class'     => 'child_detail',
					'parent_page_id' => 2,
				),
			),
			'applied_count'          => 3,
			'warnings'               => array(),
		);
		$per_item   = array(
			array(
				'status'       => 'applied',
				'title'        => 'Home',
				'object_id'    => 1,
				'menu_item_id' => 101,
			),
			array(
				'status'       => 'applied',
				'title'        => 'Services',
				'object_id'    => 2,
				'menu_item_id' => 102,
			),
			array(
				'status'       => 'applied',
				'title'        => 'Consulting',
				'object_id'    => 3,
				'menu_item_id' => 103,
			),
		);
		return array(
			'success'                       => true,
			'message'                       => 'Template-aware menu apply completed.',
			'menu_id'                       => 10,
			'menu_target_validation_result' => $validation,
			'navigation_hierarchy_summary'  => $hierarchy,
			'per_item_status'               => $per_item,
			'errors'                        => array(),
		);
	}

	public function test_success_factory_and_to_menu_apply_execution_result(): void {
		$validation = array(
			'valid'         => true,
			'location_slug' => 'primary',
		);
		$hierarchy  = array(
			'items_ordered_by_class' => array(),
			'applied_count'          => 2,
			'warnings'               => array(),
		);
		$per_item   = array(
			array(
				'status'    => 'applied',
				'title'     => 'A',
				'object_id' => 1,
			),
			array(
				'status'    => 'applied',
				'title'     => 'B',
				'object_id' => 2,
			),
		);
		$result     = Template_Menu_Apply_Result::success( 10, $validation, $hierarchy, $per_item, array( 'items_updated' => 2 ) );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 10, $result->get_menu_id() );
		$payload = $result->to_menu_apply_execution_result();
		$this->assertTrue( $payload['success'] );
		$this->assertSame( 10, $payload['menu_id'] );
		$this->assertSame( $validation, $payload['menu_target_validation_result'] );
		$this->assertSame( $hierarchy, $payload['navigation_hierarchy_summary'] );
		$this->assertSame( $per_item, $payload['per_item_status'] );
		$this->assertArrayHasKey( 'errors', $payload );
	}

	public function test_failure_factory_includes_validation_and_message(): void {
		$validation = array(
			'valid'            => false,
			'location_slug'    => 'primary',
			'missing_location' => true,
		);
		$result     = Template_Menu_Apply_Result::failure(
			'Menu location is not registered.',
			array( 'menu_target_validation_failed' ),
			$validation,
			array(
				'items_ordered_by_class' => array(),
				'applied_count'          => 0,
				'warnings'               => array(),
			),
			array()
		);
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_menu_id() );
		$this->assertContains( 'menu_target_validation_failed', $result->get_errors() );
		$this->assertSame( $validation, $result->get_validation_result() );
	}

	public function test_to_handler_result_includes_artifacts_for_snapshot(): void {
		$validation = array(
			'valid'         => true,
			'location_slug' => 'primary',
		);
		$hierarchy  = array(
			'applied_count'          => 1,
			'items_ordered_by_class' => array(),
			'warnings'               => array(),
		);
		$result     = Template_Menu_Apply_Result::success( 5, $validation, $hierarchy, array( array( 'status' => 'applied' ) ), array() );
		$out        = $result->to_handler_result();
		$this->assertTrue( $out['success'] );
		$this->assertSame( 5, $out['artifacts']['menu_id'] ?? 0 );
		$this->assertArrayHasKey( 'menu_apply_execution_result', $out['artifacts'] );
		$this->assertArrayHasKey( 'navigation_hierarchy_summary', $out['artifacts'] );
		$this->assertArrayHasKey( 'menu_target_validation_result', $out['artifacts'] );
	}

	public function test_example_menu_apply_payload_has_required_keys(): void {
		$payload = self::example_menu_apply_execution_result_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'menu_id', $payload );
		$this->assertArrayHasKey( 'menu_target_validation_result', $payload );
		$this->assertArrayHasKey( 'navigation_hierarchy_summary', $payload );
		$this->assertArrayHasKey( 'per_item_status', $payload );
		$this->assertArrayHasKey( 'valid', $payload['menu_target_validation_result'] );
		$this->assertArrayHasKey( 'items_ordered_by_class', $payload['navigation_hierarchy_summary'] );
		$this->assertArrayHasKey( 'applied_count', $payload['navigation_hierarchy_summary'] );
		$this->assertTrue( $payload['success'] );
	}
}
