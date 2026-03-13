<?php
/**
 * Unit tests for large-library query, pagination, filter result (spec §55.8, Prompt 145).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Filter_Result;
use AIOPageBuilder\Domain\Registries\Shared\Large_Library_Pagination;
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

final class Large_Library_Query_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Page_Template_Repository $page_template_repo;
	private Large_Library_Query_Service $query_service;

	private function minimal_section_definition( string $key, string $category, string $status = 'active', string $purpose_family = '' ): array {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY           => $key,
			Section_Schema::FIELD_NAME                  => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY      => 'Purpose ' . $key,
			Section_Schema::FIELD_CATEGORY             => $category,
			Section_Schema::FIELD_STATUS                => $status,
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF  => 'acf',
			Section_Schema::FIELD_HELPER_REF           => 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF     => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT      => 'default',
			Section_Schema::FIELD_VARIANTS             => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY        => array(),
			Section_Schema::FIELD_VERSION              => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_RENDER_MODE          => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION    => array( 'none' => true ),
		);
		if ( $purpose_family !== '' ) {
			$def['section_purpose_family'] = $purpose_family;
		}
		return $def;
	}

	private function minimal_page_template_definition( string $key, string $archetype, string $status = 'active' ): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY    => $key,
			Page_Template_Schema::FIELD_NAME           => 'Page ' . $key,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY => 'Purpose ' . $key,
			Page_Template_Schema::FIELD_ARCHETYPE      => $archetype,
			Page_Template_Schema::FIELD_STATUS         => $status,
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(),
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array(),
			Page_Template_Schema::FIELD_COMPATIBILITY  => array(),
			Page_Template_Schema::FIELD_ONE_PAGER      => array( 'page_purpose_summary' => '' ),
			Page_Template_Schema::FIELD_VERSION        => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
		);
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo       = new Section_Template_Repository();
		$this->page_template_repo = new Page_Template_Repository();
		$this->query_service      = new Large_Library_Query_Service( $this->section_repo, $this->page_template_repo );
	}

	protected function tearDown(): void {
		unset(
			$GLOBALS['_aio_wp_query_posts'],
			$GLOBALS['_aio_post_meta']
		);
		parent::tearDown();
	}

	public function test_pagination_from_page_size(): void {
		$p = Large_Library_Pagination::from_page_size( 1, 25, 100 );
		$this->assertSame( 1, $p->get_page() );
		$this->assertSame( 25, $p->get_per_page() );
		$this->assertSame( 100, $p->get_total() );
		$this->assertSame( 4, $p->get_total_pages() );
		$this->assertSame( 0, $p->get_offset() );

		$p2 = Large_Library_Pagination::from_page_size( 3, 10, 25 );
		$this->assertSame( 3, $p2->get_page() );
		$this->assertSame( 20, $p2->get_offset() );
		$this->assertSame( 3, $p2->get_total_pages() );
	}

	public function test_pagination_to_array(): void {
		$p = Large_Library_Pagination::from_page_size( 2, 10, 50 );
		$a = $p->to_array();
		$this->assertSame( 2, $a['page'] );
		$this->assertSame( 10, $a['per_page'] );
		$this->assertSame( 50, $a['total'] );
		$this->assertSame( 5, $a['total_pages'] );
		$this->assertSame( 10, $a['offset'] );
	}

	public function test_filter_result_to_array(): void {
		$pagination = Large_Library_Pagination::from_page_size( 1, 25, 10 );
		$result     = new Large_Library_Filter_Result(
			array( array( 'internal_key' => 'st_hero', 'name' => 'Hero' ) ),
			$pagination,
			array( 'category' => array( 'hero_intro' => 10 ) ),
			10
		);
		$a = $result->to_array();
		$this->assertCount( 1, $a['rows'] );
		$this->assertSame( 'st_hero', $a['rows'][0]['internal_key'] );
		$this->assertSame( 10, $a['total_matching'] );
		$this->assertArrayHasKey( 'category', $a['filter_counts'] );
		$this->assertSame( 10, $a['filter_counts']['category']['hero_intro'] );
	}

	public function test_category_filtering_sections(): void {
		$posts = array();
		$meta  = array();
		$defs  = array(
			$this->minimal_section_definition( 'st_hero_1', 'hero_intro' ),
			$this->minimal_section_definition( 'st_hero_2', 'hero_intro' ),
			$this->minimal_section_definition( 'st_cta_1', 'cta_conversion' ),
		);
		foreach ( $defs as $i => $def ) {
			$id = 5000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_sections( array( Large_Library_Query_Service::FILTER_CATEGORY => 'hero_intro' ), 1, 25 );
		$this->assertSame( 2, $result->get_total_matching() );
		$rows = $result->get_rows();
		$this->assertCount( 2, $rows );
		$this->assertSame( 'st_hero_1', $rows[0]['internal_key'] );
		$this->assertSame( 'st_hero_2', $rows[1]['internal_key'] );
		$counts = $result->get_filter_counts();
		$this->assertArrayHasKey( 'category', $counts );
		$this->assertSame( 2, $counts['category']['hero_intro'] );
	}

	public function test_pagination_slice(): void {
		$posts = array();
		$meta  = array();
		for ( $i = 0; $i < 30; $i++ ) {
			$def = $this->minimal_section_definition( 'st_' . $i, 'hero_intro' );
			$id  = 6000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_sections( array(), 2, 10 );
		$this->assertSame( 30, $result->get_total_matching() );
		$pagination = $result->get_pagination();
		$this->assertSame( 2, $pagination->get_page() );
		$this->assertSame( 10, $pagination->get_per_page() );
		$this->assertSame( 3, $pagination->get_total_pages() );
		$rows = $result->get_rows();
		$this->assertCount( 10, $rows );
		$this->assertSame( 'st_10', $rows[0]['internal_key'] );
	}

	public function test_combined_filters_sections(): void {
		$defs = array(
			$this->minimal_section_definition( 'st_a', 'hero_intro', 'active', 'hero' ),
			$this->minimal_section_definition( 'st_b', 'hero_intro', 'draft', 'hero' ),
			$this->minimal_section_definition( 'st_c', 'cta_conversion', 'active', 'cta' ),
		);
		$posts = array();
		$meta  = array();
		foreach ( $defs as $i => $def ) {
			$id = 7000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_sections( array(
			Large_Library_Query_Service::FILTER_CATEGORY => 'hero_intro',
			Large_Library_Query_Service::FILTER_STATUS  => 'active',
		), 1, 25 );
		$this->assertSame( 1, $result->get_total_matching() );
		$this->assertSame( 'st_a', $result->get_rows()[0]['internal_key'] );
	}

	public function test_search_filter_sections(): void {
		$defs = array(
			$this->minimal_section_definition( 'unique_hero_key', 'hero_intro' ),
			$this->minimal_section_definition( 'other_section', 'cta_conversion' ),
		);
		$defs[0]['name'] = 'Unique Hero Title';
		$defs[0]['purpose_summary'] = 'Contains unique phrase for search.';
		$posts = array();
		$meta  = array();
		foreach ( $defs as $i => $def ) {
			$id = 8000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition'  => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_sections( array( Large_Library_Query_Service::FILTER_SEARCH => 'unique phrase' ), 1, 25 );
		$this->assertSame( 1, $result->get_total_matching() );
		$this->assertSame( 'unique_hero_key', $result->get_rows()[0]['internal_key'] );
	}

	public function test_count_summary_sections(): void {
		$posts = array();
		$meta  = array();
		foreach ( array( 'active', 'active', 'draft' ) as $i => $status ) {
			$def = $this->minimal_section_definition( 'st_' . $i, 'hero_intro', $status );
			$id  = 9000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition'  => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$summary = $this->query_service->get_section_count_summary();
		$this->assertSame( 3, $summary['total'] );
		$this->assertSame( 2, $summary['by_status']['active'] );
		$this->assertSame( 1, $summary['by_status']['draft'] );
	}

	public function test_count_summary_page_templates(): void {
		$defs = array(
			$this->minimal_page_template_definition( 'pt_landing', 'landing_page', 'active' ),
			$this->minimal_page_template_definition( 'pt_service', 'service_page', 'active' ),
			$this->minimal_page_template_definition( 'pt_faq', 'faq_page', 'draft' ),
		);
		$posts = array();
		$meta  = array();
		foreach ( $defs as $i => $def ) {
			$id = 10000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'                => $def['internal_key'],
				'_aio_status'                      => $def['status'],
				'_aio_page_template_definition'    => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$summary = $this->query_service->get_page_template_count_summary();
		$this->assertSame( 3, $summary['total'] );
		$this->assertSame( 2, $summary['by_status']['active'] );
		$this->assertSame( 1, $summary['by_archetype']['landing_page'] );
		$this->assertSame( 1, $summary['by_archetype']['service_page'] );
	}

	public function test_query_page_templates_archetype_filter(): void {
		$defs = array(
			$this->minimal_page_template_definition( 'pt_landing', 'landing_page' ),
			$this->minimal_page_template_definition( 'pt_service', 'service_page' ),
		);
		$posts = array();
		$meta  = array();
		foreach ( $defs as $i => $def ) {
			$id = 11000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'             => $def['internal_key'],
				'_aio_status'                   => $def['status'],
				'_aio_page_template_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_page_templates( array( Large_Library_Query_Service::FILTER_ARCHETYPE => 'landing_page' ), 1, 25 );
		$this->assertSame( 1, $result->get_total_matching() );
		$this->assertSame( 'pt_landing', $result->get_rows()[0]['internal_key'] );
	}

	public function test_large_result_stability_many_sections(): void {
		$posts = array();
		$meta  = array();
		$n     = 260;
		for ( $i = 0; $i < $n; $i++ ) {
			$def = $this->minimal_section_definition( 'st_large_' . $i, $i % 2 === 0 ? 'hero_intro' : 'cta_conversion' );
			$id  = 12000 + $i;
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def['name'],
				'post_status' => 'publish',
				'post_name'   => $def['internal_key'],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'       => $def['internal_key'],
				'_aio_status'             => $def['status'],
				'_aio_section_definition' => wp_json_encode( $def ),
			);
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;

		$result = $this->query_service->query_sections( array(), 1, 25 );
		$this->assertSame( $n, $result->get_total_matching() );
		$this->assertCount( 25, $result->get_rows() );
		$this->assertSame( (int) ceil( $n / 25 ), $result->get_pagination()->get_total_pages() );
		$counts = $result->get_filter_counts();
		$this->assertSame( $n / 2, $counts['category']['hero_intro'] );
		$this->assertSame( $n / 2, $counts['category']['cta_conversion'] );
	}

	/**
	 * Example large-library filter result payload (spec §55.8 directory IA extension).
	 * Documents the exact shape returned by query_sections / query_page_templates for directory builders.
	 */
	public function test_example_large_library_filter_result_payload(): void {
		$pagination = Large_Library_Pagination::from_page_size( 2, 25, 87 );
		$rows = array(
			array(
				'internal_key'         => 'st_hero_01',
				'name'                 => 'Hero with CTA',
				'status'               => 'active',
				'category'             => 'hero_intro',
				'purpose_summary'      => 'Primary hero with headline and CTA.',
				'section_purpose_family' => 'hero',
				'cta_classification'   => 'primary',
				'variation_family_key' => 'default',
				'preview_available'    => true,
			),
			array(
				'internal_key'         => 'st_cta_02',
				'name'                 => 'Secondary CTA Block',
				'status'               => 'active',
				'category'             => 'cta_conversion',
				'purpose_summary'      => 'Conversion-focused CTA section.',
				'section_purpose_family' => 'cta',
				'cta_classification'   => 'secondary',
				'variation_family_key' => 'minimal',
				'preview_available'    => false,
			),
		);
		$filter_counts = array(
			'status'                 => array( 'active' => 80, 'draft' => 7 ),
			'category'               => array( 'hero_intro' => 25, 'cta_conversion' => 30, 'trust_proof' => 20, 'faq' => 12 ),
			'section_purpose_family' => array( 'hero' => 25, 'cta' => 35, 'trust' => 20 ),
			'cta_classification'    => array( 'primary' => 20, 'secondary' => 30, '' => 37 ),
		);
		$result = new Large_Library_Filter_Result( $rows, $pagination, $filter_counts, 87 );
		$payload = $result->to_array();

		$this->assertSame( 87, $payload['total_matching'] );
		$this->assertCount( 2, $payload['rows'] );
		$this->assertSame( 'st_hero_01', $payload['rows'][0]['internal_key'] );
		$this->assertSame( 2, $payload['pagination']['page'] );
		$this->assertSame( 25, $payload['pagination']['per_page'] );
		$this->assertSame( 4, $payload['pagination']['total_pages'] );
		$this->assertSame( 25, $payload['pagination']['offset'] );
		$this->assertSame( 80, $payload['filter_counts']['status']['active'] );
		$this->assertSame( 25, $payload['filter_counts']['category']['hero_intro'] );

		// Example payload shape for directory IA (one concrete instance).
		$example = array(
			'rows' => array(
				array(
					'internal_key'         => 'st_hero_01',
					'name'                 => 'Hero with CTA',
					'status'               => 'active',
					'category'             => 'hero_intro',
					'purpose_summary'      => 'Primary hero with headline and CTA.',
					'section_purpose_family' => 'hero',
					'cta_classification'   => 'primary',
					'variation_family_key' => 'default',
					'preview_available'    => true,
				),
			),
			'pagination' => array(
				'page'        => 2,
				'per_page'    => 25,
				'total'       => 87,
				'total_pages' => 4,
				'offset'      => 25,
			),
			'filter_counts' => array(
				'status'                 => array( 'active' => 80, 'draft' => 7 ),
				'category'               => array( 'hero_intro' => 25, 'cta_conversion' => 30 ),
				'section_purpose_family' => array( 'hero' => 25, 'cta' => 35 ),
				'cta_classification'    => array( 'primary' => 20, 'secondary' => 30 ),
			),
			'total_matching' => 87,
		);
		$this->assertSame( $example['total_matching'], $payload['total_matching'] );
		$this->assertSame( $example['pagination'], $payload['pagination'] );
	}
}
