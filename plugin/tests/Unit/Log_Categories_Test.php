<?php
/**
 * Unit tests for Log_Categories (spec §45.1).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Support\Logging\Log_Categories;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';

/**
 * Categories: validation, dependency, execution, provider, queue, reporting, import_export, security, compatibility.
 */
final class Log_Categories_Test extends TestCase {

	public function test_all_returns_nine_categories(): void {
		$all = Log_Categories::all();
		$this->assertCount( 9, $all );
		$this->assertContains( Log_Categories::VALIDATION, $all );
		$this->assertContains( Log_Categories::PROVIDER, $all );
		$this->assertContains( Log_Categories::IMPORT_EXPORT, $all );
	}

	public function test_is_valid_accepts_all_constants(): void {
		foreach ( Log_Categories::all() as $cat ) {
			$this->assertTrue( Log_Categories::is_valid( $cat ), "Category {$cat} should be valid" );
		}
	}

	public function test_is_valid_rejects_unknown(): void {
		$this->assertFalse( Log_Categories::is_valid( 'misc' ) );
		$this->assertFalse( Log_Categories::is_valid( '' ) );
	}
}
