<?php
/**
 * Unit tests for Content_Survivability_Checker and Content_Survivability_Result (spec §9.12, §17.3, Prompt 047).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Content_Survivability_Checker;
use AIOPageBuilder\Domain\Rendering\Diagnostics\Content_Survivability_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Diagnostics/Content_Survivability_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Diagnostics/Content_Survivability_Checker.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';

final class Content_Survivability_Checker_Test extends TestCase {

	public function test_passing_content_returns_survivable_result(): void {
		$content = "<!-- wp:html -->\n<div class=\"aio-s-hero\">\n<h2>Welcome</h2>\n<p>Intro</p>\n</div>\n<!-- /wp:html -->";
		$checker = new Content_Survivability_Checker();
		$result  = $checker->check( $content );

		$this->assertInstanceOf( Content_Survivability_Result::class, $result );
		$this->assertTrue( $result->is_survivable() );
		$this->assertTrue( $result->is_deactivation_ready() );
		$this->assertEmpty( $result->get_prohibited_runtime_dependencies() );
		$this->assertContains( 'block_markup_editable_in_block_editor', $result->get_human_editability_notes() );
	}

	public function test_failing_content_with_plugin_shortcode_returns_not_survivable(): void {
		$content = "<!-- wp:html --><div>Before [aio_some_widget] after</div><!-- /wp:html -->";
		$checker = new Content_Survivability_Checker();
		$result  = $checker->check( $content );

		$this->assertFalse( $result->is_survivable() );
		$this->assertFalse( $result->is_deactivation_ready() );
		$this->assertContains( 'plugin_shortcode_detected', $result->get_prohibited_runtime_dependencies() );
	}

	public function test_failing_content_with_unreplaced_token_returns_not_survivable(): void {
		$content = "<!-- wp:html --><p>Hello {{ first_name }} welcome</p><!-- /wp:html -->";
		$checker = new Content_Survivability_Checker();
		$result  = $checker->check( $content );

		$this->assertFalse( $result->is_survivable() );
		$this->assertContains( 'unreplaced_token_placeholder', $result->get_prohibited_runtime_dependencies() );
	}

	public function test_check_with_context_sets_dynamic_output_flags(): void {
		$content = "<!-- wp:generateblocks/container --><div>X</div><!-- /wp:generateblocks/container -->";
		$context = array( 'survivability_notes' => array( 'durable_native_blocks', 'generateblocks_compatible' ) );
		$checker = new Content_Survivability_Checker();
		$result  = $checker->check( $content, $context );

		$this->assertTrue( $result->is_survivable() );
		$this->assertContains( 'generateblocks_compatible_optional', $result->get_dynamic_output_flags() );
	}

	public function test_check_assembly_result_uses_block_content_and_notes(): void {
		$assembly = new Page_Block_Assembly_Result(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_landing',
			array(),
			'<!-- wp:html --><div>Durable</div><!-- /wp:html -->',
			array(),
			array( 'durable_native_blocks' ),
			array()
		);
		$checker = new Content_Survivability_Checker();
		$result  = $checker->check_assembly_result( $assembly );

		$this->assertTrue( $result->is_survivable() );
		$this->assertEmpty( $result->get_prohibited_runtime_dependencies() );
	}

	public function test_result_to_array_stable_shape(): void {
		$result = new Content_Survivability_Result( true, array(), array( 'optional_gb' ), array( 'editable' ), true );
		$arr    = $result->to_array();

		$this->assertArrayHasKey( 'is_survivable', $arr );
		$this->assertArrayHasKey( 'prohibited_runtime_dependencies', $arr );
		$this->assertArrayHasKey( 'dynamic_output_flags', $arr );
		$this->assertArrayHasKey( 'human_editability_notes', $arr );
		$this->assertArrayHasKey( 'deactivation_readiness', $arr );
		$this->assertTrue( $arr['is_survivable'] );
		$this->assertSame( array( 'optional_gb' ), $arr['dynamic_output_flags'] );
	}
}
