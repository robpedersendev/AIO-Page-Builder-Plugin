<?php
/**
 * Unit tests for Field_Key_Generator: deterministic generation, collision handling (Prompt 034).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';

final class Field_Key_Generator_Test extends TestCase {

	public function test_group_key_deterministic(): void {
		$k1 = Field_Key_Generator::group_key( 'st01_hero' );
		$k2 = Field_Key_Generator::group_key( 'st01_hero' );
		$this->assertSame( $k1, $k2 );
		$this->assertSame( 'group_aio_st01_hero', $k1 );
	}

	public function test_group_key_format(): void {
		$key = Field_Key_Generator::group_key( 'st05_faq' );
		$this->assertStringStartsWith( 'group_aio_', $key );
		$this->assertStringEndsWith( 'st05_faq', $key );
		$this->assertTrue( Field_Key_Generator::is_valid_key( $key, 'group' ) );
	}

	public function test_field_key_deterministic(): void {
		$k1 = Field_Key_Generator::field_key( 'st01_hero', 'headline' );
		$k2 = Field_Key_Generator::field_key( 'st01_hero', 'headline' );
		$this->assertSame( $k1, $k2 );
		$this->assertSame( 'field_st01_hero_headline', $k1 );
	}

	public function test_field_key_format(): void {
		$key = Field_Key_Generator::field_key( 'st01_hero', 'subheadline' );
		$this->assertStringStartsWith( 'field_', $key );
		$this->assertSame( 'field_st01_hero_subheadline', $key );
		$this->assertTrue( Field_Key_Generator::is_valid_key( $key, 'field' ) );
	}

	public function test_subfield_key_deterministic(): void {
		$k1 = Field_Key_Generator::subfield_key( 'st05_faq', 'faq_items', 'question' );
		$k2 = Field_Key_Generator::subfield_key( 'st05_faq', 'faq_items', 'question' );
		$this->assertSame( $k1, $k2 );
		$this->assertSame( 'field_st05_faq_faq_items_question', $k1 );
	}

	public function test_subfield_key_format(): void {
		$key = Field_Key_Generator::subfield_key( 'st05_faq', 'faq_items', 'answer' );
		$this->assertSame( 'field_st05_faq_faq_items_answer', $key );
		$this->assertTrue( Field_Key_Generator::is_valid_key( $key, 'field' ) );
	}

	public function test_sanitize_strips_invalid_chars(): void {
		$this->assertSame( 'headline', Field_Key_Generator::sanitize( 'Headline' ) );
		$this->assertSame( 'headline', Field_Key_Generator::sanitize( 'head-line' ) );
		$this->assertSame( 'st01', Field_Key_Generator::sanitize( 'ST01' ) );
	}

	public function test_collision_handling_ensure_unique(): void {
		$key = Field_Key_Generator::ensure_unique( 'field_st01_hero_headline', array() );
		$this->assertSame( 'field_st01_hero_headline', $key );

		$key2 = Field_Key_Generator::ensure_unique( 'field_st01_hero_headline', array( 'field_st01_hero_headline' ) );
		$this->assertSame( 'field_st01_hero_headline_1', $key2 );

		$key3 = Field_Key_Generator::ensure_unique( 'field_st01_hero_headline', array( 'field_st01_hero_headline', 'field_st01_hero_headline_1' ) );
		$this->assertSame( 'field_st01_hero_headline_2', $key3 );
	}

	public function test_naming_examples_matrix(): void {
		$matrix = array(
			'group_st01'   => Field_Key_Generator::group_key( 'st01_hero' ),
			'field_headline' => Field_Key_Generator::field_key( 'st01_hero', 'headline' ),
			'subfield_question' => Field_Key_Generator::subfield_key( 'st05_faq', 'faq_items', 'question' ),
		);
		$this->assertSame( 'group_aio_st01_hero', $matrix['group_st01'] );
		$this->assertSame( 'field_st01_hero_headline', $matrix['field_headline'] );
		$this->assertSame( 'field_st05_faq_faq_items_question', $matrix['subfield_question'] );
	}

	public function test_is_valid_key_rejects_invalid(): void {
		$this->assertFalse( Field_Key_Generator::is_valid_key( 'group_st01_hero', 'group' ) );
		$this->assertFalse( Field_Key_Generator::is_valid_key( 'field_Headline', 'field' ) );
		$this->assertFalse( Field_Key_Generator::is_valid_key( 'invalid', 'field' ) );
		$this->assertTrue( Field_Key_Generator::is_valid_key( 'group_aio_st01_hero', 'group' ) );
	}

	public function test_truncation_when_over_max(): void {
		$long_section = str_repeat( 'a', 70 );
		$key = Field_Key_Generator::field_key( $long_section, 'x' );
		$this->assertLessThanOrEqual( 64, strlen( $key ) );
	}
}
