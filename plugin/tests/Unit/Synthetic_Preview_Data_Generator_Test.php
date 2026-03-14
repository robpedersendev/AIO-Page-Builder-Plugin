<?php
/**
 * Unit tests for synthetic preview data generator and side-panel builder (spec §17.1, template-preview-and-dummy-data-contract, Prompt 170).
 *
 * Covers: deterministic generation, family-aware field coverage, omission-case generation,
 * animation-preview metadata, side-panel payload completeness. Example payloads for section and page at end.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Side_Panel_Builder;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Context;
use AIOPageBuilder\Domain\Preview\Synthetic_Preview_Data_Generator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Context.php';
require_once $plugin_root . '/src/Domain/Preview/Synthetic_Preview_Data_Generator.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Side_Panel_Builder.php';

final class Synthetic_Preview_Data_Generator_Test extends TestCase {

	private Synthetic_Preview_Data_Generator $generator;
	private Preview_Side_Panel_Builder $side_panel_builder;

	protected function setUp(): void {
		parent::setUp();
		$this->generator         = new Synthetic_Preview_Data_Generator();
		$this->side_panel_builder = new Preview_Side_Panel_Builder();
	}

	public function test_context_for_section_has_type_and_key(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_hero_01', 'hero' );
		$this->assertTrue( $ctx->is_section() );
		$this->assertSame( 'section', $ctx->get_type() );
		$this->assertSame( 'st_hero_01', $ctx->get_key() );
		$this->assertSame( 'hero', $ctx->get_purpose_family() );
		$this->assertSame( 'default', $ctx->get_variant() );
		$this->assertSame( Synthetic_Preview_Context::ANIMATION_TIER_NONE, $ctx->get_animation_tier() );
	}

	public function test_context_for_page_has_category_and_family(): void {
		$ctx = Synthetic_Preview_Context::for_page( 'pt_home', 'top_level', 'home' );
		$this->assertTrue( $ctx->is_page() );
		$this->assertSame( 'page', $ctx->get_type() );
		$this->assertSame( 'pt_home', $ctx->get_key() );
		$this->assertSame( 'home', $ctx->get_purpose_family() );
		$this->assertSame( 'top_level', $ctx->get_template_category_class() );
	}

	public function test_reduced_motion_forces_animation_tier_none(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_hero', 'hero', 'default', true, Synthetic_Preview_Context::ANIMATION_TIER_ENHANCED );
		$this->assertTrue( $ctx->is_reduced_motion() );
		$this->assertSame( Synthetic_Preview_Context::ANIMATION_TIER_NONE, $ctx->get_animation_tier() );
	}

	public function test_generate_for_section_hero_is_deterministic(): void {
		$ctx  = Synthetic_Preview_Context::for_section( 'st_hero', 'hero' );
		$one  = $this->generator->generate_for_section( $ctx );
		$two  = $this->generator->generate_for_section( $ctx );
		$this->assertSame( $one, $two );
		$this->assertArrayHasKey( 'headline', $one );
		$this->assertSame( 'Welcome to Our Service', $one['headline'] );
		$this->assertArrayHasKey( 'cta_text', $one );
		$this->assertArrayHasKey( 'cta_url', $one );
		$this->assertSame( '#', $one['cta_url'] );
	}

	public function test_generate_for_section_proof_has_items_repeater(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_proof', 'proof' );
		$out = $this->generator->generate_for_section( $ctx );
		$this->assertArrayHasKey( 'headline', $out );
		$this->assertArrayHasKey( 'items', $out );
		$this->assertIsArray( $out['items'] );
		$this->assertGreaterThanOrEqual( 2, count( $out['items'] ) );
		$this->assertArrayHasKey( 'name', $out['items'][0] );
		$this->assertArrayHasKey( 'quote', $out['items'][0] );
	}

	public function test_generate_for_section_faq_has_question_answer_items(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_faq', 'faq' );
		$out = $this->generator->generate_for_section( $ctx );
		$this->assertArrayHasKey( 'items', $out );
		$this->assertArrayHasKey( 'question', $out['items'][0] );
		$this->assertArrayHasKey( 'answer', $out['items'][0] );
	}

	public function test_omission_case_optional_empty_includes_empty_optional_fields(): void {
		$ctx = Synthetic_Preview_Context::for_section( 'st_hero', 'hero', 'default', false, Synthetic_Preview_Context::ANIMATION_TIER_NONE, Synthetic_Preview_Context::OMISSION_CASE_OPTIONAL_EMPTY );
		$out = $this->generator->generate_for_section( $ctx );
		$this->assertArrayHasKey( 'eyebrow', $out );
		$this->assertSame( '', $out['eyebrow'] );
	}

	public function test_generate_for_page_returns_section_field_values_per_position(): void {
		$ctx = Synthetic_Preview_Context::for_page( 'pt_landing', 'top_level', 'home' );
		$ordered = array(
			array( 'section_key' => 'st_hero', 'position' => 0, 'purpose_family' => 'hero' ),
			array( 'section_key' => 'st_cta', 'position' => 1, 'purpose_family' => 'cta' ),
		);
		$out = $this->generator->generate_for_page( $ctx, $ordered );
		$this->assertCount( 2, $out );
		$this->assertSame( 'st_hero', $out[0]['section_key'] );
		$this->assertSame( 0, $out[0]['position'] );
		$this->assertSame( 'Welcome to Our Service', $out[0]['field_values']['headline'] );
		$this->assertSame( 'st_cta', $out[1]['section_key'] );
		$this->assertSame( 'Ready to get started?', $out[1]['field_values']['headline'] );
	}

	public function test_fallback_for_field_type_returns_safe_values(): void {
		$this->assertSame( '#', Synthetic_Preview_Data_Generator::fallback_for_field_type( 'url' ) );
		$this->assertSame( 'Heading', Synthetic_Preview_Data_Generator::fallback_for_field_type( 'text' ) );
		$this->assertSame( 'Body copy for this section.', Synthetic_Preview_Data_Generator::fallback_for_field_type( 'textarea' ) );
		$this->assertSame( '', Synthetic_Preview_Data_Generator::fallback_for_field_type( 'image' ) );
		$this->assertIsArray( Synthetic_Preview_Data_Generator::fallback_for_field_type( 'repeater' ) );
	}

	public function test_side_panel_for_section_includes_required_metadata(): void {
		$def = array(
			'internal_key'           => 'st_hero_01',
			'name'                   => 'Hero with CTA',
			'purpose_summary'        => 'Primary hero section.',
			'section_purpose_family'  => 'hero',
			'cta_classification'     => '',
			'placement_tendency'     => 'opener',
			'variants'               => array( 'default' => array( 'label' => 'Default' ) ),
			'helper_ref'             => 'hero_helper',
			'field_blueprint_ref'    => 'acf_hero',
		);
		$ctx   = Synthetic_Preview_Context::for_section( 'st_hero_01', 'hero', 'default', true );
		$panel = $this->side_panel_builder->build_for_section( $def, $ctx );
		$this->assertSame( 'Hero with CTA', $panel['name'] );
		$this->assertSame( 'hero', $panel['purpose_family'] );
		$this->assertSame( 'opener', $panel['placement_tendency'] );
		$this->assertTrue( $panel['reduced_motion'] );
		$this->assertSame( Synthetic_Preview_Context::ANIMATION_TIER_NONE, $panel['animation_tier'] );
	}

	public function test_side_panel_for_page_includes_used_sections_and_category(): void {
		$def = array(
			'internal_key'             => 'pt_home',
			'name'                     => 'Home',
			'purpose_summary'          => 'Landing page for home.',
			'template_category_class'   => 'top_level',
			'template_family'          => 'home',
			'ordered_sections'         => array(
				array( 'section_key' => 'st_hero', 'position' => 0 ),
				array( 'section_key' => 'st_cta', 'position' => 1 ),
			),
		);
		$panel = $this->side_panel_builder->build_for_page( $def, null );
		$this->assertSame( 'Home', $panel['name'] );
		$this->assertSame( 'top_level', $panel['category'] );
		$this->assertSame( 'home', $panel['purpose_cta_direction'] );
		$this->assertCount( 2, $panel['used_sections'] );
		$this->assertSame( 'st_hero', $panel['used_sections'][0]['section_key'] );
	}

	public function test_build_section_preview_payload_combines_field_values_and_side_panel(): void {
		$def = array( 'internal_key' => 'st_hero', 'name' => 'Hero', 'purpose_summary' => 'Hero section.', 'section_purpose_family' => 'hero' );
		$ctx  = Synthetic_Preview_Context::for_section( 'st_hero', 'hero' );
		$field_values = $this->generator->generate_for_section( $ctx );
		$payload = $this->side_panel_builder->build_section_preview_payload( $def, $field_values, $ctx );
		$this->assertSame( 'st_hero', $payload['section_key'] );
		$this->assertSame( $field_values, $payload['field_values'] );
		$this->assertArrayHasKey( 'side_panel', $payload );
		$this->assertArrayHasKey( 'options', $payload );
		$this->assertSame( 'none', $payload['options']['animation_tier'] );
	}

	public function test_build_page_preview_payload_combines_section_field_values_and_side_panel(): void {
		$def = array( 'internal_key' => 'pt_landing', 'name' => 'Landing', 'purpose_summary' => 'Landing page.', 'template_category_class' => 'top_level', 'template_family' => 'home', 'ordered_sections' => array() );
		$ctx  = Synthetic_Preview_Context::for_page( 'pt_landing', 'top_level', 'home' );
		$section_field_values = $this->generator->generate_for_page( $ctx, array( array( 'section_key' => 'st_hero', 'position' => 0, 'purpose_family' => 'hero' ) ) );
		$payload = $this->side_panel_builder->build_page_preview_payload( $def, $section_field_values, $ctx );
		$this->assertSame( 'pt_landing', $payload['template_key'] );
		$this->assertCount( 1, $payload['section_field_values'] );
		$this->assertArrayHasKey( 'side_panel', $payload );
	}

	/**
	 * Example synthetic preview context and payload for a section template (template-preview §7.1, §10).
	 * Use this shape for integration with section renderer preview.
	 */
	public function test_example_section_synthetic_preview_context_payload(): void {
		$context = Synthetic_Preview_Context::for_section( 'st01_hero', 'hero', 'default', false, Synthetic_Preview_Context::ANIMATION_TIER_SUBTLE );
		$field_values = $this->generator->generate_for_section( $context );
		$this->assertSame( 'section', $context->get_type() );
		$this->assertSame( 'st01_hero', $context->get_key() );
		$this->assertArrayHasKey( 'headline', $field_values );
		$this->assertArrayHasKey( 'subheadline', $field_values );
		$this->assertArrayHasKey( 'cta_text', $field_values );
		$this->assertArrayHasKey( 'cta_url', $field_values );
		// Example payload shape (documentation):
		$example = array(
			'section_key'  => $context->get_key(),
			'variant'      => $context->get_variant(),
			'field_values' => $field_values,
			'context'      => $context->to_array(),
		);
		$this->assertCount( 4, $example );
		$this->assertSame( 'Welcome to Our Service', $example['field_values']['headline'] );
	}

	/**
	 * Example synthetic preview context and payload for a page template (template-preview §7.2).
	 * Use this shape for integration with page assembler preview.
	 */
	public function test_example_page_synthetic_preview_context_payload(): void {
		$context = Synthetic_Preview_Context::for_page( 'pt_home_landing', 'top_level', 'home', 'default', false, Synthetic_Preview_Context::ANIMATION_TIER_NONE );
		$ordered_sections = array(
			array( 'section_key' => 'st_hero', 'position' => 0, 'purpose_family' => 'hero' ),
			array( 'section_key' => 'st_cta', 'position' => 1, 'purpose_family' => 'cta' ),
		);
		$section_field_values = $this->generator->generate_for_page( $context, $ordered_sections );
		$this->assertSame( 'page', $context->get_type() );
		$this->assertSame( 'pt_home_landing', $context->get_key() );
		$this->assertCount( 2, $section_field_values );
		// Example payload shape (documentation):
		$example = array(
			'template_key'         => $context->get_key(),
			'section_field_values' => $section_field_values,
			'context'              => $context->to_array(),
		);
		$this->assertSame( 'st_hero', $example['section_field_values'][0]['section_key'] );
		$this->assertSame( 'Welcome to Our Service', $example['section_field_values'][0]['field_values']['headline'] );
		$this->assertSame( 'Ready to get started?', $example['section_field_values'][1]['field_values']['headline'] );
	}
}
