<?php
/**
 * Unit tests for Frontend_Style_Enqueue_Service (Prompt 245): handle constant.
 * Conditional enqueue (should_load_base_styles) is covered by manual QA / integration.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Styling\Frontend_Style_Enqueue_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Frontend_Style_Enqueue_Service.php';

final class Frontend_Style_Enqueue_Service_Test extends TestCase {

	public function test_handle_constant(): void {
		$this->assertSame( 'aio-page-builder-base', Frontend_Style_Enqueue_Service::HANDLE_BASE );
	}
}
