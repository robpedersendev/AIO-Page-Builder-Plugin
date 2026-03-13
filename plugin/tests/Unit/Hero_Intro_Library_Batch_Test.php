<?php
/**
 * Unit tests for hero/intro library batch (SEC-01): registry validation, ACF blueprint integrity,
 * preview/semantic/animation metadata, export serialization (spec §12, §15, §20, §51, Prompt 147).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\HeroBatch\Hero_Intro_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context_Builder;
use AIOPageBuilder\Domain\Rendering\Section\Section_Renderer_Base;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/HeroBatch/Hero_Intro_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/HeroBatch/Hero_Intro_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Renderer_Base.php';

final class Hero_Intro_Library_Batch_Test extends TestCase {

	private Section_Validator $validator;
	private Section_Field_Blueprint_Service $blueprint_service;
	private Section_Render_Context_Builder $context_builder;
	private Section_Renderer_Base $renderer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']     = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$repo       = new Section_Template_Repository();
		$normalizer = new Section_Definition_Normalizer();
		$this->validator = new Section_Validator( $normalizer, $repo );
		$bp_validator = new Section_Field_Blueprint_Validator();
		$this->blueprint_service = new Section_Field_Blueprint_Service(
			$repo,
			$bp_validator,
			new Section_Field_Blueprint_Normalizer( $bp_validator )
		);
		$this->context_builder = new Section_Render_Context_Builder();
		$this->renderer = new Section_Renderer_Base();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_hero_batch_section_keys_match_definitions(): void {
		$keys = Hero_Intro_Library_Batch_Definitions::section_keys();
		$this->assertCount( 12, $keys );
		$expected = array(
			'hero_conv_01', 'hero_conv_02', 'hero_cred_01', 'hero_edu_01', 'hero_local_01', 'hero_dir_01',
			'hero_prod_01', 'hero_legal_01', 'hero_edit_01', 'hero_compact_01', 'hero_media_01', 'hero_split_01',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = Hero_Intro_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 12, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertNotSame( '', $key );
			$blueprint = $def['field_blueprint'] ?? null;
			$this->assertIsArray( $blueprint, "Section {$key} must have embedded field_blueprint" );
			$result = $this->blueprint_service->validate_and_normalize(
				$blueprint,
				$key,
				(string) ( $def[ Section_Schema::FIELD_FIELD_BLUEPRINT_REF ] ?? '' )
			);
			$this->assertEmpty( $result['errors'], 'Blueprint for ' . $key . ' should validate: ' . implode( ', ', $result['errors'] ) );
			$this->assertNotNull( $result['normalized'] );
		}
	}

	public function test_get_blueprint_from_definition_returns_non_null_for_each_hero_section(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	/** Minimal field values shared by all hero sections (common + optional hero_image/split_image). */
	private function minimal_hero_values(): array {
		return array(
			'headline'       => '',
			'subheadline'    => '',
			'eyebrow'        => '',
			'primary_cta'    => array(),
			'secondary_cta'  => array(),
			'hero_image'     => array(),
			'split_image'    => array(),
		);
	}

	public function test_each_definition_builds_render_context_without_errors(): void {
		$values = $this->minimal_hero_values();
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$out = $this->context_builder->build( $def, $values, 0, null );
			$this->assertEmpty( $out['errors'], 'Render context for ' . $key . ' should build: ' . implode( ', ', $out['errors'] ) );
			$this->assertInstanceOf( \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context::class, $out['context'] );
		}
	}

	public function test_each_definition_render_produces_selector_map(): void {
		$values = $this->minimal_hero_values();
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$out = $this->context_builder->build( $def, $values, 0, null );
			$this->assertEmpty( $out['errors'] );
			$result = $this->renderer->render( $out['context'] );
			$selector_map = $result->get_selector_map();
			$this->assertArrayHasKey( 'wrapper_class', $selector_map );
			$this->assertSame( 'aio-s-' . $key, $selector_map['wrapper_class'] );
			$this->assertArrayHasKey( 'inner_class', $selector_map );
			$this->assertSame( 'aio-s-' . $key . '__inner', $selector_map['inner_class'] );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$helper_ref = (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
			$css_ref = (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' );
			$this->assertNotSame( '', $helper_ref, "Section {$key} must have non-empty helper_ref" );
			$this->assertNotSame( '', $css_ref, "Section {$key} must have non-empty css_contract_ref" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'preview_description', $def, "Section {$key} must have preview_description" );
			$this->assertNotEmpty( (string) ( $def['preview_description'] ?? '' ), "Section {$key} preview_description must be non-empty" );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertContains( (string) ( $def['animation_tier'] ?? '' ), array( 'none', 'subtle', 'moderate', 'full' ), "Section {$key} animation_tier must be allowed value" );
			$this->assertArrayHasKey( 'animation_families', $def );
			$this->assertIsArray( $def['animation_families'] ?? null );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] ?? null );
		}
	}

	public function test_each_definition_section_purpose_family_is_hero(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( Hero_Intro_Library_Batch_Definitions::PURPOSE_FAMILY, (string) ( $def['section_purpose_family'] ?? '' ) );
		}
	}

	public function test_each_definition_has_semantic_accessibility_guidance(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = $def['accessibility_warnings_or_enhancements'] ?? '';
			$this->assertNotEmpty( (string) $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
		}
	}

	public function test_hero_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( Hero_Intro_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_seeder_run_returns_success_and_twelve_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 100;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Section_Template_Repository();
		$result = Hero_Intro_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 12, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
