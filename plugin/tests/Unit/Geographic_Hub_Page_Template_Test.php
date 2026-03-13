<?php
/**
 * Unit tests for geographic hub page template batch (Prompt 158): schema validity, geographic-family tagging,
 * CTA counts (≥4), bottom-CTA, no-adjacent-CTA, section-count targets, one-pager, preview integrity, export (spec §13, §14, §16).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\GeographicHubBatch\Geographic_Hub_Page_Template_Definitions;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/GeographicHubBatch/Geographic_Hub_Page_Template_Definitions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';

final class Geographic_Hub_Page_Template_Test extends TestCase {

	/** CTA-classified section keys: prefix cta_ or key st_cta_conversion (contract §14). */
	private static function is_cta_section_key( string $section_key ): bool {
		return strpos( $section_key, 'cta_' ) === 0 || $section_key === 'st_cta_conversion';
	}

	/** Allowed template_family values for geographic hub batch (Prompt 158). */
	private const ALLOWED_GEO_FAMILIES = array(
		'service_area',
		'regional',
		'city_directory',
		'location_overview',
		'coverage_listing',
		'neighborhood',
		'campus',
		'location_directory',
	);

	public function test_batch_has_expected_number_of_templates(): void {
		$defs = Geographic_Hub_Page_Template_Definitions::all_definitions();
		$keys = Geographic_Hub_Page_Template_Definitions::template_keys();
		$this->assertCount( count( $keys ), $defs );
		$this->assertGreaterThanOrEqual( 10, $defs );
	}

	public function test_each_definition_has_required_schema_fields(): void {
		$required = Page_Template_Schema::get_required_fields();
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Page template must have required field: {$field}" );
			}
		}
	}

	public function test_each_definition_has_hub_category_class_and_geographic_family(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'template_category_class', $def );
			$this->assertSame( 'hub', (string) ( $def['template_category_class'] ?? '' ) );
			$this->assertArrayHasKey( 'template_family', $def );
			$family = (string) ( $def['template_family'] ?? '' );
			$this->assertContains( $family, self::ALLOWED_GEO_FAMILIES, "template_family must be a geographic family" );
		}
	}

	public function test_each_definition_archetype_is_hub_page(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertSame( 'hub_page', (string) ( $def[ Page_Template_Schema::FIELD_ARCHETYPE ] ?? '' ) );
		}
	}

	public function test_each_definition_has_one_pager_with_page_purpose_summary(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$summary = (string) ( $one_pager['page_purpose_summary'] ?? '' );
			$this->assertNotSame( '', $summary, 'One-pager page_purpose_summary is required and non-empty' );
		}
	}

	public function test_hub_cta_count_at_least_four_and_last_section_is_cta(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$this->assertNotEmpty( $ordered );
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				$section_keys[] = $key;
			}
			$cta_keys = array_filter( $section_keys, array( self::class, 'is_cta_section_key' ) );
			$this->assertGreaterThanOrEqual( 4, count( $cta_keys ), 'Hub template must have at least 4 CTA sections' );
			$last_key = $section_keys[ count( $section_keys ) - 1 ];
			$this->assertTrue( self::is_cta_section_key( $last_key ), "Last section must be CTA-classified, got: {$last_key}" );
		}
	}

	public function test_no_adjacent_cta_sections(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			for ( $i = 0; $i < count( $section_keys ) - 1; $i++ ) {
				$curr = self::is_cta_section_key( $section_keys[ $i ] );
				$next = self::is_cta_section_key( $section_keys[ $i + 1 ] );
				$this->assertFalse( $curr && $next, "No two adjacent CTA sections allowed at positions {$i} and " . ( $i + 1 ) );
			}
		}
	}

	public function test_non_cta_section_count_in_range_8_to_14(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$non_cta = array_filter( $section_keys, function ( $k ) {
				return ! self::is_cta_section_key( $k );
			} );
			$count = count( $non_cta );
			$this->assertGreaterThanOrEqual( 8, $count, "Non-CTA section count must be >= 8, got {$count}" );
			$this->assertLessThanOrEqual( 14, $count, "Non-CTA section count must be <= 14, got {$count}" );
		}
	}

	public function test_template_keys_match_definitions_internal_keys(): void {
		$keys = Geographic_Hub_Page_Template_Definitions::template_keys();
		$defs = Geographic_Hub_Page_Template_Definitions::all_definitions();
		$this->assertCount( count( $defs ), $keys );
		$internal_keys = array_column( $defs, Page_Template_Schema::FIELD_INTERNAL_KEY );
		foreach ( $keys as $key ) {
			$this->assertContains( $key, $internal_keys );
		}
	}

	public function test_export_fragment_builds_valid_structure(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$fragment = Registry_Export_Fragment_Builder::for_page_template( $def );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE, $fragment );
			$this->assertSame( Registry_Export_Fragment_Builder::OBJECT_TYPE_PAGE, $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_KEY, $fragment );
			$this->assertSame( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ], $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_PAYLOAD, $fragment );
		}
	}

	public function test_preview_metadata_synthetic_only(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'preview_metadata', $def );
			$this->assertIsArray( $def['preview_metadata'] );
			$this->assertArrayHasKey( 'synthetic', $def['preview_metadata'] );
			$this->assertTrue( $def['preview_metadata']['synthetic'], 'Geographic hub templates must use synthetic preview data only; no real addresses.' );
		}
	}

	public function test_one_pager_includes_drill_down_or_flow(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$has_flow = isset( $one_pager['page_flow_explanation'] ) && (string) $one_pager['page_flow_explanation'] !== '';
			$has_drill = isset( $one_pager['drill_down_intent'] ) && (string) $one_pager['drill_down_intent'] !== '';
			$this->assertTrue( $has_flow || $has_drill, 'Geographic hub one-pager should include page_flow_explanation or drill_down_intent' );
		}
	}

	public function test_section_requirements_match_ordered_sections(): void {
		foreach ( Geographic_Hub_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$reqs = $def[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ] ?? array();
			foreach ( $ordered as $item ) {
				$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				$this->assertArrayHasKey( $key, $reqs, "Section requirements must include key: {$key}" );
				$this->assertArrayHasKey( 'required', $reqs[ $key ] );
			}
		}
	}

	public function test_batch_id_constant(): void {
		$this->assertSame( 'PT-04', Geographic_Hub_Page_Template_Definitions::BATCH_ID );
	}
}
