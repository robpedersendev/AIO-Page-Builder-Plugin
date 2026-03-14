<?php
/**
 * Unit tests for Template_Accessibility_Audit_Service (Prompt 186).
 * Covers heading-rule detection, CTA clarity checks, CTA-spacing/bottom/adjacent, human-review list, and report generation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\QA\Template_Accessibility_Audit_Result;
use AIOPageBuilder\Domain\Registries\QA\Template_Accessibility_Audit_Service;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Objects\Object_Type_Keys;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/QA/Template_Accessibility_Audit_Result.php';
require_once $plugin_root . '/src/Domain/Registries/QA/Template_Accessibility_Audit_Service.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';

final class Template_Accessibility_Audit_Service_Test extends TestCase {

	private Section_Template_Repository $section_repo;
	private Page_Template_Repository $page_repo;
	private Template_Accessibility_Audit_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_aio_post_meta'] = array();
		$this->section_repo = new Section_Template_Repository();
		$this->page_repo    = new Page_Template_Repository();
		$this->service     = new Template_Accessibility_Audit_Service( $this->section_repo, $this->page_repo );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_wp_query_posts'], $GLOBALS['_aio_post_meta'] );
		parent::tearDown();
	}

	private function section_def( string $key, array $overrides = array() ): array {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY           => $key,
			Section_Schema::FIELD_NAME                   => 'Section ' . $key,
			Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Purpose',
			Section_Schema::FIELD_CATEGORY               => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF    => 'acf',
			Section_Schema::FIELD_HELPER_REF             => 'helper',
			Section_Schema::FIELD_CSS_CONTRACT_REF       => 'css',
			Section_Schema::FIELD_DEFAULT_VARIANT       => 'default',
			Section_Schema::FIELD_VARIANTS               => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY          => array(),
			Section_Schema::FIELD_VERSION                => array( 'version' => '1' ),
			Section_Schema::FIELD_STATUS                 => 'active',
			Section_Schema::FIELD_RENDER_MODE            => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION      => array( 'none' => true ),
			'section_purpose_family'                    => 'hero',
			'preview_defaults'                          => array( 'headline' => 'Preview' ),
			'accessibility_warnings_or_enhancements'    => 'Use semantic headings.',
			'hierarchy_role_hints'                       => array( 'heading_level' => 'h1' ),
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
			Page_Template_Schema::FIELD_VERSION           => array( 'version' => '1' ),
			Page_Template_Schema::FIELD_STATUS            => 'active',
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
		$id    = 8000;
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
				'_aio_section_definition'    => wp_json_encode( $def ),
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

	public function test_heading_rule_detection(): void {
		$section = $this->section_def( 'st_hero_no_hints', array( 'hierarchy_role_hints' => null, 'section_purpose_family' => 'hero' ) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$violations = $result->get_semantic_rule_violations();
		$heading_violations = array_filter( $violations, function ( $v ) {
			return ( $v['rule_code'] ?? '' ) === 'heading_role_undeclared' && ( $v['template_key'] ?? '' ) === 'st_hero_no_hints';
		} );
		$this->assertCount( 1, $heading_violations, 'Expected one heading_role_undeclared for section without hierarchy_role_hints' );
	}

	public function test_cta_clarity_check(): void {
		$section = $this->section_def( 'st_cta_no_a11y', array(
			'cta_classification' => 'primary_cta',
			'accessibility_warnings_or_enhancements' => null,
		) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$violations = $result->get_semantic_rule_violations();
		$cta_violations = array_filter( $violations, function ( $v ) {
			return ( $v['rule_code'] ?? '' ) === 'cta_clarity_marker_missing' && ( $v['template_key'] ?? '' ) === 'st_cta_no_a11y';
		} );
		$this->assertCount( 1, $cta_violations );
	}

	public function test_accessibility_expectations_missing(): void {
		$section = $this->section_def( 'st_no_a11y', array(
			'cta_classification' => 'none',
			'accessibility_warnings_or_enhancements' => null,
		) );
		$this->seed_sections_and_pages( array( $section ), array() );
		$result = $this->service->run();
		$violations = $result->get_semantic_rule_violations();
		$a11y_violations = array_filter( $violations, function ( $v ) {
			return ( $v['rule_code'] ?? '' ) === 'accessibility_expectations_missing';
		} );
		$this->assertNotEmpty( $a11y_violations );
		$this->assertContains( 'st_no_a11y', array_column( $a11y_violations, 'template_key' ) );
	}

	public function test_bottom_cta_missing(): void {
		$non_cta = $this->section_def( 'st_non', array( 'cta_classification' => 'none' ) );
		$cta     = $this->section_def( 'st_cta', array( 'cta_classification' => 'primary_cta' ) );
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_bad_end', 'top_level', $ordered );
		$this->seed_sections_and_pages( array( $non_cta, $cta ), array( $page ) );
		$result = $this->service->run();
		$violations = $result->get_semantic_rule_violations();
		$bottom = array_filter( $violations, function ( $v ) {
			return ( $v['scope'] ?? '' ) === 'page' && ( $v['rule_code'] ?? '' ) === 'bottom_cta_missing';
		} );
		$this->assertCount( 1, $bottom );
		$this->assertSame( 'pt_bad_end', $bottom[ array_key_first( $bottom ) ]['template_key'] );
	}

	public function test_adjacent_cta_violation(): void {
		$cta1 = $this->section_def( 'st_cta1', array( 'cta_classification' => 'primary_cta' ) );
		$cta2 = $this->section_def( 'st_cta2', array( 'cta_classification' => 'contact_cta' ) );
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta1', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta2', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_adjacent', 'top_level', $ordered );
		$this->seed_sections_and_pages( array( $cta1, $cta2 ), array( $page ) );
		$result = $this->service->run();
		$violations = $result->get_semantic_rule_violations();
		$adj = array_filter( $violations, function ( $v ) {
			return ( $v['rule_code'] ?? '' ) === 'adjacent_cta_violation';
		} );
		$this->assertCount( 1, $adj );
	}

	public function test_human_review_required_contains_landmark_and_alt(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_ok' ) ), array() );
		$result = $this->service->run();
		$human = $result->get_human_review_required();
		$this->assertNotEmpty( $human );
		$flat = implode( ' ', $human );
		$this->assertStringContainsString( 'Heading hierarchy', $flat );
		$this->assertStringContainsString( 'Landmark', $flat );
		$this->assertStringContainsString( 'CTA visible text', $flat );
	}

	public function test_report_generation_payload_structure(): void {
		$this->seed_sections_and_pages( array( $this->section_def( 'st_one' ) ), array() );
		$result = $this->service->run();
		$this->assertInstanceOf( Template_Accessibility_Audit_Result::class, $result );
		$payload = $result->to_array();
		$this->assertArrayHasKey( 'passed', $payload );
		$this->assertArrayHasKey( 'semantic_rule_violations', $payload );
		$this->assertArrayHasKey( 'section_audit_summary', $payload );
		$this->assertArrayHasKey( 'page_audit_summary', $payload );
		$this->assertArrayHasKey( 'human_review_required', $payload );
		$sec = $payload['section_audit_summary'];
		$this->assertArrayHasKey( 'audited', $sec );
		$this->assertArrayHasKey( 'violations', $sec );
		$this->assertArrayHasKey( 'by_rule_code', $sec );
		$lines = $result->to_summary_lines();
		$this->assertNotEmpty( $lines );
		$this->assertStringContainsString( 'Sections audited', $lines[0] );
		$this->assertStringContainsString( 'Accessibility audit', implode( ' ', $lines ) );
	}

	public function test_passed_when_no_violations(): void {
		// top_level needs min 3 CTA, 8+ non-CTA, last CTA, no adjacent CTA. All sections need a11y and (if family set) hierarchy_role_hints.
		$sections = array(
			$this->section_def( 'st_hero' ),
			$this->section_def( 'st_non1', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_non2', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_cta1', array( 'cta_classification' => 'primary_cta' ) ),
			$this->section_def( 'st_non3', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_non4', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_cta2', array( 'cta_classification' => 'contact_cta' ) ),
			$this->section_def( 'st_non5', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_non6', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_non7', array( 'cta_classification' => 'none' ) ),
			$this->section_def( 'st_cta_end', array( 'cta_classification' => 'navigation_cta' ) ),
		);
		$ordered = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_hero', Page_Template_Schema::SECTION_ITEM_POSITION => 0, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non1', Page_Template_Schema::SECTION_ITEM_POSITION => 1, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non2', Page_Template_Schema::SECTION_ITEM_POSITION => 2, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta1', Page_Template_Schema::SECTION_ITEM_POSITION => 3, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non3', Page_Template_Schema::SECTION_ITEM_POSITION => 4, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non4', Page_Template_Schema::SECTION_ITEM_POSITION => 5, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta2', Page_Template_Schema::SECTION_ITEM_POSITION => 6, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non5', Page_Template_Schema::SECTION_ITEM_POSITION => 7, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non6', Page_Template_Schema::SECTION_ITEM_POSITION => 8, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_non7', Page_Template_Schema::SECTION_ITEM_POSITION => 9, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_cta_end', Page_Template_Schema::SECTION_ITEM_POSITION => 10, Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
		);
		$page = $this->page_def( 'pt_valid', 'top_level', $ordered );
		$this->seed_sections_and_pages( $sections, array( $page ) );
		$result = $this->service->run();
		$this->assertTrue( $result->is_passed(), 'Audit should pass when all machine-checkable rules are satisfied' );
		$this->assertCount( 0, $result->get_semantic_rule_violations() );
	}
}
