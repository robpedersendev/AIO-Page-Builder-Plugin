<?php
/**
 * Unit tests for Industry_Profile_Settings_Screen and save flow (industry-admin-screen-contract; Prompt 342).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Profile_Settings_Screen;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Repository;
use AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Schema;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use AIOPageBuilder\Infrastructure\Settings\Settings_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Infrastructure/Settings/Settings_Service.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Repository.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Readiness_Result.php';
require_once $plugin_root . '/src/Domain/Industry/Profile/Industry_Profile_Validator.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
require_once $plugin_root . '/src/Infrastructure/Container/Service_Provider_Interface.php';
require_once $plugin_root . '/src/Bootstrap/Industry_Packs_Module.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Profile_Settings_Screen.php';

final class Industry_Profile_Settings_Screen_Test extends TestCase {

	/** @var Settings_Service */
	private Settings_Service $settings;

	/** @var Service_Container */
	private Service_Container $container;

	protected function setUp(): void {
		parent::setUp();
		$this->settings = new Settings_Service();
		$this->container = new Service_Container();
		$this->container->register( 'settings', function () {
			return $this->settings;
		} );
		$this->container->register( 'industry_profile_validator', function () {
			return new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator();
		} );
		$this->container->register( 'industry_profile_store', function () {
			return new Industry_Profile_Repository( $this->settings );
		} );
		$this->container->register( 'industry_pack_registry', function () {
			$r = new \AIOPageBuilder\Domain\Industry\Registry\Industry_Pack_Registry();
			$r->load( array() );
			return $r;
		} );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::INDUSTRY_PROFILE );
		parent::tearDown();
	}

	public function test_screen_slug_and_capability(): void {
		$screen = new Industry_Profile_Settings_Screen( $this->container );
		$this->assertSame( 'aio-page-builder-industry-profile', $screen::SLUG );
		$this->assertSame( 'aio_manage_settings', $screen->get_capability() );
		$this->assertNotEmpty( $screen->get_title() );
	}

	public function test_render_includes_readiness_and_form_with_container(): void {
		$GLOBALS['_aio_current_user_can_return'] = true;
		$screen = new Industry_Profile_Settings_Screen( $this->container );
		\ob_start();
		try {
			$screen->render();
			$html = \ob_get_clean();
		} catch ( \Throwable $e ) {
			\ob_end_clean();
			unset( $GLOBALS['_aio_current_user_can_return'] );
			throw $e;
		}
		unset( $GLOBALS['_aio_current_user_can_return'] );
		$this->assertStringContainsString( 'Industry Profile', $html );
		$this->assertStringContainsString( 'Readiness', $html );
		$this->assertStringContainsString( 'Industry selection', $html );
		$this->assertStringContainsString( Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY, $html );
		$this->assertStringContainsString( 'aio_save_industry_profile', $html );
	}

	public function test_save_flow_valid_partial_persists_via_repository(): void {
		$repo = $this->container->get( \AIOPageBuilder\Bootstrap\Industry_Packs_Module::CONTAINER_KEY_INDUSTRY_PROFILE_STORE );
		$this->assertInstanceOf( Industry_Profile_Repository::class, $repo );
		/** @var Industry_Profile_Repository $repo */
		$repo->merge_profile( array(
			Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY => 'legal',
			Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS => array( 'healthcare' ),
		) );
		$profile = $repo->get_profile();
		$this->assertSame( 'legal', $profile[ Industry_Profile_Schema::FIELD_PRIMARY_INDUSTRY_KEY ] );
		$this->assertSame( array( 'healthcare' ), $profile[ Industry_Profile_Schema::FIELD_SECONDARY_INDUSTRY_KEYS ] );
	}

	public function test_readiness_minimal_when_primary_empty(): void {
		$validator = new \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Validator();
		$profile = Industry_Profile_Schema::get_empty_profile();
		$result = $validator->get_readiness( $profile );
		$this->assertSame( \AIOPageBuilder\Domain\Industry\Profile\Industry_Profile_Readiness_Result::STATE_MINIMAL, $result->get_state() );
		$this->assertEmpty( $result->get_validation_errors() );
	}
}
