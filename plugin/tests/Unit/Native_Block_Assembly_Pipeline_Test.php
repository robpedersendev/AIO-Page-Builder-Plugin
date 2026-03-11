<?php
/**
 * Unit tests for Native_Block_Assembly_Pipeline: deterministic assembly,
 * survivability-friendly output, section order, dynamic_dependencies (spec §17.5, §18, Prompt 044).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Blocks\Native_Block_Assembly_Pipeline;
use AIOPageBuilder\Domain\Rendering\Blocks\Page_Block_Assembly_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Page_Block_Assembly_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Blocks/Native_Block_Assembly_Pipeline.php';

final class Native_Block_Assembly_Pipeline_Test extends TestCase {

	private function section_result( string $section_key, int $position, array $field_values = array(), string $variant = 'default' ): Section_Render_Result {
		$wrapper_class = 'aio-s-' . $section_key;
		$variant_mod   = $wrapper_class . '--variant-' . $variant;
		$inner_class   = $wrapper_class . '__inner';
		$section_id    = 'aio-section-' . $section_key . '-' . $position;
		$structure     = array(
			'wrapper_attrs'       => array(
				'class'           => array( $wrapper_class, $variant_mod ),
				'id'              => $section_id,
				'data_attributes' => array(
					'data-aio-section'  => $section_key,
					'data-aio-variant'  => $variant,
					'data-aio-position' => (string) $position,
				),
			),
			'selector_map'        => array(
				'wrapper_class'   => $wrapper_class,
				'inner_class'     => $inner_class,
				'element_classes' => array( 'inner' => $inner_class ),
			),
			'structural_nodes'    => array(
				array( 'role' => 'wrapper', 'class' => $wrapper_class ),
				array( 'role' => 'inner', 'class' => $inner_class ),
			),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes' => array(),
		);
		return new Section_Render_Result( $section_key, $variant, $position, $field_values, $structure, array() );
	}

	public function test_assemble_produces_valid_result_from_ordered_sections(): void {
		$s1 = $this->section_result( 'st01_hero', 0, array( 'headline' => 'Welcome', 'subheadline' => 'Intro text' ) );
		$s2 = $this->section_result( 'st02_cta', 1, array( 'title' => 'Sign up', 'cta' => 'Get started' ) );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_landing',
			array( $s1, $s2 )
		);

		$this->assertInstanceOf( Page_Block_Assembly_Result::class, $result );
		$this->assertTrue( $result->is_valid() );
		$this->assertSame( Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE, $result->get_source_type() );
		$this->assertSame( 'tpl_landing', $result->get_source_key() );
		$this->assertCount( 2, $result->get_ordered_sections() );
		$this->assertNotEmpty( $result->get_block_content() );
	}

	public function test_block_content_is_deterministic_and_section_order_preserved(): void {
		$s1 = $this->section_result( 'st01_hero', 0, array( 'headline' => 'First' ) );
		$s2 = $this->section_result( 'st02_cta', 1, array( 'title' => 'Second' ) );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_COMPOSITION,
			'comp_001',
			array( $s1, $s2 )
		);

		$content = $result->get_block_content();
		$this->assertStringContainsString( '<!-- wp:html -->', $content );
		$this->assertStringContainsString( '<!-- /wp:html -->', $content );
		$this->assertStringContainsString( 'aio-s-st01_hero', $content );
		$this->assertStringContainsString( 'aio-s-st02_cta', $content );
		$this->assertStringContainsString( 'First', $content );
		$this->assertStringContainsString( 'Second', $content );
		$pos_first = strpos( $content, 'aio-s-st01_hero' );
		$pos_second = strpos( $content, 'aio-s-st02_cta' );
		$this->assertLessThan( $pos_second, $pos_first, 'Section order must be preserved' );

		$ordered = $result->get_ordered_sections();
		$this->assertSame( 'st01_hero', $ordered[0]['section_key'] );
		$this->assertSame( 'st02_cta', $ordered[1]['section_key'] );
	}

	public function test_survivability_friendly_output_and_no_dynamic_dependencies(): void {
		$s1 = $this->section_result( 'st01_hero', 0, array( 'headline' => 'Hello' ) );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_one',
			array( $s1 )
		);

		$this->assertEmpty( $result->get_dynamic_dependencies(), 'Section content must not use render callbacks' );
		$this->assertContains( 'durable_native_blocks', $result->get_survivability_notes() );
		$this->assertStringNotContainsString( 'render_callback', $result->get_block_content() );
	}

	public function test_headline_and_title_mapped_to_h2_others_to_p(): void {
		$s = $this->section_result( 'st01_hero', 0, array(
			'headline'    => 'Main Headline',
			'subheadline' => 'Sub text',
			'cta'         => 'Click here',
		) );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl',
			array( $s )
		);

		$content = $result->get_block_content();
		$this->assertStringContainsString( '<h2>Main Headline</h2>', $content );
		$this->assertStringContainsString( '<p>Sub text</p>', $content );
		$this->assertStringContainsString( '<p>Click here</p>', $content );
	}

	public function test_wrapper_attrs_preserved_in_block_markup(): void {
		$s = $this->section_result( 'st01_hero', 2, array( 'headline' => 'X' ) );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl',
			array( $s )
		);

		$content = $result->get_block_content();
		$this->assertStringContainsString( 'id="aio-section-st01_hero-2"', $content );
		$this->assertStringContainsString( 'data-aio-section="st01_hero"', $content );
		$this->assertStringContainsString( 'data-aio-variant="default"', $content );
		$this->assertStringContainsString( 'data-aio-position="2"', $content );
		$this->assertStringContainsString( 'aio-s-st01_hero__inner', $content );
	}

	public function test_assemble_accepts_array_section_payloads(): void {
		$payload = array(
			'section_key'        => 'st01_hero',
			'variant'            => 'default',
			'position'           => 0,
			'field_values'       => array( 'headline' => 'From array' ),
			'wrapper_attrs'      => array(
				'class'           => array( 'aio-s-st01_hero', 'aio-s-st01_hero--variant-default' ),
				'id'              => 'aio-section-st01_hero-0',
				'data_attributes' => array(
					'data-aio-section'  => 'st01_hero',
					'data-aio-variant'  => 'default',
					'data-aio-position' => '0',
				),
			),
			'selector_map'        => array(
				'wrapper_class'   => 'aio-s-st01_hero',
				'inner_class'     => 'aio-s-st01_hero__inner',
				'element_classes' => array( 'inner' => 'aio-s-st01_hero__inner' ),
			),
			'structural_nodes'    => array(),
			'structural_hint'     => '',
			'asset_hints'         => array(),
			'accessibility_notes'  => array(),
			'errors'              => array(),
		);
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl',
			array( $payload )
		);

		$this->assertCount( 1, $result->get_ordered_sections() );
		$this->assertStringContainsString( 'From array', $result->get_block_content() );
		$this->assertStringContainsString( '<h2>From array</h2>', $result->get_block_content() );
	}

	public function test_invalid_section_item_skipped_and_error_recorded(): void {
		$valid = $this->section_result( 'st01_hero', 0, array( 'headline' => 'OK' ) );
		$invalid = array( 'not_section_key' => 'missing section_key' );
		$pipeline = new Native_Block_Assembly_Pipeline();

		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl',
			array( $valid, $invalid )
		);

		$this->assertCount( 1, $result->get_ordered_sections(), 'Only valid section assembled' );
		$this->assertNotEmpty( $result->get_errors() );
		$this->assertStringContainsString( 'invalid section item', strtolower( $result->get_errors()[0] ) );
		$this->assertContains( 'partial_or_warning', $result->get_survivability_notes() );
	}

	public function test_to_array_returns_stable_page_assembly_shape(): void {
		$s = $this->section_result( 'st01_hero', 0, array( 'headline' => 'T' ) );
		$pipeline = new Native_Block_Assembly_Pipeline();
		$result = $pipeline->assemble(
			Page_Block_Assembly_Result::SOURCE_TYPE_PAGE_TEMPLATE,
			'tpl_x',
			array( $s )
		);

		$arr = $result->to_array();

		$this->assertArrayHasKey( 'source_type', $arr );
		$this->assertArrayHasKey( 'source_key', $arr );
		$this->assertArrayHasKey( 'ordered_sections', $arr );
		$this->assertArrayHasKey( 'block_content', $arr );
		$this->assertArrayHasKey( 'dynamic_dependencies', $arr );
		$this->assertArrayHasKey( 'survivability_notes', $arr );
		$this->assertArrayHasKey( 'errors', $arr );
		$this->assertSame( 'tpl_x', $arr['source_key'] );
	}
}
