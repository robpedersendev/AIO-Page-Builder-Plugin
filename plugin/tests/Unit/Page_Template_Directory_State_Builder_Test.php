<?php
/**
 * Unit tests for Page_Template_Directory_State_Builder (spec §49.7, page-template-directory-ia-extension, Prompt 168).
 *
 * Manual verification checklist:
 * - Breadcrumbs: Page Templates > [Category] > [Family] match hierarchy.
 * - Hierarchical browse: root shows category tree; category shows family list; list shows template rows.
 * - Search/filters: status and search narrow results; pagination appears when total_pages > 1.
 * - One-pager link: placeholder until detail screen; View link includes template key.
 * - Section-order preview: section_count column in list.
 * - Permission: composition control only when can_manage_templates.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\UI\Page_Template_Directory_State_Builder;
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
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Query_Service.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/UI/Page_Template_Directory_State_Builder.php';

final class Page_Template_Directory_State_Builder_Test extends TestCase {

	private Page_Template_Repository $page_repo;
	private Section_Template_Repository $section_repo;
	private Large_Library_Query_Service $query_service;
	private Page_Template_Directory_State_Builder $builder;

	private function minimal_page_def( string $key, string $category_class, string $family, int $section_count = 0, string $version = '1' ): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => $key,
			Page_Template_Schema::FIELD_NAME             => 'Page ' . $key,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Purpose ' . $key,
			Page_Template_Schema::FIELD_ARCHETYPE        => 'landing_page',
			Page_Template_Schema::FIELD_STATUS           => 'stable',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array_fill( 0, $section_count, array( 'section_key' => 'st_hero' ) ),
			Page_Template_Schema::FIELD_VERSION          => array( 'version' => $version ),
			'template_category_class'                    => $category_class,
			'template_family'                            => $family,
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo        = new Section_Template_Repository();
		$this->page_repo           = new Page_Template_Repository();
		$this->query_service       = new Large_Library_Query_Service( $this->section_repo, $this->page_repo );
		$this->builder             = new Page_Template_Directory_State_Builder( $this->query_service );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	public function test_build_state_root_returns_view_and_breadcrumbs(): void {
		$state = $this->builder->build_state( array() );

		$this->assertSame( 'root', $state['view'] );
		$this->assertIsArray( $state['breadcrumbs'] );
		$this->assertCount( 1, $state['breadcrumbs'] );
		$this->assertSame( 'Page Templates', $state['breadcrumbs'][0]['label'] );
		$this->assertArrayHasKey( 'tree', $state );
		$this->assertCount( 4, $state['tree'] );
		$this->assertSame( 'Top Level Page Templates', $state['tree'][0]['label'] );
		$this->assertArrayHasKey( 'filters', $state );
		$this->assertArrayHasKey( 'base_url', $state );
	}

	public function test_build_state_category_returns_families(): void {
		$posts = array();
		$meta  = array();
		foreach ( array( array( 'pt_home', 'top_level', 'home' ), array( 'pt_services', 'top_level', 'services' ) ) as $i => $t ) {
			$def                  = $this->minimal_page_def( $t[0], $t[1], $t[2] );
			$id                   = 13000 + $i;
			$posts[]              = new \WP_Post(
				array(
					'ID'          => $id,
					'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
					'post_title'  => $def['name'],
					'post_status' => 'publish',
					'post_name'   => $def['internal_key'],
				)
			);
			$meta[ (string) $id ] = array(
				'_aio_internal_key'             => $def['internal_key'],
				'_aio_status'                   => $def['status'],
				'_aio_page_template_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state( array( 'category_class' => 'top_level' ) );

		$this->assertSame( 'category', $state['view'] );
		$this->assertCount( 2, $state['breadcrumbs'] );
		$this->assertSame( 'Top Level Page Templates', $state['breadcrumbs'][1]['label'] );
		$this->assertNotEmpty( $state['families'] );
		$slugs = array_column( $state['families'], 'slug' );
		$this->assertContains( 'home', $slugs );
		$this->assertContains( 'services', $slugs );
	}

	public function test_build_state_list_returns_list_result_with_section_count_and_version(): void {
		$posts                          = array();
		$meta                           = array();
		$def                            = $this->minimal_page_def( 'pt_home', 'top_level', 'home', 5, '2' );
		$id                             = 14000;
		$posts[]                        = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			)
		);
		$meta[ (string) $id ]           = array(
			'_aio_internal_key'             => $def['internal_key'],
			'_aio_status'                   => $def['status'],
			'_aio_page_template_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state(
			array(
				'category_class' => 'top_level',
				'family'         => 'home',
			)
		);

		$this->assertSame( 'list', $state['view'] );
		$this->assertCount( 3, $state['breadcrumbs'] );
		$list_result = $state['list_result'];
		$this->assertCount( 1, $list_result['rows'] );
		$this->assertSame( 5, $list_result['rows'][0]['section_count'] );
		$this->assertSame( '2', $list_result['rows'][0]['version'] );
		$this->assertArrayHasKey( 'pagination', $list_result );
		$this->assertSame( 1, $list_result['total_matching'] );
	}

	public function test_build_state_search_returns_view_search_and_list_result(): void {
		$posts                          = array();
		$meta                           = array();
		$def                            = $this->minimal_page_def( 'pt_foo_bar', 'top_level', 'home' );
		$def['name']                    = 'Foo Bar Page';
		$id                             = 15000;
		$posts[]                        = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			)
		);
		$meta[ (string) $id ]           = array(
			'_aio_internal_key'             => $def['internal_key'],
			'_aio_status'                   => $def['status'],
			'_aio_page_template_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state( array( 'search' => 'foo' ) );

		$this->assertSame( 'search', $state['view'] );
		$this->assertCount( 2, $state['breadcrumbs'] );
		$this->assertStringContainsString( 'foo', $state['breadcrumbs'][1]['label'] );
		$this->assertCount( 1, $state['list_result']['rows'] );
	}
}
