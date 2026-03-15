<?php
/**
 * Unit tests for Industry_Profile_Schema: empty profile, normalize, version handling (industry-profile-schema.md; Prompt 321).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';

final class Industry_Profile_Schema_Test extends TestCase {

	public function test_get_empty_profile_has_required_keys(): void {
		$empty = Industry_Profile_Schema::get_empty_profile();
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_SCHEMA_VERSION, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_SUBTYPE, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_SERVICE_MODEL, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_GEO_MODEL, $empty );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_DERIVED_FLAGS, $empty );
		$this->assertSame( Industry_Profile_Schema::SUPPORTED_SCHEMA_VERSION, $empty[ Industry_Profile_Schema::FIELD_SCHEMA_VERSION ] );
		$this->assertSame( '', $empty[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array(), $empty[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
		$this->assertIsArray( $empty[ Industry_Profile_Schema::FIELD_DERIVED_FLAGS ] );
	}

	public function test_is_supported_version_accepts_1_rejects_other(): void {
		$this->assertTrue( Industry_Profile_Schema::is_supported_version( '1' ) );
		$this->assertTrue( Industry_Profile_Schema::is_supported_version( Industry_Profile_Schema::SUPPORTED_SCHEMA_VERSION ) );
		$this->assertFalse( Industry_Profile_Schema::is_supported_version( '2' ) );
		$this->assertFalse( Industry_Profile_Schema::is_supported_version( '' ) );
	}

	public function test_normalize_returns_empty_profile_for_non_array(): void {
		$this->assertEquals( Industry_Profile_Schema::get_empty_profile(), Industry_Profile_Schema::normalize( null ) );
		$this->assertEquals( Industry_Profile_Schema::get_empty_profile(), Industry_Profile_Schema::normalize( 'string' ) );
	}

	public function test_normalize_returns_empty_profile_for_unsupported_version(): void {
		$raw = array( Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '2' );
		$this->assertEquals( Industry_Profile_Schema::get_empty_profile(), Industry_Profile_Schema::normalize( $raw ) );
	}

	public function test_normalize_preserves_valid_primary_and_secondary(): void {
		$raw = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION        => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY   => 'legal',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'healthcare', 'real_estate' ),
			Industry_Profile_Schema::FIELD_SUBTYPE                => 'plumber',
			Industry_Profile_Schema::FIELD_SERVICE_MODEL          => 'b2c',
			Industry_Profile_Schema::FIELD_GEO_MODEL              => 'local',
		);
		$out = Industry_Profile_Schema::normalize( $raw );
		$this->assertSame( '1', $out[ Industry_Profile_Schema::FIELD_SCHEMA_VERSION ] );
		$this->assertSame( 'legal', $out[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array( 'healthcare', 'real_estate' ), $out[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
		$this->assertSame( 'plumber', $out[ Industry_Profile_Schema::FIELD_SUBTYPE ] );
		$this->assertSame( 'b2c', $out[ Industry_Profile_Schema::FIELD_SERVICE_MODEL ] );
		$this->assertSame( 'local', $out[ Industry_Profile_Schema::FIELD_GEO_MODEL ] );
	}

	public function test_normalize_deduplicates_and_filters_secondary_keys(): void {
		$raw = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION        => '1',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'a', 'b', 'a', 1, '', '  c  ' ),
		);
		$out = Industry_Profile_Schema::normalize( $raw );
		$this->assertSame( array( 'a', 'b', 'c' ), $out[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
	}
}
