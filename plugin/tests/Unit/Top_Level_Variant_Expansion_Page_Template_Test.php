<?php
/**
 * Unit tests for top-level variant expansion super-batch (Prompt 164): schema validity, top_level tagging,
 * CTA compliance (≥3, last CTA, no adjacent), one-pager, preview integrity, differentiation-note completeness, export (spec §13, §14.3, §16).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Registries\PageTemplate\TopLevelVariantExpansionBatch\Top_Level_Variant_Expansion_Page_Template_Definitions;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/TopLevelVariantExpansionBatch/Top_Level_Variant_Expansion_Page_Template_Definitions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';

final class Top_Level_Variant_Expansion_Page_Template_Test extends TestCase {

	private static function is_cta_section_key( string $section_key ): bool {
		return strpos( $section_key, 'cta_' ) === 0 || $section_key === 'st_cta_conversion';
	}

	public function test_batch_has_large_variant_count(): void {
		$defs = Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions();
		$keys = Top_Level_Variant_Expansion_Page_Template_Definitions::template_keys();
		$this->assertCount( count( $keys ), $defs );
		$this->assertGreaterThanOrEqual( 30, count( $defs ), 'Variant expansion super-batch must add a large volume of variants (≥30)' );
	}

	public function test_each_definition_has_required_schema_fields(): void {
		$required = Page_Template_Schema::get_required_fields();
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Page template must have required field: {$field}" );
			}
		}
	}

	public function test_each_definition_has_top_level_category_class(): void {
		$allowed_families = array( 'home', 'about', 'faq', 'contact', 'services', 'offerings', 'privacy', 'terms', 'support', 'accessibility', 'disclosure', 'utility', 'resource', 'authority', 'educational', 'comparison', 'buyer_guide' );
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'template_category_class', $def );
			$this->assertSame( 'top_level', (string) ( $def['template_category_class'] ?? '' ) );
			$this->assertArrayHasKey( 'template_family', $def );
			$family = (string) ( $def['template_family'] ?? '' );
			$this->assertContains( $family, $allowed_families, "template_family must be in allowed set, got: {$family}" );
		}
	}

	public function test_each_definition_has_one_pager_with_page_purpose_summary(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$summary = (string) ( $one_pager['page_purpose_summary'] ?? '' );
			$this->assertNotSame( '', $summary, 'One-pager page_purpose_summary is required and non-empty' );
		}
	}

	public function test_cta_count_at_least_three_and_last_section_is_cta(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$internal_key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
			$ordered      = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$this->assertNotEmpty( $ordered );
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$cta_count = count( array_filter( $section_keys, array( self::class, 'is_cta_section_key' ) ) );
			$this->assertGreaterThanOrEqual( 3, $cta_count, "Variant {$internal_key} must have at least 3 CTA sections" );
			$last_key = $section_keys[ count( $section_keys ) - 1 ];
			$this->assertTrue( self::is_cta_section_key( $last_key ), "Last section must be CTA-classified, got: {$last_key}" );
		}
	}

	public function test_no_adjacent_cta_sections(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered      = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
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
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered      = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$non_cta = array_filter(
				$section_keys,
				function ( $k ) {
					return ! self::is_cta_section_key( $k );
				}
			);
			$count   = count( $non_cta );
			$this->assertGreaterThanOrEqual( 8, $count, "Non-CTA section count must be >= 8, got {$count}" );
			$this->assertLessThanOrEqual( 14, $count, "Non-CTA section count must be <= 14, got {$count}" );
		}
	}

	public function test_template_keys_match_definitions(): void {
		$keys = Top_Level_Variant_Expansion_Page_Template_Definitions::template_keys();
		$defs = Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions();
		$this->assertCount( count( $defs ), $keys );
		$internal_keys = array_column( $defs, Page_Template_Schema::FIELD_INTERNAL_KEY );
		foreach ( $keys as $key ) {
			$this->assertContains( $key, $internal_keys );
		}
	}

	public function test_export_fragment_builds_valid_structure(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$fragment = Registry_Export_Fragment_Builder::for_page_template( $def );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE, $fragment );
			$this->assertSame( Registry_Export_Fragment_Builder::OBJECT_TYPE_PAGE, $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_KEY, $fragment );
			$this->assertSame( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ], $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_PAYLOAD, $fragment );
		}
	}

	public function test_preview_metadata_synthetic_only(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'preview_metadata', $def );
			$this->assertIsArray( $def['preview_metadata'] );
			$this->assertArrayHasKey( 'synthetic', $def['preview_metadata'] );
			$this->assertTrue( $def['preview_metadata']['synthetic'], 'Variant expansion templates must use synthetic preview only.' );
		}
	}

	public function test_differentiation_notes_completeness(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'differentiation_notes', $def );
			$notes = (string) ( $def['differentiation_notes'] ?? '' );
			$this->assertNotSame( '', $notes, 'Each variant must have non-empty differentiation_notes describing material difference.' );
		}
	}

	public function test_one_pager_includes_flow_or_cta_direction(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$has_flow = isset( $one_pager['page_flow_explanation'] ) && (string) $one_pager['page_flow_explanation'] !== '';
			$has_cta  = isset( $one_pager['cta_direction_summary'] ) && (string) $one_pager['cta_direction_summary'] !== '';
			$this->assertTrue( $has_flow || $has_cta, 'One-pager should include page_flow_explanation or cta_direction_summary' );
		}
	}

	public function test_section_requirements_match_ordered_sections(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$reqs    = $def[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ] ?? array();
			foreach ( $ordered as $item ) {
				$key = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
				$this->assertArrayHasKey( $key, $reqs, "Section requirements must include key: {$key}" );
				$this->assertArrayHasKey( 'required', $reqs[ $key ] );
			}
		}
	}

	public function test_variation_family_present(): void {
		foreach ( Top_Level_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'variation_family', $def );
			$this->assertSame( $def['template_family'], $def['variation_family'], 'variation_family must match template_family' );
		}
	}

	public function test_batch_id_constant(): void {
		$this->assertSame( 'PT-11', Top_Level_Variant_Expansion_Page_Template_Definitions::BATCH_ID );
	}
}
