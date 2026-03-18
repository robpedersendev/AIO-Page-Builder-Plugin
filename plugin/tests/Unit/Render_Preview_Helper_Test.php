<?php
/**
 * Unit tests for Render_Preview_Helper: section/page/composition preview payloads (spec §17, Prompt 048).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Preview\Render_Preview_Helper;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Preview/Render_Preview_Helper.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';

final class Render_Preview_Helper_Test extends TestCase {

	private function section_result(): Section_Render_Result {
		$structure = array(
			'wrapper_attrs'       => array(
				'class'           => array( 'aio-s-st01_hero', 'aio-s-st01_hero--variant-default' ),
				'id'              => 'aio-section-st01_hero-0',
				'data_attributes' => array(),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-st01_hero',
				'inner_class'     => 'aio-s-st01_hero__inner',
				'element_classes' => array(),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => 'bp_st01',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result(
			'st01_hero',
			'default',
			0,
			array(
				'headline'    => 'Welcome',
				'subheadline' => 'Intro',
			),
			$structure,
			array()
		);
	}

	public function test_build_section_preview(): void {
		$helper  = new Render_Preview_Helper();
		$section = $this->section_result();

		$payload = $helper->build_section_preview( $section );

		$this->assertSame( 'section', $payload['type'] );
		$this->assertSame( 'st01_hero', $payload['section_key'] );
		$this->assertSame( 'default', $payload['variant'] );
		$this->assertSame( 0, $payload['position'] );
		$this->assertContains( 'aio-s-st01_hero', $payload['wrapper_classes'] );
		$this->assertSame( array( 'headline', 'subheadline' ), $payload['field_keys'] );
		$this->assertSame( 'bp_st01', $payload['structural_hint'] );
		$this->assertTrue( $payload['valid'] );
	}

	public function test_build_page_preview_example_payload(): void {
		$assembly = new Page_Block_Assembly_Result(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_landing',
			array(
				array(
					'section_key' => 'st01_hero',
					'variant'     => 'default',
					'position'    => 0,
				),
				array(
					'section_key' => 'st02_cta',
					'variant'     => 'default',
					'position'    => 1,
				),
			),
			'<!-- wp:html --><div class="aio-s-st01_hero">Content</div><!-- /wp:html -->',
			array(),
			array( 'durable_native_blocks', 'generateblocks_compatible' ),
			array()
		);
		$helper   = new Render_Preview_Helper();

		$payload = $helper->build_page_preview( $assembly, true );

		$this->assertSame( 'page', $payload['type'] );
		$this->assertSame( 'page_template', $payload['source_type'] );
		$this->assertSame( 'tpl_landing', $payload['source_key'] );
		$this->assertSame( 2, $payload['section_count'] );
		$this->assertCount( 2, $payload['section_previews'] );
		$this->assertStringContainsString( '<!-- wp:html -->', $payload['block_content_preview'] );
		$this->assertSame( array( 'durable_native_blocks', 'generateblocks_compatible' ), $payload['survivability_notes'] );
		$this->assertArrayHasKey( 'block_content_length', $payload );
	}

	public function test_build_composition_preview(): void {
		$assembly = new Page_Block_Assembly_Result( Page_Block_Assembly_Result::SOURCE_TYPE_COMPOSITION, 'comp_uuid', array(), '<!-- wp:html -->x<!-- /wp:html -->', array(), array(), array() );
		$helper   = new Render_Preview_Helper();

		$comp_payload = $helper->build_composition_preview( $assembly );

		$this->assertSame( 'composition', $comp_payload['type'] );
		$this->assertSame( 'composition', $comp_payload['source_type'] );
		$this->assertSame( 'comp_uuid', $comp_payload['source_key'] );
	}
}
