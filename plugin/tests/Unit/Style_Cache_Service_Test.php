<?php
/**
 * Unit tests for Style_Cache_Service (Prompt 256): version and invalidation.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Preview\Preview_Cache_Service;
use AIOPageBuilder\Domain\Styling\Style_Cache_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Styling/Style_Cache_Service.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Cache_Record.php';
require_once $plugin_root . '/src/Domain/Preview/Preview_Cache_Service.php';

final class Style_Cache_Service_Test extends TestCase {

	private string $option_key = Style_Cache_Service::OPTION_VERSION;

	protected function setUp(): void {
		parent::setUp();
		\delete_option( $this->option_key );
	}

	protected function tearDown(): void {
		\delete_option( $this->option_key );
		parent::tearDown();
	}

	public function test_get_version_returns_non_empty(): void {
		$svc = new Style_Cache_Service( null );
		$this->assertNotSame( '', $svc->get_version() );
	}

	public function test_invalidate_sets_version_option(): void {
		$svc = new Style_Cache_Service( null );
		$svc->invalidate();
		$after = \get_option( $this->option_key, null );
		$this->assertNotNull( $after );
		$this->assertIsString( $after );
		$this->assertNotSame( '', $after );
	}

	public function test_invalidate_calls_preview_cache_invalidate_all(): void {
		$preview = new Preview_Cache_Service( 2 );
		$record  = new \AIOPageBuilder\Domain\Preview\Preview_Cache_Record( 'aio_preview_test', 'section', 'sec_hero', 'hash', '<div>test</div>', time(), false, 'none' );
		$preview->set( $record );
		$this->assertSame( 1, $preview->get_cache_entry_count() );
		$svc = new Style_Cache_Service( $preview );
		$svc->invalidate();
		$this->assertSame( 0, $preview->get_cache_entry_count() );
	}

	public function test_get_version_after_invalidate_returns_new_value(): void {
		$svc = new Style_Cache_Service( null );
		$svc->invalidate();
		$ver = $svc->get_version();
		$this->assertNotSame( '', $ver );
		$this->assertIsString( $ver );
	}
}
