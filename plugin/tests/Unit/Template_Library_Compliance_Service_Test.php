<?php
/**
 * Unit tests for Template_Library_Compliance_Service (Prompt 176).
 * Covers count validation, CTA rule validation, category coverage, preview/one-pager, metadata, exportability.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\QA\Template_Library_Compliance_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/QA/Template_Library_Compliance_Result.php';
require_once $plugin_root . '/src/Domain/Registries/QA/Template_Library_Compliance_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Template_Library_Compliance_Service_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Page_Template_Repository $page_repo;
	private Template_Library_Compliance_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo = new Section_Template_Repository();
		$this->page_repo    = new Page_Template_Repository();
		$this->service     = new Template_Library_Compliance_Service( $this->section_repo, $this->page_repo );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function section_def( string $key, array $overrides = array() ): array {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY           => $key,
			Section_Schema::FIELD_NAME                  => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY        => 'Purpose',
			Section_Schema::FIELD_CATEGORY              => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF   => 'acf',
			Section_Schema::FIELD_HELPER_REF            => 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF      => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
			Section_Schema::FIELD_VARIANTS              => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY         => array(),
			Section_Schema::FIELD_VERSION               => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                => 'active',
			Section_Schema::FIELD_RENDER_MODE          => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION     => array( 'none' => true ),
			'section_purpose_family'                   => 'hero',
			'preview_defaults'                         => array( 'headline' => 'Preview' ),
			'accessibility_warnings_or_enhancements'   => 'Use semantic headings.',
			'animation_tier'                          => 'none',
		);
		return array_merge( $def, $overrides );
	}

	private function page_def( string $key, string $template_class, array $ordered_sections, array $overrides = array() ): array {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY       => $key,
			Page_Template_Schema::FIELD_NAME               => 'Page ' . $key,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Purpose',
			Page_Template_Schema::FIELD_ARCHETYPE          => 'landing',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS   => $ordered_sections,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array(),
			Page_Template_Schema::FIELD_COMPATIBILITY      => array(),
			Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Summary' ),
			Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS             => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
			'template_category_class'                     => $template_class,
			'template_family'                             => 'home',
		);
		return array_merge( $def, $overrides );
	}

	private function seed_sections_and_pages( array $section_defs, array $page_defs ): void {
		$posts = array();
		$meta  = array();
		$id    = 7000;
		foreach ( $section_defs as $def ) {
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def[ Section_Schema::FIELD_NAME ],
				'post_status' => 'publish',
				'post_name'   => $def[ Section_Schema::FIELD_INTERNAL_KEY ],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'        => $def[ Section_Schema::FIELD_INTERNAL_KEY ],
				'_aio_status'              => $def[ Section_Schema::FIELD_STATUS ] ?? 'active',
				'_aio_section_definition'   => wp_json_encode( $def ),
			);
			$id++;
		}
		foreach ( $page_defs as $def ) {
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::PAGE_TEMPLATE,
				'post_title'  => $def[ Page_Template_Schema::FIELD_NAME ],
				'post_status' => 'publish',
				'post_name'   => $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'             => $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ],
				'_aio_status'                   => $def[ Page_Template_Schema::FIELD_STATUS ] ?? 'active',
				'_aio_page_template_definition' => wp_json_encode( $def ),
			);
			$id++;
		}
		$GLOBALS['_aio_wp_query_posts'] = $posts;
		$GLOBALS['_aio_post_meta']      = $meta;
	}

	public function test_run_with_empty_library_fails_count(): void {
		$this->seed_sections_and_pages( array(), array() );
		$result = $this->service->run();
		$this->assertFalse( $result->is_passed() );
		$c = $result->get_count_summary();
		$this->assertSame( 0, $c['section_total'] );
		$this->assertSame( 0, $c['page_total'] );
		$this->assertSame( 250, $c['section_target'] );
		$this->assertSame( 500, $c['page_target'] );
	}

	public function test_run_detects_bottom_cta_missing(): void {
		$non_cta = $this->section_def( 'st_non_cta', array( 'cta_classification' => 'none' ) );
		$cta     = $this->section_def( 'st_cta', array( 'cta_classification' => 'primary_cta' ) );
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non_cta', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_bad', 'top_level', $ordered );
		$this->seed_sections_and_pages( array( $non_cta, $cta ), array( $page ) );
		$result = $this->service->run();
		$violations = $result->get_cta_rule_violations();
		$codes = array_column( $violations, 'code' );
		$this->assertContains( 'bottom_cta_missing', $codes );
	}

	public function test_run_detects_adjacent_cta_violation(): void {
		$cta1 = $this->section_def( 'st_cta1', array( 'cta_classification' => 'primary_cta' ) );
		$cta2 = $this->section_def( 'st_cta2', array( 'cta_classification' => 'contact_cta' ) );
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta1', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta2', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_adjacent', 'top_level', $ordered );
		$this->seed_sections_and_pages( array( $cta1, $cta2 ), array( $page ) );
		$result = $this->service->run();
		$violations = $result->get_cta_rule_violations();
		$codes = array_column( $violations, 'code' );
		$this->assertContains( 'adjacent_cta_violation', $codes );
	}

	public function test_run_detects_pages_missing_one_pager(): void {
		$section = $this->section_def( 'st_hero' );
		$page = $this->page_def( 'pt_no_op', 'top_level', array(), array( Page_Template_Schema::FIELD_ONE_PAGER => null ) );
		$this->seed_sections_and_pages( array( $section ), array( $page ) );
		$result = $this->service->run();
		$preview = $result->get_preview_readiness();
		$this->assertContains( 'pt_no_op', $preview['pages_missing_one_pager'] );
	}

	public function test_run_detects_sections_missing_accessibility(): void {
		$section = $this->section_def( 'st_no_a11y', array( 'accessibility_warnings_or_enhancements' => null ) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$meta = $result->get_metadata_checks();
		$this->assertContains( 'st_no_a11y', $meta['sections_missing_accessibility'] );
	}

	public function test_run_detects_sections_invalid_animation_tier(): void {
		$section = $this->section_def( 'st_bad_anim', array( 'animation_tier' => 'invalid_tier' ) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$meta = $result->get_metadata_checks();
		$this->assertContains( 'st_bad_anim', $meta['sections_invalid_animation'] );
	}

	public function test_run_result_has_category_coverage_summary(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_one' ) ), array() );
		$result = $this->service->run();
		$cov = $result->get_category_coverage_summary();
		$this->assertArrayHasKey( 'section_family_minimums', $cov );
		$this->assertArrayHasKey( 'page_class_minimums', $cov );
		$this->assertArrayHasKey( 'max_share_violations', $cov );
	}

	public function test_run_export_viability_structure(): void {
		$section = $this->section_def( 'st_export_ok' );
		$page = $this->page_def( 'pt_export_ok', 'top_level', array() );
		$this->seed_sections_and_pages( array( $section ), array( $page ) );
		$result = $this->service->run();
		$export = $result->get_export_viability();
		$this->assertArrayHasKey( 'viable', $export );
		$this->assertArrayHasKey( 'errors', $export );
		$this->assertIsBool( $export['viable'] );
		$this->assertIsArray( $export['errors'] );
	}

	public function test_run_example_payload_and_summary_excerpt(): void {
		$sections = array(
			$this->section_def( 'st_hero' ),
			$this->section_def( 'st_cta', array( 'cta_classification' => 'primary_cta' ) ),
		);
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_min', 'top_level', $ordered );
		$this->seed_sections_and_pages( $sections, array( $page ) );
		$result = $this->service->run();
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'count_summary', $payload );
		$this->assertArrayHasKey( 'cta_rule_violations', $payload );
		$this->assertSame( 2, $payload['count_summary']['section_total'] );
		$this->assertSame( 1, $payload['count_summary']['page_total'] );
		$lines = $result->to_summary_lines();
		$this->assertNotEmpty( $lines );
		$this->assertStringContainsString( 'Sections:', $lines[0] );
		$this->assertStringContainsString( 'Compliance:', $lines[ count( $lines ) - 1 ] );
	}
}
