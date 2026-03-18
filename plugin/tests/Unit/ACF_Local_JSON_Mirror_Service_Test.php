<?php
/**
 * Unit tests for ACF local JSON mirror (Prompt 224).
 * Covers: deterministic mirror generation, manifest version markers, get_manifest_without_writing.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Field_Key_Generator;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Group_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Constants.php';
\AIOPageBuilder\Bootstrap\Constants::init();
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Blueprint_Schema.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Field_Key_Generator.php';
require_once $plugin_root . '/src/Domain/ACF/Blueprints/Section_Field_Blueprint_Service_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Config/Versions.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Field_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Registration/ACF_Group_Builder.php';
require_once $plugin_root . '/src/Domain/ACF/Debug/ACF_Local_JSON_Mirror_Service.php';

final class ACF_Local_JSON_Mirror_Service_Test extends TestCase {

	private function blueprint_st01(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
			Field_Blueprint_Schema::FIELDS          => array(
				array(
					'key'   => 'field_st01_hero_headline',
					'name'  => 'headline',
					'label' => 'Headline',
					'type'  => 'text',
				),
			),
		);
	}

	private function blueprint_st05(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st05_faq',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'FAQ Fields',
			Field_Blueprint_Schema::FIELDS          => array(
				array(
					'key'   => 'field_st05_faq_question',
					'name'  => 'question',
					'label' => 'Question',
					'type'  => 'text',
				),
			),
		);
	}

	private function create_blueprint_service_mock( array $blueprints ): Section_Field_Blueprint_Service_Interface {
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_all_blueprints' )->willReturn( $blueprints );
		return $mock;
	}

	public function test_get_manifest_without_writing_returns_manifest_with_version_markers(): void {
		$blueprints        = array( $this->blueprint_st01(), $this->blueprint_st05() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder     = new ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$service           = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );

		$manifest = $service->get_manifest_without_writing();

		$this->assertSame( ACF_Local_JSON_Mirror_Service::MANIFEST_SCHEMA_VERSION, $manifest['schema_version'] ?? '' );
		$this->assertSame( '1', $manifest['mirror_version'] ?? '' );
		$this->assertArrayHasKey( 'generated_at', $manifest );
		$this->assertArrayHasKey( 'plugin_version', $manifest );
		$this->assertSame( 'registry', $manifest['source'] ?? '' );
		$this->assertArrayHasKey( 'label', $manifest );
		$this->assertStringContainsString( 'internal', strtolower( $manifest['label'] ?? '' ) );
		$this->assertFalse( $manifest['error'] ?? false, 'Manifest must not indicate error when generation succeeds' );
	}

	public function test_get_manifest_without_writing_includes_deterministic_group_keys(): void {
		$blueprints        = array( $this->blueprint_st01(), $this->blueprint_st05() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder     = new ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$service           = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );

		$manifest = $service->get_manifest_without_writing();

		$this->assertContains( 'group_aio_st01_hero', $manifest['group_keys'] ?? array() );
		$this->assertContains( 'group_aio_st05_faq', $manifest['group_keys'] ?? array() );
		$this->assertCount( 2, $manifest['group_keys'] ?? array() );
		$files = $manifest['files'] ?? array();
		$this->assertCount( 2, $files );
		$group_keys_from_files = array_column( $files, 'group_key' );
		$this->assertContains( 'group_aio_st01_hero', $group_keys_from_files );
		$this->assertContains( 'group_aio_st05_faq', $group_keys_from_files );
	}

	public function test_generate_mirror_to_directory_writes_files_and_returns_manifest(): void {
		$blueprints        = array( $this->blueprint_st01() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder     = new ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$service           = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );

		$target_dir = sys_get_temp_dir() . '/aio-mirror-test-' . uniqid( '', true );
		$this->assertTrue( mkdir( $target_dir, 0755, true ), 'Temp mirror dir must be created' );

		try {
			$manifest = $service->generate_mirror_to_directory( $target_dir );
			$this->assertFalse( $manifest['error'] ?? false );
			$this->assertSame( '1', $manifest['schema_version'] ?? '' );
			$this->assertSame( 'registry', $manifest['source'] ?? '' );
			$this->assertCount( 1, $manifest['group_keys'] ?? array() );
			$this->assertSame( 'group_aio_st01_hero', ( $manifest['group_keys'] ?? array() )[0] );

			$expected_file = $target_dir . '/group_aio_st01_hero.json';
			$this->assertFileExists( $expected_file );
			$content = file_get_contents( $expected_file );
			$this->assertNotFalse( $content );
			$decoded = json_decode( $content, true );
			$this->assertIsArray( $decoded );
			$this->assertSame( 'group_aio_st01_hero', $decoded['key'] ?? '' );
			$this->assertArrayHasKey( '_aio_section_key', $decoded );
			$this->assertSame( 'st01_hero', $decoded['_aio_section_key'] ?? '' );
		} finally {
			if ( is_dir( $target_dir ) ) {
				$f = $target_dir . '/group_aio_st01_hero.json';
				if ( file_exists( $f ) ) {
					unlink( $f );
				}
				rmdir( $target_dir );
			}
		}
	}

	public function test_deterministic_mirror_same_blueprints_same_group_keys(): void {
		$blueprints        = array( $this->blueprint_st01(), $this->blueprint_st05() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder     = new ACF_Group_Builder( new \AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder() );
		$service           = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );

		$manifest1 = $service->get_manifest_without_writing();
		$manifest2 = $service->get_manifest_without_writing();

		$sorted1 = $manifest1['group_keys'] ?? array();
		$sorted2 = $manifest2['group_keys'] ?? array();
		sort( $sorted1 );
		sort( $sorted2 );
		$this->assertSame( $sorted1, $sorted2 );
		$this->assertSame( array( 'group_aio_st01_hero', 'group_aio_st05_faq' ), $sorted1 );
	}
}
