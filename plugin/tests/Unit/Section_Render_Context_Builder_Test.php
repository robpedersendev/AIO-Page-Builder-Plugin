<?php
/**
 * Unit tests for Section_Render_Context_Builder: valid context creation,
 * invalid/incomplete context rejection (spec §17, Prompt 043).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';

final class Section_Render_Context_Builder_Test extends TestCase {

	private function valid_definition(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => 'st01_hero',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array( 'label' => 'Default' ),
				'compact' => array( 'label' => 'Compact' ),
			),
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st01',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
		);
	}

	public function test_build_returns_context_when_definition_valid(): void {
		$builder      = new Section_Render_Context_Builder();
		$def          = $this->valid_definition();
		$field_values = array(
			'headline'    => 'Test Headline',
			'subheadline' => 'Sub',
		);

		$out = $builder->build( $def, $field_values, 1, null );

		$this->assertEmpty( $out['errors'] );
		$this->assertInstanceOf( Section_Render_Context::class, $out['context'] );
		$this->assertSame( $def, $out['context']->get_section_definition() );
		$this->assertSame( 1, $out['context']->get_position() );
		$this->assertArrayHasKey( 'headline', $out['context']->get_field_values() );
		$this->assertSame( 'Test Headline', $out['context']->get_field_values()['headline'] );
	}

	public function test_build_rejects_missing_internal_key(): void {
		$builder = new Section_Render_Context_Builder();
		$def     = $this->valid_definition();
		unset( $def[ Section_Schema::FIELD_INTERNAL_KEY ] );

		$out = $builder->build( $def, array(), 0, null );

		$this->assertNotEmpty( $out['errors'] );
		$this->assertNull( $out['context'] );
		$this->assertStringContainsString( 'internal_key', implode( ' ', $out['errors'] ) );
	}

	public function test_build_rejects_empty_variants(): void {
		$builder                               = new Section_Render_Context_Builder();
		$def                                   = $this->valid_definition();
		$def[ Section_Schema::FIELD_VARIANTS ] = array();

		$out = $builder->build( $def, array(), 0, null );

		$this->assertNotEmpty( $out['errors'] );
		$this->assertNull( $out['context'] );
	}

	public function test_build_rejects_default_variant_not_in_variants(): void {
		$builder = new Section_Render_Context_Builder();
		$def     = $this->valid_definition();
		$def[ Section_Schema::FIELD_DEFAULT_VARIANT ] = 'missing_variant';

		$out = $builder->build( $def, array(), 0, null );

		$this->assertNotEmpty( $out['errors'] );
		$this->assertNull( $out['context'] );
		$this->assertStringContainsString( 'default_variant', implode( ' ', $out['errors'] ) );
	}

	public function test_build_rejects_invalid_variant_override(): void {
		$builder = new Section_Render_Context_Builder();
		$def     = $this->valid_definition();

		$out = $builder->build( $def, array(), 0, 'invalid_override' );

		$this->assertNotEmpty( $out['errors'] );
		$this->assertNull( $out['context'] );
		$this->assertStringContainsString( 'Variant override', implode( ' ', $out['errors'] ) );
	}

	public function test_build_accepts_valid_variant_override(): void {
		$builder = new Section_Render_Context_Builder();
		$def     = $this->valid_definition();

		$out = $builder->build( $def, array(), 0, 'compact' );

		$this->assertEmpty( $out['errors'] );
		$this->assertSame( 'compact', $out['context']->get_variant_override() );
	}

	public function test_validate_definition_returns_errors_for_invalid_key_pattern(): void {
		$builder                                   = new Section_Render_Context_Builder();
		$def                                       = $this->valid_definition();
		$def[ Section_Schema::FIELD_INTERNAL_KEY ] = 'Invalid-Key';

		$errors = $builder->validate_definition( $def, null );

		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'pattern', implode( ' ', $errors ) );
	}

	public function test_sanitizes_field_values(): void {
		$builder      = new Section_Render_Context_Builder();
		$def          = $this->valid_definition();
		$field_values = array( 'headline' => '  <script>alert(1)</script>  Headline  ' );

		$out = $builder->build( $def, $field_values, 0, null );

		$this->assertEmpty( $out['errors'] );
		$values = $out['context']->get_field_values();
		$this->assertArrayHasKey( 'headline', $values );
		$this->assertStringNotContainsString( '<script>', (string) $values['headline'] );
	}
}
