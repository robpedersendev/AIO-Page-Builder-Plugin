<?php
/**
 * Unit tests for Template_Showcase_Fixture_Generator: deterministic seeding, family coverage,
 * compare-set generation, no external-call leakage (spec §56.4, §60.7; Prompt 201).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\BuildPlan\Schema\Build_Plan_Item_Schema;
use AIOPageBuilder\Domain\Fixtures\Template_Showcase_Fixture_Generator;
use AIOPageBuilder\Domain\Registries\Composition\Composition_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Composition/Composition_Schema.php';
require_once $plugin_root . '/src/Domain/BuildPlan/Schema/Build_Plan_Item_Schema.php';
require_once $plugin_root . '/src/Domain/Fixtures/Template_Showcase_Fixture_Generator.php';

final class Template_Showcase_Fixture_Generator_Test extends TestCase {

	public function test_generate_returns_manifest_and_all_domains(): void {
		$gen  = new Template_Showcase_Fixture_Generator();
		$pack = $gen->generate();
		$this->assertArrayHasKey( 'manifest', $pack );
		$this->assertArrayHasKey( 'sections', $pack );
		$this->assertArrayHasKey( 'page_templates', $pack );
		$this->assertArrayHasKey( 'compositions', $pack );
		$this->assertArrayHasKey( 'build_plan_recommendation_items', $pack );
		$this->assertArrayHasKey( 'compare_sets', $pack );
		$this->assertTrue( $pack['manifest'][ Template_Showcase_Fixture_Generator::SYNTHETIC_MARKER ] ?? false );
	}

	public function test_deterministic_seeding_same_output_twice(): void {
		$gen = new Template_Showcase_Fixture_Generator();
		$a   = $gen->generate();
		$b   = $gen->generate();
		$this->assertSame( $a['manifest']['counts'], $b['manifest']['counts'] );
		$this->assertSame( $a['manifest']['version'], $b['manifest']['version'] );
		$this->assertSame( $a['manifest']['generated_at'], $b['manifest']['generated_at'] );
		$this->assertSame(
			array_map( fn( $s ) => $s[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '', $a['sections'] ),
			array_map( fn( $s ) => $s[ Section_Schema::FIELD_INTERNAL_KEY ] ?? '', $b['sections'] )
		);
		$this->assertSame( $a['compare_sets'], $b['compare_sets'] );
	}

	public function test_family_coverage_section_families_and_page_classes(): void {
		$gen      = new Template_Showcase_Fixture_Generator();
		$pack     = $gen->generate();
		$manifest = $pack['manifest'];
		$this->assertContains( 'hero_intro', $manifest['section_families'] );
		$this->assertContains( 'trust_proof', $manifest['section_families'] );
		$this->assertContains( 'cta', $manifest['section_families'] );
		$this->assertContains( 'top_level', $manifest['page_classes'] );
		$this->assertContains( 'hub', $manifest['page_classes'] );
		$this->assertContains( 'nested_hub', $manifest['page_classes'] );
		$this->assertContains( 'child_detail', $manifest['page_classes'] );
		$this->assertSame( 3, count( $pack['sections'] ) );
		$this->assertSame( 4, count( $pack['page_templates'] ) );
	}

	public function test_compare_sets_generated(): void {
		$gen  = new Template_Showcase_Fixture_Generator();
		$pack = $gen->generate();
		$this->assertArrayHasKey( 'section_keys', $pack['compare_sets'] );
		$this->assertArrayHasKey( 'page_keys', $pack['compare_sets'] );
		$this->assertIsArray( $pack['compare_sets']['section_keys'] );
		$this->assertIsArray( $pack['compare_sets']['page_keys'] );
		$this->assertGreaterThanOrEqual( 1, count( $pack['compare_sets']['section_keys'] ) );
		$this->assertGreaterThanOrEqual( 1, count( $pack['compare_sets']['page_keys'] ) );
		foreach ( $pack['compare_sets']['section_keys'] as $key ) {
			$this->assertStringStartsWith( 'st_showcase_', $key );
		}
		foreach ( $pack['compare_sets']['page_keys'] as $key ) {
			$this->assertStringStartsWith( 'pt_showcase_', $key );
		}
	}

	public function test_build_plan_recommendation_items_have_template_summaries(): void {
		$gen   = new Template_Showcase_Fixture_Generator();
		$pack  = $gen->generate();
		$items = $pack['build_plan_recommendation_items'];
		$this->assertGreaterThanOrEqual( 2, count( $items ) );
		$new_pages = array_filter( $items, fn( $i ) => ( $i[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) === Build_Plan_Item_Schema::ITEM_TYPE_NEW_PAGE );
		$epc       = array_filter( $items, fn( $i ) => ( $i[ Build_Plan_Item_Schema::KEY_ITEM_TYPE ] ?? '' ) === Build_Plan_Item_Schema::ITEM_TYPE_EXISTING_PAGE_CHANGE );
		$this->assertGreaterThanOrEqual( 1, count( $new_pages ) );
		$this->assertGreaterThanOrEqual( 1, count( $epc ) );
		foreach ( $new_pages as $item ) {
			$payload = $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ?? array();
			$this->assertArrayHasKey( 'proposed_template_summary', $payload );
			$this->assertArrayHasKey( 'template_key', $payload['proposed_template_summary'] );
		}
		foreach ( $epc as $item ) {
			$payload = $item[ Build_Plan_Item_Schema::KEY_PAYLOAD ] ?? array();
			$this->assertArrayHasKey( 'existing_page_template_change_summary', $payload );
			$this->assertArrayHasKey( 'template_key', $payload['existing_page_template_change_summary'] );
		}
	}

	/**
	 * No external calls: generator is pure data; no network or provider usage.
	 */
	public function test_no_external_call_leakage_generator_has_no_dependencies(): void {
		$gen = new Template_Showcase_Fixture_Generator();
		$this->assertInstanceOf( Template_Showcase_Fixture_Generator::class, $gen );
		$pack = $gen->generate();
		$this->assertArrayNotHasKey( 'api_key', $pack );
		$this->assertArrayNotHasKey( 'provider_url', $pack );
		$json = \json_encode( $pack );
		$this->assertNotFalse( $json );
		$this->assertStringNotContainsString( 'sk-', $json );
		$this->assertStringNotContainsString( 'https://api.', $json );
	}

	public function test_sections_have_required_schema_keys(): void {
		$gen  = new Template_Showcase_Fixture_Generator();
		$pack = $gen->generate();
		foreach ( $pack['sections'] as $section ) {
			$this->assertArrayHasKey( Section_Schema::FIELD_INTERNAL_KEY, $section );
			$this->assertArrayHasKey( Section_Schema::FIELD_CATEGORY, $section );
			$this->assertArrayHasKey( Section_Schema::FIELD_NAME, $section );
		}
	}

	public function test_compositions_have_required_schema_keys(): void {
		$gen  = new Template_Showcase_Fixture_Generator();
		$pack = $gen->generate();
		foreach ( $pack['compositions'] as $comp ) {
			$this->assertArrayHasKey( Composition_Schema::FIELD_COMPOSITION_ID, $comp );
			$this->assertArrayHasKey( Composition_Schema::FIELD_ORDERED_SECTION_LIST, $comp );
			$this->assertArrayHasKey( Composition_Schema::FIELD_VALIDATION_STATUS, $comp );
		}
	}

	public function test_page_templates_have_template_family_and_class(): void {
		$gen  = new Template_Showcase_Fixture_Generator();
		$pack = $gen->generate();
		foreach ( $pack['page_templates'] as $pt ) {
			$this->assertArrayHasKey( Page_Template_Schema::FIELD_INTERNAL_KEY, $pt );
			$this->assertArrayHasKey( 'template_family', $pt );
			$this->assertArrayHasKey( 'template_category_class', $pt );
		}
	}

	/**
	 * Example template showcase fixture manifest payload (Prompt 201). No pseudocode.
	 */
	public function test_example_template_showcase_manifest_payload(): void {
		$gen      = new Template_Showcase_Fixture_Generator();
		$pack     = $gen->generate();
		$manifest = $pack['manifest'];

		$this->assertSame( '1.0', $manifest['version'] );
		$this->assertSame( '2025-03-15T10:00:00Z', $manifest['generated_at'] );
		$this->assertSame( array( 'hero_intro', 'trust_proof', 'cta' ), $manifest['section_families'] );
		$this->assertSame( array( 'top_level', 'hub', 'nested_hub', 'child_detail' ), $manifest['page_classes'] );
		$this->assertSame( 3, $manifest['counts']['sections'] );
		$this->assertSame( 4, $manifest['counts']['page_templates'] );
		$this->assertSame( 2, $manifest['counts']['compositions'] );
		$this->assertSame( 3, $manifest['counts']['build_plan_recommendation_items'] );
		$this->assertTrue( $manifest['_synthetic'] );
		$this->assertSame( array( 'st_showcase_hero_01', 'st_showcase_trust_01', 'st_showcase_cta_01' ), $manifest['compare_sets']['section_keys'] );
		$this->assertSame( array( 'pt_showcase_landing_01', 'pt_showcase_hub_01', 'pt_showcase_nested_hub_01' ), $manifest['compare_sets']['page_keys'] );
	}
}
