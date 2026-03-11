<?php
/**
 * Unit tests for URL_Normalizer: same-host, fragment/tracking removal, dedup key (crawler contract §3.4, §7.2).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Crawler\Discovery\URL_Normalizer;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Crawler/Discovery/URL_Normalizer.php';

final class URL_Normalizer_Test extends TestCase {

	private const HOST = 'example.com';

	public function test_normalize_same_host_returns_https_canonical(): void {
		$n = new URL_Normalizer( 'example.com', 'https' );
		$this->assertSame( 'example.com', $n->get_canonical_host() );
		$this->assertSame( 'https://example.com/about', $n->normalize( 'https://example.com/about' ) );
		$this->assertSame( 'https://example.com/contact', $n->normalize( 'https://example.com/contact' ) );
	}

	public function test_normalize_strips_fragment(): void {
		$n = new URL_Normalizer( self::HOST );
		$this->assertSame( 'https://example.com/', $n->normalize( 'https://example.com/#section' ) );
		$this->assertSame( 'https://example.com/page', $n->normalize( 'https://example.com/page#anchor' ) );
	}

	public function test_normalize_strips_tracking_parameters(): void {
		$n = new URL_Normalizer( self::HOST );
		$this->assertSame( 'https://example.com/', $n->normalize( 'https://example.com/?utm_source=email&utm_campaign=test' ) );
		$with_foo = $n->normalize( 'https://example.com/p?fbclid=123&foo=bar' );
		$this->assertStringNotContainsString( 'fbclid', $with_foo );
		$this->assertStringContainsString( 'foo=bar', $with_foo );
	}

	public function test_normalize_external_host_returns_empty(): void {
		$n = new URL_Normalizer( self::HOST );
		$this->assertSame( '', $n->normalize( 'https://other.com/' ) );
		$this->assertSame( '', $n->normalize( 'https://sub.example.com/' ) );
	}

	public function test_dedup_key_deterministic(): void {
		$n = new URL_Normalizer( self::HOST );
		$url1 = 'https://example.com/about?utm_source=x';
		$url2 = 'https://example.com/about#top';
		$norm1 = $n->normalize( $url1 );
		$norm2 = $n->normalize( $url2 );
		$this->assertSame( $norm1, $norm2 );
		$this->assertSame( $n->dedup_key( $norm1 ), $n->dedup_key( $norm2 ) );
	}

	public function test_is_same_host_url(): void {
		$n = new URL_Normalizer( self::HOST );
		$this->assertTrue( $n->is_same_host_url( 'https://example.com/any' ) );
		$this->assertFalse( $n->is_same_host_url( 'https://other.com/' ) );
		$this->assertFalse( $n->is_same_host_url( 'https://www.example.com/' ) );
	}

	public function test_get_canonical_host(): void {
		$n = new URL_Normalizer( 'example.com', 'https' );
		$this->assertSame( 'example.com', $n->get_canonical_host() );
	}

	public function test_normalize_scheme_respected(): void {
		$n = new URL_Normalizer( self::HOST, 'http' );
		$this->assertSame( 'http://example.com/', $n->normalize( 'https://example.com/' ) );
	}
}
