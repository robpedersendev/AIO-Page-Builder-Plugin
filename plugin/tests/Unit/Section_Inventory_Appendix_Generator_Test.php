<?php
/**
 * Unit tests for Section_Inventory_Appendix_Generator (Prompt 181, spec §62.11, §57.9, §60.6).
 *
 * Covers: deterministic generation, required-field inclusion, grouping by category, generate_from_definitions output.
 * Includes one example section appendix row (real structure).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Docs\Section_Inventory_Appendix_Generator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Type_Keys.php';
require_once $plugin_root . '/src/Domain/Storage/Objects/Object_Status_Families.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Abstract_CPT_Repository.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Section_Template_Repository.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';
require_once $plugin_root . '/src/Domain/Registries/Docs/Section_Inventory_Appendix_Generator.php';

final class Section_Inventory_Appendix_Generator_Test extends TestCase {

	/** Example section appendix row (spec §62.11). Real structure for contract reference. */
	public const EXAMPLE_SECTION_APPENDIX_ROW = array(
		'key'                => 'st01_hero_intro',
		'name'               => 'Hero Intro',
		'purpose'            => 'Hero section with headline and optional CTA.',
		'category'           => 'hero_intro',
		'variants'           => array( 'default', 'compact' ),
		'helper_status'      => 'yes',
		'deprecation_status' => 'active',
		'version'            => '1',
	);

	private function get_generator(): Section_Inventory_Appendix_Generator {
		$repo = new Section_Template_Repository();
		return new Section_Inventory_Appendix_Generator( $repo );
	}

	public function test_build_result_from_definitions_empty_returns_zero_total(): void {
		$gen    = $this->get_generator();
		$result = $gen->build_result_from_definitions( array() );
		$this->assertSame( 0, $result['total'] );
		$this->assertSame( array(), $result['rows'] );
	}

	public function test_build_result_from_definitions_includes_required_fields(): void {
		$def    = array(
			Section_Schema::FIELD_INTERNAL_KEY    => 'st01_hero_intro',
			Section_Schema::FIELD_NAME            => 'Hero Intro',
			Section_Schema::FIELD_PURPOSE_SUMMARY => 'Hero section with headline and optional CTA.',
			Section_Schema::FIELD_CATEGORY        => 'hero_intro',
			Section_Schema::FIELD_VARIANTS        => array(
				'default' => array(),
				'compact' => array(),
			),
			Section_Schema::FIELD_HELPER_REF      => 'helper_st01',
			Section_Schema::FIELD_STATUS          => 'active',
			Section_Schema::FIELD_VERSION         => array( 'version' => '1' ),
		);
		$gen    = $this->get_generator();
		$result = $gen->build_result_from_definitions( array( $def ) );
		$this->assertSame( 1, $result['total'] );
		$row = $result['rows'][0];
		$this->assertSame( 'st01_hero_intro', $row['key'] );
		$this->assertSame( 'Hero Intro', $row['name'] );
		$this->assertSame( 'Hero section with headline and optional CTA.', $row['purpose'] );
		$this->assertSame( 'hero_intro', $row['category'] );
		$this->assertSame( array( 'default', 'compact' ), $row['variants'] );
		$this->assertSame( 'yes', $row['helper_status'] );
		$this->assertSame( 'active', $row['deprecation_status'] );
		$this->assertSame( '1', $row['version'] );
	}

	public function test_deprecation_status_when_status_deprecated(): void {
		$def    = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st_old',
			Section_Schema::FIELD_NAME         => 'Old',
			Section_Schema::FIELD_STATUS       => 'deprecated',
			Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
		);
		$gen    = $this->get_generator();
		$result = $gen->build_result_from_definitions( array( $def ) );
		$this->assertSame( 'deprecated', $result['rows'][0]['deprecation_status'] );
	}

	public function test_generate_from_definitions_produces_markdown_with_header_and_table(): void {
		$def = array(
			Section_Schema::FIELD_INTERNAL_KEY => 'st01_hero_intro',
			Section_Schema::FIELD_NAME         => 'Hero Intro',
			Section_Schema::FIELD_CATEGORY     => 'hero_intro',
			Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
		);
		$gen = $this->get_generator();
		$md  = $gen->generate_from_definitions( array( $def ) );
		$this->assertStringContainsString( '# Section Template Inventory Appendix', $md );
		$this->assertStringContainsString( '§62.11', $md );
		$this->assertStringContainsString( '| Key | Name | Purpose | Variants | Helper | Deprecation | Version |', $md );
		$this->assertStringContainsString( 'st01_hero_intro', $md );
		$this->assertStringContainsString( '**Total section templates**: 1', $md );
	}

	public function test_grouping_by_category(): void {
		$defs = array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 'st_a',
				Section_Schema::FIELD_NAME         => 'A',
				Section_Schema::FIELD_CATEGORY     => 'cta',
				Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
			),
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 'st_b',
				Section_Schema::FIELD_NAME         => 'B',
				Section_Schema::FIELD_CATEGORY     => 'hero_intro',
				Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
			),
		);
		$gen  = $this->get_generator();
		$md   = $gen->generate_from_definitions( $defs );
		$this->assertStringContainsString( '## Cta', $md );
		$this->assertStringContainsString( '## Hero intro', $md );
	}

	public function test_example_section_row_has_all_required_keys(): void {
		$row = self::EXAMPLE_SECTION_APPENDIX_ROW;
		$this->assertArrayHasKey( 'key', $row );
		$this->assertArrayHasKey( 'name', $row );
		$this->assertArrayHasKey( 'purpose', $row );
		$this->assertArrayHasKey( 'category', $row );
		$this->assertArrayHasKey( 'variants', $row );
		$this->assertArrayHasKey( 'helper_status', $row );
		$this->assertArrayHasKey( 'deprecation_status', $row );
		$this->assertArrayHasKey( 'version', $row );
	}

	/**
	 * Same-version regen: identical definitions produce identical markdown (Prompt 202; migration appendix safety).
	 */
	public function test_same_version_regen_produces_identical_markdown(): void {
		$defs = array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 'st_same',
				Section_Schema::FIELD_NAME         => 'Same',
				Section_Schema::FIELD_CATEGORY     => 'cta',
				Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
			),
		);
		$gen  = $this->get_generator();
		$md1  = $gen->generate_from_definitions( $defs );
		$md2  = $gen->generate_from_definitions( $defs );
		$this->assertSame( $md1, $md2, 'Same definitions must yield identical appendix markdown (deterministic regen)' );
	}

	/**
	 * Appendix regeneration after migration: generator builds valid result from definitions (Prompt 202).
	 * No stored appendix; "regen" is running the generator after upgrade.
	 */
	public function test_appendix_regen_after_migration_produces_valid_result(): void {
		$defs   = array(
			array(
				Section_Schema::FIELD_INTERNAL_KEY => 'st_post_upgrade',
				Section_Schema::FIELD_NAME         => 'Post Upgrade',
				Section_Schema::FIELD_CATEGORY     => 'hero_intro',
				Section_Schema::FIELD_VERSION      => array( 'version' => '1' ),
			),
		);
		$gen    = $this->get_generator();
		$result = $gen->build_result_from_definitions( $defs );
		$this->assertArrayHasKey( 'rows', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertCount( 1, $result['rows'] );
		$this->assertSame( 'st_post_upgrade', $result['rows'][0]['key'] );
	}
}
