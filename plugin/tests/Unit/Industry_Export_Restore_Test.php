<?php
/**
 * Unit tests for industry export/restore schema and payload (Prompt 355).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Industry_Export_Restore_Schema;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Industry_Export_Restore_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';

final class Industry_Export_Restore_Test extends TestCase {

	public function test_schema_version_supported(): void {
		$this->assertTrue( Industry_Export_Restore_Schema::is_supported_version( Industry_Export_Restore_Schema::SCHEMA_VERSION ) );
		$this->assertTrue( Industry_Export_Restore_Schema::is_supported_version( '1' ) );
	}

	public function test_schema_version_unsupported(): void {
		$this->assertFalse( Industry_Export_Restore_Schema::is_supported_version( '' ) );
		$this->assertFalse( Industry_Export_Restore_Schema::is_supported_version( '2' ) );
		$this->assertFalse( Industry_Export_Restore_Schema::is_supported_version( '0' ) );
	}

	public function test_industry_export_payload_has_required_keys(): void {
		$industry_profile = array( 'primary_industry_key' => 'realtor' );
		$applied_preset   = null;
		$payload = array(
			Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION  => Industry_Export_Restore_Schema::SCHEMA_VERSION,
			Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE => $industry_profile,
			Industry_Export_Restore_Schema::KEY_APPLIED_PRESET  => $applied_preset,
		);
		$this->assertArrayHasKey( Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION, $payload );
		$this->assertArrayHasKey( Industry_Export_Restore_Schema::KEY_INDUSTRY_PROFILE, $payload );
		$this->assertArrayHasKey( Industry_Export_Restore_Schema::KEY_APPLIED_PRESET, $payload );
		$this->assertSame( '1', $payload[ Industry_Export_Restore_Schema::KEY_SCHEMA_VERSION ] );
	}

	public function test_industry_profile_normalized_on_restore(): void {
		$raw = array( 'primary_industry_key' => 'plumber', 'secondary_industry_keys' => array() );
		$normalized = Industry_Profile_Schema::normalize( $raw );
		$this->assertSame( 'plumber', $normalized[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertArrayHasKey( Industry_Profile_Schema::FIELD_SCHEMA_VERSION, $normalized );
	}
}
