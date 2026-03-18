<?php
/**
 * Unit tests for crawler admin screen slugs and menu visibility (spec §24.17, crawler-admin-screen-contract).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Comparison_Screen;
use AIOPageBuilder\Admin\Screens\Crawler\Crawler_Sessions_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Sessions_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/Crawler/Crawler_Comparison_Screen.php';

final class Crawler_Admin_Screen_Test extends TestCase {

	public function test_crawler_sessions_slug_matches_contract(): void {
		$this->assertSame( 'aio-page-builder-crawler-sessions', Crawler_Sessions_Screen::SLUG );
	}

	public function test_crawler_comparison_slug_matches_contract(): void {
		$this->assertSame( 'aio-page-builder-crawler-comparison', Crawler_Comparison_Screen::SLUG );
	}

	public function test_crawler_sessions_screen_has_title_and_capability(): void {
		$screen = new Crawler_Sessions_Screen( null );
		$this->assertNotEmpty( $screen->get_title() );
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability(), 'Crawler screens use plugin capability for least-privilege.' );
	}

	public function test_crawler_comparison_screen_has_title_and_capability(): void {
		$screen = new Crawler_Comparison_Screen( null );
		$this->assertSame( 'Crawl Comparison', $screen->get_title() );
		$this->assertSame( Capabilities::VIEW_SENSITIVE_DIAGNOSTICS, $screen->get_capability(), 'Crawler screens use plugin capability for least-privilege.' );
	}
}
