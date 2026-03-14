<?php
/**
 * Unit tests for Animation_QA_Service (Prompt 187).
 * Covers fallback/metadata violations, reduced-motion check result, manual checklist, and report payload.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\QA\Animation_QA_Result;
use AIOPageBuilder\Domain\Registries\QA\Animation_QA_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Fallback_Service;
use AIOPageBuilder\Domain\Rendering\Animation\Animation_Tier_Resolver;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/QA/Animation_QA_Result.php';
require_once $plugin_root . '/src/Domain/Registries/QA/Animation_QA_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Rendering/Animation/Animation_Fallback_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Animation/Reduced_Motion_Service.php';
require_once $plugin_root . '/src/Domain/Rendering/Animation/Animation_Tier_Resolver.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Animation_QA_Service_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Page_Template_Repository $page_repo;
	private Animation_QA_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo = new Section_Template_Repository();
		$this->page_repo    = new Page_Template_Repository();
		$this->service     = new Animation_QA_Service(
			$this->section_repo,
			$this->page_repo,
			new Animation_Fallback_Service(),
			new Animation_Tier_Resolver()
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function section_def( string $key, array $overrides = array() ): array {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY           => $key,
			Section_Schema::FIELD_NAME                   => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY        => 'Purpose',
			Section_Schema::FIELD_CATEGORY               => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF     => 'acf',
			Section_Schema::FIELD_HELPER_REF              => 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF        => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT         => 'default',
			Section_Schema::FIELD_VARIANTS                => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY           => array(),
			Section_Schema::FIELD_VERSION                 => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                  => 'active',
			Section_Schema::FIELD_RENDER_MODE             => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION       => array( 'none' => true ),
			'animation_tier'                             => 'none',
			'reduced_motion_behavior'                     => 'honor',
		);
		return array_merge( $def, $overrides );
	}

	private function page_def( string $key, array $overrides = array() ): array {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY       => $key,
			Page_Template_Schema::FIELD_NAME               => 'Page ' . $key,
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY    => 'Purpose',
			Page_Template_Schema::FIELD_ARCHETYPE          => 'landing',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS   => array(),
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array(),
			Page_Template_Schema::FIELD_COMPATIBILITY      => array(),
			Page_Template_Schema::FIELD_ONE_PAGER          => array( 'page_purpose_summary' => 'Summary' ),
			Page_Template_Schema::FIELD_VERSION            => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS             => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
		);
		return array_merge( $def, $overrides );
	}

	private function seed_sections_and_pages( array $section_defs, array $page_defs ): void {
		$posts = array();
		$meta  = array();
		$id    = 9000;
		foreach ( $section_defs as $def ) {
			$posts[] = new \WP_Post( array(
				'ID'          => $id,
				'post_type'   => Object_Type_Keys::SECTION_TEMPLATE,
				'post_title'  => $def[ Section_Schema::FIELD_NAME ],
				'post_status' => 'publish',
				'post_name'   => $def[ Section_Schema::FIELD_INTERNAL_KEY ],
			) );
			$meta[ (string) $id ] = array(
				'_aio_internal_key'         => $def[ Section_Schema::FIELD_INTERNAL_KEY ],
				'_aio_status'               => $def[ Section_Schema::FIELD_STATUS ] ?? 'active',
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

	public function test_invalid_tier_produces_violation(): void {
		$section = $this->section_def( 'st_bad_tier', array( 'animation_tier' => 'invalid_tier_value' ) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$violations = $result->get_fallback_violation_summary();
		$invalid = array_filter( $violations, function ( $v ) {
			return ( $v['code'] ?? '' ) === 'invalid_tier' && ( $v['template_key'] ?? '' ) === 'st_bad_tier';
		} );
		$this->assertCount( 1, $invalid );
	}

	public function test_invalid_family_produces_violation(): void {
		$section = $this->section_def( 'st_bad_family', array( 'animation_families' => array( 'entrance', 'invalid_family' ) ) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$violations = $result->get_fallback_violation_summary();
		$invalid = array_filter( $violations, function ( $v ) {
			return ( $v['code'] ?? '' ) === 'invalid_family';
		} );
		$this->assertNotEmpty( $invalid );
	}

	public function test_reduced_motion_check_result_present(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_ok' ) ), array() );
		$result = $this->service->run();
		$rm = $result->get_reduced_motion_check_result();
		$this->assertArrayHasKey( 'sections_checked', $rm );
		$this->assertArrayHasKey( 'all_resolve_safe_tier', $rm );
		$this->assertArrayHasKey( 'sections_capped_count', $rm );
		$this->assertSame( 1, $rm['sections_checked'] );
		$this->assertTrue( $rm['all_resolve_safe_tier'] );
	}

	public function test_manual_qa_checklist_non_empty(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_ok' ) ), array() );
		$result = $this->service->run();
		$checklist = $result->get_manual_qa_checklist();
		$this->assertNotEmpty( $checklist );
		$this->assertStringContainsString( 'Tier none', $checklist[0] );
		$this->assertStringContainsString( 'Reduced-motion', implode( ' ', $checklist ) );
	}

	public function test_run_returns_animation_qa_result(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_ok' ) ), array( $this->page_def( 'pt_ok' ) ) );
		$result = $this->service->run();
		$this->assertInstanceOf( Animation_QA_Result::class, $result );
	}

	public function test_example_payload_structure(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_ok' ) ), array( $this->page_def( 'pt_ok' ) ) );
		$result = $this->service->run();
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'passed', $payload );
		$this->assertArrayHasKey( 'fallback_violation_summary', $payload );
		$this->assertArrayHasKey( 'reduced_motion_check_result', $payload );
		$this->assertArrayHasKey( 'section_summary', $payload );
		$this->assertArrayHasKey( 'page_summary', $payload );
		$this->assertArrayHasKey( 'manual_qa_checklist', $payload );
		$this->assertArrayHasKey( 'by_tier', $payload['section_summary'] );
		$this->assertArrayHasKey( 'with_tier_cap', $payload['page_summary'] );
		$lines = $result->to_summary_lines();
		$this->assertNotEmpty( $lines );
		$this->assertStringContainsString( 'Animation QA', implode( ' ', $lines ) );
	}

	public function test_passed_when_no_violations_and_safe_reduced_motion(): void {
		$sections = array(
			$this->section_def( 'st_none' ),
			$this->section_def( 'st_subtle', array( 'animation_tier' => 'subtle', 'animation_families' => array( 'entrance' ) ) ),
		);
		$this->seed_sections_and_pages( $sections, array( $this->page_def( 'pt_ok' ) ) );
		$result = $this->service->run();
		$this->assertTrue( $result->is_passed() );
		$this->assertCount( 0, $result->get_fallback_violation_summary() );
	}

	public function test_invalid_tier_cap_on_page_produces_violation(): void {
		$page = $this->page_def( 'pt_bad_cap', array( 'animation_tier_cap' => 'invalid_cap' ) );
		$this->seed_sections_and_pages( array(), array( $page ) );
		$result = $this->service->run();
		$violations = $result->get_fallback_violation_summary();
		$invalid = array_filter( $violations, function ( $v ) {
			return ( $v['scope'] ?? '' ) === 'page' && ( $v['code'] ?? '' ) === 'invalid_tier_cap';
		} );
		$this->assertCount( 1, $invalid );
	}
}
