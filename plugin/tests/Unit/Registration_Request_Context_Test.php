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
		$skip    = $context->should_skip_registration();
		$this->assertSame( ! is_admin(), $skip );
	}

	public function test_is_front_end_opposite_of_is_admin(): void {
		$context = new Registration_Request_Context();
		$this->assertSame( ! is_admin(), $context->is_front_end() );
		$this->assertSame( is_admin(), $context->is_admin() );
	}

	/** Prompt 304: scripted context triggers skip. */
	public function test_should_skip_registration_true_when_scripted_context(): void {
		$context = new class() extends Registration_Request_Context {
			public function is_front_end(): bool {
				return false;
			}
			public function is_scripted_context(): bool {
				return true;
			}
		};
		$this->assertTrue( $context->should_skip_registration() );
	}

	/** Prompt 304: is_scripted_context is false when not CLI or cron (normal run). */
	public function test_is_scripted_context_false_when_not_cli_or_cron(): void {
		$context = new Registration_Request_Context();
		$this->assertFalse( $context->is_scripted_context() );
	}
}
