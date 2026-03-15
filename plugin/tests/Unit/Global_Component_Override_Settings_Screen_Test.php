<?php
/**
 * Unit tests for Global_Component_Override_Settings_Screen (Prompt 248): capability, slug, title.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Settings\Global_Component_Override_Settings_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Settings/Global_Component_Override_Settings_Screen.php';

final class Global_Component_Override_Settings_Screen_Test extends TestCase {

	public function test_slug_constant(): void {
		$this->assertSame( 'aio-page-builder-global-component-overrides', Global_Component_Override_Settings_Screen::SLUG );
	}

	public function test_capability_is_manage_settings(): void {
		$screen = new Global_Component_Override_Settings_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SETTINGS, $screen->get_capability() );
	}

	public function test_title_non_empty(): void {
		$screen = new Global_Component_Override_Settings_Screen( null );
		$this->assertNotEmpty( $screen->get_title() );
	}
}
