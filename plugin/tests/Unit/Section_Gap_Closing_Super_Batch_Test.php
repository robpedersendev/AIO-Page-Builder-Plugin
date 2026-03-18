<?php
/**
 * Unit tests for gap-closing section super-batch (SEC-09): count threshold, family coverage,
 * registry validity, blueprint and preview metadata (spec §12, §62.11, Prompt 182).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch\Section_Gap_Closing_Super_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\GapClosingSuperBatch\Section_Gap_Closing_Super_Batch_Seeder;
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
require_once $plugin_root . '/src/Domain/Registries/Section/GapClosingSuperBatch/Section_Gap_Closing_Super_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/GapClosingSuperBatch/Section_Gap_Closing_Super_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class Section_Gap_Closing_Super_Batch_Test extends TestCase {

	/** SEC-09 gap-closing batch size (from Definitions::specs()). */
	private const EXPECTED_COUNT = 134;
	private const SECTION_TARGET = 250;

	private Section_Validator $validator;
	private Section_Field_Blueprint_Service $blueprint_service;

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
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_post_meta'], $GLOBALS['_aio_wp_query_posts'] );
		parent::tearDown();
	}

	public function test_gap_closing_batch_section_keys_count_and_match_definitions(): void {
		$keys = Section_Gap_Closing_Super_Batch_Definitions::section_keys();
		$this->assertCount( self::EXPECTED_COUNT, $keys );
		$all = Section_Gap_Closing_Super_Batch_Definitions::all_definitions();
		$this->assertCount( self::EXPECTED_COUNT, $all );
		foreach ( $all as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertContains( $key, $keys );
			$this->assertStringStartsWith( 'gc_', $key );
		}
		$this->assertSame( array_values( array_unique( $keys ) ), array_values( $keys ), 'section_keys must be unique' );
	}

	public function test_batch_id_and_target_constant(): void {
		$this->assertSame( 'SEC-09', Section_Gap_Closing_Super_Batch_Definitions::BATCH_ID );
		$this->assertSame( self::SECTION_TARGET, Section_Gap_Closing_Super_Batch_Definitions::SECTION_TARGET );
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors     = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_each_definition_has_section_purpose_family(): void {
		$allowed_families = array( 'offer', 'explainer', 'faq', 'profile', 'stats', 'listing', 'comparison', 'contact', 'legal', 'utility', 'timeline', 'related', 'proof', 'cta', 'hero' );
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$family = (string) ( $def['section_purpose_family'] ?? '' );
			$this->assertNotEmpty( $family, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' must have section_purpose_family' );
			$this->assertContains( $family, $allowed_families );
		}
	}

	public function test_each_definition_has_helper_css_preview_and_animation_metadata(): void {
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' ), "Section {$key} must have helper_ref" );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ), "Section {$key} must have css_contract_ref" );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertContains( (string) ( $def['animation_tier'] ?? '' ), array( 'none', 'subtle', 'moderate', 'full' ) );
		}
	}

	public function test_each_definition_has_accessibility_and_export_metadata(): void {
		foreach ( Section_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'accessibility_warnings_or_enhancements', $def );
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_seeder_run_returns_success_and_gap_closing_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 100;
		$GLOBALS['_aio_wp_query_posts']        = array();
		$repo                                  = new Section_Template_Repository();
		$result                                = Section_Gap_Closing_Super_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertArrayHasKey( 'section_keys', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( self::EXPECTED_COUNT, $result['section_ids'] );
		$this->assertCount( self::EXPECTED_COUNT, $result['section_keys'] );
		$this->assertEmpty( $result['errors'] );
		$this->assertSame( Section_Gap_Closing_Super_Batch_Definitions::section_keys(), $result['section_keys'] );
	}

	public function test_gap_closure_keys_list_matches_definitions_section_keys(): void {
		$keys = Section_Gap_Closing_Super_Batch_Definitions::section_keys();
		$this->assertCount( self::EXPECTED_COUNT, $keys );
		$this->assertContains( 'gc_offer_value_01', $keys );
		$this->assertContains( 'gc_cta_inline_01', $keys );
		$this->assertContains( 'gc_hero_compact_02', $keys );
	}
}
