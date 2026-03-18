<?php
/**
 * Unit tests for section expansion pack: registry validation, ACF blueprint validation,
 * render preview, and documentation completeness (spec §12, §20, §17, Prompt 122).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\ExpansionPack\Section_Expansion_Pack_Definitions;
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
require_once $plugin_root . '/src/Domain/Registries/Section/ExpansionPack/Section_Expansion_Pack_Definitions.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Context_Builder.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Render_Result.php';
require_once $plugin_root . '/src/Domain/Rendering/Section/Section_Renderer_Base.php';

final class Section_Expansion_Pack_Test extends TestCase {

	private Section_Validator $validator;
	private Section_Field_Blueprint_Service $blueprint_service;
	private Section_Render_Context_Builder $context_builder;
	private Section_Renderer_Base $renderer;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta']      = array();
		$GLOBALS['_aio_wp_query_posts'] = array();
		$repo                           = new Section_Template_Repository();
		$normalizer                     = new Section_Definition_Normalizer();
		$this->validator                = new Section_Validator( $normalizer, $repo );
		$bp_validator                   = new Section_Field_Blueprint_Validator();
		$this->blueprint_service        = new Section_Field_Blueprint_Service(
			$repo,
			$bp_validator,
			new Section_Field_Blueprint_Normalizer( $bp_validator )
		);
		$this->context_builder          = new Section_Render_Context_Builder();
		$this->renderer                 = new Section_Renderer_Base();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_expansion_pack_section_keys_match_definitions(): void {
		$keys = Section_Expansion_Pack_Definitions::section_keys();
		$this->assertCount( 3, $keys );
		$this->assertContains( Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS, $keys );
		$this->assertContains( Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION, $keys );
		$this->assertContains( Section_Expansion_Pack_Definitions::KEY_FAQ, $keys );

		$all = Section_Expansion_Pack_Definitions::all_definitions();
		$this->assertCount( 3, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors     = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
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

	public function test_each_definition_builds_render_context_without_errors(): void {
		$minimal_values = array(
			Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS => array(
				'headline'   => 'By the numbers',
				'stat_items' => array(
					array(
						'label'  => 'Clients',
						'value'  => '100',
						'suffix' => '+',
					),
				),
			),
			Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION => array(
				'headline'      => 'Get started',
				'subheadline'   => '',
				'primary_cta'   => array(
					'url'   => '#',
					'title' => 'Sign up',
				),
				'secondary_cta' => array(),
			),
			Section_Expansion_Pack_Definitions::KEY_FAQ => array(
				'headline'  => 'FAQ',
				'faq_items' => array(
					array(
						'question' => 'Q?',
						'answer'   => 'A.',
					),
				),
			),
		);
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
			$key    = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$values = $minimal_values[ $key ] ?? array();
			$out    = $this->context_builder->build( $def, $values, 0, null );
			$this->assertEmpty( $out['errors'], 'Render context for ' . $key . ' should build: ' . implode( ', ', $out['errors'] ) );
			$this->assertInstanceOf( \AIOPageBuilder\Domain\Rendering\Section\Section_Render_Context::class, $out['context'] );
		}
	}

	public function test_each_definition_render_produces_selector_map(): void {
		$minimal_values = array(
			Section_Expansion_Pack_Definitions::KEY_STATS_HIGHLIGHTS => array(
				'headline'   => '',
				'stat_items' => array(),
			),
			Section_Expansion_Pack_Definitions::KEY_CTA_CONVERSION => array(
				'headline'      => '',
				'subheadline'   => '',
				'primary_cta'   => array(),
				'secondary_cta' => array(),
			),
			Section_Expansion_Pack_Definitions::KEY_FAQ => array(
				'headline'  => '',
				'faq_items' => array(),
			),
		);
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
			$key    = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$values = $minimal_values[ $key ] ?? array();
			$out    = $this->context_builder->build( $def, $values, 0, null );
			$this->assertEmpty( $out['errors'] );
			$result       = $this->renderer->render( $out['context'] );
			$selector_map = $result->get_selector_map();
			$this->assertArrayHasKey( 'wrapper_class', $selector_map );
			$this->assertSame( 'aio-s-' . $key, $selector_map['wrapper_class'], 'wrapper_class must follow css-selector-contract' );
			$this->assertArrayHasKey( 'inner_class', $selector_map );
			$this->assertSame( 'aio-s-' . $key . '__inner', $selector_map['inner_class'] );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
			$key        = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$helper_ref = (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
			$css_ref    = (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' );
			$this->assertNotSame( '', $helper_ref, "Section {$key} must have non-empty helper_ref" );
			$this->assertNotSame( '', $css_ref, "Section {$key} must have non-empty css_contract_ref" );
		}
	}

	public function test_expansion_pack_definitions_are_exportable_and_versioned(): void {
		foreach ( Section_Expansion_Pack_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}
}
