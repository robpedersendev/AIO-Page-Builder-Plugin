<?php
/**
 * Authorization tests for SPR-003: plugin-owned screens use plugin capabilities, not manage_options.
 *
 * Asserts get_capability() for in-scope screens and Industry_Status_Summary_Widget align with
 * Capabilities constants. Prevents regression to manage_options.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\AI\Onboarding_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Session_Detail_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use AIOPageBuilder\Admin\Screens\Diagnostics_Screen;
use AIOPageBuilder\Admin\Screens\Settings_Screen;
use AIOPageBuilder\Admin\Widgets\Industry_Status_Summary_Widget;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Bootstrap/Constants.php';
require_once $plugin_root . '/src/Infrastructure/Config/Dependency_Requirements.php';
require_once $plugin_root . '/src/Bootstrap/Environment_Validator.php';
require_once $plugin_root . '/src/Admin/Screens/Settings_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Diagnostics_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Sessions_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Comparison_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Session_Detail_Screen.php';
require_once $plugin_root . '/src/Admin/Widgets/Industry_Status_Summary_Widget.php';

// Onboarding_Screen and Dashboard provider have many domain deps; require only for get_capability().
require_once $plugin_root . '/src/Infrastructure/Container/Service_Container.php';
require_once $plugin_root . '/src/Admin/Screens/AI/Onboarding_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Dashboard/Dashboard_Screen.php';

final class SPR003_Capability_Alignment_Test extends TestCase {

	public function test_settings_screen_uses_manage_settings(): void {
		$screen = new Settings_Screen();
		$this->assertSame( Capabilities::MANAGE_SETTINGS, $screen->get_capability() );
	}

	public function test_diagnostics_screen_uses_view_sensitive_diagnostics(): void {
		$screen = new Diagnostics_Screen();
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability() );
	}

	public function test_onboarding_screen_uses_run_onboarding(): void {
		$screen = new Onboarding_Screen( new Service_Container() );
		$this->assertSame( Capabilities::RUN_ONBOARDING, $screen->get_capability() );
	}

	/** Menu uses Dashboard\Dashboard_Screen (single Dashboard per spec §49.5). */
	public function test_dashboard_screen_uses_view_logs(): void {
		$screen = new Dashboard_Screen( null );
		$this->assertSame( Capabilities::VIEW_LOGS, $screen->get_capability() );
	}

	public function test_crawler_sessions_screen_uses_view_sensitive_diagnostics(): void {
		$screen = new Crawler_Sessions_Screen( null );
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability() );
	}

	public function test_crawler_comparison_screen_uses_view_sensitive_diagnostics(): void {
		$screen = new Crawler_Comparison_Screen( null );
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability() );
	}

	public function test_crawler_session_detail_screen_uses_view_sensitive_diagnostics(): void {
		$screen = new Crawler_Session_Detail_Screen( null );
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability() );
	}

	public function test_industry_status_summary_widget_uses_view_logs_aligned_with_dashboard(): void {
		$this->assertSame( Capabilities::VIEW_LOGS, Industry_Status_Summary_Widget::get_required_capability() );
	}

	public function test_no_plugin_capability_equals_manage_options(): void {
		$this->assertNotSame( 'manage_options', Capabilities::MANAGE_SETTINGS );
		$this->assertNotSame( 'manage_options', Capabilities::VIEW_LOGS );
		$this->assertNotSame( 'manage_options', Capabilities::VIEW_SENSITIVE_DIAGNOSTICS );
		$this->assertNotSame( 'manage_options', Capabilities::RUN_ONBOARDING );
	}
}
