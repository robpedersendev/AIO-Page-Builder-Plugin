<?php
/**
 * Unit tests for Build Plan list and workspace screen shell: slug, capability (spec §31, build-plan-admin-ia-contract.md).
 *
 * Manual verification checklist (run in browser with a plan created):
 * - List: Navigate to Build Plans submenu → list shows plans or empty message; "Open" links to workspace with plan_id.
 * - Workspace: URL ?page=aio-page-builder-build-plans&plan_id=<id> shows three-zone layout (context rail left, stepper top, workspace main).
 * - Stepper: Step links change active step; blocked steps show disabled link; status badge and unresolved count visible.
 * - Context rail: Plan title, plan ID, source AI run, status, site purpose/flow, warnings summary, Save and exit, Export (if cap), View source artifacts (if cap).
 * - Empty states: Blocked step shows "This step is blocked until earlier required actions are completed."; empty step shows appropriate message.
 * - Capability: With only aio_view_build_plans, Export and View source artifacts hidden or disabled; with aio_export_data / aio_view_sensitive_diagnostics they appear.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plan_Workspace_Screen;
use AIOPageBuilder\Admin\Screens\BuildPlan\Build_Plans_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use AIOPageBuilder\Infrastructure\Container\Service_Container;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/BuildPlan/Build_Plans_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/BuildPlan/Build_Plan_Workspace_Screen.php';

final class Build_Plan_Screen_Shell_Test extends TestCase {

	public function test_list_screen_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder-build-plans', Build_Plans_Screen::SLUG );
	}

	public function test_list_screen_capability_is_view_build_plans(): void {
		$screen = new Build_Plans_Screen( new Service_Container() );
		$this->assertSame( Capabilities::VIEW_BUILD_PLANS, $screen->get_capability() );
	}

	public function test_workspace_screen_capability_is_view_build_plans(): void {
		$screen = new Build_Plan_Workspace_Screen( new Service_Container() );
		$this->assertSame( Capabilities::VIEW_BUILD_PLANS, $screen->get_capability() );
	}
}
