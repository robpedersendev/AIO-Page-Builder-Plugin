<?php
/**
 * Unit tests for Field_Group_Derivation_Service: template-derived, composition-derived,
 * deprecated section behavior, merge_for_refinement (spec §20.10, §20.11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Assignment\Field_Group_Derivation_Service;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Composition_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Deprecation_Metadata.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Composition_Repository.php';
require_once $plugin_root . '/src/Domain/ACF/Assignment/Field_Group_Derivation_Service.php';

final class Field_Group_Derivation_Service_Test extends TestCase {

	private Page_Template_Repository $page_repo;
	private Composition_Repository $comp_repo;
	private Section_Template_Repository $section_repo;
	private Field_Group_Derivation_Service $service;

	private static int $seed_id = 9000;

	protected function setUp(): void {
		parent::setUp();
		self::$seed_id = 9000;
		$this->page_repo   = new Page_Template_Repository();
		$this->comp_repo   = new Composition_Repository();
		$this->section_repo = new Section_Template_Repository();
		$this->service    = new Field_Group_Derivation_Service(
			$this->page_repo,
			$this->comp_repo,
			$this->section_repo
		);
		$GLOBALS['_aio_wp_query_posts'] = array();
		$GLOBALS['_aio_post_meta']       = array();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function seed_template( string $key, array $definition ): void {
		$id   = self::$seed_id++;
		$post = new \WP_Post( array(
			'ID'          => $id,
			'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
			'post_title'  => 'Test',
			'post_status' => 'publish',
			'post_name'   => $key,
		) );
		$GLOBALS['_aio_wp_query_posts'][] = $post;
		$def = array_merge( array( 'internal_key' => $key ), $definition );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'             => $key,
			'_aio_status'                   => 'active',
			'_aio_page_template_definition' => wp_json_encode( $def ),
		);
	}

	private function seed_composition( string $comp_id, array $definition ): void {
		$id   = self::$seed_id++;
		$post = new \WP_Post( array(
			'ID'          => $id,
			'post_type'   => Object_Type_Keys::COMPOSITION,
			'post_title'  => 'Test',
			'post_status' => 'publish',
			'post_name'   => $comp_id,
		) );
		$GLOBALS['_aio_wp_query_posts'][] = $post;
		$def = array_merge( array( 'composition_id' => $comp_id ), $definition );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'          => $comp_id,
			'_aio_status'                => 'active',
			'_aio_composition_definition' => wp_json_encode( $def ),
		);
	}

	private function seed_section( string $key, array $definition ): void {
		$id   = self::$seed_id++;
		$post = new \WP_Post( array(
			'ID'          => $id,
			'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
			'post_title'  => 'Test',
			'post_status' => 'publish',
			'post_name'   => $key,
		) );
		$GLOBALS['_aio_wp_query_posts'][] = $post;
		$def = array_merge( array( 'internal_key' => $key ), $definition );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'      => $key,
			'_aio_status'            => $definition['status'] ?? 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
	}

	public function test_derive_from_template_returns_empty_when_template_not_found(): void {
		$result = $this->service->derive_from_template( 'unknown_template', true );
		$this->assertSame( array(), $result );
	}

	public function test_derive_from_template_returns_group_keys_from_ordered_sections(): void {
		$this->seed_template( 'pt_landing_contact', array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st05_faq', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => false ),
			),
		) );
		$this->seed_section( 'st01_hero', array( 'status' => 'active' ) );
		$this->seed_section( 'st05_faq', array( 'status' => 'active' ) );

		$result = $this->service->derive_from_template( 'pt_landing_contact', true );

		$this->assertContains( 'group_aio_st01_hero', $result );
		$this->assertContains( 'group_aio_st05_faq', $result );
		$this->assertCount( 2, $result );
	}

	public function test_derive_from_template_excludes_deprecated_sections_for_new_page(): void {
		$this->seed_template( 'pt_landing', array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0 ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_old_deprecated', Page_Template_Schema::SECTION_ITEM_POSITION => 1 ),
			),
		) );
		$this->seed_section( 'st01_hero', array( 'status' => 'active' ) );
		$this->seed_section( 'st_old_deprecated', array( 'status' => 'deprecated', 'deprecation' => array( 'deprecated' => true ) ) );

		$result = $this->service->derive_from_template( 'pt_landing', true );

		$this->assertContains( 'group_aio_st01_hero', $result );
		$this->assertNotContains( 'group_aio_st_old_deprecated', $result );
		$this->assertCount( 1, $result );
	}

	public function test_derive_from_template_includes_all_sections_when_not_for_new_page(): void {
		$this->seed_template( 'pt_legacy', array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero' ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_old_deprecated' ),
			),
		) );
		$this->seed_section( 'st01_hero', array( 'status' => 'active' ) );
		$this->seed_section( 'st_old_deprecated', array( 'status' => 'deprecated', 'deprecation' => array( 'deprecated' => true ) ) );

		$result = $this->service->derive_from_template( 'pt_legacy', false );

		$this->assertContains( 'group_aio_st01_hero', $result );
		$this->assertContains( 'group_aio_st_old_deprecated', $result );
		$this->assertCount( 2, $result );
	}

	public function test_derive_from_composition_returns_empty_when_composition_not_found(): void {
		$result = $this->service->derive_from_composition( 'comp-missing', true );
		$this->assertSame( array(), $result );
	}

	public function test_derive_from_composition_returns_group_keys_from_ordered_section_list(): void {
		$this->seed_composition( 'comp-custom-landing-001', array(
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array( Composition_Schema::SECTION_ITEM_KEY => 'st01_hero', Composition_Schema::SECTION_ITEM_POSITION => 0 ),
				array( Composition_Schema::SECTION_ITEM_KEY => 'st03_cta', Composition_Schema::SECTION_ITEM_POSITION => 1, Composition_Schema::SECTION_ITEM_VARIANT => 'compact' ),
			),
		) );
		$this->seed_section( 'st01_hero', array( 'status' => 'active' ) );
		$this->seed_section( 'st03_cta', array( 'status' => 'active' ) );

		$result = $this->service->derive_from_composition( 'comp-custom-landing-001', true );

		$this->assertContains( 'group_aio_st01_hero', $result );
		$this->assertContains( 'group_aio_st03_cta', $result );
		$this->assertCount( 2, $result );
	}

	public function test_merge_for_refinement_union_derived_and_existing(): void {
		$derived  = array( 'group_aio_st01_hero', 'group_aio_st05_faq' );
		$existing = array( 'group_aio_st01_hero', 'group_aio_st_old_deprecated' );

		$result = $this->service->merge_for_refinement( $derived, $existing );

		$this->assertContains( 'group_aio_st01_hero', $result );
		$this->assertContains( 'group_aio_st05_faq', $result );
		$this->assertContains( 'group_aio_st_old_deprecated', $result );
		$this->assertCount( 3, $result );
	}

	public function test_merge_for_refinement_deduplicates(): void {
		$derived  = array( 'group_aio_st01_hero' );
		$existing = array( 'group_aio_st01_hero' );

		$result = $this->service->merge_for_refinement( $derived, $existing );

		$this->assertSame( array( 'group_aio_st01_hero' ), $result );
	}

	public function test_derive_from_template_handles_empty_ordered_sections(): void {
		$this->seed_template( 'pt_empty', array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(),
		) );
		$result = $this->service->derive_from_template( 'pt_empty', true );
		$this->assertSame( array(), $result );
	}
}
