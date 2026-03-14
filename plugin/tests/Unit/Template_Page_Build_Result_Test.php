<?php
/**
 * Unit tests for Template_Page_Build_Result (spec §33.5, §33.9; Prompt 194).
 *
 * Covers success/failure factory, to_array shape, hierarchy/one-pager/section_count, example payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Pages\Template_Page_Build_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Pages/Template_Page_Build_Result.php';

final class Template_Page_Build_Result_Test extends TestCase {

	public function test_success_result_to_array_has_required_keys(): void {
		$result = Template_Page_Build_Result::success(
			42,
			'tpl_services_hub',
			'services',
			'hub',
			true,
			10,
			true,
			array( 'doc_ref' => 'one-pager' ),
			5,
			3,
			array(),
			'log_1'
		);
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 42, $result->get_post_id() );
		$this->assertSame( 'tpl_services_hub', $result->get_template_key() );
		$this->assertSame( 'services', $result->get_template_family() );
		$this->assertTrue( $result->is_hierarchy_applied() );
		$this->assertSame( 10, $result->get_parent_post_id() );
		$this->assertTrue( $result->is_one_pager_available() );
		$this->assertSame( 5, $result->get_section_count() );
		$this->assertSame( 3, $result->get_field_assignment_count() );

		$arr = $result->to_array();
		$this->assertSame( true, $arr['success'] );
		$this->assertSame( 42, $arr['post_id'] );
		$this->assertSame( 'tpl_services_hub', $arr['template_key'] );
		$this->assertSame( 'services', $arr['template_family'] );
		$this->assertSame( true, $arr['hierarchy_applied'] );
		$this->assertSame( 10, $arr['parent_post_id'] );
		$this->assertSame( true, $arr['one_pager_available'] );
		$this->assertSame( 5, $arr['section_count'] );
		$this->assertSame( 3, $arr['field_assignment_count'] );
		$this->assertSame( array(), $arr['warnings'] );
		$this->assertSame( array(), $arr['errors'] );
	}

	public function test_failure_result_has_errors_and_template_key(): void {
		$result = Template_Page_Build_Result::failure( 'Template not found.', array( 'template_not_found' ), 'tpl_missing', 'log_2' );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_post_id() );
		$this->assertSame( 'tpl_missing', $result->get_template_key() );
		$this->assertSame( array( 'template_not_found' ), $result->get_errors() );
		$arr = $result->to_array();
		$this->assertSame( false, $arr['success'] );
		$this->assertSame( array( 'template_not_found' ), $arr['errors'] );
	}

	public function test_example_payload_has_all_template_build_execution_result_keys(): void {
		$payload = Template_Page_Build_Result::example_payload();
		$this->assertTrue( $payload['success'] );
		$this->assertSame( 42, $payload['post_id'] );
		$this->assertSame( 'tpl_services_hub', $payload['template_key'] );
		$this->assertSame( 'services', $payload['template_family'] );
		$this->assertSame( 'hub', $payload['template_category_class'] );
		$this->assertTrue( $payload['hierarchy_applied'] );
		$this->assertSame( 10, $payload['parent_post_id'] );
		$this->assertTrue( $payload['one_pager_available'] );
		$this->assertSame( 5, $payload['section_count'] );
		$this->assertSame( 3, $payload['field_assignment_count'] );
		$this->assertArrayHasKey( 'warnings', $payload );
		$this->assertArrayHasKey( 'errors', $payload );
		$this->assertArrayHasKey( 'log_ref', $payload );
		$this->assertArrayHasKey( 'message', $payload );
	}

	public function test_success_with_warnings(): void {
		$result = Template_Page_Build_Result::success(
			1,
			'tpl_a',
			'',
			'',
			false,
			0,
			false,
			array(),
			0,
			0,
			array( 'Section X optional and omitted.' ),
			''
		);
		$this->assertSame( array( 'Section X optional and omitted.' ), $result->get_warnings() );
		$this->assertSame( array( 'Section X optional and omitted.' ), $result->to_array()['warnings'] );
	}
}
