<?php
/**
 * Unit tests for large-scale ACF blueprint reuse: Blueprint_Family_Registry, Blueprint_Family_Resolver, Preview_Family_Mapping, deterministic registration (Prompt 173).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Blueprint_Family_Registry;
use AIOPageBuilder\Domain\ACF\Blueprints\Blueprint_Family_Resolver;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Preview_Family_Mapping;
use AIOPageBuilder\Domain\Registries\Section\Section_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Blueprint_Family_Registry.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Blueprint_Family_Resolver.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Preview_Family_Mapping.php';
require_once $plugin_root . '/src/Domain/Registries/Section/Section_Schema.php';

final class Blueprint_Family_Scale_Test extends TestCase {

	/** Example normalized blueprint (hero family base) for resolution payload. */
	private function example_normalized_blueprint(): array {
		return array(
			'blueprint_id'    => 'acf_blueprint_hero_primary',
			'section_key'     => 'hero_primary_01',
			'section_version' => '1',
			'label'           => 'Hero Section Fields',
			'fields'          => array(
				array(
					'key'   => 'field_hero_primary_01_headline',
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_hero_primary_01_subheadline',
					'name'  => 'subheadline',
					'label' => 'Subheadline',
					'type'  => 'textarea',
				),
				array(
					'key'   => 'field_hero_primary_01_cta',
					'name'  => 'cta',
					'label' => 'CTA Link',
					'type'  => 'link',
				),
			),
		);
	}

	/** Example section definition with variation_family_key and default_variant. */
	private function example_section_definition( string $variant = 'default' ): array {
		return array(
			Section_Schema::FIELD_INTERNAL_KEY    => 'hero_primary_01',
			'field_blueprint_ref'                 => 'acf_blueprint_hero_primary',
			'variation_family_key'                => 'hero_primary',
			Section_Schema::FIELD_DEFAULT_VARIANT => $variant,
			'section_purpose_family'              => 'hero',
		);
	}

	// ---------- Blueprint_Family_Registry ----------

	public function test_registry_register_and_get_family(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family( 'hero_primary', 'acf_blueprint_hero_primary', array() );
		$fam = $registry->get_family( 'hero_primary' );
		$this->assertIsArray( $fam );
		$this->assertSame( 'acf_blueprint_hero_primary', $fam[ Blueprint_Family_Registry::KEY_BASE_BLUEPRINT_REF ] );
		$this->assertSame( array(), $fam[ Blueprint_Family_Registry::KEY_VARIANT_OVERRIDES ] );
	}

	public function test_registry_get_base_blueprint_ref_and_variant_overrides(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family(
			'proof_cards',
			'acf_blueprint_proof',
			array(
				'compact' => array(
					Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES => array( 'quote', 'attribution' ),
				),
			)
		);
		$this->assertSame( 'acf_blueprint_proof', $registry->get_base_blueprint_ref( 'proof_cards' ) );
		$overrides = $registry->get_variant_overrides( 'proof_cards' );
		$this->assertArrayHasKey( 'compact', $overrides );
		$this->assertSame( array( 'quote', 'attribution' ), $overrides['compact'][ Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES ] );
	}

	public function test_registry_has_family_and_get_registered_family_keys(): void {
		$registry = new Blueprint_Family_Registry();
		$this->assertFalse( $registry->has_family( 'hero_primary' ) );
		$registry->register_family( 'hero_primary', 'ref1', array() );
		$registry->register_family( 'proof_cards', 'ref2', array() );
		$this->assertTrue( $registry->has_family( 'hero_primary' ) );
		$keys = $registry->get_registered_family_keys();
		$this->assertSame( array( 'hero_primary', 'proof_cards' ), $keys );
	}

	// ---------- Blueprint_Family_Resolver ----------

	public function test_resolver_returns_blueprint_unchanged_when_no_family(): void {
		$registry   = new Blueprint_Family_Registry();
		$resolver   = new Blueprint_Family_Resolver( $registry );
		$definition = array( Section_Schema::FIELD_INTERNAL_KEY => 'sec_01' );
		$blueprint  = $this->example_normalized_blueprint();
		$resolved   = $resolver->resolve( $definition, $blueprint );
		$this->assertSame( $blueprint, $resolved );
	}

	public function test_resolver_returns_blueprint_unchanged_when_family_has_no_override_for_variant(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family(
			'hero_primary',
			'acf_blueprint_hero_primary',
			array(
				'minimal' => array( Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES => array( 'subheadline' ) ),
			)
		);
		$resolver   = new Blueprint_Family_Resolver( $registry );
		$definition = $this->example_section_definition( 'default' );
		$blueprint  = $this->example_normalized_blueprint();
		$resolved   = $resolver->resolve( $definition, $blueprint );
		$this->assertSame( 3, count( $resolved[ Field_Blueprint_Schema::FIELDS ] ) );
	}

	public function test_resolver_applies_hide_field_names(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family(
			'hero_primary',
			'acf_blueprint_hero_primary',
			array(
				'compact' => array( Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES => array( 'subheadline', 'cta' ) ),
			)
		);
		$resolver   = new Blueprint_Family_Resolver( $registry );
		$definition = $this->example_section_definition( 'compact' );
		$blueprint  = $this->example_normalized_blueprint();
		$resolved   = $resolver->resolve( $definition, $blueprint );
		$names      = array_column( $resolved[ Field_Blueprint_Schema::FIELDS ], 'name' );
		$this->assertContains( 'headline', $names );
		$this->assertNotContains( 'subheadline', $names );
		$this->assertNotContains( 'cta', $names );
	}

	public function test_resolver_applies_add_fields(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family(
			'hero_primary',
			'acf_blueprint_hero_primary',
			array(
				'extended' => array(
					Blueprint_Family_Registry::KEY_ADD_FIELDS => array(
						array(
							'name'  => 'eyebrow',
							'label' => 'Eyebrow',
							'type'  => 'text',
						),
					),
				),
			)
		);
		$resolver   = new Blueprint_Family_Resolver( $registry );
		$definition = $this->example_section_definition( 'extended' );
		$blueprint  = $this->example_normalized_blueprint();
		$resolved   = $resolver->resolve( $definition, $blueprint );
		$names      = array_column( $resolved[ Field_Blueprint_Schema::FIELDS ], 'name' );
		$this->assertContains( 'eyebrow', $names );
		$this->assertContains( 'headline', $names );
		$eyebrow = null;
		foreach ( $resolved[ Field_Blueprint_Schema::FIELDS ] as $f ) {
			if ( ( $f['name'] ?? '' ) === 'eyebrow' ) {
				$eyebrow = $f;
				break;
			}
		}
		$this->assertNotNull( $eyebrow );
		$this->assertArrayHasKey( 'key', $eyebrow );
		$this->assertStringContainsString( 'hero_primary_01', $eyebrow['key'] );
	}

	/**
	 * Example blueprint-family resolution payload (real structure, no pseudocode). Contract §7.
	 */
	public function test_example_blueprint_family_resolution_payload(): void {
		$registry = new Blueprint_Family_Registry();
		$registry->register_family(
			'hero_primary',
			'acf_blueprint_hero_primary',
			array(
				'default'  => array(),
				'compact'  => array( Blueprint_Family_Registry::KEY_HIDE_FIELD_NAMES => array( 'subheadline', 'cta' ) ),
				'extended' => array(
					Blueprint_Family_Registry::KEY_ADD_FIELDS => array(
						array(
							'name'  => 'eyebrow',
							'label' => 'Eyebrow',
							'type'  => 'text',
						),
					),
				),
			)
		);
		$resolver   = new Blueprint_Family_Resolver( $registry );
		$definition = array(
			Section_Schema::FIELD_INTERNAL_KEY    => 'hero_primary_01',
			'field_blueprint_ref'                 => 'acf_blueprint_hero_primary',
			'variation_family_key'                => 'hero_primary',
			Section_Schema::FIELD_DEFAULT_VARIANT => 'compact',
		);
		$normalized = $this->example_normalized_blueprint();
		$effective  = $resolver->resolve( $definition, $normalized );

		$this->assertSame( 'acf_blueprint_hero_primary', $effective['blueprint_id'] );
		$this->assertSame( 'hero_primary_01', $effective['section_key'] );
		$this->assertCount( 1, $effective['fields'] );
		$this->assertSame( 'headline', $effective['fields'][0]['name'] );
		$this->assertSame( 'field_hero_primary_01_headline', $effective['fields'][0]['key'] );
	}

	// ---------- Preview_Family_Mapping ----------

	public function test_preview_family_mapping_section_identity_and_aliases(): void {
		$mapping = new Preview_Family_Mapping();
		$this->assertSame( 'hero', $mapping->get_preview_family_for_section( 'hero', '' ) );
		$this->assertSame( 'cta', $mapping->get_preview_family_for_section( 'contact', '' ) );
		$this->assertSame( 'legal', $mapping->get_preview_family_for_section( 'policy', '' ) );
		$this->assertSame( 'other', $mapping->get_preview_family_for_section( 'utility', '' ) );
		$this->assertSame( 'other', $mapping->get_preview_family_for_section( '', '' ) );
		$this->assertSame( 'other', $mapping->get_preview_family_for_section( 'unknown_slug', '' ) );
	}

	public function test_preview_family_mapping_page(): void {
		$mapping = new Preview_Family_Mapping();
		$this->assertSame( 'other', $mapping->get_preview_family_for_page( 'top_level', 'home' ) );
		$this->assertSame( 'cta', $mapping->get_preview_family_for_page( 'top_level', 'landing' ) );
	}
}
