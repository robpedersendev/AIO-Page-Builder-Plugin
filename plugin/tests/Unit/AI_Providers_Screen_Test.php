<?php
/**
 * Unit tests for AI Providers admin screen: slug, capability, title (spec §49.9).
 *
 * Manual verification checklist (spec §49.9):
 * - Provider list: AI Providers submenu opens; table shows at least one provider (e.g. OpenAI) with label.
 * - Credential status: Column shows "Not configured" or "Configured" only; no raw keys in UI or page source.
 * - Connection-test result: Last connection test column shows "—" when none, or success/failure message and timestamp when run.
 * - Default model: Default model (planning) column shows model id or "—".
 * - Capability: User without aio_manage_ai_providers does not see AI Providers menu and gets 403 when opening the page URL directly.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Admin\Screens\AI\AI_Providers_Screen;
use AIOPageBuilder\Infrastructure\Config\Capabilities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Admin/Screens/AI/AI_Providers_Screen.php';

final class AI_Providers_Screen_Test extends TestCase {

	public function test_screen_has_correct_slug(): void {
		$this->assertSame( 'aio-page-builder-ai-providers', AI_Providers_Screen::SLUG );
	}

	public function test_screen_capability_is_manage_ai_providers(): void {
		$screen = new AI_Providers_Screen();
		$this->assertSame( Capabilities::MANAGE_AI_PROVIDERS, $screen->get_capability() );
	}

	public function test_screen_has_non_empty_title(): void {
		$screen = new AI_Providers_Screen();
		$this->assertNotEmpty( $screen->get_title() );
	}
}
