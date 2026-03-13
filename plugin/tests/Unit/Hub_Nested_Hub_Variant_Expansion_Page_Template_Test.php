<?php
/**
 * Unit tests for hub and nested hub variant expansion super-batch (Prompt 165): schema validity,
 * hub/nested_hub tagging, CTA compliance (≥4, last CTA, no adjacent), hierarchy metadata, preview, one-pager, export (spec §13, §14.3, §16).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Export\Registry_Export_Fragment_Builder;
use AIOPageBuilder\Domain\Registries\PageTemplate\HubNestedHubVariantExpansionBatch\Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/HubNestedHubVariantExpansionBatch/Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/Registries/Export/Registry_Export_Fragment_Builder.php';

final class Hub_Nested_Hub_Variant_Expansion_Page_Template_Test extends TestCase {

	private static function is_cta_section_key( string $section_key ): bool {
		return strpos( $section_key, 'cta_' ) === 0 || $section_key === 'st_cta_conversion';
	}

	public function test_batch_has_large_variant_count(): void {
		$defs = Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions();
		$keys = Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::template_keys();
		$this->assertCount( count( $keys ), $defs );
		$this->assertGreaterThanOrEqual( 25, count( $defs ), 'Hub/nested hub variant expansion batch must add a large volume of variants (≥25)' );
	}

	public function test_each_definition_has_required_schema_fields(): void {
		$required = Page_Template_Schema::get_required_fields();
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Page template must have required field: {$field}" );
			}
		}
	}

	public function test_each_definition_is_hub_or_nested_hub(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$class = (string) ( $def['template_category_class'] ?? '' );
			$this->assertContains( $class, array( 'hub', 'nested_hub' ), "template_category_class must be hub or nested_hub, got: {$class}" );
		}
	}

	public function test_hub_definitions_have_hierarchy_role_hub(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			if ( ( $def['template_category_class'] ?? '' ) !== 'hub' ) {
				continue;
			}
			$this->assertArrayHasKey( 'hierarchy_hints', $def );
			$role = (string) ( $def['hierarchy_hints']['hierarchy_role'] ?? '' );
			$this->assertSame( 'hub', $role, 'Hub templates must have hierarchy_hints.hierarchy_role = hub' );
		}
	}

	public function test_nested_hub_definitions_have_hierarchy_role_and_parent_compatibility(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			if ( ( $def['template_category_class'] ?? '' ) !== 'nested_hub' ) {
				continue;
			}
			$this->assertArrayHasKey( 'hierarchy_hints', $def );
			$role = (string) ( $def['hierarchy_hints']['hierarchy_role'] ?? '' );
			$this->assertSame( 'nested_hub', $role, 'Nested hub templates must have hierarchy_hints.hierarchy_role = nested_hub' );
			$this->assertArrayHasKey( 'parent_family_compatibility', $def );
			$compat = $def['parent_family_compatibility'];
			$this->assertIsArray( $compat );
			$this->assertNotEmpty( $compat );
		}
	}

	public function test_each_definition_has_one_pager_with_page_purpose_summary(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$summary = (string) ( $one_pager['page_purpose_summary'] ?? '' );
			$this->assertNotSame( '', $summary, 'One-pager page_purpose_summary is required and non-empty' );
		}
	}

	public function test_cta_count_at_least_four_and_last_section_is_cta(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$internal_key = (string) ( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ] ?? 'unknown' );
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$this->assertNotEmpty( $ordered );
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$cta_count = count( array_filter( $section_keys, array( self::class, 'is_cta_section_key' ) ) );
			$this->assertGreaterThanOrEqual( 4, $cta_count, "Hub/nested hub variant {$internal_key} must have at least 4 CTA sections" );
			$last_key = $section_keys[ count( $section_keys ) - 1 ];
			$this->assertTrue( self::is_cta_section_key( $last_key ), "Last section must be CTA-classified, got: {$last_key}" );
		}
	}

	public function test_no_adjacent_cta_sections(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
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
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
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

	public function test_template_keys_match_definitions(): void {
		$keys = Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::template_keys();
		$defs = Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions();
		$this->assertCount( count( $defs ), $keys );
		$internal_keys = array_column( $defs, Page_Template_Schema::FIELD_INTERNAL_KEY );
		foreach ( $keys as $key ) {
			$this->assertContains( $key, $internal_keys );
		}
	}

	public function test_export_fragment_builds_valid_structure(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$fragment = Registry_Export_Fragment_Builder::for_page_template( $def );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE, $fragment );
			$this->assertSame( Registry_Export_Fragment_Builder::OBJECT_TYPE_PAGE, $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_TYPE ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_OBJECT_KEY, $fragment );
			$this->assertSame( $def[ Page_Template_Schema::FIELD_INTERNAL_KEY ], $fragment[ Registry_Export_Fragment_Builder::KEY_OBJECT_KEY ] );
			$this->assertArrayHasKey( Registry_Export_Fragment_Builder::KEY_PAYLOAD, $fragment );
		}
	}

	public function test_preview_metadata_synthetic_only(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'preview_metadata', $def );
			$this->assertIsArray( $def['preview_metadata'] );
			$this->assertArrayHasKey( 'synthetic', $def['preview_metadata'] );
			$this->assertTrue( $def['preview_metadata']['synthetic'], 'Hub/nested hub variant templates must use synthetic preview only.' );
		}
	}

	public function test_differentiation_notes_completeness(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'differentiation_notes', $def );
			$notes = (string) ( $def['differentiation_notes'] ?? '' );
			$this->assertNotSame( '', $notes, 'Each variant must have non-empty differentiation_notes.' );
		}
	}

	public function test_section_requirements_match_ordered_sections(): void {
		foreach ( Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::all_definitions() as $def ) {
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
		$this->assertSame( 'PT-12', Hub_Nested_Hub_Variant_Expansion_Page_Template_Definitions::BATCH_ID );
	}
}
