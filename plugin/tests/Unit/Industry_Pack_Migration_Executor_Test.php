<?php
/**
 * Unit tests for Industry_Pack_Migration_Executor (Prompt 412).
 *
 * Covers: valid migration updates profile; invalid/partial replacement fails; no matching refs skips safely; run_migration_to_replacement uses replacement_ref.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Executor;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Pack_Migration_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Pack_Migration_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Pack_Migration_Executor.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';

final class Industry_Pack_Migration_Executor_Test extends TestCase {

	private function pack_def( string $key, string $status = 'active', string $replacement_ref = '' ): array {
		$p = array(
			Industry_Pack_Schema::FIELD_INDUSTRY_KEY => $key,
			Industry_Pack_Schema::FIELD_NAME => $key,
			Industry_Pack_Schema::FIELD_SUMMARY => 'Summary',
			Industry_Pack_Schema::FIELD_STATUS => $status,
			Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
		);
		if ( $replacement_ref !== '' ) {
			$p[ Industry_Pack_Schema::FIELD_REPLACEMENT_REF ] = $replacement_ref;
		}
		return $p;
	}

	private function bundle_def( string $bundle_key, string $industry_key, string $replacement_ref = '' ): array {
		$b = array(
			Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => $industry_key,
			Industry_Starter_Bundle_Registry::FIELD_LABEL => $bundle_key,
			Industry_Starter_Bundle_Registry::FIELD_SUMMARY => 'Summary',
			Industry_Starter_Bundle_Registry::FIELD_STATUS => Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
			Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => '1',
		);
		if ( $replacement_ref !== '' ) {
			$b[ Industry_Starter_Bundle_Registry::FIELD_REPLACEMENT_REF ] = $replacement_ref;
		}
		return $b;
	}

	protected function setUp(): void {
		parent::setUp();
		\update_option( Option_Names::INDUSTRY_PROFILE, array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => '',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => '',
		) );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_run_migration_with_valid_from_to_updates_primary(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array(
			$this->pack_def( 'realtor_old' ),
			$this->pack_def( 'realtor' ),
		) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array( $this->bundle_def( 'b1', 'realtor' ) ) );

		$profile_repo->merge_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor_old' ) );
		$executor = new Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
		$result = $executor->run_migration( 'realtor_old', 'realtor' );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $result->get_migrated_refs() );
		$this->assertSame( Industry_Pack_Migration_Result::OBJECT_TYPE_PRIMARY_INDUSTRY, $result->get_migrated_refs()[0]['object_type'] );
		$this->assertSame( 'realtor_old', $result->get_migrated_refs()[0]['old_ref'] );
		$this->assertSame( 'realtor', $result->get_migrated_refs()[0]['new_ref'] );
		$profile = $profile_repo->get_profile();
		$this->assertSame( 'realtor', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
	}

	public function test_run_migration_with_invalid_to_pack_returns_failure(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array( $this->pack_def( 'realtor_old' ) ) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$profile_repo->merge_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor_old' ) );
		$executor = new Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
		$result = $executor->run_migration( 'realtor_old', 'nonexistent' );

		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
		$profile = $profile_repo->get_profile();
		$this->assertSame( 'realtor_old', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
	}

	public function test_run_migration_when_no_matching_refs_returns_success_with_warning(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array( $this->pack_def( 'realtor_old' ), $this->pack_def( 'realtor' ) ) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$profile_repo->merge_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor' ) );
		$executor = new Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
		$result = $executor->run_migration( 'realtor_old', 'realtor' );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 0, $result->get_migrated_refs() );
		$this->assertNotEmpty( $result->get_warnings() );
	}

	public function test_run_migration_to_replacement_when_no_replacement_ref_returns_failure(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array( $this->pack_def( 'realtor_old' ), $this->pack_def( 'realtor' ) ) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$executor = new Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
		$result = $executor->run_migration_to_replacement( 'realtor_old' );

		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_run_migration_to_replacement_uses_replacement_ref(): void {
		$settings = new Settings_Service();
		$profile_repo = new Industry_Profile_Repository( $settings );
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load( array(
			$this->pack_def( 'realtor_old', 'deprecated', 'realtor' ),
			$this->pack_def( 'realtor' ),
		) );
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );

		$profile_repo->merge_profile( array( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor_old' ) );
		$executor = new Industry_Pack_Migration_Executor( $profile_repo, $pack_registry, $bundle_registry );
		$result = $executor->run_migration_to_replacement( 'realtor_old' );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $result->get_migrated_refs() );
		$profile = $profile_repo->get_profile();
		$this->assertSame( 'realtor', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
	}
}
