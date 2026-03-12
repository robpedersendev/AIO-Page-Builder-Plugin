<?php
/**
 * Unit tests for Dashboard screen: slug, capability, title (spec §49.5).
 *
 * Manual verification checklist (spec §49.5):
 * - Card visibility: Environment, Dependency, Provider readiness cards render with ready/not-ready state.
 * - Welcome/resume: First-run shows welcome notice and onboarding link; in-progress draft shows resume notice.
 * - Permission-sensitive quick actions: Only actions for which the user has capability are shown.
 * - Degraded state: When dependencies or provider are not ready, cards show concise message and no raw diagnostics.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Dashboard\Dashboard_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Admin/Screens/Dashboard/Dashboard_Screen.php';

final class Dashboard_Screen_Test extends TestCase {

	public function test_dashboard_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder', Dashboard_Screen::SLUG );
	}

	public function test_dashboard_capability_is_manage_options(): void {
		$screen = new Dashboard_Screen();
		$this->assertSame( 'manage_options', $screen->get_capability() );
	}

	public function test_dashboard_has_non_empty_title(): void {
		$screen = new Dashboard_Screen();
		$this->assertNotEmpty( $screen->get_title() );
	}
}
