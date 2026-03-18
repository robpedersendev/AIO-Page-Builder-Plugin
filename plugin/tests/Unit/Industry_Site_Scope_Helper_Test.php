<?php
/**
 * Unit tests for Industry_Site_Scope_Helper: site-local cache key on multisite (Prompt 397).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Industry_Site_Scope_Helper;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Industry_Site_Scope_Helper.php';

final class Industry_Site_Scope_Helper_Test extends TestCase {

	public function test_scope_cache_key_returns_prefixed_key(): void {
		$key = Industry_Site_Scope_Helper::scope_cache_key( 'recommendation_preview' );
		$this->assertStringStartsWith( 'aio_industry_', $key );
		$this->assertStringContainsString( 'recommendation_preview', $key );
	}

	public function test_scope_cache_key_sanitizes_invalid_chars(): void {
		$key = Industry_Site_Scope_Helper::scope_cache_key( 'foo-bar.baz' );
		$this->assertStringStartsWith( 'aio_industry_', $key );
		$this->assertMatchesRegularExpression( '/^aio_industry_[a-z0-9_]+$/i', $key );
	}

	public function test_scope_cache_key_empty_base_becomes_industry(): void {
		$key = Industry_Site_Scope_Helper::scope_cache_key( '' );
		$this->assertSame( 'aio_industry_industry', $key );
	}

	public function test_current_blog_id_returns_int(): void {
		$id = Industry_Site_Scope_Helper::current_blog_id();
		$this->assertIsInt( $id );
		$this->assertGreaterThanOrEqual( 1, $id );
	}
}
