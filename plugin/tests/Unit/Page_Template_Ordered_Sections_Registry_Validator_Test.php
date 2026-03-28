<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Ordered_Sections_Registry_Validator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use AIOPageBuilder\Domain\Storage\Repositories\Section_Template_Repository;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Page_Template_Ordered_Sections_Registry_Validator_Test extends TestCase {

	public function test_valid_ordered_sections_pass(): void {
		$sections = $this->createMock( Section_Template_Repository::class );
		$sections->method( 'get_by_key' )->willReturn( array( 'internal_key' => 'sec_a' ) );
		$v   = new Page_Template_Ordered_Sections_Registry_Validator( $sections );
		$def = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => 'sec_a',
					Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
		$this->assertSame( array(), $v->validate( $def ) );
	}

	public function test_unknown_section_key_fails(): void {
		$sections = $this->createMock( Section_Template_Repository::class );
		$sections->method( 'get_by_key' )->willReturn( null );
		$v    = new Page_Template_Ordered_Sections_Registry_Validator( $sections );
		$def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => 'missing',
					Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				),
			),
		);
		$errs = $v->validate( $def );
		$this->assertNotSame( array(), $errs );
		$this->assertTrue( in_array( 'unknown_section_key:missing', $errs, true ) );
	}

	public function test_duplicate_position_fails(): void {
		$sections = $this->createMock( Section_Template_Repository::class );
		$sections->method( 'get_by_key' )->willReturn( array( 'internal_key' => 'x' ) );
		$v    = new Page_Template_Ordered_Sections_Registry_Validator( $sections );
		$def  = array(
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => array(
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => 'sec_a',
					Page_Template_Schema::SECTION_ITEM_POSITION => 1,
				),
				array(
					Page_Template_Schema::SECTION_ITEM_KEY => 'sec_b',
					Page_Template_Schema::SECTION_ITEM_POSITION => 1,
				),
			),
		);
		$errs = $v->validate( $def );
		$this->assertTrue( in_array( 'duplicate_position:1', $errs, true ) );
	}
}
