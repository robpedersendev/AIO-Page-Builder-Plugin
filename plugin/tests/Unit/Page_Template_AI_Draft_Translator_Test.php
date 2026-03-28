<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Translation\Page_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Page_Template_AI_Draft_Translator_Test extends TestCase {

	private function base_draft(): array {
		$ordered = array(
			array(
				Page_Template_Schema::SECTION_ITEM_KEY => 'st_a',
				Page_Template_Schema::SECTION_ITEM_POSITION => 0,
				Page_Template_Schema::SECTION_ITEM_REQUIRED => true,
			),
		);
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY     => 'pt_test_ai',
			Page_Template_Schema::FIELD_NAME             => 'AI Page',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY  => 'Purpose',
			Page_Template_Schema::FIELD_ARCHETYPE        => 'landing_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS => $ordered,
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS => array( 'st_a' => array( 'required' => true ) ),
			Page_Template_Schema::FIELD_COMPATIBILITY    => array( 'lpagery' => 'ok' ),
			Page_Template_Schema::FIELD_ONE_PAGER        => array( Page_Template_Schema::ONE_PAGER_PURPOSE_SUMMARY => 'p' ),
			Page_Template_Schema::FIELD_VERSION          => array(
				'major' => 1,
				'minor' => 0,
			),
			Page_Template_Schema::FIELD_STATUS           => 'draft',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => array(),
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => '',
		);
	}

	public function test_valid_draft(): void {
		$t = new Page_Template_AI_Draft_Translator();
		$r = $t->translate( $this->base_draft() );
		$this->assertTrue( $r->is_ok() );
	}

	public function test_malformed_ordered_section_fails(): void {
		$d = $this->base_draft();
		$d[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] = array(
			array( Page_Template_Schema::SECTION_ITEM_KEY => 'x' ),
		);
		$t = new Page_Template_AI_Draft_Translator();
		$r = $t->translate( $d );
		$this->assertFalse( $r->is_ok() );
	}
}
