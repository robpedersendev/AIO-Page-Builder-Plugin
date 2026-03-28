<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use AIOPageBuilder\Domain\Registries\Section\Section_Template_Canonical_Semantic_Validator;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Section_Template_Canonical_Semantic_Validator_Test extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private function valid_section_definition(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY             => 'st_test',
			Section_Schema::FIELD_NAME                     => 'Test',
			Section_Schema::FIELD_PURPOSE_SUMMARY          => 'Purpose',
			Section_Schema::FIELD_CATEGORY                 => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'bp/ref',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF      => 'fb/ref',
			Section_Schema::FIELD_HELPER_REF               => 'helper/ref',
			Section_Schema::FIELD_CSS_CONTRACT_REF         => 'css/ref',
			Section_Schema::FIELD_DEFAULT_VARIANT          => 'default',
			Section_Schema::FIELD_VARIANTS                 => array(
				'default' => array( 'label' => 'Default' ),
			),
			Section_Schema::FIELD_COMPATIBILITY            => array( 'block' => true ),
			Section_Schema::FIELD_VERSION                  => array(
				'major' => 1,
				'minor' => 0,
			),
			Section_Schema::FIELD_STATUS                   => 'draft',
			Section_Schema::FIELD_RENDER_MODE              => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION        => array( 'deps' => array() ),
		);
	}

	public function test_valid_definition_passes(): void {
		$v = new Section_Template_Canonical_Semantic_Validator();
		$this->assertSame( array(), $v->validate( $this->valid_section_definition() ) );
	}

	public function test_default_variant_not_in_variants_fails(): void {
		$d = $this->valid_section_definition();
		$d[ Section_Schema::FIELD_DEFAULT_VARIANT ] = 'other';
		$v = new Section_Template_Canonical_Semantic_Validator();
		$this->assertContains( 'default_variant_not_in_variants', $v->validate( $d ) );
	}

	public function test_invalid_render_mode_fails(): void {
		$d                                      = $this->valid_section_definition();
		$d[ Section_Schema::FIELD_RENDER_MODE ] = 'not_a_mode';
		$v                                      = new Section_Template_Canonical_Semantic_Validator();
		$this->assertContains( 'invalid_render_mode', $v->validate( $d ) );
	}

	public function test_invalid_category_fails(): void {
		$d                                   = $this->valid_section_definition();
		$d[ Section_Schema::FIELD_CATEGORY ] = 'unknown_cat';
		$v                                   = new Section_Template_Canonical_Semantic_Validator();
		$this->assertContains( 'invalid_category', $v->validate( $d ) );
	}

	public function test_empty_contract_ref_fails(): void {
		$d = $this->valid_section_definition();
		$d[ Section_Schema::FIELD_CSS_CONTRACT_REF ] = ' ';
		$v = new Section_Template_Canonical_Semantic_Validator();
		$this->assertContains( 'empty_ref:' . Section_Schema::FIELD_CSS_CONTRACT_REF, $v->validate( $d ) );
	}
}
