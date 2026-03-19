<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Admin;

use AIOPageBuilder\Admin\Screens\AI\AI_Providers_Screen;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/../wordpress/' );

$plugin_root = dirname( __DIR__, 3 );
require_once $plugin_root . '/src/Admin/Screens/AI/AI_Providers_Screen.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';

final class AIProvidersControllerTest extends TestCase {

	public function test_action_names_match_required_contract(): void {
		$screen = new AI_Providers_Screen( null );
		$this->assertSame( 'aio_manage_ai_providers', $screen->get_capability() );
	}
}

