<?php
/**
 * Unit tests for Privacy, Reporting & Settings screen (spec §49.12).
 *
 * Manual verification: disclosure visible and not in tooltip-only; retention state shown;
 * uninstall/export choices and built-pages message; permission gating (aio_manage_reporting_and_privacy);
 * no raw credentials or destination email in output.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Settings\Privacy_Reporting_Settings_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Settings/Privacy_Reporting_Settings_Screen.php';

final class Privacy_Reporting_Settings_Screen_Test extends TestCase {

	public function test_screen_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder-privacy-reporting', Privacy_Reporting_Settings_Screen::SLUG );
	}

	public function test_screen_capability_is_manage_reporting_and_privacy(): void {
		$screen = new Privacy_Reporting_Settings_Screen();
		$this->assertSame( Capabilities::MANAGE_REPORTING_AND_PRIVACY, $screen->get_capability() );
	}

	public function test_screen_has_non_empty_title(): void {
		$screen = new Privacy_Reporting_Settings_Screen();
		$this->assertNotEmpty( $screen->get_title() );
	}
}
