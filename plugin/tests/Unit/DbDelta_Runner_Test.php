<?php
/**
 * Unit tests for DbDelta_Runner: success when no error, sanitized error on failure (spec §11).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Tables\DbDelta_Runner;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Tables/DbDelta_Runner.php';

final class DbDelta_Runner_Test extends TestCase {

	private object $wpdb_stub;

	protected function setUp(): void {
		parent::setUp();
		$this->wpdb_stub = new class() {
			public string $last_error = '';
			public function suppress_errors( bool $suppress = true ): void {
			}
		};
	}

	public function test_run_returns_success_when_no_last_error(): void {
		$runner = new DbDelta_Runner();
		$result = $runner->run( $this->wpdb_stub, 'CREATE TABLE `wp_test` ( `id` bigint(20) NOT NULL, PRIMARY KEY  (`id`) );' );
		$this->assertTrue( $result['success'] );
		$this->assertSame( '', $result['error'] );
	}

	public function test_run_returns_failure_and_sanitized_error_when_last_error_set(): void {
		$this->wpdb_stub->last_error = '  MySQL said: something <script>bad</script>  ';
		$runner                      = new DbDelta_Runner();
		$result                      = $runner->run( $this->wpdb_stub, 'CREATE TABLE `wp_test` ( `id` bigint(20) NOT NULL, PRIMARY KEY  (`id`) );' );
		$this->assertFalse( $result['success'] );
		$this->assertStringNotContainsString( '<script>', $result['error'] );
		$this->assertNotEmpty( $result['error'] );
	}
}
