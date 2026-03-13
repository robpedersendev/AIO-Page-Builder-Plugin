<?php
/**
 * Unit tests for media/listing/profile/detail library batch (SEC-06): schema validity, ACF blueprint integrity,
 * semantic list/card/table structure, preview-data realism, omission safety, export serialization (spec §12, §51, Prompt 151).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Validator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\Section\MediaListingProfileBatch\Media_Listing_Profile_Detail_Library_Batch_Seeder;
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
require_once $plugin_root . '/src/Domain/Registries/Section/MediaListingProfileBatch/Media_Listing_Profile_Detail_Library_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/Section/MediaListingProfileBatch/Media_Listing_Profile_Detail_Library_Batch_Seeder.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Validator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Normalizer.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service.php';

final class Media_Listing_Profile_Detail_Library_Batch_Test extends TestCase {

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

	public function test_mlp_batch_section_keys_match_definitions(): void {
		$keys = Media_Listing_Profile_Detail_Library_Batch_Definitions::section_keys();
		$this->assertCount( 15, $keys );
		$expected = array(
			'mlp_card_grid_01', 'mlp_listing_01', 'mlp_profile_summary_01', 'mlp_profile_cards_01', 'mlp_place_highlight_01',
			'mlp_recommendation_band_01', 'mlp_gallery_01', 'mlp_media_band_01', 'mlp_detail_spec_01', 'mlp_comparison_cards_01',
			'mlp_related_content_01', 'mlp_location_info_01', 'mlp_directory_entry_01', 'mlp_team_grid_01', 'mlp_product_cards_01',
		);
		foreach ( $expected as $k ) {
			$this->assertContains( $k, $keys );
		}
		$all = Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions();
		$this->assertCount( 15, $all );
		foreach ( $all as $def ) {
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' ), $keys );
		}
	}

	public function test_each_definition_passes_registry_completeness(): void {
		$normalizer = new Section_Definition_Normalizer();
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$normalized = $normalizer->normalize( $def );
			$errors = $this->validator->validate_completeness( $normalized );
			$this->assertEmpty( $errors, 'Definition ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) . ' should pass completeness: ' . implode( ', ', $errors ) );
		}
	}

	public function test_each_definition_has_valid_embedded_blueprint(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
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

	public function test_get_blueprint_from_definition_returns_non_null_for_each_mlp_section(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$bp = $this->blueprint_service->get_blueprint_from_definition( $def );
			$this->assertNotNull( $bp, 'get_blueprint_from_definition must return normalized blueprint for ' . ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '?' ) );
		}
	}

	public function test_each_definition_has_helper_and_css_contract_refs(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_HELPER_REF ] ?? '' ), "Section {$key} must have helper_ref" );
			$this->assertNotSame( '', (string) ( $def[ Section_Schema::FIELD_CSS_CONTRACT_REF ] ?? '' ), "Section {$key} must have css_contract_ref" );
		}
	}

	public function test_each_definition_has_preview_and_animation_metadata(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$this->assertArrayHasKey( 'preview_description', $def );
			$this->assertNotEmpty( (string) ( $def['preview_description'] ?? '' ) );
			$this->assertArrayHasKey( 'animation_tier', $def );
			$this->assertArrayHasKey( 'preview_defaults', $def );
			$this->assertIsArray( $def['preview_defaults'] ?? null );
		}
	}

	public function test_section_purpose_family_is_listing_media_profile_detail_or_related(): void {
		$allowed = array( 'listing', 'media', 'profile', 'detail', 'related' );
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$family = (string) ( $def['section_purpose_family'] ?? '' );
			$this->assertContains( $family, $allowed, 'section_purpose_family must be listing, media, profile, detail, or related' );
		}
	}

	public function test_each_definition_has_accessibility_guidance(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$guidance = (string) ( $def['accessibility_warnings_or_enhancements'] ?? '' );
			$this->assertNotEmpty( $guidance, "Section {$key} must have accessibility_warnings_or_enhancements" );
			$this->assertMatchesRegularExpression( '/list|grid|table|semantic|contrast|omit|aria|heading/i', $guidance, "Section {$key} should reference list, grid, table, semantic, contrast, omit, ARIA, or heading (spec §51)" );
		}
	}

	public function test_preview_defaults_non_empty(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$key = (string) ( $def[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '' );
			$defaults = $def['preview_defaults'] ?? array();
			$this->assertIsArray( $defaults );
			$this->assertNotEmpty( $defaults, "Section {$key} must have non-empty preview_defaults" );
		}
	}

	public function test_categories_are_allowed(): void {
		$allowed = Section_Schema::get_allowed_categories();
		$mlp_cats = array( 'directory_listing', 'media_gallery', 'profile_bio', 'comparison', 'related_recommended' );
		foreach ( $mlp_cats as $c ) {
			$this->assertArrayHasKey( $c, $allowed );
		}
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$cat = (string) ( $def[ Section_Schema::FIELD_CATEGORY ] ?? '' );
			$this->assertContains( $cat, $mlp_cats );
			$this->assertArrayHasKey( $cat, $allowed );
		}
	}

	public function test_mlp_batch_definitions_are_exportable_and_versioned(): void {
		foreach ( Media_Listing_Profile_Detail_Library_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_VERSION, $def );
			$this->assertIsArray( $def[ Section_Schema::FIELD_VERSION ] );
			$this->assertNotEmpty( (string) ( $def[ Section_Schema::FIELD_VERSION ]['version'] ?? '' ) );
			$this->assertContains( (string) ( $def[ Section_Schema::FIELD_STATUS ] ?? '' ), array( 'active', 'draft', 'inactive', 'deprecated' ) );
		}
	}

	public function test_seeder_run_returns_success_and_fifteen_section_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 500;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Section_Template_Repository();
		$result = Media_Listing_Profile_Detail_Library_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'section_ids', $result );
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertTrue( $result['success'] );
		$this->assertCount( 15, $result['section_ids'] );
		$this->assertEmpty( $result['errors'] );
	}
}
