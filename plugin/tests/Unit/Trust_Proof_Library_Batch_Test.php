<?php
/**
 * Unit tests for trust/proof library batch (SEC-02): registry validation, ACF blueprint integrity,
 * accessibility/contrast metadata, preview-data coverage, export serialization (spec §12, §15, §20, §51, Prompt 148).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\TrustProofBatch\Trust_Proof_Library_Batch_Seeder;
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
require_once $plugin_root . '/src/Domain/Registries/Section/TrustProofBatch/Trust_Proof_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/TrustProofBatch/Trust_Proof_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class Trust_Proof_Library_Batch_Test extends TestCase {

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

	public function test_trust_proof_batch_section_keys_match_definitions(): void {
		$keys = Trust_Proof_Library_Batch_Definitions::section_keys();
		$this->assertCount( 18, $keys );
		$expected = array(
			'tp_testimonial_01', 'tp_testimonial_02', 'tp_review_01', 'tp_credential_01', 'tp_credential_02',
			'tp_guarantee_01', 'tp_case_teaser_01', 'tp_outcome_01', 'tp_badge_01', 'tp_certification_01',
			'tp_client_logo_01', 'tp_authority_01', 'tp_reassurance_01', 'tp_faq_microproof_01', 'tp_partner_01',
			'tp_rating_01', 'tp_quote_01', 'tp_trust_band_01',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = Trust_Proof_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 18, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_get_blueprint_from_definition_returns_non_null_for_each_trust_proof_section(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$helper_ref = (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' );
			$css_ref = (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' );
			$this->assertNotSame( '', $helper_ref, "Section {$key} must have non-empty helper_ref" );
			$this->assertNotSame( '', $css_ref, "Section {$key} must have non-empty css_contract_ref" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_each_definition_section_purpose_family_is_proof(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( Trust_Proof_Library_Batch_Definitions::PURPOSE_FAMILY, (string) ( $def['section_purpose_family'] ?? '' ) );
		}
	}

	public function test_each_definition_has_accessibility_and_contrast_guidance(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
			$this->assertNotEmpty( $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
			$this->assertMatchesRegularExpression( '/contrast|color/i', $guidance, "Section {$key} should reference contrast or color (spec §51.8)" );
		}
	}

	public function test_preview_defaults_coverage(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$defaults = $def['preview_defaults'] ?? array();
			$this->assertIsArray( $defaults, "Section {$key} must have preview_defaults array" );
			$this->assertNotEmpty( $defaults, "Section {$key} preview_defaults must not be empty for preview realism" );
		}
	}

	public function test_each_definition_has_seo_relevance_notes(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'seo_relevance_notes', $def, "Section {$key} must have seo_relevance_notes (spec §15.9)" );
			$this->assertNotEmpty( (string) ( $def['seo_relevance_notes'] ?? '' ), "Section {$key} seo_relevance_notes must be non-empty" );
		}
	}

	public function test_trust_proof_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_trust_proof_category_is_allowed(): void {
		$allowed = Section_Schema::get_allowed_categories();
		$this->assertArrayHasKey( 'trust_proof', $allowed );
		foreach ( Trust_Proof_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertSame( 'trust_proof', (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' ) );
		}
	}

	public function test_seeder_run_returns_success_and_eighteen_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 200;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Section_Template_Repository();
		$result = Trust_Proof_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 18, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
