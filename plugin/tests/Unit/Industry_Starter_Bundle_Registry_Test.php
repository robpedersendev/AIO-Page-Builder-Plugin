<?php
/**
 * Unit tests for Industry_Starter_Bundle_Registry (industry-starter-bundle-schema.md, Prompt 386).
 *
 * Covers: valid bundle load/get/get_for_industry/list_all; invalid definitions skipped; validate_bundle errors; duplicate key first wins.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/StarterBundles/Builtin_Starter_Bundles.php';

final class Industry_Starter_Bundle_Registry_Test extends TestCase {

	private function valid_bundle( string $bundle_key = 'realtor_starter', string $industry_key = 'realtor' ): array {
		return array(
			Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY   => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => $industry_key,
			Industry_Starter_Bundle_Registry::FIELD_LABEL       => 'Realtor Starter',
			Industry_Starter_Bundle_Registry::FIELD_SUMMARY     => 'Curated starting set for real estate sites.',
			Industry_Starter_Bundle_Registry::FIELD_STATUS      => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
			Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => Industry_Starter_Bundle_Registry::SUPPORTED_SCHEMA_VERSION,
		);
	}

	public function test_registry_loads_valid_bundle_and_get_returns_it(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $this->valid_bundle( 'realtor_starter' ) ) );
		$bundle = $registry->get( 'realtor_starter' );
		$this->assertNotNull( $bundle );
		$this->assertSame( 'realtor_starter', $bundle[ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] );
		$this->assertSame( 'realtor', $bundle[ Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY ] );
		$this->assertSame( Industry_Starter_Bundle_Registry::STATUS_ACTIVE, $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] );
	}

	public function test_get_for_industry_returns_matching_bundles(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array(
			$this->valid_bundle( 'realtor_a', 'realtor' ),
			$this->valid_bundle( 'realtor_b', 'realtor' ),
			$this->valid_bundle( 'plumber_essentials', 'plumber' ),
		) );
		$realtor = $registry->get_for_industry( 'realtor' );
		$this->assertCount( 2, $realtor );
		$this->assertCount( 1, $registry->get_for_industry( 'plumber' ) );
		$this->assertCount( 0, $registry->get_for_industry( 'unknown' ) );
	}

	public function test_list_all_returns_all_loaded_bundles(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array(
			$this->valid_bundle( 'b1', 'realtor' ),
			$this->valid_bundle( 'b2', 'plumber' ),
		) );
		$all = $registry->list_all();
		$this->assertCount( 2, $all );
	}

	public function test_invalid_bundle_missing_required_skipped(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$bad = $this->valid_bundle( 'bad' );
		unset( $bad[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] );
		$registry->load( array( $bad ) );
		$this->assertNull( $registry->get( 'bad' ) );
		$this->assertNotEmpty( $registry->validate_bundle( $bad ) );
		$this->assertContains( 'missing_label', $registry->validate_bundle( $bad ) );
	}

	public function test_invalid_status_skipped(): void {
		$bad = $this->valid_bundle( 'bad_status' );
		$bad[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] = 'invalid';
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $bad ) );
		$this->assertNull( $registry->get( 'bad_status' ) );
		$this->assertContains( 'invalid_status', $registry->validate_bundle( $bad ) );
	}

	public function test_unsupported_version_skipped(): void {
		$bad = $this->valid_bundle( 'v2_bundle' );
		$bad[ Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER ] = '2';
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $bad ) );
		$this->assertNull( $registry->get( 'v2_bundle' ) );
		$this->assertContains( 'unsupported_version', $registry->validate_bundle( $bad ) );
	}

	public function test_duplicate_key_first_wins(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$first  = $this->valid_bundle( 'dup' );
		$first[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] = 'First';
		$second = $this->valid_bundle( 'dup' );
		$second[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] = 'Second';
		$registry->load( array( $first, $second ) );
		$bundle = $registry->get( 'dup' );
		$this->assertNotNull( $bundle );
		$this->assertSame( 'First', $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] );
		$this->assertCount( 1, $registry->list_all() );
	}

	public function test_valid_bundle_with_optional_refs_normalized(): void {
		$bundle = $this->valid_bundle( 'full_bundle', 'plumber' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES ] = array( 'home', 'services' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] = array( 'pt_home', 'pt_services' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] = array( 'hero_01', 'cta_01' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF ] = 'plumber_trust';
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $bundle ) );
		$loaded = $registry->get( 'full_bundle' );
		$this->assertNotNull( $loaded );
		$this->assertSame( array( 'home', 'services' ), $loaded[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_FAMILIES ] );
		$this->assertSame( array( 'pt_home', 'pt_services' ), $loaded[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_PAGE_TEMPLATE_REFS ] );
		$this->assertSame( array( 'hero_01', 'cta_01' ), $loaded[ Industry_Starter_Bundle_Registry::FIELD_RECOMMENDED_SECTION_REFS ] );
		$this->assertSame( 'plumber_trust', $loaded[ Industry_Starter_Bundle_Registry::FIELD_TOKEN_PRESET_REF ] );
	}

	public function test_subtype_scoped_bundle_loads_and_validates(): void {
		$bundle = $this->valid_bundle( 'realtor_buyer_starter', 'realtor' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] = 'realtor_buyer_agent';
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array( $bundle ) );
		$loaded = $registry->get( 'realtor_buyer_starter' );
		$this->assertNotNull( $loaded );
		$this->assertSame( 'realtor_buyer_agent', $loaded[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] );
		$this->assertEmpty( $registry->validate_bundle( $bundle ) );
	}

	public function test_get_for_industry_with_subtype_returns_subtype_bundles_when_present(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$industry_only = $this->valid_bundle( 'realtor_starter', 'realtor' );
		$subtype_bundle = $this->valid_bundle( 'realtor_buyer_starter', 'realtor' );
		$subtype_bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] = 'realtor_buyer_agent';
		$registry->load( array( $industry_only, $subtype_bundle ) );
		$for_subtype = $registry->get_for_industry( 'realtor', 'realtor_buyer_agent' );
		$this->assertCount( 1, $for_subtype );
		$this->assertSame( 'realtor_buyer_starter', $for_subtype[0][ Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY ] );
		$this->assertSame( 'realtor_buyer_agent', $for_subtype[0][ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] );
	}

	public function test_get_for_industry_with_subtype_falls_back_to_industry_bundles_when_no_subtype(): void {
		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( array(
			$this->valid_bundle( 'realtor_starter', 'realtor' ),
			$this->valid_bundle( 'realtor_second', 'realtor' ),
		) );
		$for_subtype = $registry->get_for_industry( 'realtor', 'realtor_buyer_agent' );
		$this->assertCount( 2, $for_subtype );
		$for_empty = $registry->get_for_industry( 'realtor', '' );
		$this->assertCount( 2, $for_empty );
	}

	public function test_invalid_subtype_key_rejected(): void {
		$bundle = $this->valid_bundle( 'bad_subtype', 'realtor' );
		$bundle[ Industry_Starter_Bundle_Registry::FIELD_SUBTYPE_KEY ] = 'invalid key!';
		$errors = ( new Industry_Starter_Bundle_Registry() )->validate_bundle( $bundle );
		$this->assertContains( 'invalid_subtype_key', $errors );
	}

	/** Prompt 387/429: builtin starter bundles (industry + subtype) load and validate. */
	public function test_builtin_definitions_load_and_validate(): void {
		$definitions = Industry_Starter_Bundle_Registry::get_builtin_definitions();
		$this->assertGreaterThanOrEqual( 4, count( $definitions ), 'At least four industry bundles plus optional subtype bundles (Prompt 429).' );

		$registry = new Industry_Starter_Bundle_Registry();
		$registry->load( $definitions );

		$expected_industry_bundles = array( 'cosmetology_nail_starter', 'realtor_starter', 'plumber_starter', 'disaster_recovery_starter' );
		$expected_industries = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );

		foreach ( $expected_industry_bundles as $key ) {
			$bundle = $registry->get( $key );
			$this->assertNotNull( $bundle, "Builtin industry bundle {$key} must load." );
			$this->assertSame( Industry_Starter_Bundle_Registry::STATUS_ACTIVE, $bundle[ Industry_Starter_Bundle_Registry::FIELD_STATUS ] );
			$this->assertSame( '1', $bundle[ Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER ] );
			$this->assertNotEmpty( $bundle[ Industry_Starter_Bundle_Registry::FIELD_LABEL ] );
			$this->assertNotEmpty( $bundle[ Industry_Starter_Bundle_Registry::FIELD_SUMMARY ] );
		}

		foreach ( $expected_industries as $industry_key ) {
			$for_industry = $registry->get_for_industry( $industry_key );
			$this->assertCount( 1, $for_industry, "Exactly one industry-scoped bundle for {$industry_key} when no subtype requested." );
		}

		$for_subtype = $registry->get_for_industry( 'realtor', 'realtor_buyer_agent' );
		$this->assertGreaterThanOrEqual( 1, count( $for_subtype ), 'At least one subtype bundle for realtor_buyer_agent when seeded (Prompt 429).' );
	}
}
