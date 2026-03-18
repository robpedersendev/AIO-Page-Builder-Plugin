<?php
/**
 * Unit tests for process/timeline/FAQ library batch (SEC-05): schema validity, ACF blueprint integrity,
 * accessible interactive pattern rules, heading structure, preview-data realism, export serialization (spec §12, §51, Prompt 150).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\ProcessTimelineFaqBatch\Process_Timeline_FAQ_Library_Batch_Seeder;
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
require_once $plugin_root . '/src/Domain/Registries/Section/ProcessTimelineFaqBatch/Process_Timeline_FAQ_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/ProcessTimelineFaqBatch/Process_Timeline_FAQ_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class Process_Timeline_FAQ_Library_Batch_Test extends TestCase {

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

	public function test_ptf_batch_section_keys_match_definitions(): void {
		$keys = Process_Timeline_FAQ_Library_Batch_Definitions::section_keys();
		$this->assertCount( 15, $keys );
		$expected = array(
			'ptf_steps_01',
			'ptf_steps_horizontal_01',
			'ptf_steps_vertical_01',
			'ptf_buying_process_01',
			'ptf_onboarding_01',
			'ptf_service_flow_01',
			'ptf_expectations_01',
			'ptf_timeline_01',
			'ptf_timeline_compact_01',
			'ptf_policy_explainer_01',
			'ptf_faq_01',
			'ptf_faq_accordion_01',
			'ptf_faq_by_category_01',
			'ptf_how_it_works_01',
			'ptf_comparison_steps_01',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 15, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors     = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_get_blueprint_from_definition_returns_non_null_for_each_ptf_section(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' ), "Section {$key} must have helper_ref" );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ), "Section {$key} must have css_contract_ref" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'preview_description', $def );
			$this->assertNotEmpty( (string) ( $def['preview_description'] ?? '' ) );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] ?? null );
		}
	}

	public function test_section_purpose_family_is_process_timeline_or_faq(): void {
		$allowed = array( 'process', 'timeline', 'faq' );
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$family = (string) ( $def['section_purpose_family'] ?? '' );
			$this->assertContains( $family, $allowed, 'section_purpose_family must be process, timeline, or faq' );
		}
	}

	public function test_each_definition_has_accessibility_guidance_with_heading_or_aria(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$key      = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
			$this->assertNotEmpty( $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
			$this->assertMatchesRegularExpression( '/heading|aria|list|semantic|fallback/i', $guidance, "Section {$key} should reference heading, ARIA, list, or fallback (spec §51.6, §51.7)" );
		}
	}

	public function test_faq_accordion_has_static_fallback_guidance(): void {
		$def = null;
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $d ) {
			if ( ( $d[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ) === 'ptf_faq_accordion_01' ) {
				$def = $d;
				break;
			}
		}
		$this->assertNotNull( $def );
		$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
		$this->assertMatchesRegularExpression( '/static\s+fallback|fallback\s+preserves/i', $guidance, 'FAQ accordion must mention static fallback' );
		$this->assertMatchesRegularExpression( '/aria-expanded/i', $guidance, 'FAQ accordion must mention aria-expanded' );
	}

	public function test_preview_defaults_coverage(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$key      = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$defaults = $def['preview_defaults'] ?? array();
			$this->assertIsArray( $defaults );
			$this->assertNotEmpty( $defaults, "Section {$key} must have non-empty preview_defaults" );
		}
	}

	public function test_categories_are_allowed(): void {
		$allowed = Section_Schema::get_allowed_categories();
		$this->assertArrayHasKey( 'process_steps', $allowed );
		$this->assertArrayHasKey( 'timeline', $allowed );
		$this->assertArrayHasKey( 'faq', $allowed );
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$cat = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			$this->assertContains( $cat, array( 'process_steps', 'timeline', 'faq' ) );
			$this->assertArrayHasKey( $cat, $allowed );
		}
	}

	public function test_ptf_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( Process_Timeline_FAQ_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_seeder_run_returns_success_and_fifteen_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 400;
		$GLOBALS['_aio_wp_query_posts']        = array();
		$repo                                  = new Section_Template_Repository();
		$result                                = Process_Timeline_FAQ_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 15, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
