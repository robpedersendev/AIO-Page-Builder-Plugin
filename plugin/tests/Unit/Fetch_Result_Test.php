<?php
/**
 * Unit tests for Fetch_Result value object (spec 24.15, 24.16; contract 11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Fetch\Fetch_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Fetch/Fetch_Result.php';

final class Fetch_Result_Test extends TestCase {

	public function test_success_result_to_array_and_is_success(): void {
		$r = new Fetch_Result(
			'https://example.com/about',
			200,
			'text/html',
			Fetch_Result::FETCH_STATUS_SUCCESS,
			null,
			150,
			'<html><body>ok</body></html>',
			array( 'content-type' => 'text/html' ),
			null
		);
		$this->assertTrue( $r->is_success() );
		$arr = $r->to_array();
		$this->assertSame( 'https://example.com/about', $arr['normalized_url'] );
		$this->assertSame( 200, $arr['http_status'] );
		$this->assertSame( 'text/html', $arr['content_type'] );
		$this->assertSame( Fetch_Result::FETCH_STATUS_SUCCESS, $arr['fetch_status'] );
		$this->assertNull( $arr['error_code'] );
		$this->assertSame( 150, $arr['response_time_ms'] );
		$this->assertTrue( $arr['has_html'] );
	}

	public function test_failure_result_is_not_success(): void {
		$r = new Fetch_Result(
			'https://example.com/',
			null,
			null,
			Fetch_Result::FETCH_STATUS_TIMEOUT,
			Fetch_Result::ERROR_TIMEOUT,
			8000,
			null,
			array(),
			null
		);
		$this->assertFalse( $r->is_success() );
		$this->assertSame( Fetch_Result::ERROR_TIMEOUT, $r->error_code );
	}
}
