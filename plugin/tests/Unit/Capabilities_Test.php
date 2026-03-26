<?php
/**
 * Unit tests for Capabilities config (spec §44.3). Verifies stable capability list and helper accessors.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';

/**
 * Capability source-of-truth: constants, get_all(), get_editor_defaults(), is_plugin_capability().
 */
final class Capabilities_Test extends TestCase {

	public function test_get_all_returns_full_list_in_stable_order(): void {
		$all = Capabilities::get_all();
		$this->assertIsArray( $all );
		$this->assertCount( 34, $all );
		$this->assertSame( Capabilities::MANAGE_SETTINGS, $all[0] );
		$this->assertSame( Capabilities::VIEW_VERSION_SNAPSHOTS, $all[26] );
		$this->assertSame( Capabilities::ACCESS_INDUSTRY_WORKSPACE, $all[33] );
	}

	public function test_constants_are_stable_strings(): void {
		$this->assertSame( 'aio_manage_settings', Capabilities::MANAGE_SETTINGS );
		$this->assertSame( 'aio_view_build_plans', Capabilities::VIEW_BUILD_PLANS );
		$this->assertSame( 'aio_approve_build_plans', Capabilities::APPROVE_BUILD_PLANS );
		$this->assertSame( 'aio_view_logs', Capabilities::VIEW_LOGS );
		$this->assertSame( 'aio_manage_reporting_and_privacy', Capabilities::MANAGE_REPORTING_AND_PRIVACY );
	}

	public function test_get_editor_defaults_returns_only_allowed_subset(): void {
		$editor = Capabilities::get_editor_defaults();
		$this->assertCount( 3, $editor );
		$this->assertContains( Capabilities::VIEW_BUILD_PLANS, $editor );
		$this->assertContains( Capabilities::APPROVE_BUILD_PLANS, $editor );
		$this->assertContains( Capabilities::VIEW_LOGS, $editor );
		$this->assertNotContains( Capabilities::MANAGE_AI_PROVIDERS, $editor );
		$this->assertNotContains( Capabilities::EXECUTE_ROLLBACKS, $editor );
	}

	public function test_is_plugin_capability_returns_true_for_all_registered(): void {
		foreach ( Capabilities::get_all() as $cap ) {
			$this->assertTrue( Capabilities::is_plugin_capability( $cap ), "Expected {$cap} to be a plugin capability" );
		}
	}

	public function test_is_plugin_capability_returns_false_for_unknown(): void {
		$this->assertFalse( Capabilities::is_plugin_capability( 'manage_options' ) );
		$this->assertFalse( Capabilities::is_plugin_capability( 'aio_fake_cap' ) );
		$this->assertFalse( Capabilities::is_plugin_capability( '' ) );
	}

	public function test_current_user_can_or_site_admin_true_when_manage_options(): void {
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options' => true,
		);
		$this->assertTrue( Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_SECTION_TEMPLATES ) );
		unset( $GLOBALS['_aio_current_user_can_caps'] );
	}

	public function test_current_user_can_or_site_admin_falls_back_to_registry_cap(): void {
		unset( $GLOBALS['_aio_current_user_can_caps'], $GLOBALS['_aio_current_user_can_return'] );
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options'                    => false,
			Capabilities::MANAGE_PAGE_TEMPLATES => true,
		);
		$this->assertTrue( Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_PAGE_TEMPLATES ) );
		unset( $GLOBALS['_aio_current_user_can_caps'] );
	}

	public function test_current_user_can_or_site_admin_false_when_no_match(): void {
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options' => false,
		);
		$this->assertFalse( Capabilities::current_user_can_or_site_admin( Capabilities::MANAGE_COMPOSITIONS ) );
		unset( $GLOBALS['_aio_current_user_can_caps'] );
	}

	public function test_is_meta_post_or_page_cap_without_object_detects_core_meta_caps(): void {
		$this->assertTrue( Capabilities::is_meta_post_or_page_cap_without_object( 'delete_post' ) );
		$this->assertTrue( Capabilities::is_meta_post_or_page_cap_without_object( 'edit_page' ) );
		$this->assertFalse( Capabilities::is_meta_post_or_page_cap_without_object( Capabilities::VIEW_LOGS ) );
		$this->assertFalse( Capabilities::is_meta_post_or_page_cap_without_object( 'delete_posts' ) );
	}

	public function test_current_user_can_for_route_denies_bare_meta_caps_without_invoking_grant(): void {
		unset( $GLOBALS['_aio_current_user_can_caps'], $GLOBALS['_aio_current_user_can_return'] );
		$GLOBALS['_aio_current_user_can_return'] = true;
		$this->assertFalse( Capabilities::current_user_can_for_route( 'delete_post' ) );
		$this->assertFalse( Capabilities::current_user_can_for_route( 'edit_post' ) );
		unset( $GLOBALS['_aio_current_user_can_return'] );
	}

	public function test_current_user_can_for_route_delegates_for_plugin_caps(): void {
		unset( $GLOBALS['_aio_current_user_can_return'] );
		$GLOBALS['_aio_current_user_can_caps'] = array(
			Capabilities::VIEW_LOGS => true,
		);
		$this->assertTrue( Capabilities::current_user_can_for_route( Capabilities::VIEW_LOGS ) );
		unset( $GLOBALS['_aio_current_user_can_caps'] );
	}

	public function test_current_user_can_for_route_grants_plugin_caps_to_site_admin_without_primitive(): void {
		unset( $GLOBALS['_aio_current_user_can_return'] );
		$GLOBALS['_aio_is_logged_in']          = true;
		$GLOBALS['_aio_current_user_can_caps'] = array(
			'manage_options' => true,
		);
		$this->assertTrue( Capabilities::current_user_can_for_route( Capabilities::VIEW_AI_RUNS ) );
		unset( $GLOBALS['_aio_current_user_can_caps'], $GLOBALS['_aio_is_logged_in'] );
	}
}
