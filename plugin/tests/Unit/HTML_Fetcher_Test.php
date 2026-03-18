<?php
/**
 * Unit tests for HTML_Fetcher: success, timeout, non-HTML rejection, disallowed URL (spec 24.7, contract 10, 11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Request_Policy;
use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Result;
use AIOPageBuilder\Domain\Crawler\Fetch\HTML_Fetcher;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Fetch/Fetch_Result.php';
require_once $plugin_root . '/src/Domain/Crawler/Fetch/Fetch_Request_Policy.php';
require_once $plugin_root . '/src/Domain/Crawler/Fetch/HTML_Fetcher.php';

final class HTML_Fetcher_Test extends TestCase {

	private function make_fetcher( ?callable $allowed = null ): HTML_Fetcher {
		$policy = new Fetch_Request_Policy( 8, 250, 3, 'TestCrawler/1.0' );
		return new HTML_Fetcher( $policy, $allowed );
	}

	public function test_empty_url_returns_disallowed(): void {
		$fetcher = $this->make_fetcher();
		$result  = $fetcher->fetch( '' );
		$this->assertSame( Fetch_Result::FETCH_STATUS_DISALLOWED, $result->fetch_status );
		$this->assertFalse( $result->is_success() );
	}

	public function test_disallowed_url_checker_refuses_fetch(): void {
		$fetcher = $this->make_fetcher(
			function ( string $url ): bool {
				return false;
			}
		);
		$result  = $fetcher->fetch( 'https://example.com/page' );
		$this->assertSame( Fetch_Result::FETCH_STATUS_DISALLOWED, $result->fetch_status );
		$this->assertSame( Fetch_Result::ERROR_DISALLOWED_URL, $result->error_code );
	}

	public function test_wp_error_timeout_returns_timeout_result(): void {
		$GLOBALS['_aio_wp_remote_get_return'] = function (): \WP_Error {
			return new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		};
		$fetcher                              = $this->make_fetcher(
			function (): bool {
				return true;
			}
		);
		$result                               = $fetcher->fetch( 'https://example.com/about' );
		unset( $GLOBALS['_aio_wp_remote_get_return'] );
		$this->assertSame( Fetch_Result::FETCH_STATUS_TIMEOUT, $result->fetch_status );
		$this->assertSame( Fetch_Result::ERROR_TIMEOUT, $result->error_code );
	}

	public function test_mock_success_returns_success_result(): void {
		$GLOBALS['_aio_wp_remote_get_return'] = function (): array {
			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'headers'  => array( 'content-type' => 'text/html; charset=UTF-8' ),
				'body'     => '<!DOCTYPE html><html><body>Hello</body></html>',
			);
		};
		$fetcher                              = $this->make_fetcher(
			function (): bool {
				return true;
			}
		);
		$result                               = $fetcher->fetch( 'https://example.com/about' );
		unset( $GLOBALS['_aio_wp_remote_get_return'] );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 200, $result->http_status );
		$this->assertSame( 'text/html', $result->content_type );
		$this->assertStringContainsString( 'Hello', (string) $result->html );
	}

	public function test_mock_non_html_returns_non_html_result(): void {
		$GLOBALS['_aio_wp_remote_get_return'] = function (): array {
			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'headers'  => array( 'content-type' => 'application/json' ),
				'body'     => '{"key":"value"}',
			);
		};
		$fetcher                              = $this->make_fetcher(
			function (): bool {
				return true;
			}
		);
		$result                               = $fetcher->fetch( 'https://example.com/api' );
		unset( $GLOBALS['_aio_wp_remote_get_return'] );
		$this->assertSame( Fetch_Result::FETCH_STATUS_NON_HTML, $result->fetch_status );
		$this->assertSame( Fetch_Result::ERROR_UNSUPPORTED_CONTENT, $result->error_code );
		$this->assertNull( $result->html );
	}
}
