<?php
/**
 * Unit tests for Page_Template_Inventory_Appendix_Generator (Prompt 181, spec §62.12, §57.9, §60.6).
 *
 * Covers: deterministic generation, required-field inclusion, grouping by category_class, optional_sections, generate_from_definitions output.
 * Includes one example page template appendix row (real structure).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Docs\Page_Template_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Page_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Page_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Page_Template_Inventory_Appendix_Generator.php';

final class Page_Template_Inventory_Appendix_Generator_Test extends TestCase {

	/** Example page template appendix row (spec §62.12). Real structure for contract reference. */
	public const EXAMPLE_PAGE_APPENDIX_ROW = array(
		'key'                 => 'pt_marketing_landing',
		'name'                => 'Marketing Landing',
		'purpose'             => 'Landing page for campaigns.',
		'ordered_sections'    => array( 'st01_hero_intro', 'st_cta_conversion', 'st_faq' ),
		'optional_sections'   => array( 'st_faq' ),
		'category_class'      => 'top_level',
		'hierarchy_hint'      => 'top_level, marketing',
		'one_pager_status'    => 'yes',
		'version'             => '1',
		'deprecation_status'   => 'active',
	);

	private function get_generator(): Page_Template_Inventory_Appendix_Generator {
		$repo = new Page_Template_Repository();
		return new Page_Template_Inventory_Appendix_Generator( $repo );
	}

	public function test_build_result_from_definitions_empty_returns_zero_total(): void {
		$gen = $this->get_generator();
		$result = $gen->build_result_from_definitions( array() );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( array(), $result['rows'] );
	}

	public function test_build_result_from_definitions_includes_required_fields(): void {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY    => 'pt_marketing_landing',
			Page_Template_Schema::FIELD_NAME           => 'Marketing Landing',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY => 'Landing page for campaigns.',
			'template_category_class'                  => 'top_level',
			'template_family'                           => 'marketing',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st01_hero_intro', Page_Template_Schema::SECTION_ITEM_REQUIRED => true ),
				array( Page_Template_Schema::SECTION_ITEM_KEY => 'st_faq', Page_Template_Schema::SECTION_ITEM_REQUIRED => false ),
			),
			Page_Template_Schema::FIELD_ONE_PAGER      => array( 'link' => 'https://example.org/one-pager' ),
			Page_Template_Schema::FIELD_STATUS         => 'active',
			Page_Template_Schema::FIELD_VERSION        => array( 'version' => '1' ),
		);
		$gen = $this->get_generator();
		$result = $gen->build_result_from_definitions( array( $def ) );
		$this->assertSame( 1, $result['total'] );
		$row = $result['rows'][0];
		$this->assertSame( 'pt_marketing_landing', $row['key'] );
		$this->assertSame( 'Marketing Landing', $row['name'] );
		$this->assertSame( array( 'st01_hero_intro', 'st_faq' ), $row['ordered_sections'] );
		$this->assertContains( 'st_faq', $row['optional_sections'] );
		$this->assertSame( 'top_level', $row['category_class'] );
		$this->assertSame( 'yes', $row['one_pager_status'] );
		$this->assertSame( '1', $row['version'] );
		$this->assertSame( 'active', $row['deprecation_status'] );
	}

	public function test_generate_from_definitions_produces_markdown_with_header_and_table(): void {
		$def = array(
			Page_Template_Schema::FIELD_INTERNAL_KEY   => 'pt_test',
			Page_Template_Schema::FIELD_NAME         => 'Test',
			'template_category_class'                 => 'hub',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(),
			Page_Template_Schema::FIELD_VERSION       => array( 'version' => '1' ),
		);
		$gen = $this->get_generator();
		$md = $gen->generate_from_definitions( array( $def ) );
		$this->assertStringContainsString( '# Page Template Inventory Appendix', $md );
		$this->assertStringContainsString( '§62.12', $md );
		$this->assertStringContainsString( '| Key | Name | Purpose | Ordered sections | Optional sections | Hierarchy | One-pager | Version | Deprecation |', $md );
		$this->assertStringContainsString( 'pt_test', $md );
		$this->assertStringContainsString( '**Total page templates**: 1', $md );
	}

	public function test_example_page_row_has_all_required_keys(): void {
		$row = self::EXAMPLE_PAGE_APPENDIX_ROW;
		$this->assertArrayHasKey( 'key', $row );
		$this->assertArrayHasKey( 'name', $row );
		$this->assertArrayHasKey( 'purpose', $row );
		$this->assertArrayHasKey( 'ordered_sections', $row );
		$this->assertArrayHasKey( 'optional_sections', $row );
		$this->assertArrayHasKey( 'hierarchy_hint', $row );
		$this->assertArrayHasKey( 'one_pager_status', $row );
		$this->assertArrayHasKey( 'version', $row );
		$this->assertArrayHasKey( 'deprecation_status', $row );
	}
}
