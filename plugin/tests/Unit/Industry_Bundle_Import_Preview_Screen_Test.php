<?php
/**
 * Unit tests for Industry Bundle Import Preview screen (SPR-007).
 *
 * Screen is preview-only; apply/confirm import is deferred. Asserts capability and slug.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Industry\Industry_Bundle_Import_Preview_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Industry/Industry_Bundle_Import_Preview_Screen.php';

final class Industry_Bundle_Import_Preview_Screen_Test extends TestCase {

	public function test_screen_uses_manage_settings_capability(): void {
		$screen = new Industry_Bundle_Import_Preview_Screen( null );
		$this->assertSame( Capabilities::MANAGE_SETTINGS, $screen->get_capability(), 'SPR-007: preview screen gated by MANAGE_SETTINGS.' );
	}

	public function test_screen_has_expected_slug(): void {
		$this->assertSame( 'aio-page-builder-industry-bundle-import-preview', Industry_Bundle_Import_Preview_Screen::SLUG );
	}

	public function test_screen_has_non_empty_title(): void {
		$screen = new Industry_Bundle_Import_Preview_Screen( null );
		$this->assertNotEmpty( $screen->get_title() );
	}
}
