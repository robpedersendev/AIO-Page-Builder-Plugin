<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Sessions_Screen.php';

final class CrawlerActionsControllerTest extends TestCase {

	public function test_screen_slug_constant_exists(): void {
		$this->assertSame( 'aio-page-builder-crawler-sessions', Crawler_Sessions_Screen::SLUG );
	}
}
