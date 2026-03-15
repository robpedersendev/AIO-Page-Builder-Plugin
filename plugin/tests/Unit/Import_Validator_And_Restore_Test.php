<?php
/**
 * Unit tests for import validator, conflict resolution, restore result (spec §52.7–52.10, Prompt 098).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Import\Conflict_Resolution_Service;
use AIOPageBuilder\Domain\ExportRestore\Import\Import_Validation_Result;
use AIOPageBuilder\Domain\ExportRestore\Import\Import_Validator;
use AIOPageBuilder\Domain\ExportRestore\Import\Restore_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Import/Import_Validation_Result.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Import/Restore_Result.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Import/Conflict_Resolution_Service.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Import/Import_Validator.php';
require_once $plugin_root . '/src/Domain/Styling/Global_Style_Settings_Schema.php';
require_once $plugin_root . '/src/Domain/Styling/Entity_Style_Payload_Schema.php';

final class Import_Validator_And_Restore_Test extends TestCase {

	public function test_validate_missing_file_returns_blocking_failure(): void {
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( __DIR__ . '/nonexistent.zip' );
		$this->assertFalse( $result->validation_passed() );
		$this->assertNotEmpty( $result->get_blocking_failures() );
		$this->assertStringContainsString( 'missing', strtolower( implode( ' ', $result->get_blocking_failures() ) ) );
	}

	public function test_validate_invalid_zip_returns_blocking_failure(): void {
		$tmp = sys_get_temp_dir() . '/aio-invalid-' . uniqid() . '.zip';
		file_put_contents( $tmp, 'not a zip' );
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertFalse( $result->validation_passed() );
		$this->assertNotEmpty( $result->get_blocking_failures() );
	}

	public function test_validate_zip_without_manifest_returns_blocking_failure(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive not available.' );
		}
		$tmp = sys_get_temp_dir() . '/aio-no-manifest-' . uniqid() . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		$zip->addFromString( 'settings/settings.json', '{}' );
		$zip->close();
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertFalse( $result->validation_passed() );
		$this->assertNotEmpty( $result->get_blocking_failures() );
	}

	public function test_validate_zip_with_valid_manifest_same_version_passes_structure(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive not available.' );
		}
		$manifest = $this->minimal_manifest( \AIOPageBuilder\Infrastructure\Config\Versions::export_schema() );
		$tmp = sys_get_temp_dir() . '/aio-valid-' . uniqid() . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
		$zip->close();
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertTrue( $result->validation_passed() );
		$this->assertEmpty( $result->get_blocking_failures() );
		$this->assertSame( $manifest['schema_version'], $result->get_manifest()['schema_version'] );
	}

	public function test_validation_result_to_payload(): void {
		$r = new Import_Validation_Result(
			true,
			array(),
			array( array( 'category' => 'registries', 'key' => 'st01', 'message' => 'Exists.' ) ),
			array( 'Checksum warning.' ),
			array( 'export_type' => 'full_operational_backup' ),
			'/path/to.zip',
			false
		);
		$p = $r->to_payload();
		$this->assertTrue( $p['validation_passed'] );
		$this->assertCount( 1, $p['conflicts'] );
		$this->assertCount( 1, $p['warnings'] );
		$this->assertFalse( $p['checksum_verified'] );
	}

	public function test_conflict_resolution_cancel_returns_cancelled(): void {
		$svc = new Conflict_Resolution_Service();
		$conflicts = array( array( 'category' => 'registries', 'key' => 'st01', 'message' => 'Exists.' ) );
		$out = $svc->resolve( $conflicts, Conflict_Resolution_Service::MODE_CANCEL );
		$this->assertTrue( $out['cancelled'] );
		$this->assertEmpty( $out['resolved'] );
	}

	public function test_conflict_resolution_overwrite_returns_overwrite_actions(): void {
		$svc = new Conflict_Resolution_Service();
		$conflicts = array(
			array( 'category' => 'registries', 'key' => 'st01', 'message' => 'Exists.' ),
			array( 'category' => 'plans', 'key' => 'plan-1', 'message' => 'Exists.' ),
		);
		$out = $svc->resolve( $conflicts, Conflict_Resolution_Service::MODE_OVERWRITE );
		$this->assertFalse( $out['cancelled'] );
		$this->assertCount( 2, $out['resolved'] );
		$this->assertSame( Conflict_Resolution_Service::ACTION_OVERWRITE, $out['resolved'][0]['action'] );
	}

	public function test_conflict_resolution_keep_current(): void {
		$svc = new Conflict_Resolution_Service();
		$conflicts = array( array( 'category' => 'registries', 'key' => 'st01', 'message' => 'Exists.' ) );
		$out = $svc->resolve( $conflicts, Conflict_Resolution_Service::MODE_KEEP_CURRENT );
		$this->assertFalse( $out['cancelled'] );
		$this->assertSame( Conflict_Resolution_Service::ACTION_KEEP_CURRENT, $out['resolved'][0]['action'] );
	}

	public function test_restore_result_success_payload(): void {
		$r = Restore_Result::success(
			array( 'settings', 'profiles' ),
			array( array( 'category' => 'settings', 'action' => 'overwrite' ) ),
			'restore-2025-07-15T12:00:00Z'
		);
		$this->assertTrue( $r->is_success() );
		$this->assertCount( 2, $r->get_restored_categories() );
		$p = $r->to_payload();
		$this->assertTrue( $p['success'] );
		$this->assertSame( 'restore-2025-07-15T12:00:00Z', $p['log_reference'] );
	}

	public function test_restore_result_failure_blocked(): void {
		$r = Restore_Result::failure( 'Validation failed.', array( 'Manifest missing.' ), false, '' );
		$this->assertFalse( $r->is_success() );
		$this->assertCount( 1, $r->get_blocking_failures() );
		$this->assertFalse( $r->validation_passed() );
	}

	public function test_is_valid_mode(): void {
		$this->assertTrue( Conflict_Resolution_Service::is_valid_mode( Conflict_Resolution_Service::MODE_OVERWRITE ) );
		$this->assertTrue( Conflict_Resolution_Service::is_valid_mode( Conflict_Resolution_Service::MODE_CANCEL ) );
		$this->assertFalse( Conflict_Resolution_Service::is_valid_mode( 'invalid' ) );
	}

	public function test_validate_newer_schema_version_blocked(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive not available.' );
		}
		$manifest = $this->minimal_manifest( '2' );
		$tmp = sys_get_temp_dir() . '/aio-newer-' . uniqid() . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
		$zip->close();
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertFalse( $result->validation_passed() );
		$failures = implode( ' ', $result->get_blocking_failures() );
		$this->assertStringContainsString( 'newer', strtolower( $failures ) );
	}

	public function test_validate_styling_unsupported_global_version_blocked(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive not available.' );
		}
		$manifest = $this->minimal_manifest( \AIOPageBuilder\Infrastructure\Config\Versions::export_schema() );
		$manifest['included_categories'] = array( 'settings', 'styling' );
		$global_settings = array( 'version' => '2', 'global_tokens' => array(), 'global_component_overrides' => array() );
		$tmp = sys_get_temp_dir() . '/aio-styling-unsup-' . uniqid() . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
		$zip->addFromString( 'styling/global_settings.json', wp_json_encode( $global_settings ) );
		$zip->close();
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertFalse( $result->validation_passed() );
		$failures = implode( ' ', $result->get_blocking_failures() );
		$this->assertStringContainsString( 'Unsupported global styling schema version', $failures );
	}

	public function test_validate_prohibited_path_rejected(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive not available.' );
		}
		$manifest = $this->minimal_manifest( '1' );
		$tmp = sys_get_temp_dir() . '/aio-prohibited-' . uniqid() . '.zip';
		$zip = new \ZipArchive();
		$zip->open( $tmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
		$zip->addFromString( '../evil.txt', 'no' );
		$zip->close();
		$validator = new Import_Validator( null, null, null, null, null );
		$result = $validator->validate( $tmp );
		unlink( $tmp );
		$this->assertFalse( $result->validation_passed() );
		$this->assertStringContainsString( 'Prohibited', implode( ' ', $result->get_blocking_failures() ) );
	}

	/**
	 * @param string $schema_version
	 * @return array<string, mixed>
	 */
	private function minimal_manifest( string $schema_version = '1' ): array {
		return array(
			'export_type'            => 'full_operational_backup',
			'export_timestamp'       => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'plugin_version'         => \AIOPageBuilder\Infrastructure\Config\Versions::plugin(),
			'schema_version'         => $schema_version,
			'source_site_url'        => 'https://example.com',
			'included_categories'    => array( 'settings' ),
			'excluded_categories'    => array(),
			'package_checksum_list'  => array(),
			'restore_notes'          => '',
			'compatibility_flags'    => array(
				'schema_version'          => $schema_version,
				'same_major_required'     => true,
				'migration_floor'         => $schema_version,
				'max_supported_export_schema' => $schema_version,
			),
		);
	}
}
