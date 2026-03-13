<?php
/**
 * Unit tests for CTA super-library (SEC-08): schema validity, CTA metadata completeness,
 * semantic label clarity, preview realism, omission safety, animation fallback, export serialization (spec §12, §14, §51, Prompt 153).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\CtaSuperLibraryBatch\CTA_Super_Library_Batch_Seeder;
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
require_once $plugin_root . '/src/Domain/Registries/Section/CtaSuperLibraryBatch/CTA_Super_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/CtaSuperLibraryBatch/CTA_Super_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class CTA_Super_Library_Batch_Test extends TestCase {

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

	public function test_cta_batch_section_keys_match_definitions(): void {
		$keys = CTA_Super_Library_Batch_Definitions::section_keys();
		$this->assertCount( 26, $keys );
		$expected = array(
			'cta_consultation_01', 'cta_consultation_02', 'cta_booking_01', 'cta_booking_02',
			'cta_purchase_01', 'cta_purchase_02', 'cta_inquiry_01', 'cta_inquiry_02',
			'cta_contact_01', 'cta_contact_02', 'cta_quote_request_01', 'cta_quote_request_02',
			'cta_directory_nav_01', 'cta_compare_next_01', 'cta_trust_confirm_01', 'cta_trust_confirm_02',
			'cta_local_action_01', 'cta_local_action_02', 'cta_service_detail_01', 'cta_service_detail_02',
			'cta_product_detail_01', 'cta_product_detail_02', 'cta_support_01', 'cta_support_02',
			'cta_policy_utility_01', 'cta_policy_utility_02',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = CTA_Super_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 26, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_get_blueprint_from_definition_returns_non_null_for_each_cta_section(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' ), "Section {$key} must have helper_ref" );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ), "Section {$key} must have css_contract_ref" );
		}
	}

	public function test_cta_metadata_completeness(): void {
		$allowed_intent = array( 'consultation', 'booking', 'purchase', 'inquiry', 'contact', 'quote_request', 'directory_nav', 'compare_next', 'trust_confirm', 'local_action', 'service_detail', 'product_detail', 'support', 'policy_utility' );
		$allowed_strength = array( 'subtle', 'strong', 'media_backed', 'proof_backed', 'minimalist' );
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertSame( 'cta', (string) ( $def['cta_classification'] ?? '' ), "Section {$key} must have cta_classification = cta" );
			$intent = (string) ( $def['cta_intent_family'] ?? '' );
			$this->assertContains( $intent, $allowed_intent, "Section {$key} cta_intent_family must be allowed" );
			$strength = (string) ( $def['cta_strength'] ?? '' );
			$this->assertContains( $strength, $allowed_strength, "Section {$key} cta_strength must be allowed" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'preview_description', $def );
			$this->assertNotEmpty( (string) ( $def['preview_description'] ?? '' ) );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertContains( (string) ( $def['animation_tier'] ?? '' ), array( 'none', 'subtle' ), "Section {$key} animation_tier must be none or subtle for fallback" );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] ?? null );
		}
	}

	public function test_each_definition_has_accessibility_guidance(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
			$this->assertNotEmpty( $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
			$this->assertMatchesRegularExpression( '/label|contrast|focus|omit|51\.3/i', $guidance, "Section {$key} should reference label, contrast, or focus (spec §51.3)" );
		}
	}

	public function test_preview_defaults_non_empty_and_include_primary_button_label(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$defaults = $def['preview_defaults'] ?? array();
			$this->assertIsArray( $defaults );
			$this->assertNotEmpty( $defaults, "Section {$key} must have non-empty preview_defaults" );
			$this->assertArrayHasKey( 'primary_button_label', $defaults, "Section {$key} preview_defaults must include primary_button_label for semantic clarity" );
			$this->assertNotEmpty( (string) ( $defaults['primary_button_label'] ?? '' ), "Section {$key} primary_button_label must be non-empty in preview" );
		}
	}

	public function test_category_is_cta_conversion(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( 'cta_conversion', (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ) );
		}
	}

	public function test_cta_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( CTA_Super_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_seeder_run_returns_success_and_twenty_six_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 700;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Section_Template_Repository();
		$result = CTA_Super_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 26, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
