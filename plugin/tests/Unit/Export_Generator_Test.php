<?php
/**
 * Unit tests for export generator, packager, manifest builder, result (spec §52, Prompt 097).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Contracts\Export_Mode_Keys;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Manifest_Builder;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Result;
use AIOPageBuilder\Domain\ExportRestore\Export\Export_Zip_Packager;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Bundle_Schema.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Contracts/Export_Mode_Keys.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Export_Result.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Export_Manifest_Builder.php';
require_once $plugin_root . '/src/Infrastructure/Files/Plugin_Path_Manager.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Export/Export_Zip_Packager.php';

final class Export_Generator_Test extends TestCase {

	public function test_export_result_success_payload(): void {
		$r = Export_Result::success(
			'/path/to/export.zip',
			Export_Mode_Keys::FULL_OPERATIONAL_BACKUP,
			array( 'settings', 'profiles' ),
			array( 'raw_ai_artifacts' ),
			3,
			1024,
			'aio-export-full_operational_backup-20250715-120000-site.zip',
			'log-ref-1'
		);
		$this->assertTrue( $r->is_success() );
		$this->assertSame( '/path/to/export.zip', $r->get_package_path() );
		$this->assertSame( 3, $r->get_checksum_count() );
		$this->assertSame( 1024, $r->get_package_size_bytes() );
		$payload = $r->to_payload();
		$this->assertArrayHasKey( 'success', $payload );
		$this->assertArrayHasKey( 'included_categories', $payload );
		$this->assertArrayHasKey( 'package_filename', $payload );
		$this->assertTrue( $payload['success'] );
	}

	public function test_export_result_failure_payload(): void {
		$r = Export_Result::failure( 'Invalid mode.', 'unknown_mode', array(), array() );
		$this->assertFalse( $r->is_success() );
		$this->assertSame( '', $r->get_package_path() );
		$this->assertSame( 0, $r->get_checksum_count() );
		$this->assertSame( 'Invalid mode.', $r->get_message() );
	}

	public function test_manifest_builder_produces_required_keys(): void {
		$builder  = new Export_Manifest_Builder();
		$manifest = $builder->build(
			Export_Mode_Keys::FULL_OPERATIONAL_BACKUP,
			'https://example.com',
			array( 'settings', 'profiles' ),
			array( 'raw_ai_artifacts' ),
			array( 'settings/settings.json' => 'sha256:abc' ),
			'Full backup.',
			array(),
			''
		);
		$this->assertTrue( $builder->manifest_has_required_keys( $manifest ) );
		$this->assertSame( Export_Mode_Keys::FULL_OPERATIONAL_BACKUP, $manifest['export_type'] );
		$this->assertArrayHasKey( 'compatibility_flags', $manifest );
		$this->assertArrayHasKey( 'schema_version', $manifest['compatibility_flags'] );
	}

	public function test_zip_packager_filename_format(): void {
		$path_manager = new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager();
		$packager     = new Export_Zip_Packager( $path_manager );
		$name         = $packager->build_package_filename( Export_Mode_Keys::TEMPLATE_ONLY_EXPORT, 'my-site' );
		$this->assertStringStartsWith( 'aio-export-template_only_export-', $name );
		$this->assertStringEndsWith( '-my-site.zip', $name );
		$this->assertMatchesRegularExpression( '#^aio-export-[a-z0-9_]+-\d{8}-\d{6}-[a-zA-Z0-9_-]+\.zip$#', $name );
	}

	public function test_zip_packager_pack_creates_zip_and_checksums(): void {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'ZipArchive extension not available.' );
		}
		$staging = sys_get_temp_dir() . '/aio-export-test-' . uniqid();
		mkdir( $staging, 0755, true );
		mkdir( $staging . '/settings', 0755, true );
		file_put_contents( $staging . '/settings/settings.json', '{"foo":"bar"}' );
		$destination = sys_get_temp_dir() . '/aio-export-out-' . uniqid() . '.zip';

		$path_manager     = new \AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager();
		$packager         = new Export_Zip_Packager( $path_manager );
		$manifest_factory = function ( array $checksum_list ) {
			$m    = array(
				'export_type'           => 'full_operational_backup',
				'export_timestamp'      => gmdate( 'Y-m-d\TH:i:s\Z' ),
				'plugin_version'        => '0.1.0',
				'schema_version'        => '1',
				'source_site_url'       => 'https://example.com',
				'included_categories'   => array( 'settings' ),
				'excluded_categories'   => array(),
				'package_checksum_list' => $checksum_list,
				'restore_notes'         => '',
				'compatibility_flags'   => array(
					'schema_version'      => '1',
					'same_major_required' => true,
				),
			);
			$json = json_encode( $m );
			return $json !== false ? $json : '{}';
		};
		$result           = $packager->pack( $staging, $destination, $manifest_factory );

		$this->assertTrue( $result['success'] );
		$this->assertArrayHasKey( 'settings/settings.json', $result['checksum_list'] );
		$this->assertStringStartsWith( 'sha256:', $result['checksum_list']['settings/settings.json'] );
		$this->assertGreaterThan( 0, $result['size_bytes'] );
		$this->assertFileExists( $destination );

		$zip = new \ZipArchive();
		$this->assertTrue( $zip->open( $destination ) );
		$this->assertNotFalse( $zip->locateName( 'manifest.json' ) );
		$this->assertNotFalse( $zip->locateName( 'settings/settings.json' ) );
		$zip->close();

		unlink( $destination );
		unlink( $staging . '/settings/settings.json' );
		rmdir( $staging . '/settings' );
		rmdir( $staging );
	}

	public function test_excluded_categories_never_in_manifest_included(): void {
		$builder  = new Export_Manifest_Builder();
		$manifest = $builder->build(
			Export_Mode_Keys::SUPPORT_BUNDLE,
			'https://example.com',
			array( 'settings', 'profiles', 'registries' ),
			array( 'raw_ai_artifacts', 'api_keys', 'passwords' ),
			array(),
			'Support bundle.',
			array(),
			''
		);
		$included = $manifest['included_categories'];
		$this->assertNotContains( 'api_keys', $included );
		$this->assertNotContains( 'passwords', $included );
		$this->assertContains( 'settings', $included );
	}
}
