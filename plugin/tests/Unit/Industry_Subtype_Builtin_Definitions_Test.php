<?php
/**
 * Validation tests for built-in subtype definitions (Prompt 415).
 *
 * Verifies: seeded subtypes load; required fields present; parent_industry_key resolves via pack registry.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Subtype_Registry;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Subtype_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Subtypes/Builtin_Subtypes.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';

final class Industry_Subtype_Builtin_Definitions_Test extends TestCase {

	public function test_builtin_definitions_load_into_registry(): void {
		$registry = new Industry_Subtype_Registry();
		$registry->load( Industry_Subtype_Registry::get_builtin_definitions() );

		$expected_keys = array(
			'cosmetology_nail_luxury_salon',
			'cosmetology_nail_mobile_tech',
			'realtor_buyer_agent',
			'realtor_listing_agent',
			'plumber_residential',
			'plumber_commercial',
			'disaster_recovery_residential',
			'disaster_recovery_commercial',
		);
		foreach ( $expected_keys as $key ) {
			$def = $registry->get( $key );
			$this->assertNotNull( $def, "Subtype {$key} must be loaded" );
			$this->assertArrayHasKey( Industry_Subtype_Registry::FIELD_SUBTYPE_KEY, $def );
			$this->assertArrayHasKey( Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY, $def );
			$this->assertArrayHasKey( Industry_Subtype_Registry::FIELD_LABEL, $def );
			$this->assertArrayHasKey( Industry_Subtype_Registry::FIELD_SUMMARY, $def );
			$this->assertArrayHasKey( Industry_Subtype_Registry::FIELD_STATUS, $def );
			$this->assertSame( Industry_Subtype_Registry::STATUS_ACTIVE, $def[ Industry_Subtype_Registry::FIELD_STATUS ] ?? '' );
			$this->assertSame( '1', $def[ Industry_Subtype_Registry::FIELD_VERSION_MARKER ] ?? '' );
		}
	}

	public function test_builtin_subtype_parent_refs_resolve_via_pack_registry(): void {
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( Industry_Pack_Registry::get_builtin_pack_definitions() );

		$subtype_registry = new Industry_Subtype_Registry();
		$subtype_registry->load( Industry_Subtype_Registry::get_builtin_definitions() );

		$parents = array( 'cosmetology_nail', 'realtor', 'plumber', 'disaster_recovery' );
		foreach ( $parents as $parent ) {
			$subtypes = $subtype_registry->get_for_parent( $parent, true );
			$this->assertNotEmpty( $subtypes, "At least one active subtype for parent {$parent}" );
			foreach ( $subtypes as $def ) {
				$parent_key = $def[ Industry_Subtype_Registry::FIELD_PARENT_INDUSTRY_KEY ] ?? '';
				$this->assertSame( $parent, $parent_key );
				$pack = $pack_registry->get( $parent_key );
				$this->assertNotNull( $pack, "Parent industry {$parent_key} must exist in pack registry" );
			}
		}
	}

	public function test_no_duplicate_subtype_keys_in_builtin_definitions(): void {
		$defs = Industry_Subtype_Registry::get_builtin_definitions();
		$keys = array();
		foreach ( $defs as $def ) {
			if ( is_array( $def ) && isset( $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ] ) ) {
				$key = $def[ Industry_Subtype_Registry::FIELD_SUBTYPE_KEY ];
				$this->assertNotContains( $key, $keys, "Duplicate subtype_key: {$key}" );
				$keys[] = $key;
			}
		}
		$this->assertCount( 8, $keys, 'Expected 8 built-in subtypes' );
	}
}
