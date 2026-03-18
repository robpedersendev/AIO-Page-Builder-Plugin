<?php
/**
 * Unit tests for Field_Blueprint_Schema: supported types, required properties, validation (Prompt 033).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';

final class Field_Blueprint_Schema_Test extends TestCase {

	public function test_supported_types_include_all_spec_types(): void {
		$types = Field_Blueprint_Schema::get_supported_types();
		$this->assertContains( Field_Blueprint_Schema::TYPE_TEXT, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_TEXTAREA, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_WYSIWYG, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_IMAGE, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_GALLERY, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_LINK, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_REPEATER, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_SELECT, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_TRUE_FALSE, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_RELATIONSHIP, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_NUMBER, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_URL, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_GROUP, $types );
		$this->assertContains( Field_Blueprint_Schema::TYPE_COLOR_PICKER, $types );
	}

	public function test_required_blueprint_fields_checklist(): void {
		$required = Field_Blueprint_Schema::get_required_blueprint_fields();
		$this->assertContains( Field_Blueprint_Schema::BLUEPRINT_ID, $required );
		$this->assertContains( Field_Blueprint_Schema::SECTION_KEY, $required );
		$this->assertContains( Field_Blueprint_Schema::SECTION_VERSION, $required );
		$this->assertContains( Field_Blueprint_Schema::LABEL, $required );
		$this->assertContains( Field_Blueprint_Schema::FIELDS, $required );
		$this->assertCount( 5, $required );
	}

	public function test_required_field_properties_checklist(): void {
		$required = Field_Blueprint_Schema::get_required_field_properties();
		$this->assertContains( Field_Blueprint_Schema::FIELD_KEY, $required );
		$this->assertContains( Field_Blueprint_Schema::FIELD_NAME, $required );
		$this->assertContains( Field_Blueprint_Schema::FIELD_LABEL, $required );
		$this->assertContains( Field_Blueprint_Schema::FIELD_TYPE, $required );
		$this->assertCount( 4, $required );
	}

	public function test_validate_blueprint_rejects_missing_fields(): void {
		$blueprint = array(
			'blueprint_id' => 'acf_st01',
			'section_key'  => 'st01_hero',
		);
		$errors    = Field_Blueprint_Schema::validate_blueprint_required_fields( $blueprint );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'section_version', implode( ' ', $errors ) );
		$this->assertStringContainsString( 'label', implode( ' ', $errors ) );
		$this->assertStringContainsString( 'fields', implode( ' ', $errors ) );
	}

	public function test_validate_blueprint_rejects_empty_fields(): void {
		$blueprint = array(
			'blueprint_id'    => 'acf_st01',
			'section_key'     => 'st01_hero',
			'section_version' => '1',
			'label'           => 'Hero',
			'fields'          => array(),
		);
		$errors    = Field_Blueprint_Schema::validate_blueprint_required_fields( $blueprint );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'fields', implode( ' ', $errors ) );
	}

	public function test_validate_blueprint_accepts_valid(): void {
		$blueprint = array(
			'blueprint_id'    => 'acf_st01',
			'section_key'     => 'st01_hero',
			'section_version' => '1',
			'label'           => 'Hero',
			'fields'          => array(
				array(
					'key'   => 'field_st01_h',
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
			),
		);
		$errors    = Field_Blueprint_Schema::validate_blueprint_required_fields( $blueprint );
		$this->assertEmpty( $errors );
	}

	public function test_validate_field_rejects_empty_key(): void {
		$field  = array(
			'key'   => '',
			'name'  => 'headline',
			'label' => 'Headline',
			'type'  => 'text',
		);
		$errors = Field_Blueprint_Schema::validate_field_required_properties( $field );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'key', implode( ' ', $errors ) );
	}

	public function test_validate_field_rejects_unsupported_type(): void {
		$field  = array(
			'key'   => 'field_st99_x',
			'name'  => 'x',
			'label' => 'X',
			'type'  => 'flexible_content',
		);
		$errors = Field_Blueprint_Schema::validate_field_required_properties( $field );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'Unsupported', implode( ' ', $errors ) );
	}

	public function test_validate_field_repeater_requires_sub_fields(): void {
		$field  = array(
			'key'   => 'field_st05_items',
			'name'  => 'items',
			'label' => 'Items',
			'type'  => 'repeater',
		);
		$errors = Field_Blueprint_Schema::validate_field_required_properties( $field );
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'sub_fields', implode( ' ', $errors ) );
	}

	public function test_lpagery_supported_types(): void {
		$this->assertTrue( Field_Blueprint_Schema::is_lpagery_supported_type( 'text' ) );
		$this->assertTrue( Field_Blueprint_Schema::is_lpagery_supported_type( 'textarea' ) );
		$this->assertTrue( Field_Blueprint_Schema::is_lpagery_supported_type( 'url' ) );
		$this->assertFalse( Field_Blueprint_Schema::is_lpagery_supported_type( 'image' ) );
	}

	public function test_lpagery_unsupported_types(): void {
		$this->assertTrue( Field_Blueprint_Schema::is_lpagery_unsupported_type( 'relationship' ) );
		$this->assertTrue( Field_Blueprint_Schema::is_lpagery_unsupported_type( 'gallery' ) );
		$this->assertFalse( Field_Blueprint_Schema::is_lpagery_unsupported_type( 'text' ) );
	}
}
