<?php
/**
 * Unit tests for Page_Template_Schema: required fields completeness (§13.2, §10.2), ordered section structure, valid/invalid examples (Prompt 022).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Registries\PageTemplate\Page_Template_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Registries/PageTemplate/Page_Template_Schema.php';

final class Page_Template_Schema_Test extends TestCase {

	/**
	 * Completeness checklist: every required field from spec §13.2 / object contract §10.2 must be in get_required_fields().
	 */
	public function test_required_fields_include_all_spec_13_2_items(): void {
		$required = Page_Template_Schema::get_required_fields();
		$checklist = array(
			'stable internal page-template key'   => Page_Template_Schema::FIELD_INTERNAL_KEY,
			'human-readable name'                 => Page_Template_Schema::FIELD_NAME,
			'page purpose summary'                => Page_Template_Schema::FIELD_PURPOSE_SUMMARY,
			'category or template archetype'      => Page_Template_Schema::FIELD_ARCHETYPE,
			'ordered section list'                => Page_Template_Schema::FIELD_ORDERED_SECTIONS,
			'required vs optional section design' => Page_Template_Schema::FIELD_SECTION_REQUIREMENTS,
			'compatibility metadata'              => Page_Template_Schema::FIELD_COMPATIBILITY,
			'one-pager generation metadata'       => Page_Template_Schema::FIELD_ONE_PAGER,
			'version marker'                      => Page_Template_Schema::FIELD_VERSION,
			'active/deprecated status'           => Page_Template_Schema::FIELD_STATUS,
			'default structural assumptions'     => Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS,
			'endpoint or usage notes'             => Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES,
		);
		$this->assertCount( 12, $checklist );
		foreach ( $checklist as $label => $field_name ) {
			$this->assertContains( $field_name, $required, "Required field from §13.2 must be present: {$label} ({$field_name})" );
		}
		$this->assertCount( 12, $required );
	}

	public function test_optional_fields_list_non_empty(): void {
		$optional = Page_Template_Schema::get_optional_fields();
		$this->assertNotEmpty( $optional );
		$this->assertContains( 'notes_for_ai_planning', $optional );
		$this->assertContains( 'seo_defaults', $optional );
		$this->assertContains( 'deprecation', $optional );
	}

	public function test_allowed_archetypes_include_spec_13_6_examples(): void {
		$archetypes = Page_Template_Schema::get_allowed_archetypes();
		$this->assertArrayHasKey( 'landing_page', $archetypes );
		$this->assertArrayHasKey( 'faq_page', $archetypes );
		$this->assertArrayHasKey( 'service_page', $archetypes );
		$this->assertArrayHasKey( 'hub_page', $archetypes );
	}

	public function test_is_allowed_archetype_accepts_valid_rejects_invalid(): void {
		$this->assertTrue( Page_Template_Schema::is_allowed_archetype( 'landing_page' ) );
		$this->assertTrue( Page_Template_Schema::is_allowed_archetype( 'informational_detail' ) );
		$this->assertFalse( Page_Template_Schema::is_allowed_archetype( 'unknown' ) );
		$this->assertFalse( Page_Template_Schema::is_allowed_archetype( '' ) );
	}

	public function test_ordered_section_item_keys(): void {
		$keys = Page_Template_Schema::get_ordered_section_item_keys();
		$this->assertContains( Page_Template_Schema::SECTION_ITEM_KEY, $keys );
		$this->assertContains( Page_Template_Schema::SECTION_ITEM_POSITION, $keys );
		$this->assertContains( Page_Template_Schema::SECTION_ITEM_REQUIRED, $keys );
		$this->assertCount( 3, $keys );
	}

	/** Example valid minimal page template has all required keys. */
	public function test_example_valid_minimal_has_all_required_keys(): void {
		$valid = $this->get_valid_minimal_page_template();
		$required = Page_Template_Schema::get_required_fields();
		foreach ( $required as $field ) {
			$this->assertArrayHasKey( $field, $valid, "Valid minimal example must have required field: {$field}" );
		}
		$this->assertNotEmpty( $valid[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] );
		$this->assertArrayHasKey( 'page_purpose_summary', $valid[ Page_Template_Schema::FIELD_ONE_PAGER ] );
		$this->assertArrayHasKey( 'version', $valid[ Page_Template_Schema::FIELD_VERSION ] );
	}

	/** Example invalid: missing required field (one_pager) → incomplete. */
	public function test_example_invalid_missing_required_field(): void {
		$invalid = $this->get_valid_minimal_page_template();
		unset( $invalid[ Page_Template_Schema::FIELD_ONE_PAGER ] );
		$this->assertArrayNotHasKey( Page_Template_Schema::FIELD_ONE_PAGER, $invalid );
		$required = Page_Template_Schema::get_required_fields();
		$this->assertContains( Page_Template_Schema::FIELD_ONE_PAGER, $required );
	}

	/** Example invalid: empty ordered_sections → incomplete. */
	public function test_example_invalid_empty_ordered_sections(): void {
		$invalid = $this->get_valid_minimal_page_template();
		$invalid[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] = array();
		$this->assertEmpty( $invalid[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ] );
	}

	/** Example invalid: section_requirements does not cover all section_key in ordered_sections. */
	public function test_example_invalid_section_requirements_missing_entry(): void {
		$valid = $this->get_valid_minimal_page_template();
		$valid[ Page_Template_Schema::FIELD_ORDERED_SECTIONS ][] = array(
			'section_key' => 'st05_cta',
			'position'    => 2,
			'required'    => false,
		);
		// section_requirements still only has st01_hero
		$req = $valid[ Page_Template_Schema::FIELD_SECTION_REQUIREMENTS ];
		$this->assertArrayNotHasKey( 'st05_cta', $req );
	}

	public function test_internal_key_pattern_and_constants(): void {
		$this->assertSame( 64, Page_Template_Schema::INTERNAL_KEY_MAX_LENGTH );
		$this->assertMatchesRegularExpression( Page_Template_Schema::INTERNAL_KEY_PATTERN, 'pt_landing_contact' );
		$this->assertDoesNotMatchRegularExpression( Page_Template_Schema::INTERNAL_KEY_PATTERN, 'Landing-Contact' );
	}

	private function get_valid_minimal_page_template(): array {
		return array(
			Page_Template_Schema::FIELD_INTERNAL_KEY           => 'pt_landing_contact',
			Page_Template_Schema::FIELD_NAME                   => 'Landing – Contact',
			Page_Template_Schema::FIELD_PURPOSE_SUMMARY        => 'Single-purpose landing for contact.',
			Page_Template_Schema::FIELD_ARCHETYPE              => 'landing_page',
			Page_Template_Schema::FIELD_ORDERED_SECTIONS        => array(
				array(
					'section_key' => 'st01_hero',
					'position'    => 0,
					'required'    => true,
				),
			),
			Page_Template_Schema::FIELD_SECTION_REQUIREMENTS    => array(
				'st01_hero' => array( 'required' => true ),
			),
			Page_Template_Schema::FIELD_COMPATIBILITY           => array(
				'site_contexts_appropriate' => array(),
				'site_contexts_inappropriate' => array(),
				'conflicts_with_purposes'   => array(),
			),
			Page_Template_Schema::FIELD_ONE_PAGER               => array(
				'page_purpose_summary' => 'Contact-focused landing: hero plus CTA.',
				'section_helper_order' => 'same_as_template',
			),
			Page_Template_Schema::FIELD_VERSION                 => array(
				'version'             => '1',
				'stable_key_retained' => true,
			),
			Page_Template_Schema::FIELD_STATUS                  => 'active',
			Page_Template_Schema::FIELD_DEFAULT_STRUCTURAL_ASSUMPTIONS => 'Single column.',
			Page_Template_Schema::FIELD_ENDPOINT_OR_USAGE_NOTES => 'Campaign landing.',
		);
	}
}
