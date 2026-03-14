<?php
/**
 * Unit tests for page template gap-closing super-batch (PT-14): count threshold, class balance,
 * CTA compliance, one-pager, schema completeness (spec §13, §62.12, Prompt 183).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\GapClosingSuperBatch\Page_Template_Gap_Closing_Super_Batch_Definitions;
use AIOPageBuilder\Domain\Registries\PageTemplate\GapClosingSuperBatch\Page_Template_Gap_Closing_Super_Batch_Seeder;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/GapClosingSuperBatch/Page_Template_Gap_Closing_Super_Batch_Definitions.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/GapClosingSuperBatch/Page_Template_Gap_Closing_Super_Batch_Seeder.php';

final class Page_Template_Gap_Closing_Super_Batch_Test extends TestCase {

	/** CTA-classified section keys: cta_ prefix or gc_cta prefix (PT-14 uses both). */
	private static function is_cta_section_key( string $section_key ): bool {
		return strpos( $section_key, 'cta_' ) === 0 || strpos( $section_key, 'gc_cta' ) === 0;
	}

	/** Min CTA by class (cta-sequencing-and-placement-contract §3). */
	private static function min_cta_for_class( string $class ): int {
		$map = array( 'top_level' => 3, 'hub' => 4, 'nested_hub' => 4, 'child_detail' => 5 );
		return $map[ $class ] ?? 3;
	}

	public function test_batch_id_and_page_target(): void {
		$this->assertSame( 'PT-14', Page_Template_Gap_Closing_Super_Batch_Definitions::BATCH_ID );
		$this->assertSame( 500, Page_Template_Gap_Closing_Super_Batch_Definitions::PAGE_TARGET );
	}

	public function test_gap_closing_batch_reaches_275_plus_templates(): void {
		$keys = Page_Template_Gap_Closing_Super_Batch_Definitions::template_keys();
		$defs = Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions();
		$this->assertCount( count( $keys ), $defs );
		$this->assertGreaterThanOrEqual( 275, count( $keys ), 'Gap-closing batch must add at least 275 templates to approach 500 total' );
	}

	public function test_each_definition_has_required_schema_fields(): void {
		$required = Page_Template_Schema::get_required_fields();
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			foreach ( $required as $field ) {
				$this->assertArrayHasKey( $field, $def, "Page template must have required field: {$field}" );
			}
		}
	}

	public function test_each_definition_has_class_and_family(): void {
		$allowed_classes = array( 'top_level', 'hub', 'nested_hub', 'child_detail' );
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$this->assertArrayHasKey( 'template_category_class', $def );
			$class = (string) ( $def['template_category_class'] ?? '' );
			$this->assertContains( $class, $allowed_classes );
			$this->assertArrayHasKey( 'template_family', $def );
			$this->assertNotEmpty( (string) ( $def['template_family'] ?? '' ) );
		}
	}

	public function test_each_definition_has_one_pager_and_preview_metadata(): void {
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$one_pager = $def[ Page_Template_Schema::FIELD_ONE_PAGER ] ?? array();
			$this->assertIsArray( $one_pager );
			$this->assertNotEmpty( (string) ( $one_pager['page_purpose_summary'] ?? '' ) );
			$this->assertArrayHasKey( 'preview_metadata', $def );
			$this->assertTrue( (bool) ( $def['preview_metadata']['synthetic'] ?? false ) );
		}
	}

	public function test_each_definition_cta_count_and_last_section_cta(): void {
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$class = (string) ( $def['template_category_class'] ?? '' );
			$min_cta = self::min_cta_for_class( $class );
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$this->assertNotEmpty( $ordered );
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$cta_count = count( array_filter( $section_keys, array( self::class, 'is_cta_section_key' ) ) );
			$this->assertGreaterThanOrEqual( $min_cta, $cta_count, "Template {$def[ Page_Template_Schema::FIELD_INTERNAL_KEY ]} must have at least {$min_cta} CTA sections" );
			$last_key = $section_keys[ count( $section_keys ) - 1 ];
			$this->assertTrue( self::is_cta_section_key( $last_key ), "Last section must be CTA, got: {$last_key}" );
		}
	}

	public function test_no_adjacent_cta_sections(): void {
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			for ( $i = 0; $i < count( $section_keys ) - 1; $i++ ) {
				$curr = self::is_cta_section_key( $section_keys[ $i ] );
				$next = self::is_cta_section_key( $section_keys[ $i + 1 ] );
				$this->assertFalse( $curr && $next, "No adjacent CTA at positions {$i} and " . ( $i + 1 ) . " in {$def[ Page_Template_Schema::FIELD_INTERNAL_KEY ]}" );
			}
		}
	}

	public function test_non_cta_section_count_at_least_8(): void {
		foreach ( Page_Template_Gap_Closing_Super_Batch_Definitions::all_definitions() as $def ) {
			$ordered = $def[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] ?? array();
			$section_keys = array();
			foreach ( $ordered as $item ) {
				$section_keys[] = (string) ( $item[ Page_Template_Schema::SECTION_ITEM_KEY ] ?? '' );
			}
			$non_cta = count( array_filter( $section_keys, function ( $k ) {
				return ! self::is_cta_section_key( $k );
			} ) );
			$this->assertGreaterThanOrEqual( 8, $non_cta, "Template {$def[ Page_Template_Schema::FIELD_INTERNAL_KEY ]} must have at least 8 non-CTA sections" );
		}
	}

	public function test_template_keys_are_unique(): void {
		$keys = Page_Template_Gap_Closing_Super_Batch_Definitions::template_keys();
		$this->assertSame( count( $keys ), count( array_unique( $keys ) ) );
		$this->assertContains( 'pt_gap_top_level_001', $keys );
		$this->assertContains( 'pt_gap_child_detail_130', $keys );
	}

	public function test_seeder_run_returns_success_and_ids(): void {
		$GLOBALS['_aio_wp_insert_post_return'] = 200;
		$GLOBALS['_aio_wp_query_posts']       = array();
		$repo   = new Page_Template_Repository();
		$result = Page_Template_Gap_Closing_Super_Batch_Seeder::run( $repo );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'page_template_ids', $result );
		$this->assertArrayHasKey( 'template_keys', $result );
		$this->assertTrue( $result['success'] );
		$this->assertGreaterThanOrEqual( 275, count( $result['page_template_ids'] ) );
		$this->assertSame( Page_Template_Gap_Closing_Super_Batch_Definitions::template_keys(), $result['template_keys'] );
		$this->assertEmpty( $result['errors'] );
	}
}
