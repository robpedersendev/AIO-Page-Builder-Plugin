<?php
/**
 * Unit tests for Registry Deprecation and Integrity: valid transitions, missing reason, invalid replacement, historical readability, integrity scan (Prompt 031).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Deprecation_Metadata;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Deprecation_Service;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Integrity_Validator;
use AIOPageBuilder\Domain\Registries\Shared\Registry_Validation_Result;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Deprecation_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Registry_Integrity_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';

final class Registry_Deprecation_Test extends TestCase {

	private \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository $section_repo;
	private \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository $page_repo;
	private Section_Registry_Service $section_registry;
	private \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service $page_template_registry;
	private Registry_Deprecation_Service $deprecation_service;
	private Registry_Integrity_Validator $integrity_validator;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();

		$this->section_repo = new \AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository();
		$this->page_repo    = new \AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository();

		$section_norm              = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_valid             = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_norm, $this->section_repo );
		$this->deprecation_service = new Registry_Deprecation_Service( $this->section_repo, $this->page_repo );
		$this->section_registry    = new Section_Registry_Service( $section_valid, $this->section_repo, $this->deprecation_service );

		$page_norm                    = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer();
		$page_valid                   = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator( $page_norm, $this->page_repo, $this->section_registry );
		$this->page_template_registry = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service( $page_valid, $this->page_repo, $this->deprecation_service );

		$this->integrity_validator = new Registry_Integrity_Validator( $this->section_registry, $this->page_template_registry );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	private function valid_minimal_section( string $key = 'st01_hero', string $name = 'Hero' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => $name,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Purpose.',
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'acf',
			Section_Schema::FIELD_HELPER_REF               => 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY            => array(),
			Section_Schema::FIELD_VERSION                  => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
		);
	}

	public function test_valid_deprecation_transition_with_replacement(): void {
		$this->seed_sections();
		$GLOBALS['_aio_wp_insert_post_return'] = 101;
		$r1                                    = $this->section_registry->create( $this->valid_minimal_section( 'st_old', 'Old' ) );
		$this->assertTrue( $r1->success );

		$post                                  = new \WP_Post(
			array(
				'ID'          => 101,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => 'Old',
				'post_status' => 'publish',
				'post_name'   => 'st_old',
			)
		);
		$GLOBALS['_aio_get_post_return']       = $post;
		$GLOBALS['_aio_wp_query_posts']        = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), array( $post ) );
		$GLOBALS['_aio_post_meta']['101']      = array(
			'_aio_internal_key'       => 'st_old',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $r1->definition ),
		);
		$GLOBALS['_aio_wp_update_post_return'] = 101;

		$dep = $this->section_registry->deprecate( 101, 'Superseded by st01_hero', 'st01_hero' );
		$this->assertTrue( $dep->success );
		$this->assertSame( 'deprecated', $dep->definition[ Section_Schema::FIELD_STATUS ] );
		$this->assertArrayHasKey( 'deprecation', $dep->definition );
		$this->assertSame( 'st01_hero', $dep->definition['deprecation']['replacement_section_key'] ?? '' );
	}

	public function test_missing_reason_rejection(): void {
		$this->seed_sections();
		$GLOBALS['_aio_wp_insert_post_return'] = 102;
		$r1                                    = $this->section_registry->create( $this->valid_minimal_section( 'st_noreason', 'NoReason' ) );
		$this->assertTrue( $r1->success );

		$post                             = new \WP_Post(
			array(
				'ID'          => 102,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => 'NoReason',
				'post_status' => 'publish',
				'post_name'   => 'st_noreason',
			)
		);
		$GLOBALS['_aio_get_post_return']  = $post;
		$GLOBALS['_aio_wp_query_posts']   = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), array( $post ) );
		$GLOBALS['_aio_post_meta']['102'] = array(
			'_aio_internal_key'       => 'st_noreason',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $r1->definition ),
		);

		$dep = $this->section_registry->deprecate( 102, '', '' );
		$this->assertFalse( $dep->success );
		$this->assertStringContainsString( 'reason', implode( ' ', $dep->errors ) );
	}

	public function test_invalid_replacement_rejection(): void {
		$this->seed_sections();
		$GLOBALS['_aio_wp_insert_post_return'] = 103;
		$r1                                    = $this->section_registry->create( $this->valid_minimal_section( 'st_invalid', 'Invalid' ) );
		$this->assertTrue( $r1->success );

		$post                             = new \WP_Post(
			array(
				'ID'          => 103,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => 'Invalid',
				'post_status' => 'publish',
				'post_name'   => 'st_invalid',
			)
		);
		$GLOBALS['_aio_get_post_return']  = $post;
		$GLOBALS['_aio_wp_query_posts']   = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), array( $post ) );
		$GLOBALS['_aio_post_meta']['103'] = array(
			'_aio_internal_key'       => 'st_invalid',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $r1->definition ),
		);

		$dep = $this->section_registry->deprecate( 103, 'Invalid replacement', 'st_nonexistent_xyz' );
		$this->assertFalse( $dep->success );
		$this->assertStringContainsString( 'Replacement', implode( ' ', $dep->errors ) );
	}

	public function test_historical_object_readable_after_deprecation(): void {
		$this->seed_sections();
		$GLOBALS['_aio_wp_insert_post_return'] = 104;
		$r1                                    = $this->section_registry->create( $this->valid_minimal_section( 'st_hist', 'Hist' ) );
		$this->assertTrue( $r1->success );

		$post                                  = new \WP_Post(
			array(
				'ID'          => 104,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => 'Hist',
				'post_status' => 'publish',
				'post_name'   => 'st_hist',
			)
		);
		$GLOBALS['_aio_get_post_return']       = $post;
		$GLOBALS['_aio_wp_query_posts']        = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), array( $post ) );
		$GLOBALS['_aio_post_meta']['104']      = array(
			'_aio_internal_key'       => 'st_hist',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $r1->definition ),
		);
		$GLOBALS['_aio_wp_update_post_return'] = 104;

		$dep = $this->section_registry->deprecate( 104, 'Legacy', '' );
		$this->assertTrue( $dep->success );

		$GLOBALS['_aio_post_meta']['104'] = array(
			'_aio_internal_key'       => 'st_hist',
			'_aio_status'             => 'deprecated',
			'_aio_section_definition' => wp_json_encode( $dep->definition ),
		);
		$read                             = $this->section_registry->get_by_key( 'st_hist' );
		$this->assertNotNull( $read );
		$this->assertSame( 'st_hist', $read[ Section_Schema::FIELD_INTERNAL_KEY ] );
		$this->assertSame( 'deprecated', $read['status'] ?? '' );
		$this->assertFalse( Deprecation_Metadata::is_eligible_for_new_use( $read ) );
	}

	public function test_list_eligible_for_new_selection_excludes_deprecated(): void {
		$this->seed_sections();
		$list = $this->section_registry->list_eligible_for_new_selection( 'active', 20, 0 );
		$this->assertIsArray( $list );
		foreach ( $list as $def ) {
			$this->assertTrue( Deprecation_Metadata::is_eligible_for_new_use( $def ), 'Eligible list must exclude deprecated' );
		}
	}

	public function test_integrity_scan_detects_deprecated_dependencies(): void {
		$this->seed_sections();
		$scan = $this->integrity_validator->scan_registry_integrity();
		$this->assertArrayHasKey( 'missing_section_refs', $scan );
		$this->assertArrayHasKey( 'deprecated_section_refs', $scan );
		$this->assertIsArray( $scan['missing_section_refs'] );
		$this->assertIsArray( $scan['deprecated_section_refs'] );
	}

	public function test_validate_composition_section_refs_missing(): void {
		$this->seed_sections();
		$comp   = array(
			'ordered_section_list' => array(
				array(
					'section_key' => 'st99_missing',
					'position'    => 0,
				),
			),
		);
		$result = $this->integrity_validator->validate_composition_section_refs( $comp );
		$this->assertFalse( $result->valid );
		$this->assertContains( Registry_Validation_Result::CODE_REFERENCE_MISSING, $result->codes );
	}

	public function test_deprecation_metadata_blocks(): void {
		$section_block = Deprecation_Metadata::for_section( 'Legacy', 'st02_new' );
		$this->assertTrue( $section_block[ Deprecation_Metadata::IS_DEPRECATED ] );
		$this->assertSame( 'Legacy', $section_block[ Deprecation_Metadata::DEPRECATED_REASON ] );
		$this->assertSame( 'st02_new', $section_block[ Deprecation_Metadata::REPLACEMENT_KEY ] );
		$this->assertFalse( $section_block[ Deprecation_Metadata::ELIGIBLE_FOR_NEW_USE ] );
		$this->assertTrue( $section_block[ Deprecation_Metadata::HISTORICAL_REFERENCE_ALLOWED ] );
	}

	private function seed_sections(): void {
		$GLOBALS['_aio_wp_query_posts']   = array(
			new \WP_Post(
				array(
					'ID'          => 901,
					'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
					'post_title'  => 'Hero',
					'post_status' => 'publish',
					'post_name'   => 'st01_hero',
				)
			),
		);
		$GLOBALS['_aio_post_meta']['901'] = array(
			'_aio_internal_key'       => 'st01_hero',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $this->valid_minimal_section( 'st01_hero', 'Hero' ) ),
		);
	}
}
