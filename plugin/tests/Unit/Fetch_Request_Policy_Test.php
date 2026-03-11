<?php
/**
 * Unit tests for Fetch_Request_Policy: timeout, delay, user-agent (contract §10).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Request_Policy;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Fetch/Fetch_Request_Policy.php';

final class Fetch_Request_Policy_Test extends TestCase {

	public function test_defaults_match_contract(): void {
		$policy = new Fetch_Request_Policy();
		$this->assertSame( 8, $policy->get_timeout_seconds() );
		$this->assertSame( 250, $policy->get_delay_after_request_ms() );
		$this->assertSame( 3, $policy->get_max_redirects() );
		$this->assertStringContainsString( 'AIOPageBuilderCrawler', $policy->get_user_agent() );
	}

	public function test_custom_values(): void {
		$policy = new Fetch_Request_Policy( 15, 500, 2, 'CustomBot/1.0' );
		$this->assertSame( 15, $policy->get_timeout_seconds() );
		$this->assertSame( 500, $policy->get_delay_after_request_ms() );
		$this->assertSame( 2, $policy->get_max_redirects() );
		$this->assertSame( 'CustomBot/1.0', $policy->get_user_agent() );
	}

	public function test_request_headers_contain_only_user_agent(): void {
		$policy = new Fetch_Request_Policy( 8, 250, 3, 'Crawler/1' );
		$headers = $policy->get_request_headers();
		$this->assertCount( 1, $headers );
		$this->assertArrayHasKey( 'User-Agent', $headers );
		$this->assertSame( 'Crawler/1', $headers['User-Agent'] );
	}
}
