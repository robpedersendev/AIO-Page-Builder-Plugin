<?php
/**
 * Unit tests for Null_Logger: accepts records without throwing, no persistence.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Support\Logging\Error_Record;
use AIOPageBuilder\Support\Logging\Log_Categories;
use AIOPageBuilder\Support\Logging\Log_Severities;
use AIOPageBuilder\Support\Logging\Null_Logger;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';
require_once $plugin_root . '/src/Support/Logging/Error_Record.php';
require_once $plugin_root . '/src/Support/Logging/Logger_Interface.php';
require_once $plugin_root . '/src/Support/Logging/Null_Logger.php';

/**
 * Null logger implements interface and safely no-ops.
 */
final class Null_Logger_Test extends TestCase {

	public function test_implements_logger_interface(): void {
		$logger = new Null_Logger();
		$this->assertInstanceOf( \AIOPageBuilder\Support\Logging\Logger_Interface::class, $logger );
	}

	public function test_log_accepts_record_without_throwing(): void {
		$logger = new Null_Logger();
		$record = new Error_Record( 'id', Log_Categories::EXECUTION, Log_Severities::INFO, 'Test.' );
		$logger->log( $record );
		$this->assertTrue( true, 'log() must not throw' );
	}

	public function test_log_accepts_multiple_records(): void {
		$logger = new Null_Logger();
		for ( $i = 0; $i < 3; $i++ ) {
			$record = new Error_Record( "id-{$i}", Log_Categories::QUEUE, Log_Severities::WARNING, "Msg {$i}." );
			$logger->log( $record );
		}
		$this->assertTrue( true, 'multiple log() calls must not throw' );
	}
}
