<?php
/**
 * Unit tests for Registration_Request_Context (Prompt 283).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ACF\Registration\Registration_Request_Context;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/ACF/Registration/Registration_Request_Context.php';

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

final class Registration_Request_Context_Test extends TestCase {

	public function test_should_skip_registration_returns_true_when_front_end(): void {
		$context = new Registration_Request_Context();
		$skip = $context->should_skip_registration();
		$this->assertSame( ! is_admin(), $skip );
	}

	public function test_is_front_end_opposite_of_is_admin(): void {
		$context = new Registration_Request_Context();
		$this->assertSame( ! is_admin(), $context->is_front_end() );
		$this->assertSame( is_admin(), $context->is_admin() );
	}
}
