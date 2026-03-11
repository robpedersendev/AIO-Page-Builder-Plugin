<?php
/**
 * Unit tests for Page Template Registry: create, section refs, ordered sections, deprecation, archetype query, one-pager (Prompt 028).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Normalizer;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Result;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Registry_Service;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Registry_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Definition_Normalizer.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validation_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Validator.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Registry_Service.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Page_Template_Registry_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Section_Registry_Service $section_registry;
	private Page_Template_Repository $page_repo;
	private Page_Template_Normalizer $normalizer;
	private Page_Template_Registry_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo   = new Section_Template_Repository();
		$section_normalizer   = new \AIOPageBuilder\Domain\Registries\Section\Section_Definition_Normalizer();
		$section_validator   = new \AIOPageBuilder\Domain\Registries\Section\Section_Validator( $section_normalizer, $this->section_repo );
		$this->section_registry = new Section_Registry_Service( $section_validator, $this->section_repo );
		$this->page_repo    = new Page_Template_Repository();
		$this->normalizer   = new Page_Template_Normalizer();
		$validator          = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator(
			$this->normalizer,
			$this->page_repo,
			$this->section_registry
		);
		$this->service      = new Page_Template_Registry_Service( $validator, $this->page_repo );
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
			Section_Schema::FIELD_INTERNAL_KEY            => 'st01_hero',
			Section_Schema::FIELD_NAME                    => 'Hero',
			Section_Schema::FIELD_PURPOSE_SUMMARY         => 'Hero section.',
			Section_Schema::FIELD_CATEGORY                => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_st01',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => 'acf_st01',
			Section_Schema::FIELD_HELPER_REF              => 'helper_st01',
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css_st01',
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY           => array(),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
		);
	}

	private function valid_minimal_page_template(): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY             => 'pt_landing_contact',
			Page_Template_Schema::FIELD_NAME                     => 'Landing – Contact',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY          => 'Contact landing page.',
			Page_Template_Schema::FIELD_ARCHETYPE                 => 'landing_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS          => array(
				array( 'section_key' => 'st01_hero', 'position' => 0, 'required' => true ),
				array( 'section_key' => 'st05_cta', 'position' => 1, 'required' => true ),
			),
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS      => array(
				'st01_hero' => array( 'required' => true ),
				'st05_cta'  => array( 'required' => true ),
			),
			Page_Template_Schema::FIELD_COMPATIBILITY            => array(),
			Page_Template_Schema::FIELD_ONE_PAGER                => array(
				'page_purpose_summary' => 'Contact landing: hero plus CTA.',
				'section_helper_order' => 'same_as_template',
			),
			Page_Template_Schema::FIELD_VERSION                  => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS                   => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => '',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES  => '',
		);
	}

	public function test_valid_template_creation(): void {
		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
			new \WP_Post( array( 'ID' => 9002, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'CTA', 'post_status' => 'publish', 'post_name' => 'st05_cta' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );
		$GLOBALS['_aio_post_meta']['9002'] = array( '_aio_internal_key' => 'st05_cta', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st05_cta', 'name' => 'CTA' ) ) ) );
		$GLOBALS['_aio_wp_insert_post_return'] = 1000;

		$result = $this->service->create( $this->valid_minimal_page_template() );
		$this->assertTrue( $result->success );
		$this->assertSame( 1000, $result->post_id );
		$this->assertNotNull( $result->definition );
		$this->assertSame( 'pt_landing_contact', $result->definition[ Page_Template_Schema::FIELD_INTERNAL_KEY ] );
	}

	public function test_nonexistent_section_reference_rejection(): void {
		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );

		$input = $this->valid_minimal_page_template();
		$input[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ][] = array( 'section_key' => 'st99_nonexistent', 'position' => 2, 'required' => false );
		$input[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ]['st99_nonexistent'] = array( 'required' => false );

		$result = $this->service->create( $input );
		$this->assertFalse( $result->success );
		$this->assertStringContainsString( 'st99_nonexistent', implode( ' ', $result->errors ) );
	}

	public function test_ordered_section_persistence(): void {
		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
			new \WP_Post( array( 'ID' => 9002, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'CTA', 'post_status' => 'publish', 'post_name' => 'st05_cta' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );
		$GLOBALS['_aio_post_meta']['9002'] = array( '_aio_internal_key' => 'st05_cta', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st05_cta', 'name' => 'CTA' ) ) ) );
		$GLOBALS['_aio_wp_insert_post_return'] = 2000;

		$result = $this->service->create( $this->valid_minimal_page_template() );
		$this->assertTrue( $result->success );
		$ordered = $result->definition[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ];
		$this->assertCount( 2, $ordered );
		$this->assertSame( 'st01_hero', $ordered[0][ Page_Template_Schema::SECTION_ITEM_KEY ] );
		$this->assertSame( 0, $ordered[0][ Page_Template_Schema::SECTION_ITEM_POSITION ] );
		$this->assertSame( 'st05_cta', $ordered[1][ Page_Template_Schema::SECTION_ITEM_KEY ] );
		$this->assertSame( 1, $ordered[1][ Page_Template_Schema::SECTION_ITEM_POSITION ] );
	}

	public function test_deprecated_status_transition(): void {
		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
			new \WP_Post( array( 'ID' => 9002, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'CTA', 'post_status' => 'publish', 'post_name' => 'st05_cta' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );
		$GLOBALS['_aio_post_meta']['9002'] = array( '_aio_internal_key' => 'st05_cta', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st05_cta', 'name' => 'CTA' ) ) ) );
		$GLOBALS['_aio_wp_insert_post_return'] = 3000;

		$r = $this->service->create( $this->valid_minimal_page_template() );
		$this->assertTrue( $r->success );

		$pt_post = new \WP_Post( array( 'ID' => 3000, 'post_type' => Object_Type_Keys::PAGE_TEMPLATE, 'post_title' => 'Landing', 'post_status' => 'publish', 'post_name' => 'pt_landing_contact' ) );
		$GLOBALS['_aio_get_post_return']   = $pt_post;
		$GLOBALS['_aio_wp_query_posts']    = array( $pt_post );
		$GLOBALS['_aio_post_meta']['3000'] = array(
			'_aio_internal_key'               => 'pt_landing_contact',
			'_aio_status'                     => 'active',
			'_aio_page_template_definition'   => wp_json_encode( $r->definition ),
		);
		$GLOBALS['_aio_wp_update_post_return'] = 3000;

		$dep = $this->service->deprecate( 3000, 'Superseded by pt_landing_v2', 'pt_landing_v2' );
		$this->assertTrue( $dep->success );
		$this->assertSame( 'deprecated', $dep->definition[ Page_Template_Schema::FIELD_STATUS ] );
		$this->assertArrayHasKey( 'deprecation', $dep->definition );
		$this->assertSame( 'pt_landing_v2', $dep->definition['deprecation']['replacement_template_key'] );
	}

	public function test_archetype_query_behavior(): void {
		$def = $this->valid_minimal_page_template();
		$pt  = new \WP_Post( array( 'ID' => 4000, 'post_type' => Object_Type_Keys::PAGE_TEMPLATE, 'post_title' => 'Landing', 'post_status' => 'publish', 'post_name' => 'pt_landing_contact' ) );
		$GLOBALS['_aio_wp_query_posts']   = array( $pt );
		$GLOBALS['_aio_post_meta']['4000'] = array(
			'_aio_internal_key'             => 'pt_landing_contact',
			'_aio_status'                   => 'active',
			'_aio_page_template_definition' => wp_json_encode( $def ),
		);
		$list = $this->service->list_by_archetype( 'landing_page', 10, 0 );
		$this->assertCount( 1, $list );
		$this->assertSame( 'pt_landing_contact', $list[0][ Page_Template_Schema::FIELD_INTERNAL_KEY ] );
		$this->assertSame( 'landing_page', $list[0][ Page_Template_Schema::FIELD_ARCHETYPE ] );
	}

	public function test_one_pager_metadata_persistence(): void {
		$def = $this->valid_minimal_page_template();
		$def[ Page_Template_Schema::FIELD_ONE_PAGER ]['page_purpose_summary'] = 'Contact-focused landing: hero plus primary CTA.';
		$def[ Page_Template_Schema::FIELD_ONE_PAGER ]['cross_section_strategy_notes'] = 'Keep tone consistent.';

		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
			new \WP_Post( array( 'ID' => 9002, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'CTA', 'post_status' => 'publish', 'post_name' => 'st05_cta' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );
		$GLOBALS['_aio_post_meta']['9002'] = array( '_aio_internal_key' => 'st05_cta', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st05_cta', 'name' => 'CTA' ) ) ) );
		$GLOBALS['_aio_wp_insert_post_return'] = 5000;

		$result = $this->service->create( $def );
		$this->assertTrue( $result->success );
		$one_pager = $this->service->get_one_pager_metadata( $result->definition );
		$this->assertSame( 'Contact-focused landing: hero plus primary CTA.', $one_pager['page_purpose_summary'] );
		$this->assertSame( 'Keep tone consistent.', $one_pager['cross_section_strategy_notes'] );
	}

	public function test_empty_one_pager_page_purpose_summary_rejection(): void {
		$input = $this->valid_minimal_page_template();
		$input[ Page_Template_Schema::FIELD_ONE_PAGER ]['page_purpose_summary'] = '';

		$GLOBALS['_aio_wp_query_posts'] = array(
			new \WP_Post( array( 'ID' => 9001, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'Hero', 'post_status' => 'publish', 'post_name' => 'st01_hero' ) ),
			new \WP_Post( array( 'ID' => 9002, 'post_type' => Object_Type_Keys::SECTION_TEMPLATE, 'post_title' => 'CTA', 'post_status' => 'publish', 'post_name' => 'st05_cta' ) ),
		);
		$GLOBALS['_aio_post_meta']['9001'] = array( '_aio_internal_key' => 'st01_hero', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st01_hero' ) ) ) );
		$GLOBALS['_aio_post_meta']['9002'] = array( '_aio_internal_key' => 'st05_cta', '_aio_status' => 'active', '_aio_section_definition' => wp_json_encode( array_merge( $this->valid_minimal_section(), array( 'internal_key' => 'st05_cta', 'name' => 'CTA' ) ) ) );

		$validator = new \AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Validator(
			$this->normalizer,
			$this->page_repo,
			$this->section_registry
		);
		$result    = $validator->validate_for_create( $input );
		$this->assertFalse( $result->valid );
		$this->assertStringContainsString( 'one_pager', implode( ' ', $result->errors ) );
	}
}
