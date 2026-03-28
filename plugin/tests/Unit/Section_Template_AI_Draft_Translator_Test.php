<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\AI\Translation\Section_Template_AI_Draft_Translator;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Section_Template_AI_Draft_Translator_Test extends TestCase {

	private function base_draft(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => 'st_ai_test',
			Section_Schema::FIELD_NAME                     => 'AI Section',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Summary',
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp_ref',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'fb_ref',
			Section_Schema::FIELD_HELPER_REF               => 'h_ref',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css_ref',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array( 'label' => 'Default' ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array( 'x' => 'y' ),
			Section_Schema::FIELD_VERSION                  => array(
				'major' => 1,
				'minor' => 0,
			),
			Section_Schema::FIELD_STATUS                   => 'draft',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'deps' => array() ),
		);
	}

	public function test_valid_draft(): void {
		$t = new Section_Template_AI_Draft_Translator();
		$r = $t->translate( $this->base_draft() );
		$this->assertTrue( $r->is_ok() );
	}

	public function test_default_variant_mismatch_fails(): void {
		$d = $this->base_draft();
		$d[ Section_Schema::FIELD_DEFAULT_VARIANT ] = 'missing';
		$t = new Section_Template_AI_Draft_Translator();
		$r = $t->translate( $d );
		$this->assertFalse( $r->is_ok() );
	}

	public function test_invalid_render_mode_fails(): void {
		$d                                      = $this->base_draft();
		$d[ Section_Schema::FIELD_RENDER_MODE ] = 'invalid_mode_xyz';
		$t                                      = new Section_Template_AI_Draft_Translator();
		$r                                      = $t->translate( $d );
		$this->assertFalse( $r->is_ok() );
	}
}
