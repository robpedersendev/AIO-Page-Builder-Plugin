<?php
/**
 * Unit tests for AI Runs admin screen: slug, capability, routing (spec §29, §44.7).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\AI\AI_Run_Detail_Screen;
use AIOPageBuilder\Admin\Screens\AI\AI_Runs_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/AI/AI_Runs_Screen.php';
require_once $plugin_root . '/src/Admin/Screens/AI/AI_Run_Detail_Screen.php';

final class AI_Runs_Screen_Test extends TestCase {

	public function test_list_screen_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder-ai-runs', AI_Runs_Screen::SLUG );
		$screen = new AI_Runs_Screen();
		$this->assertNotEmpty( $screen->get_title() );
	}

	public function test_list_screen_capability_is_view_ai_runs(): void {
		$screen = new AI_Runs_Screen();
		$this->assertSame( Capabilities::VIEW_AI_RUNS, $screen->get_capability() );
	}

	public function test_detail_screen_capability_is_view_ai_runs(): void {
		$screen = new AI_Run_Detail_Screen();
		$this->assertSame( Capabilities::VIEW_AI_RUNS, $screen->get_capability() );
	}

	public function test_detail_screen_uses_same_slug_as_list_for_routing(): void {
		$this->assertSame( AI_Runs_Screen::SLUG, AI_Run_Detail_Screen::SLUG );
	}
}
