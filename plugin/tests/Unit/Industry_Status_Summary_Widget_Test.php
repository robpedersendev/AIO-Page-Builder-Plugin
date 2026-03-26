<?php
/**
 * Unit tests for Industry_Status_Summary_Widget (Prompt 410).
 *
 * Covers: no-industry state, healthy state (primary set, no warnings), warning state (health warnings).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Admin\Widgets\Industry_Status_Summary_Widget;
use AIOPageBuilder\Bootstrap\Industry_Packs_Module;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Readiness_Result;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Schema;
use AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry;
use AIOPageBuilder\Domain\Industry\Reporting\Industry_Health_Check_Service;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Readiness_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
require_once $plugin_root . '/src/Admin/Admin_Screen_Hub.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Profile_Settings_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Health_Report_Screen.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Provider_Interface.php';
require_once $plugin_root . '/src/Bootstrap/Industry_Packs_Module.php';
require_once $plugin_root . '/src/Admin/Widgets/Industry_Status_Summary_Widget.php';

final class Industry_Status_Summary_Widget_Test extends TestCase {

	public function test_widget_requires_view_logs_capability(): void {
		$this->assertSame( Capabilities::VIEW_LOGS, Industry_Status_Summary_Widget::get_required_capability(), 'Widget aligned with Industry Author Dashboard capability.' );
	}

	public function test_build_view_model_without_industry_profile_store_returns_no_industry(): void {
		$container = new Service_Container();
		$vm        = Industry_Status_Summary_Widget::build_view_model( $container );
		$this->assertFalse( $vm['has_industry'] );
		$this->assertSame( __( 'No pack', 'aio-page-builder' ), $vm['pack_state'] );
		$this->assertNotEmpty( $vm['profile_url'] );
		$this->assertNotEmpty( $vm['health_url'] );
		$this->assertStringContainsString( Industry_Profile_Settings_Screen::SLUG, $vm['profile_url'] );
		$this->assertStringContainsString( Industry_Profile_Settings_Screen::SLUG, $vm['health_url'] );
		$this->assertStringContainsString( 'aio_tab=reports', $vm['health_url'] );
		$this->assertStringContainsString( 'aio_subtab=health', $vm['health_url'] );
	}

	public function test_build_view_model_with_empty_primary_returns_not_configured(): void {
		$settings  = new Settings_Service();
		$container = new Service_Container();
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE,
			function () use ( $settings ): Industry_Profile_Repository {
				return new Industry_Profile_Repository( $settings );
			}
		);
		\update_option( Option_Names::INDUSTRY_PROFILE, array( Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1' ) );
		$vm = Industry_Status_Summary_Widget::build_view_model( $container );
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		$this->assertFalse( $vm['has_industry'] );
	}

	public function test_build_view_model_with_primary_and_pack_returns_healthy_summary(): void {
		$settings      = new Settings_Service();
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load(
			array(
				array(
					Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'realtor',
					Industry_Pack_Schema::FIELD_NAME    => 'Realtor',
					Industry_Pack_Schema::FIELD_SUMMARY => 'Summary',
					Industry_Pack_Schema::FIELD_STATUS  => Industry_Pack_Schema::STATUS_ACTIVE,
					Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load(
			array(
				array(
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_BUNDLE_KEY => 'realtor_starter',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_INDUSTRY_KEY => 'realtor',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_LABEL => 'Realtor Starter',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_SUMMARY => 'Summary',
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_STATUS => \AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::STATUS_ACTIVE,
					\AIOPageBuilder\Domain\Industry\Registry\Industry_Starter_Bundle_Registry::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$container = new Service_Container();
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE,
			function () use ( $settings ): Industry_Profile_Repository {
				return new Industry_Profile_Repository( $settings );
			}
		);
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY,
			function () use ( $pack_registry ): Industry_Pack_Registry {
				return $pack_registry;
			}
		);
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY,
			function () use ( $bundle_registry ) {
				return $bundle_registry;
			}
		);
		$profile = array(
			Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
			Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'realtor_starter',
		);
		\update_option( Option_Names::INDUSTRY_PROFILE, $profile );
		$vm = Industry_Status_Summary_Widget::build_view_model( $container );
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		$this->assertTrue( $vm['has_industry'] );
		$this->assertSame( 'Realtor', $vm['primary_label'] );
		$this->assertSame( 'Realtor Starter', $vm['starter_bundle_label'] );
		$this->assertSame( 0, $vm['error_count'] );
		$this->assertSame( 0, $vm['warning_count'] );
	}

	/**
	 * With real health check and profile that references a missing starter bundle, error_count is raised.
	 */
	public function test_build_view_model_with_health_errors_returns_error_count(): void {
		$settings      = new Settings_Service();
		$pack_registry = new Industry_Pack_Registry();
		$pack_registry->load(
			array(
				array(
					Industry_Pack_Schema::FIELD_INDUSTRY_KEY => 'realtor',
					Industry_Pack_Schema::FIELD_NAME    => 'Realtor',
					Industry_Pack_Schema::FIELD_SUMMARY => 'S',
					Industry_Pack_Schema::FIELD_STATUS  => Industry_Pack_Schema::STATUS_ACTIVE,
					Industry_Pack_Schema::FIELD_VERSION_MARKER => '1',
				),
			)
		);
		$bundle_registry = new Industry_Starter_Bundle_Registry();
		$bundle_registry->load( array() );
		$container = new Service_Container();
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE,
			function () use ( $settings ): Industry_Profile_Repository {
				return new Industry_Profile_Repository( $settings );
			}
		);
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY,
			function () use ( $pack_registry ): Industry_Pack_Registry {
				return $pack_registry;
			}
		);
		$container->register(
			Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY,
			function () use ( $bundle_registry ) {
				return $bundle_registry;
			}
		);
		$container->register(
			'industry_health_check_service',
			function () use ( $container ): Industry_Health_Check_Service {
				return new Industry_Health_Check_Service(
					$container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE ),
					$container->get( Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PACK_REGISTRY ),
					null,
					null,
					null,
					null,
					null,
					null,
					null,
					$container->get( Industry_Packs_Module::CONTAINER_KEY_STARTER_BUNDLE_REGISTRY ),
					null
				);
			}
		);
		\update_option(
			Option_Names::INDUSTRY_PROFILE,
			array(
				Industry_Profile_Schema::FIELD_SCHEMA_VERSION => '1',
				Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'realtor',
				Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array(),
				Industry_Profile_Schema::FIELD_SELECTED_STARTER_BUNDLE_KEY => 'nonexistent_bundle',
			)
		);
		$vm = Industry_Status_Summary_Widget::build_view_model( $container );
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		$this->assertTrue( $vm['has_industry'] );
		$this->assertGreaterThan( 0, $vm['error_count'], 'Profile references missing starter bundle; health check should report an error.' );
	}
}
