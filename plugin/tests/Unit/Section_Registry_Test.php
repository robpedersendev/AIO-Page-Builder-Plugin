<?php
/**
 * Unit tests for Section Registry: create, validate, immutable key, deprecation, replacement ref, query (Prompt 027).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Result;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Validation_Result;
use AIOPageBuilder\Domain\Registries\Section\Section_Validator;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';

final class Section_Registry_Test extends TestCase {

	private Section_Template_Repository $repository;
	private Section_Definition_Normalizer $normalizer;
	private Section_Validator $validator;
	private Section_Registry_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->repository = new Section_Template_Repository();
		$this->normalizer = new Section_Definition_Normalizer();
		$this->validator  = new Section_Validator( $this->normalizer, $this->repository );
		$this->service    = new Section_Registry_Service( $this->validator, $this->repository );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_get_post_return'],
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_wp_insert_post_return'],
			$GLOBALS['_aio_wp_update_post_return'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	private function valid_minimal_section(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY           => 'st01_hero',
			Section_Schema::FIELD_NAME                  => 'Hero',
			Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Primary hero section.',
			Section_Schema::FIELD_CATEGORY              => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'blueprint_st01',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF   => 'acf_st01',
			Section_Schema::FIELD_HELPER_REF            => 'helper_st01',
			Section_Schema::FIELD_CSS_CONTRACT_REF      => 'css_st01',
			Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
			Section_Schema::FIELD_VARIANTS              => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY         => array( 'may_precede' => array(), 'may_follow' => array(), 'avoid_adjacent' => array(), 'duplicate_purpose_of' => array() ),
			Section_Schema::FIELD_VERSION               => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS                => 'active',
			Section_Schema::FIELD_RENDER_MODE           => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
		);
	}

	public function test_valid_section_creation(): void {
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_wp_insert_post_return'] = 100;
		$result = $this->service->create( $this->valid_minimal_section() );
		$this->assertTrue( $result->success );
		$this->assertSame( 100, $result->post_id );
		$this->assertNotNull( $result->definition );
		$this->assertSame( 'st01_hero', $result->definition[ Section_Schema::FIELD_INTERNAL_KEY ] );
	}

	public function test_missing_required_field_rejection(): void {
		$invalid = $this->valid_minimal_section();
		unset( $invalid[ Section_Schema::FIELD_HELPER_REF ] );
		$result = $this->validator->validate_for_create( $invalid );
		$this->assertFalse( $result->valid );
		$this->assertNotEmpty( $result->errors );
		$has_helper_error = false;
		foreach ( $result->errors as $e ) {
			if ( strpos( $e, 'helper' ) !== false || strpos( $e, 'Required' ) !== false ) {
				$has_helper_error = true;
				break;
			}
		}
		$this->assertTrue( $has_helper_error, 'Expected error about missing required field' );
	}

	public function test_duplicate_key_rejection(): void {
		$post = new \WP_Post( array(
			'ID'          => 99,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Existing',
			'post_status' => 'publish',
			'post_name'   => 'st01_hero',
		) );
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['99'] = array(
			'_aio_internal_key' => 'st01_hero',
			'_aio_status'       => 'active',
		);
		$result = $this->validator->validate_for_create( $this->valid_minimal_section() );
		$this->assertFalse( $result->valid );
		$this->assertNotEmpty( $result->errors );
		$this->assertStringContainsString( 'already exists', implode( ' ', $result->errors ) );
	}

	public function test_immutable_key_enforcement_on_update(): void {
		$existing_def = $this->valid_minimal_section();
		$post = new \WP_Post( array(
			'ID'          => 200,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Hero',
			'post_status' => 'publish',
			'post_name'   => 'st01_hero',
		) );
		$GLOBALS['_aio_get_post_return'] = $post;
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['200'] = array(
			'_aio_internal_key' => 'st01_hero',
			'_aio_status'       => 'active',
			'_aio_section_definition' => wp_json_encode( $existing_def ),
		);
		$attempt = $existing_def;
		$attempt[ Section_Schema::FIELD_INTERNAL_KEY ] = 'st99_changed';
		$result = $this->validator->validate_for_update( $this->normalizer->normalize( $attempt ), 200 );
		$this->assertFalse( $result->valid );
		$this->assertStringContainsString( 'immutable', implode( ' ', $result->errors ) );
	}

	public function test_deprecation_transition_stores_reason_and_replacement(): void {
		$def  = $this->valid_minimal_section();
		$post = new \WP_Post( array(
			'ID'          => 300,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Hero',
			'post_status' => 'publish',
			'post_name'   => 'st01_hero',
		) );
		$GLOBALS['_aio_get_post_return']   = $post;
		$GLOBALS['_aio_wp_query_posts']    = array( $post );
		$GLOBALS['_aio_post_meta']['300']  = array(
			'_aio_internal_key'       => 'st01_hero',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_update_post_return'] = 300;
		$result = $this->service->deprecate( 300, 'Superseded by st02_hero', 'st02_hero' );
		$this->assertTrue( $result->success );
		$this->assertNotNull( $result->definition );
		$this->assertSame( 'deprecated', $result->definition[ Section_Schema::FIELD_STATUS ] );
		$this->assertArrayHasKey( 'deprecation', $result->definition );
		$this->assertSame( 'Superseded by st02_hero', $result->definition['deprecation']['reason'] );
		$this->assertSame( 'st02_hero', $result->definition['deprecation']['replacement_section_key'] );
	}

	public function test_replacement_reference_stored_in_deprecation(): void {
		$def  = $this->valid_minimal_section();
		$def[ Section_Schema::FIELD_INTERNAL_KEY ] = 'st10_legacy';
		$post = new \WP_Post( array(
			'ID'          => 401,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Legacy',
			'post_status' => 'publish',
			'post_name'   => 'st10_legacy',
		) );
		$GLOBALS['_aio_get_post_return']   = $post;
		$GLOBALS['_aio_wp_query_posts']    = array( $post );
		$GLOBALS['_aio_post_meta']['401']  = array(
			'_aio_internal_key'       => 'st10_legacy',
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_update_post_return'] = 401;
		$result = $this->service->deprecate( 401, 'Use st01_hero', 'st01_hero' );
		$this->assertTrue( $result->success );
		$this->assertArrayHasKey( 'replacement_section_suggestions', $result->definition );
		$this->assertContains( 'st01_hero', $result->definition['replacement_section_suggestions'] );
	}

	public function test_query_by_key_returns_definition(): void {
		$def = $this->valid_minimal_section();
		$post = new \WP_Post( array(
			'ID'          => 500,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Hero',
			'post_status' => 'publish',
			'post_name'   => 'st01_hero',
		) );
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['500'] = array(
			'_aio_internal_key'      => 'st01_hero',
			'_aio_status'            => 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$found = $this->service->get_by_key( 'st01_hero' );
		$this->assertIsArray( $found );
		$this->assertSame( 'st01_hero', $found[ Section_Schema::FIELD_INTERNAL_KEY ] );
		$this->assertSame( 'Hero', $found[ Section_Schema::FIELD_NAME ] );
	}

	public function test_query_by_status_returns_definitions(): void {
		$def = $this->valid_minimal_section();
		$post = new \WP_Post( array(
			'ID'          => 600,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Hero',
			'post_status' => 'publish',
			'post_name'   => 'st01_hero',
		) );
		$GLOBALS['_aio_wp_query_posts'] = array( $post );
		$GLOBALS['_aio_post_meta']['600'] = array(
			'_aio_internal_key'      => 'st01_hero',
			'_aio_status'            => 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$list = $this->service->list_by_status( 'active', 10, 0 );
		$this->assertIsArray( $list );
		$this->assertCount( 1, $list );
		$this->assertSame( 'st01_hero', $list[0][ Section_Schema::FIELD_INTERNAL_KEY ] );
	}

	public function test_default_variant_must_be_in_variants(): void {
		$invalid = $this->valid_minimal_section();
		$invalid[ Section_Schema::FIELD_DEFAULT_VARIANT ] = 'missing';
		$invalid[ Section_Schema::FIELD_VARIANTS ]       = array( 'default' => array( 'label' => 'Default' ) );
		$result = $this->validator->validate_completeness( $invalid );
		$this->assertNotEmpty( $result );
		$this->assertStringContainsString( 'default_variant', implode( ' ', $result ) );
	}
}
