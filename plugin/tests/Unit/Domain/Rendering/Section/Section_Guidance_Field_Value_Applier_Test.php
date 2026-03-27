<?php
/**
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit\Domain\Rendering\Section;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\Rendering\Section\Section_Guidance_Field_Value_Applier;
use PHPUnit\Framework\TestCase;

/**
 * @covers \AIOPageBuilder\Domain\Rendering\Section\Section_Guidance_Field_Value_Applier
 */
final class Section_Guidance_Field_Value_Applier_Test extends TestCase {

	public function test_parse_json_string_guidance(): void {
		$json = '[{"section_key":"hero_01","intent":"Intro","content_direction":"Keep headline short."}]';
		$got  = Section_Guidance_Field_Value_Applier::parse_guidance_items( array( 'section_guidance' => $json ) );
		$this->assertCount( 1, $got );
		$this->assertSame( 'hero_01', $got[0]['section_key'] );
		$this->assertSame( 'Intro', $got[0]['intent'] );
	}

	public function test_field_values_fill_first_text_field(): void {
		$def    = array(
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => Field_Blueprint_Schema::TYPE_TEXT,
					),
				),
			),
		);
		$row    = array(
			'section_key'       => 'x',
			'intent'            => 'Hello',
			'content_direction' => 'World',
			'must_include'      => '',
			'must_avoid'        => '',
		);
		$values = Section_Guidance_Field_Value_Applier::field_values_for_section( $def, $row );
		$this->assertArrayHasKey( 'headline', $values );
		$this->assertStringContainsString( 'Hello', (string) $values['headline'] );
		$this->assertStringContainsString( 'World', (string) $values['headline'] );
	}

	public function test_field_values_split_across_multiple_text_fields(): void {
		$def    = array(
			'field_blueprint' => array(
				Field_Blueprint_Schema::FIELDS => array(
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'headline',
						Field_Blueprint_Schema::FIELD_TYPE => Field_Blueprint_Schema::TYPE_TEXT,
					),
					array(
						Field_Blueprint_Schema::FIELD_NAME => 'subhead',
						Field_Blueprint_Schema::FIELD_TYPE => Field_Blueprint_Schema::TYPE_TEXT,
					),
				),
			),
		);
		$row    = array(
			'section_key'       => 'x',
			'intent'            => 'First block.',
			'content_direction' => 'Second block.',
			'must_include'      => '',
			'must_avoid'        => '',
		);
		$values = Section_Guidance_Field_Value_Applier::field_values_for_section( $def, $row );
		$this->assertArrayHasKey( 'headline', $values );
		$this->assertArrayHasKey( 'subhead', $values );
		$this->assertNotSame( (string) $values['headline'], (string) $values['subhead'] );
	}
}
