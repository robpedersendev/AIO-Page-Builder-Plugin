<?php
/**
 * Unit tests for Documentation_Schema: types, required source refs, helper and one-pager examples (spec §10.7, Prompt 025).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Documentation\Documentation_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Documentation/Documentation_Schema.php';

final class Documentation_Schema_Test extends TestCase {

	public function test_documentation_types_cover_spec_use_cases(): void {
		$types = Documentation_Schema::get_documentation_types();
		$this->assertContains( Documentation_Schema::TYPE_SECTION_HELPER, $types );
		$this->assertContains( Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER, $types );
		$this->assertContains( Documentation_Schema::TYPE_COMPOSITION_ONE_PAGER, $types );
		$this->assertCount( 3, $types );
	}

	public function test_required_fields_include_object_model_attributes(): void {
		$required = Documentation_Schema::get_required_fields();
		$this->assertContains( Documentation_Schema::FIELD_DOCUMENTATION_ID, $required );
		$this->assertContains( Documentation_Schema::FIELD_DOCUMENTATION_TYPE, $required );
		$this->assertContains( Documentation_Schema::FIELD_CONTENT_BODY, $required );
		$this->assertContains( Documentation_Schema::FIELD_STATUS, $required );
		$this->assertCount( 4, $required );
	}

	public function test_required_source_key_for_type(): void {
		$this->assertSame( Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY, Documentation_Schema::get_required_source_key_for_type( Documentation_Schema::TYPE_SECTION_HELPER ) );
		$this->assertSame( Documentation_Schema::SOURCE_PAGE_TEMPLATE_KEY, Documentation_Schema::get_required_source_key_for_type( Documentation_Schema::TYPE_PAGE_TEMPLATE_ONE_PAGER ) );
		$this->assertSame( Documentation_Schema::SOURCE_COMPOSITION_ID, Documentation_Schema::get_required_source_key_for_type( Documentation_Schema::TYPE_COMPOSITION_ONE_PAGER ) );
		$this->assertSame( '', Documentation_Schema::get_required_source_key_for_type( 'unknown_type' ) );
	}

	public function test_statuses_match_object_model(): void {
		$statuses = Documentation_Schema::get_statuses();
		$this->assertContains( 'draft', $statuses );
		$this->assertContains( 'active', $statuses );
		$this->assertContains( 'archived', $statuses );
	}

	public function test_editing_postures(): void {
		$postures = Documentation_Schema::get_editing_postures();
		$this->assertContains( Documentation_Schema::EDITING_GENERATED, $postures );
		$this->assertContains( Documentation_Schema::EDITING_HUMAN_EDITED, $postures );
		$this->assertContains( Documentation_Schema::EDITING_MIXED, $postures );
	}

	public function test_is_valid_documentation_type_and_is_valid_status(): void {
		$this->assertTrue( Documentation_Schema::is_valid_documentation_type( Documentation_Schema::TYPE_SECTION_HELPER ) );
		$this->assertFalse( Documentation_Schema::is_valid_documentation_type( 'custom_blog_post' ) );
		$this->assertTrue( Documentation_Schema::is_valid_status( 'active' ) );
		$this->assertFalse( Documentation_Schema::is_valid_status( 'published' ) );
	}

	/** Example: section helper document has all required keys and source. */
	public function test_example_section_helper_has_required_keys_and_source(): void {
		$doc = $this->get_valid_section_helper_example();
		$required = Documentation_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $doc, "Section helper example must have: {$field}" );
		}
		$this->assertSame( Documentation_Schema::TYPE_SECTION_HELPER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] );
		$this->assertArrayHasKey( Documentation_Schema::FIELD_SOURCE_REFERENCE, $doc );
		$this->assertArrayHasKey( Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY, $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] );
	}

	/** Example: composition one-pager has required source composition_id. */
	public function test_example_composition_one_pager_has_required_source(): void {
		$doc = $this->get_valid_composition_one_pager_example();
		$this->assertSame( Documentation_Schema::TYPE_COMPOSITION_ONE_PAGER, $doc[ Documentation_Schema::FIELD_DOCUMENTATION_TYPE ] );
		$this->assertArrayHasKey( Documentation_Schema::SOURCE_COMPOSITION_ID, $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] );
		$this->assertNotEmpty( $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ][ Documentation_Schema::SOURCE_COMPOSITION_ID ] );
	}

	/** Invalid: missing source reference for section_helper. */
	public function test_example_invalid_missing_source_reference(): void {
		$required_key = Documentation_Schema::get_required_source_key_for_type( Documentation_Schema::TYPE_SECTION_HELPER );
		$this->assertSame( Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY, $required_key );
		$doc = array(
			Documentation_Schema::FIELD_DOCUMENTATION_ID   => 'doc-bad',
			Documentation_Schema::FIELD_DOCUMENTATION_TYPE => Documentation_Schema::TYPE_SECTION_HELPER,
			Documentation_Schema::FIELD_CONTENT_BODY       => 'Text',
			Documentation_Schema::FIELD_STATUS             => 'draft',
			Documentation_Schema::FIELD_SOURCE_REFERENCE  => array(),
		);
		$this->assertArrayNotHasKey( Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY, $doc[ Documentation_Schema::FIELD_SOURCE_REFERENCE ] );
	}

	/** Invalid: undocumented document type. */
	public function test_example_invalid_undocumented_type(): void {
		$this->assertFalse( Documentation_Schema::is_valid_documentation_type( 'custom_blog_post' ) );
	}

	public function test_documentation_id_max_length(): void {
		$this->assertSame( 64, Documentation_Schema::DOCUMENTATION_ID_MAX_LENGTH );
	}

	private function get_valid_section_helper_example(): array {
		return array(
			Documentation_Schema::FIELD_DOCUMENTATION_ID   => 'doc-helper-st01-hero',
			Documentation_Schema::FIELD_DOCUMENTATION_TYPE => Documentation_Schema::TYPE_SECTION_HELPER,
			Documentation_Schema::FIELD_CONTENT_BODY       => '<p>This hero section is the first thing visitors see.</p>',
			Documentation_Schema::FIELD_STATUS             => 'active',
			Documentation_Schema::FIELD_SOURCE_REFERENCE   => array(
				Documentation_Schema::SOURCE_SECTION_TEMPLATE_KEY => 'st01_hero',
			),
			Documentation_Schema::FIELD_GENERATED_OR_HUMAN_EDITED => Documentation_Schema::EDITING_HUMAN_EDITED,
		);
	}

	private function get_valid_composition_one_pager_example(): array {
		return array(
			Documentation_Schema::FIELD_DOCUMENTATION_ID   => 'doc-onepager-comp-123',
			Documentation_Schema::FIELD_DOCUMENTATION_TYPE => Documentation_Schema::TYPE_COMPOSITION_ONE_PAGER,
			Documentation_Schema::FIELD_CONTENT_BODY       => '# Composition guide\n\nPurpose: Contact landing...',
			Documentation_Schema::FIELD_STATUS             => 'active',
			Documentation_Schema::FIELD_SOURCE_REFERENCE   => array(
				Documentation_Schema::SOURCE_COMPOSITION_ID => 'comp-uuid-12345',
			),
			Documentation_Schema::FIELD_GENERATED_OR_HUMAN_EDITED => Documentation_Schema::EDITING_GENERATED,
		);
	}
}
