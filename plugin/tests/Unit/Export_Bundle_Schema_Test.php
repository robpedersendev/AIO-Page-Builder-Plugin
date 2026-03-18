<?php
/**
 * Unit tests for Export_Bundle_Schema and Export_Mode_Keys: manifest required keys, category inclusion/exclusion (spec §52, Prompt 096).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Bundle_Schema;
use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Mode_Keys.php';

final class Export_Bundle_Schema_Test extends TestCase {

	public function test_manifest_required_keys_count(): void {
		$this->assertCount( 10, Export_Bundle_Schema::MANIFEST_REQUIRED_KEYS );
		$this->assertContains( 'export_type', Export_Bundle_Schema::MANIFEST_REQUIRED_KEYS );
		$this->assertContains( 'schema_version', Export_Bundle_Schema::MANIFEST_REQUIRED_KEYS );
		$this->assertContains( 'compatibility_flags', Export_Bundle_Schema::MANIFEST_REQUIRED_KEYS );
	}

	public function test_manifest_has_required_keys_with_full_manifest(): void {
		$manifest = $this->minimal_valid_manifest();
		$this->assertTrue( Export_Bundle_Schema::manifest_has_required_keys( $manifest ) );
	}

	public function test_manifest_has_required_keys_fails_when_key_missing(): void {
		$manifest = $this->minimal_valid_manifest();
		unset( $manifest['export_timestamp'] );
		$this->assertFalse( Export_Bundle_Schema::manifest_has_required_keys( $manifest ) );
	}

	public function test_manifest_has_required_keys_fails_for_empty_array(): void {
		$this->assertFalse( Export_Bundle_Schema::manifest_has_required_keys( array() ) );
	}

	public function test_included_categories_are_allowed_and_not_excluded(): void {
		foreach ( Export_Bundle_Schema::INCLUDED_CATEGORIES as $cat ) {
			$this->assertTrue( Export_Bundle_Schema::is_included_category( $cat ), "Included: {$cat}" );
			$this->assertTrue( Export_Bundle_Schema::is_allowed_category( $cat ), "Allowed: {$cat}" );
			$this->assertFalse( Export_Bundle_Schema::is_excluded_category( $cat ), "Not excluded: {$cat}" );
		}
	}

	public function test_optional_categories_are_allowed_and_not_excluded(): void {
		foreach ( Export_Bundle_Schema::OPTIONAL_CATEGORIES as $cat ) {
			$this->assertTrue( Export_Bundle_Schema::is_optional_category( $cat ), "Optional: {$cat}" );
			$this->assertTrue( Export_Bundle_Schema::is_allowed_category( $cat ), "Allowed: {$cat}" );
			$this->assertFalse( Export_Bundle_Schema::is_excluded_category( $cat ), "Not excluded: {$cat}" );
		}
	}

	public function test_excluded_categories_are_not_allowed(): void {
		foreach ( Export_Bundle_Schema::EXCLUDED_CATEGORIES as $cat ) {
			$this->assertTrue( Export_Bundle_Schema::is_excluded_category( $cat ), "Excluded: {$cat}" );
			$this->assertFalse( Export_Bundle_Schema::is_allowed_category( $cat ), "Not allowed: {$cat}" );
			$this->assertFalse( Export_Bundle_Schema::is_included_category( $cat ), "Not included: {$cat}" );
			$this->assertFalse( Export_Bundle_Schema::is_optional_category( $cat ), "Not optional: {$cat}" );
		}
	}

	public function test_unknown_category_is_not_included_optional_or_allowed(): void {
		$this->assertFalse( Export_Bundle_Schema::is_included_category( 'unknown_cat' ) );
		$this->assertFalse( Export_Bundle_Schema::is_optional_category( 'unknown_cat' ) );
		$this->assertFalse( Export_Bundle_Schema::is_excluded_category( 'unknown_cat' ) );
		$this->assertFalse( Export_Bundle_Schema::is_allowed_category( 'unknown_cat' ) );
	}

	public function test_export_mode_keys_all_returns_five_modes(): void {
		$all = Export_Mode_Keys::all();
		$this->assertCount( 5, $all );
		$this->assertContains( Export_Mode_Keys::FULL_OPERATIONAL_BACKUP, $all );
		$this->assertContains( Export_Mode_Keys::SUPPORT_BUNDLE, $all );
		$this->assertContains( Export_Mode_Keys::TEMPLATE_ONLY_EXPORT, $all );
	}

	public function test_export_mode_keys_is_valid(): void {
		$this->assertTrue( Export_Mode_Keys::is_valid( Export_Mode_Keys::FULL_OPERATIONAL_BACKUP ) );
		$this->assertTrue( Export_Mode_Keys::is_valid( Export_Mode_Keys::PRE_UNINSTALL_BACKUP ) );
		$this->assertFalse( Export_Mode_Keys::is_valid( 'full_backup' ) );
		$this->assertFalse( Export_Mode_Keys::is_valid( '' ) );
	}

	public function test_zip_root_dirs_include_expected_entries(): void {
		$dirs = Export_Bundle_Schema::ZIP_ROOT_DIRS;
		$this->assertContains( 'settings', $dirs );
		$this->assertContains( 'styling', $dirs );
		$this->assertContains( 'registries', $dirs );
		$this->assertContains( 'plans', $dirs );
		$this->assertContains( 'tokens', $dirs );
		$this->assertContains( 'artifacts', $dirs );
	}

	public function test_styling_is_included_category(): void {
		$this->assertTrue( Export_Bundle_Schema::is_included_category( 'styling' ) );
		$this->assertContains( 'styling', Export_Bundle_Schema::INCLUDED_CATEGORIES );
	}

	/**
	 * Minimal manifest that has all required keys (export-bundle-structure-contract.md §3).
	 *
	 * @return array<string, mixed>
	 */
	private function minimal_valid_manifest(): array {
		return array(
			'export_type'           => Export_Mode_Keys::FULL_OPERATIONAL_BACKUP,
			'export_timestamp'      => '2025-07-15T12:00:00Z',
			'plugin_version'        => '1.0.0',
			'schema_version'        => 1,
			'source_site_url'       => 'https://example.com',
			'included_categories'   => array(),
			'excluded_categories'   => array(),
			'package_checksum_list' => array(),
			'restore_notes'         => '',
			'compatibility_flags'   => array(
				'schema_version'              => 1,
				'same_major_required'         => true,
				'migration_floor'             => null,
				'max_supported_export_schema' => 1,
			),
		);
	}
}
