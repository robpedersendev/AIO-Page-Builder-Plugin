<?php
/**
 * Minimal unit tests for section template detail screen wiring.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Screens\Templates\Section_Template_Detail_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );
defined( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES' ) || define( 'AIOPAGEBUILDER_TEST_PLUGIN_INCLUDES', dirname( __DIR__ ) . '/../fixtures/wp-plugin-api-stub.php' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/tests/fixtures/wp-plugin-api-stub.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Templates/Section_Template_Detail_Screen.php';

final class SectionTemplateDetailControllerTest extends TestCase {

	public function test_screen_slug_constant_exists(): void {
		$this->assertSame( 'aio-page-builder-section-template-detail', Section_Template_Detail_Screen::SLUG );
	}
}

