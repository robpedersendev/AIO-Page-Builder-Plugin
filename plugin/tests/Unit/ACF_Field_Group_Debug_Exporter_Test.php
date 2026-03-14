<?php
/**
 * Unit tests for ACF field group debug exporter (Prompt 224).
 * Covers: debug export generation, registry-to-mirror diff summary, version mismatch.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Blueprints\Field_Blueprint_Schema;
use AIOPageBuilder\Domain\ACF\Blueprints\Section_Field_Blueprint_Service_Interface;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Field_Group_Debug_Exporter;
use AIOPageBuilder\Domain\ACF\Debug\ACF_Local_JSON_Mirror_Service;
use AIOPageBuilder\Domain\ACF\Registration\ACF_Field_Builder;
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
require_once $plugin_root . '/src/Domain/ACF/Debug/ACF_Field_Group_Debug_Exporter.php';

final class ACF_Field_Group_Debug_Exporter_Test extends TestCase {

	private function blueprint_st01(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st01_hero',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'Hero Fields',
			Field_Blueprint_Schema::FIELDS          => array(
				array( 'key' => 'field_st01_hero_headline', 'name' => 'headline', 'label' => 'Headline', 'type' => 'text' ),
			),
		);
	}

	private function blueprint_st05(): array {
		return array(
			Field_Blueprint_Schema::SECTION_KEY     => 'st05_faq',
			Field_Blueprint_Schema::SECTION_VERSION => '1',
			Field_Blueprint_Schema::LABEL           => 'FAQ Fields',
			Field_Blueprint_Schema::FIELDS          => array(),
		);
	}

	private function create_blueprint_service_mock( array $blueprints ): Section_Field_Blueprint_Service_Interface {
		$mock = $this->createMock( Section_Field_Blueprint_Service_Interface::class );
		$mock->method( 'get_all_blueprints' )->willReturn( $blueprints );
		return $mock;
	}

	public function test_build_debug_export_returns_record_per_blueprint(): void {
		$blueprints = array( $this->blueprint_st01(), $this->blueprint_st05() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$mirror_service = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$exporter = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$records = $exporter->build_debug_export();

		$this->assertCount( 2, $records );
		$st01 = null;
		$st05 = null;
		foreach ( $records as $r ) {
			if ( ( $r['section_key'] ?? '' ) === 'st01_hero' ) {
				$st01 = $r;
			}
			if ( ( $r['section_key'] ?? '' ) === 'st05_faq' ) {
				$st05 = $r;
			}
		}
		$this->assertNotNull( $st01 );
		$this->assertSame( 'group_aio_st01_hero', $st01['group_key'] ?? '' );
		$this->assertSame( 'registry', $st01['source'] ?? '' );
		$this->assertSame( 1, $st01['field_count'] ?? 0 );
		$this->assertSame( 'section_key:st01_hero', $st01['registry_reference'] ?? '' );
		$this->assertNotNull( $st05 );
		$this->assertSame( 0, $st05['field_count'] ?? -1 );
	}

	public function test_build_diff_summary_detects_in_registry_not_mirror(): void {
		$blueprints = array( $this->blueprint_st01(), $this->blueprint_st05() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$mirror_service = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$exporter = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$registry_manifest = $mirror_service->get_manifest_without_writing();
		$mirror_manifest = array(
			'group_keys' => array( 'group_aio_st01_hero' ),
			'files'     => array(
				array( 'group_key' => 'group_aio_st01_hero', 'section_version' => '1' ),
			),
		);

		$diff = $exporter->build_diff_summary( $registry_manifest, $mirror_manifest );

		$this->assertArrayHasKey( 'in_registry_not_mirror', $diff );
		$this->assertContains( 'group_aio_st05_faq', $diff['in_registry_not_mirror'] );
		$this->assertArrayHasKey( 'in_mirror_not_registry', $diff );
		$this->assertArrayHasKey( 'version_mismatch', $diff );
		$this->assertArrayHasKey( 'summary', $diff );
		$this->assertStringContainsString( '1 only in registry', $diff['summary'] );
	}

	public function test_build_diff_summary_detects_version_mismatch(): void {
		$blueprints = array( $this->blueprint_st01() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$mirror_service = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$exporter = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$registry_manifest = $mirror_service->get_manifest_without_writing();
		$mirror_manifest = array(
			'group_keys' => array( 'group_aio_st01_hero' ),
			'files'      => array(
				array( 'group_key' => 'group_aio_st01_hero', 'section_version' => '0' ),
			),
		);

		$diff = $exporter->build_diff_summary( $registry_manifest, $mirror_manifest );

		$this->assertCount( 1, $diff['version_mismatch'] );
		$this->assertSame( 'group_aio_st01_hero', $diff['version_mismatch'][0]['group_key'] );
		$this->assertSame( '1', $diff['version_mismatch'][0]['registry_version'] );
		$this->assertSame( '0', $diff['version_mismatch'][0]['mirror_version'] );
		$this->assertStringContainsString( 'version mismatch', $diff['summary'] );
	}

	public function test_build_diff_summary_in_sync_when_manifests_match(): void {
		$blueprints = array( $this->blueprint_st01() );
		$blueprint_service = $this->create_blueprint_service_mock( $blueprints );
		$group_builder = new ACF_Group_Builder( new ACF_Field_Builder() );
		$mirror_service = new ACF_Local_JSON_Mirror_Service( $blueprint_service, $group_builder );
		$exporter = new ACF_Field_Group_Debug_Exporter( $blueprint_service, $mirror_service );

		$registry_manifest = $mirror_service->get_manifest_without_writing();
		$diff = $exporter->build_diff_summary( $registry_manifest, $registry_manifest );

		$this->assertEmpty( $diff['in_registry_not_mirror'] );
		$this->assertEmpty( $diff['in_mirror_not_registry'] );
		$this->assertEmpty( $diff['version_mismatch'] );
		$this->assertStringContainsString( '1 in sync', $diff['summary'] );
	}
}
