<?php
/**
 * Unit tests for Section_Template_Directory_State_Builder (spec §49.6, section-template-directory-ia-extension, Prompt 169).
 *
 * Manual verification checklist:
 * - Purpose-family browsing: root shows purpose tree; purpose shows L3 (CTA or variant + All); list shows section rows.
 * - CTA filters: for cta/contact purpose, L3 shows CTA classification nodes.
 * - Helper-doc / field-summary: list row shows helper_ref and field_blueprint_ref; View link includes section key.
 * - Search/filters: status and search narrow results; pagination when total_pages > 1.
 * - Permission: directory is capability-gated.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\UI\Section_Template_Directory_State_Builder;
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
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Shared/Large_Library_Query_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/UI/Section_Template_Directory_State_Builder.php';

final class Section_Template_Directory_State_Builder_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Page_Template_Repository $page_repo;
	private Large_Library_Query_Service $query_service;
	private Section_Template_Directory_State_Builder $builder;

	private function minimal_section_def( string $key, string $purpose_family, string $cta = '', string $variation_family = '', string $helper_ref = '' ): array {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY             => $key,
			Section_Schema::FIELD_NAME                     => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Purpose ' . $key,
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STATUS                   => 'active',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'acf',
			Section_Schema::FIELD_HELPER_REF               => $helper_ref !== '' ? $helper_ref : 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array( 'label' => 'Default' ),
				'compact' => array( 'label' => 'Compact' ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array(),
			Section_Schema::FIELD_VERSION                  => array( 'version' => '1' ),
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'none' => true ),
			'section_purpose_family'                       => $purpose_family,
			'cta_classification'                           => $cta,
			'variation_family_key'                         => $variation_family,
		);
		return $def;
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo        = new Section_Template_Repository();
		$this->page_repo           = new Page_Template_Repository();
		$this->query_service       = new Large_Library_Query_Service( $this->section_repo, $this->page_repo );
		$this->builder             = new Section_Template_Directory_State_Builder( $this->query_service );
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
		$this->assertSame( 'Section Templates', $state['breadcrumbs'][0]['label'] );
		$this->assertArrayHasKey( 'tree', $state );
		$this->assertArrayHasKey( 'filters', $state );
		$this->assertArrayHasKey( 'base_url', $state );
	}

	public function test_build_state_purpose_returns_l3_nodes(): void {
		$posts = array();
		$meta  = array();
		foreach ( array( array( 'st_hero_1', 'hero', '', 'hero_primary' ), array( 'st_hero_2', 'hero', '', 'hero_compact' ) ) as $i => $t ) {
			$def                  = $this->minimal_section_def( $t[0], $t[1], $t[2], $t[3] );
			$id                   = 16000 + $i;
			$posts[]              = new \WP_Post(
				array(
					'ID'          => $id,
					'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
					'post_title'  => $def['name'],
					'post_status' => 'publish',
					'post_name'   => $def['internal_key'],
				)
			);
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state( array( 'purpose_family' => 'hero' ) );

		$this->assertSame( 'purpose', $state['view'] );
		$this->assertCount( 2, $state['breadcrumbs'] );
		$this->assertSame( 'Hero', $state['breadcrumbs'][1]['label'] );
		$this->assertNotEmpty( $state['l3_nodes'] );
	}

	public function test_build_state_list_returns_list_result_with_helper_and_version(): void {
		$posts                          = array();
		$meta                           = array();
		$def                            = $this->minimal_section_def( 'st_hero_01', 'hero', '', 'hero_primary', 'hero_helper' );
		$id                             = 17000;
		$posts[]                        = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			)
		);
		$meta[ (string) $id ]           = array(
			'_aio_internal_key'       => $def['internal_key'],
			'_aio_status'             => $def['status'],
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state(
			array(
				'purpose_family'       => 'hero',
				'variation_family_key' => 'hero_primary',
			)
		);

		$this->assertSame( 'list', $state['view'] );
		$this->assertCount( 3, $state['breadcrumbs'] );
		$list_result = $state['list_result'];
		$this->assertCount( 1, $list_result['rows'] );
		$this->assertSame( 'hero_helper', $list_result['rows'][0]['helper_ref'] );
		$this->assertSame( '1', $list_result['rows'][0]['version'] );
		$this->assertSame( 2, $list_result['rows'][0]['variant_count'] );
		$this->assertArrayHasKey( 'pagination', $list_result );
	}

	public function test_build_state_search_returns_view_search_and_list_result(): void {
		$posts                          = array();
		$meta                           = array();
		$def                            = $this->minimal_section_def( 'st_cta_signup', 'cta', 'primary_cta', '' );
		$def['name']                    = 'CTA Signup Section';
		$id                             = 18000;
		$posts[]                        = new \WP_Post(
			array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			)
		);
		$meta[ (string) $id ]           = array(
			'_aio_internal_key'       => $def['internal_key'],
			'_aio_status'             => $def['status'],
			'_aio_section_definition' => wp_json_encode( $def ),
		);
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$state = $this->builder->build_state( array( 'search' => 'signup' ) );

		$this->assertSame( 'search', $state['view'] );
		$this->assertCount( 2, $state['breadcrumbs'] );
		$this->assertStringContainsString( 'signup', $state['breadcrumbs'][1]['label'] );
		$this->assertCount( 1, $state['list_result']['rows'] );
	}
}
