<?php
/**
 * Unit tests for Industry_Health_Check_Service: missing refs, profile/bundle validation, empty state (Prompt 390).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Validator;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Style_Preset_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Style_Preset_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';

final class Industry_Health_Check_Service_Test extends TestCase {

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_run_returns_empty_errors_and_warnings_when_all_null(): void {
		$service = new Industry_Health_Check_Service( null, null, null, null, null, null, null, null, null, null, null );
		$result = $service->run();
		$this->assertArrayHasKey( 'errors', $result );
		$this->assertArrayHasKey( 'warnings', $result );
		$this->assertIsArray( $result['errors'] );
		$this->assertIsArray( $result['warnings'] );
		$this->assertCount( 0, $result['errors'] );
		$this->assertCount( 0, $result['warnings'] );
	}

	public function test_run_detects_profile_primary_pack_not_found(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'nonexistent_pack',
		) );
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array() );
		$service = new Industry_Health_Check_Service( $profile_repo, $pack_registry, null, null, null, null, null, null, null, null, null );
		$result = $service->run();
		$this->assertCount( 1, $result['errors'] );
		$this->assertSame( Industry_Health_Check_Service::OBJECT_TYPE_PROFILE, $result['errors'][0]['object_type'] );
		$this->assertSame( 'primary_industry_key', $result['errors'][0]['key'] );
		$this->assertStringContainsString( 'not found', $result['errors'][0]['issue_summary'] );
		$this->assertContains( 'nonexistent_pack', $result['errors'][0]['related_refs'] );
	}

	public function test_run_detects_pack_token_preset_ref_missing(): void {
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'test_industry',
				Industry_Pack_Schema::FIELD_NAME           => 'Test',
				Industry_Pack_Schema::FIELD_SUMMARY        => 'Test pack',
				Industry_Pack_Schema::FIELD_STATUS         => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
				Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF => 'nonexistent_preset',
			),
		) );
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array() );
		$service = new Industry_Health_Check_Service( null, $pack_registry, null, null, null, $preset_registry, null, null, null, null, null );
		$result = $service->run();
		$this->assertCount( 1, $result['errors'] );
		$this->assertSame( Industry_Health_Check_Service::OBJECT_TYPE_PACK, $result['errors'][0]['object_type'] );
		$this->assertSame( 'test_industry', $result['errors'][0]['key'] );
		$this->assertStringContainsString( 'token_preset_ref', $result['errors'][0]['issue_summary'] );
		$this->assertContains( 'nonexistent_preset', $result['errors'][0]['related_refs'] );
	}

	public function test_run_detects_profile_selected_starter_bundle_not_found(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$profile_repo->set_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'nonexistent_bundle',
		) );
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'realtor',
				Industry_Pack_Schema::FIELD_NAME           => 'Realtor',
				Industry_Pack_Schema::FIELD_SUMMARY        => 'Realtor pack',
				Industry_Pack_Schema::FIELD_STATUS         => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
			),
		) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );
		$service = new Industry_Health_Check_Service( $profile_repo, $pack_registry, null, null, null, null, null, null, null, $bundle_registry, null );
		$result = $service->run();
		$this->assertCount( 1, $result['errors'] );
		$this->assertSame( Industry_Health_Check_Service::OBJECT_TYPE_PROFILE, $result['errors'][0]['object_type'] );
		$this->assertSame( 'selected_starter_bundle_key', $result['errors'][0]['key'] );
		$this->assertStringContainsString( 'starter bundle not found', $result['errors'][0]['issue_summary'] );
	}

	public function test_run_healthy_when_refs_resolve(): void {
		$preset_registry = new Industry_Style_Preset_Registry();
		$preset_registry->load( array(
			array(
				Industry_Style_Preset_Registry::FIELD_STYLE_PRESET_KEY => 'realtor_light',
				Industry_Style_Preset_Registry::FIELD_LABEL            => 'Realtor Light',
				Industry_Style_Preset_Registry::FIELD_VERSION_MARKER   => '1',
				Industry_Style_Preset_Registry::FIELD_STATUS           => Industry_Style_Preset_Registry::STATUS_ACTIVE,
				Industry_Style_Preset_Registry::FIELD_INDUSTRY_KEY     => 'realtor',
			),
		) );
		$pack_registry = new Industry_Pack_Registry( new Industry_Pack_Validator() );
		$pack_registry->load( array(
			array(
				Industry_Pack_Schema::FIELD_INDUSTRY_KEY   => 'realtor',
				Industry_Pack_Schema::FIELD_NAME           => 'Realtor',
				Industry_Pack_Schema::FIELD_SUMMARY        => 'Realtor pack',
				Industry_Pack_Schema::FIELD_STATUS         => Industry_Pack_Schema::STATUS_ACTIVE,
				Industry_Pack_Schema::FIELD_VERSION_MARKER => Industry_Pack_Schema::SUPPORTED_SCHEMA_VERSION,
				Industry_Pack_Schema::FIELD_TOKEN_PRESET_REF => 'realtor_light',
			),
		) );
		$service = new Industry_Health_Check_Service( null, $pack_registry, null, null, null, $preset_registry, null, null, null, null, null );
		$result = $service->run();
		$this->assertCount( 0, $result['errors'] );
	}
}
