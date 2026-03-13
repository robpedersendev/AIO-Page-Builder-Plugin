<?php
/**
 * Unit tests for feature/benefit/value library batch (SEC-03): schema validity, ACF blueprint integrity,
 * preview realism, semantic/accessibility metadata, export serialization (spec §12, §15, §20, §51, Prompt 149).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\FeatureBenefitBatch\Feature_Benefit_Value_Library_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
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
require_once $plugin_root . '/src/Domain/Registries/Section/FeatureBenefitBatch/Feature_Benefit_Value_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/FeatureBenefitBatch/Feature_Benefit_Value_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class Feature_Benefit_Value_Library_Batch_Test extends TestCase {

	private Section_Validator $validator;
	private Section_Field_Blueprint_Service $blueprint_service;

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
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_feature_benefit_batch_section_keys_match_definitions(): void {
		$keys = Feature_Benefit_Value_Library_Batch_Definitions::section_keys();
		$this->assertCount( 16, $keys );
		$expected = array(
			'fb_feature_grid_01', 'fb_benefit_band_01', 'fb_offer_compare_01', 'fb_package_summary_01', 'fb_differentiator_01',
			'fb_before_after_01', 'fb_why_choose_01', 'fb_product_spec_01', 'fb_service_offering_01', 'fb_value_prop_01',
			'fb_feature_compact_01', 'fb_benefit_detail_01', 'fb_offer_highlight_01', 'fb_local_value_01', 'fb_directory_value_01',
			'fb_resource_explainer_01',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = Feature_Benefit_Value_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 16, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_get_blueprint_from_definition_returns_non_null_for_each_fb_section(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$helper_ref = (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
			$css_ref = (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' );
			$this->assertNotSame( '', $helper_ref, "Section {$key} must have non-empty helper_ref" );
			$this->assertNotSame( '', $css_ref, "Section {$key} must have non-empty css_contract_ref" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'preview_description', $def, "Section {$key} must have preview_description" );
			$this->assertNotEmpty( (string) ( $def['preview_description'] ?? '' ), "Section {$key} preview_description must be non-empty" );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertContains( (string) ( $def['animation_tier'] ?? '' ), array( 'none', 'subtle', 'moderate', 'full' ) );
			$this->assertArrayHasKey( 'animation_families', $def );
			$this->assertIsArray( $def['animation_families'] ?? null );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] ?? null );
		}
	}

	public function test_each_definition_section_purpose_family_is_feature_benefit(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( Feature_Benefit_Value_Library_Batch_Definitions::PURPOSE_FAMILY, (string) ( $def['section_purpose_family'] ?? '' ) );
		}
	}

	public function test_each_definition_has_accessibility_and_omit_safety_guidance(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
			$this->assertNotEmpty( $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
			$this->assertMatchesRegularExpression( '/contrast|color|semantic|omit/i', $guidance, "Section {$key} should reference contrast, semantic, or omit-safe (spec §51.3)" );
		}
	}

	public function test_preview_defaults_coverage(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$defaults = $def['preview_defaults'] ?? array();
			$this->assertIsArray( $defaults, "Section {$key} must have preview_defaults array" );
			$this->assertNotEmpty( $defaults, "Section {$key} preview_defaults must not be empty for preview realism" );
		}
	}

	public function test_each_definition_has_seo_relevance_notes(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'seo_relevance_notes', $def, "Section {$key} must have seo_relevance_notes (spec §15.9)" );
			$this->assertNotEmpty( (string) ( $def['seo_relevance_notes'] ?? '' ), "Section {$key} seo_relevance_notes must be non-empty" );
		}
	}

	public function test_fb_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_feature_benefit_category_is_allowed(): void {
		$allowed = Section_Schema::get_allowed_categories();
		$this->assertArrayHasKey( 'feature_benefit', $allowed );
		foreach ( Feature_Benefit_Value_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( 'feature_benefit', (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ) );
		}
	}

	public function test_seeder_run_returns_success_and_sixteen_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 300;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Section_Template_Repository();
		$result = Feature_Benefit_Value_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 16, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
