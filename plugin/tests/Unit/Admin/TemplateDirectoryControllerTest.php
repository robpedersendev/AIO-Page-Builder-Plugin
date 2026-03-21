<?php
/**
 * Minimal unit tests for template directory screens wiring.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Screens\Templates\Page_Templates_Directory_Screen;
use AIOPageBuilder\Admin\Screens\Templates\Section_Templates_Directory_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );
defined( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES' ) || define( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES', dirname( __DIR__ ) . '/../fixtures/wp-plugin-api-stub.php' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/tests/fixtures/wp-plugin-api-stub.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Section_Templates_Directory_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Page_Templates_Directory_Screen.php';

final class TemplateDirectoryControllerTest extends TestCase {

	public function test_directory_slugs_exist(): void {
		$this->assertSame( 'aio-page-builder-section-templates', Section_Templates_Directory_Screen::SLUG );
		$this->assertSame( 'aio-page-builder-page-templates', Page_Templates_Directory_Screen::SLUG );
	}
}
