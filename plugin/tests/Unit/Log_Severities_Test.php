<?php
/**
 * Unit tests for Log_Severities (spec §45.2).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Support\Logging\Log_Severities;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';

/**
 * Severity levels: info, warning, error, critical.
 */
final class Log_Severities_Test extends TestCase {

	public function test_all_returns_four_severities(): void {
		$all = Log_Severities::all();
		$this->assertCount( 4, $all );
		$this->assertContains( Log_Severities::INFO, $all );
		$this->assertContains( Log_Severities::WARNING, $all );
		$this->assertContains( Log_Severities::ERROR, $all );
		$this->assertContains( Log_Severities::CRITICAL, $all );
	}

	public function test_is_valid_accepts_all_constants(): void {
		$this->assertTrue( Log_Severities::isValid( Log_Severities::INFO ) );
		$this->assertTrue( Log_Severities::isValid( Log_Severities::WARNING ) );
		$this->assertTrue( Log_Severities::isValid( Log_Severities::ERROR ) );
		$this->assertTrue( Log_Severities::isValid( Log_Severities::CRITICAL ) );
	}

	public function test_is_valid_rejects_unknown(): void {
		$this->assertFalse( Log_Severities::isValid( 'debug' ) );
		$this->assertFalse( Log_Severities::isValid( '' ) );
	}
}
