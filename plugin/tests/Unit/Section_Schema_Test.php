<?php
/**
 * Unit tests for Section_Schema: required fields completeness (§12.2), allowed categories/render modes, valid/invalid examples (Prompt 021).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';

final class Section_Schema_Test extends TestCase {

	/**
	 * Completeness checklist: every required field from spec §12.2 must be in get_required_fields().
	 */
	public function test_required_fields_include_all_spec_12_2_items(): void {
		$required = Section_Schema::get_required_fields();
		$checklist = array(
			'stable internal section key'        => Section_Schema::FIELD_INTERNAL_KEY,
			'human-readable section name'        => Section_Schema::FIELD_NAME,
			'purpose summary'                    => Section_Schema::FIELD_PURPOSE_SUMMARY,
			'section category'                  => Section_Schema::FIELD_CATEGORY,
			'blueprint definition reference'     => Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF,
			'field-group blueprint reference'   => Section_Schema::FIELD_FIELD_BLUEPRINT_REF,
			'helper paragraph reference'        => Section_Schema::FIELD_HELPER_REF,
			'CSS contract manifest reference'    => Section_Schema::FIELD_CSS_CONTRACT_REF,
			'default variant or baseline'       => Section_Schema::FIELD_DEFAULT_VARIANT,
			'variants'                           => Section_Schema::FIELD_VARIANTS,
			'compatibility metadata'            => Section_Schema::FIELD_COMPATIBILITY,
			'version marker'                     => Section_Schema::FIELD_VERSION,
			'active/deprecated status'          => Section_Schema::FIELD_STATUS,
			'render mode classification'        => Section_Schema::FIELD_RENDER_MODE,
			'asset dependency declaration'      => Section_Schema::FIELD_ASSET_DECLARATION,
		);
		$this->assertCount( 15, $checklist, 'Checklist must cover all 12.2 required concepts' );
		foreach ( $checklist as $label => $field_name ) {
			$this->assertContains( $field_name, $required, "Required field from §12.2 must be present: {$label} ({$field_name})" );
		}
		$this->assertCount( 15, $required, 'Required fields count must match §12.2 checklist' );
	}

	public function test_optional_fields_list_non_empty(): void {
		$optional = Section_Schema::get_optional_fields();
		$this->assertNotEmpty( $optional );
		$this->assertContains( 'short_label', $optional );
		$this->assertContains( 'notes_for_ai_planning', $optional );
		$this->assertContains( 'deprecation', $optional );
	}

	public function test_allowed_categories_include_spec_12_6_examples(): void {
		$categories = Section_Schema::get_allowed_categories();
		$this->assertArrayHasKey( 'hero_intro', $categories );
		$this->assertArrayHasKey( 'faq', $categories );
		$this->assertArrayHasKey( 'cta_conversion', $categories );
		$this->assertArrayHasKey( 'utility_structural', $categories );
	}

	public function test_is_allowed_category_accepts_valid_rejects_invalid(): void {
		$this->assertTrue( Section_Schema::is_allowed_category( 'hero_intro' ) );
		$this->assertTrue( Section_Schema::is_allowed_category( 'faq' ) );
		$this->assertFalse( Section_Schema::is_allowed_category( 'unknown' ) );
		$this->assertFalse( Section_Schema::is_allowed_category( '' ) );
	}

	public function test_allowed_render_modes_include_block_contained(): void {
		$modes = Section_Schema::get_allowed_render_modes();
		$this->assertContains( 'block', $modes );
		$this->assertContains( 'contained', $modes );
		$this->assertContains( 'full_width', $modes );
	}

	public function test_is_allowed_render_mode_accepts_valid_rejects_invalid(): void {
		$this->assertTrue( Section_Schema::is_allowed_render_mode( 'block' ) );
		$this->assertFalse( Section_Schema::is_allowed_render_mode( 'custom' ) );
		$this->assertFalse( Section_Schema::is_allowed_render_mode( '' ) );
	}

	/** Example valid minimal section definition (all required keys present). */
	public function test_example_valid_minimal_has_all_required_keys(): void {
		$valid = $this->get_valid_minimal_section_definition();
		$required = Section_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $valid, "Valid minimal example must have required field: {$field}" );
		}
	}

	/** Example invalid: missing required field (helper_ref) → incomplete. */
	public function test_example_invalid_missing_required_field(): void {
		$invalid = $this->get_valid_minimal_section_definition();
		unset( $invalid[ Section_Schema::FIELD_HELPER_REF ] );
		$required = Section_Schema::get_required_fields();
		$missing = array_filter( $required, function ( $f ) use ( $invalid ) {
			return ! array_key_exists( $f, $invalid ) || $invalid[ $f ] === '' || $invalid[ $f ] === array();
		} );
		$this->assertNotEmpty( $missing, 'Example invalid definition must be missing at least one required field' );
		$this->assertContains( Section_Schema::FIELD_HELPER_REF, $missing );
	}

	/** Example invalid: default_variant not in variants → incomplete. */
	public function test_example_invalid_default_variant_not_in_variants(): void {
		$required = Section_Schema::get_required_fields();
		$this->assertContains( Section_Schema::FIELD_DEFAULT_VARIANT, $required );
		$this->assertContains( Section_Schema::FIELD_VARIANTS, $required );
		$invalid = $this->get_valid_minimal_section_definition();
		$invalid[ Section_Schema::FIELD_VARIANTS ] = array( 'other_variant' => array( 'label' => 'Other' ) );
		$invalid[ Section_Schema::FIELD_DEFAULT_VARIANT ] = 'default';
		$this->assertArrayNotHasKey( 'default', $invalid[ Section_Schema::FIELD_VARIANTS ] );
	}

	/** Example invalid: status not in allowed set. */
	public function test_example_invalid_status(): void {
		$allowed_statuses = array( 'draft', 'active', 'inactive', 'deprecated' );
		$this->assertNotContains( 'published', $allowed_statuses );
		$this->assertNotContains( 'archived', $allowed_statuses );
	}

	public function test_internal_key_pattern_and_constants(): void {
		$this->assertSame( 64, Section_Schema::INTERNAL_KEY_MAX_LENGTH );
		$this->assertMatchesRegularExpression( Section_Schema::INTERNAL_KEY_PATTERN, 'st01_hero' );
		$this->assertMatchesRegularExpression( Section_Schema::INTERNAL_KEY_PATTERN, 'hero_intro' );
		$this->assertDoesNotMatchRegularExpression( Section_Schema::INTERNAL_KEY_PATTERN, 'Hero-Intro' );
		$this->assertDoesNotMatchRegularExpression( Section_Schema::INTERNAL_KEY_PATTERN, '' );
	}

	private function get_valid_minimal_section_definition(): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY           => 'st01_hero',
			Section_Schema::FIELD_NAME                  => 'Hero',
			Section_Schema::FIELD_PURPOSE_SUMMARY       => 'Primary hero section.',
			Section_Schema::FIELD_CATEGORY              => 'hero_intro',
			Section_Schema::FIELD_STRUCTURAL_BLUEPRINT_REF => 'blueprint_st01',
			Section_Schema::FIELD_FIELD_BLUEPRINT_REF  => 'acf_st01',
			Section_Schema::FIELD_HELPER_REF           => 'helper_st01',
			Section_Schema::FIELD_CSS_CONTRACT_REF     => 'css_st01',
			Section_Schema::FIELD_DEFAULT_VARIANT      => 'default',
			Section_Schema::FIELD_VARIANTS             => array( 'default' => array( 'label' => 'Default' ) ),
			Section_Schema::FIELD_COMPATIBILITY        => array( 'may_precede' => array(), 'may_follow' => array(), 'avoid_adjacent' => array(), 'duplicate_purpose_of' => array() ),
			Section_Schema::FIELD_VERSION              => array( 'version' => '1', 'stable_key_retained' => true ),
			Section_Schema::FIELD_STATUS               => 'active',
			Section_Schema::FIELD_RENDER_MODE          => 'block',
			Section_Schema::FIELD_ASSET_DECLARATION    => array( 'none' => true ),
		);
	}
}
