<?php
/**
 * Unit tests for Template_Diff_Context (spec §59.11; Prompt 197).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rollback\Diff\Template_Diff_Context;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rollback/Diff/Template_Diff_Context.php';

final class Template_Diff_Context_Test extends TestCase {

	public function test_to_array_returns_stable_payload(): void {
		$ctx = new Template_Diff_Context( 'tpl_foo', 'services', 'hub', true, 'v2', 'deprecated_after_2025' );
		$arr = $ctx->to_array();
		$this->assertSame( 'tpl_foo', $arr['template_key'] );
		$this->assertSame( 'services', $arr['template_family'] );
		$this->assertSame( 'hub', $arr['template_variation'] );
		$this->assertTrue( $arr['cta_pattern_shift'] );
		$this->assertSame( 'v2', $arr['version_context'] );
		$this->assertSame( 'deprecated_after_2025', $arr['deprecation_context'] );
	}

	public function test_example_rollback_template_context_payload_has_contract_shape(): void {
		$ex = Template_Diff_Context::example_rollback_template_context_payload();
		$this->assertArrayHasKey( 'template_key', $ex );
		$this->assertArrayHasKey( 'template_family', $ex );
		$this->assertArrayHasKey( 'template_variation', $ex );
		$this->assertArrayHasKey( 'cta_pattern_shift', $ex );
		$this->assertArrayHasKey( 'version_context', $ex );
		$this->assertArrayHasKey( 'deprecation_context', $ex );
		$this->assertSame( 'tpl_services_hub', $ex['template_key'] );
		$this->assertSame( 'services', $ex['template_family'] );
		$this->assertFalse( $ex['cta_pattern_shift'] );
	}
}
