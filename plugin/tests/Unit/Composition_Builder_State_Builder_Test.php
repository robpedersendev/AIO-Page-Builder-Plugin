<?php
/**
 * Unit tests for Composition_Builder_State_Builder (Prompt 177).
 * CTA warning visibility, state payload shape, example composition-builder state payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\Compositions\UI\Composition_Builder_State_Builder;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Query_Service;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Pagination.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Filter_Result.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Query_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/UI/Composition_Filter_State.php';
require_once $plugin_root . '/src/Domain/Registries/Compositions/UI/Composition_Builder_State_Builder.php';

final class Composition_Builder_State_Builder_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Large_Library_Query_Service $query_service;
	private Composition_Builder_State_Builder $builder;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo        = new Section_Template_Repository();
		$page_repo                 = new Page_Template_Repository();
		$this->query_service       = new Large_Library_Query_Service( $this->section_repo, $page_repo );
		$this->builder             = new Composition_Builder_State_Builder( $this->query_service, $this->section_repo );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function seed_section( string $key, string $cta_classification = 'none', bool $preview = true ): void {
		$def                                       = array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Purpose',
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
			'section_purpose_family'                       => 'hero',
			'cta_classification'                           => $cta_classification,
			'preview_defaults'                             => $preview ? array( 'headline' => 'Preview' ) : null,
		);
		$id                                        = 8000 + count( $GLOBALS['_aio_post_meta'] );
		$posts                                     = array(
			new \WP_Post(
				array(
					'ID'          => $id,
					'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
					'post_title'  => $key,
					'post_status' => 'publish',
					'post_name'   => $key,
				)
			),
		);
		$GLOBALS['_aio_wp_query_posts']            = array_merge( $GLOBALS['_aio_wp_query_posts'] ?? array(), $posts );
		$GLOBALS['_aio_post_meta'][ (string) $id ] = array(
			'_aio_internal_key'       => $key,
			'_aio_status'             => 'active',
			'_aio_section_definition' => wp_json_encode( $def ),
		);
	}

	public function test_build_state_returns_expected_keys(): void {
		$this->seed_section( 'st_hero' );
		$request = array(
			'paged'    => 1,
			'per_page' => 25,
		);
		$state   = $this->builder->build_state( $request, null );
		$this->assertArrayHasKey( 'filter_state', $state );
		$this->assertArrayHasKey( 'section_result', $state );
		$this->assertArrayHasKey( 'ordered_sections_display', $state );
		$this->assertArrayHasKey( 'cta_warnings', $state );
		$this->assertArrayHasKey( 'insertion_hint', $state );
		$this->assertArrayHasKey( 'validation_status', $state );
		$this->assertArrayHasKey( 'preview_readiness', $state );
		$this->assertArrayHasKey( 'one_pager_ready', $state );
		$this->assertArrayHasKey( 'base_url', $state );
		$this->assertArrayHasKey( 'category_labels', $state );
		$this->assertArrayHasKey( 'cta_labels', $state );
	}

	public function test_cta_warning_bottom_cta_missing_when_last_section_not_cta(): void {
		$this->seed_section( 'st_cta', 'primary_cta' );
		$this->seed_section( 'st_hero', 'none' );
		$composition = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_test',
			Composition_Schema::FIELD_NAME                 => 'Test',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st_cta',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_STATUS               => 'draft',
			Composition_Schema::FIELD_VALIDATION_STATUS    => 'valid',
		);
		$state       = $this->builder->build_state( array(), $composition );
		$warnings    = $state['cta_warnings'];
		$codes       = array_column( $warnings, 'code' );
		$this->assertContains( 'bottom_cta_missing', $codes );
	}

	public function test_example_composition_builder_state_payload(): void {
		$this->seed_section( 'st_hero', 'none' );
		$this->seed_section( 'st_cta', 'primary_cta' );
		$composition = array(
			Composition_Schema::FIELD_COMPOSITION_ID       => 'comp_example',
			Composition_Schema::FIELD_NAME                 => 'Example',
			Composition_Schema::FIELD_ORDERED_SECTION_LIST => array(
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st_hero',
					Composition_Schema::SECTION_ITEM_POSITION => 0,
				),
				array(
					Composition_Schema::SECTION_ITEM_KEY => 'st_cta',
					Composition_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
			Composition_Schema::FIELD_STATUS               => 'draft',
			Composition_Schema::FIELD_VALIDATION_STATUS    => 'valid',
			Composition_Schema::FIELD_HELPER_ONE_PAGER_REF => 'doc_one_pager',
		);
		$state       = $this->builder->build_state(
			array(
				'purpose_family' => 'hero',
				'paged'          => 1,
				'per_page'       => 25,
			),
			$composition
		);
		$this->assertCount( 2, $state['ordered_sections_display'] );
		$this->assertTrue( $state['preview_readiness'] );
		$this->assertTrue( $state['one_pager_ready'] );
		$this->assertSame( 'valid', $state['validation_status'] );
		$this->assertNotEmpty( $state['section_result']['rows'] );
		$this->assertArrayHasKey( 'pagination', $state['section_result'] );
		$this->assertIsString( $state['insertion_hint'] );
	}
}
