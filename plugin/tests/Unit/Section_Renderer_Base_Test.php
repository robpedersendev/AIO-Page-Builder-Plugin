<?php
/**
 * Unit tests for Section_Renderer_Base: selector-map preservation,
 * variant/modifier handling, render-ready structure (spec §17, css-selector-contract, Prompt 043).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Result;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Renderer_Base.php';

final class Section_Renderer_Base_Test extends TestCase {

	private function valid_definition( string $key = 'st01_hero', string $structural_ref = 'bp_st01' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY            => $key,
			Section_Schema::FIELD_VARIANTS                => array(
				'default' => array( 'label' => 'Default' ),
				'compact' => array( 'label' => 'Compact', 'css_modifiers' => array( 'aio-s-st01_hero--theme-dark' ) ),
			),
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => $structural_ref,
			Section_Schema::FIELD_ASSET_DECLARATION      => array( 'none' => true ),
		);
	}

	private function build_context( array $definition, array $field_values = array(), int $position = 0, ?string $variant_override = null ): Section_Render_Context {
		$builder = new Section_Render_Context_Builder();
		$out = $builder->build( $definition, $field_values, $position, $variant_override );
		$this->assertEmpty( $out['errors'], 'Context build should have no errors: ' . implode( '; ', $out['errors'] ) );
		$this->assertNotNull( $out['context'] );
		return $out['context'];
	}

	public function test_render_produces_valid_result(): void {
		$def = $this->valid_definition();
		$context = $this->build_context( $def, array( 'headline' => 'Hello' ), 2 );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );

		$this->assertInstanceOf( Section_Render_Result::class, $result );
		$this->assertTrue( $result->is_valid() );
		$this->assertSame( 'st01_hero', $result->get_section_key() );
		$this->assertSame( 'default', $result->get_variant() );
		$this->assertSame( 2, $result->get_position() );
		$this->assertSame( 'Hello', $result->get_field_values()['headline'] );
	}

	public function test_selector_map_preserves_css_contract(): void {
		$def = $this->valid_definition( 'st05_faq' );
		$context = $this->build_context( $def, array(), 1 );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );
		$selector_map = $result->get_selector_map();

		$this->assertSame( 'aio-s-st05_faq', $selector_map['wrapper_class'] );
		$this->assertSame( 'aio-s-st05_faq__inner', $selector_map['inner_class'] );
		$this->assertArrayHasKey( 'inner', $selector_map['element_classes'] );
		$this->assertSame( 'aio-s-st05_faq__inner', $selector_map['element_classes']['inner'] );
	}

	public function test_wrapper_attrs_include_id_and_data_attributes(): void {
		$def = $this->valid_definition( 'st01_hero' );
		$context = $this->build_context( $def, array(), 3 );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );
		$attrs = $result->get_wrapper_attrs();

		$this->assertSame( 'aio-section-st01_hero-3', $attrs['id'] );
		$this->assertArrayHasKey( 'data_attributes', $attrs );
		$this->assertSame( 'st01_hero', $attrs['data_attributes']['data-aio-section'] );
		$this->assertSame( 'default', $attrs['data_attributes']['data-aio-variant'] );
		$this->assertSame( '3', $attrs['data_attributes']['data-aio-position'] );
		$this->assertContains( 'aio-s-st01_hero', $attrs['class'] );
		$this->assertContains( 'aio-s-st01_hero--variant-default', $attrs['class'] );
	}

	public function test_variant_override_applied(): void {
		$def = $this->valid_definition();
		$context = $this->build_context( $def, array(), 0, 'compact' );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );

		$this->assertSame( 'compact', $result->get_variant() );
		$this->assertContains( 'aio-s-st01_hero--variant-compact', $result->get_wrapper_attrs()['class'] );
	}

	public function test_css_modifiers_from_variant_descriptor_included(): void {
		$def = $this->valid_definition();
		$context = $this->build_context( $def, array(), 0, 'compact' );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );
		$classes = $result->get_wrapper_attrs()['class'];

		$this->assertContains( 'aio-s-st01_hero--theme-dark', $classes );
	}

	public function test_structural_nodes_include_wrapper_and_inner(): void {
		$def = $this->valid_definition();
		$context = $this->build_context( $def, array(), 0 );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );
		$nodes = $result->get_structural_nodes();

		$this->assertCount( 2, $nodes );
		$this->assertSame( 'wrapper', $nodes[0]['role'] );
		$this->assertSame( 'aio-s-st01_hero', $nodes[0]['class'] );
		$this->assertSame( 'inner', $nodes[1]['role'] );
		$this->assertSame( 'aio-s-st01_hero__inner', $nodes[1]['class'] );
	}

	public function test_structural_hint_and_asset_hints_preserved(): void {
		$def = $this->valid_definition( 'st01_hero', 'blueprint_st01_structure' );
		$def[ Section_Schema::FIELD_ASSET_DECLARATION ] = array( 'frontend_css' => true );
		$context = $this->build_context( $def, array(), 0 );
		$renderer = new Section_Renderer_Base();

		$result = $renderer->render( $context );

		$this->assertSame( 'blueprint_st01_structure', $result->get_structural_hint() );
		$this->assertSame( array( 'frontend_css' => true ), $result->get_asset_hints() );
	}

	public function test_to_array_returns_stable_payload_shape(): void {
		$def = $this->valid_definition();
		$context = $this->build_context( $def, array( 'headline' => 'Title' ), 0 );
		$renderer = new Section_Renderer_Base();
		$result = $renderer->render( $context );

		$arr = $result->to_array();

		$this->assertArrayHasKey( 'section_key', $arr );
		$this->assertArrayHasKey( 'variant', $arr );
		$this->assertArrayHasKey( 'position', $arr );
		$this->assertArrayHasKey( 'field_values', $arr );
		$this->assertArrayHasKey( 'wrapper_attrs', $arr );
		$this->assertArrayHasKey( 'selector_map', $arr );
		$this->assertArrayHasKey( 'structural_nodes', $arr );
		$this->assertArrayHasKey( 'structural_hint', $arr );
		$this->assertArrayHasKey( 'asset_hints', $arr );
		$this->assertArrayHasKey( 'accessibility_notes', $arr );
		$this->assertArrayHasKey( 'errors', $arr );
	}
}
