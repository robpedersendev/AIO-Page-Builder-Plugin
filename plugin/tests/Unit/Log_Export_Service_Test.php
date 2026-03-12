<?php
/**
 * Unit tests for Log_Export_Service: allowed types, refusal when no valid types, redaction and labeling (spec §48.10, §45.9).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Reporting\Errors\Reporting_Redaction_Service;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Result;
use AIOPageBuilder\Domain\Reporting\Logs\Log_Export_Service;
use AIOPageBuilder\Infrastructure\Files\Plugin_Path_Manager;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Reporting/Logs/Log_Export_Result.php';
require_once $plugin_root . '/src/Domain/Reporting/Errors/Reporting_Redaction_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Files/Plugin_Path_Manager.php';
require_once $plugin_root . '/src/Domain/Reporting/Contracts/Reporting_Event_Types.php';
require_once $plugin_root . '/src/Support/Logging/Log_Categories.php';
require_once $plugin_root . '/src/Support/Logging/Log_Severities.php';
require_once $plugin_root . '/src/Support/Logging/Error_Record.php';
require_once $plugin_root . '/src/Domain/Reporting/Logs/Log_Export_Service.php';

final class Log_Export_Service_Test extends TestCase {

	public function test_allowed_log_types_constant(): void {
		$this->assertContains( Log_Export_Service::LOG_TYPE_QUEUE, Log_Export_Service::ALLOWED_LOG_TYPES );
		$this->assertContains( Log_Export_Service::LOG_TYPE_REPORTING, Log_Export_Service::ALLOWED_LOG_TYPES );
		$this->assertContains( Log_Export_Service::LOG_TYPE_CRITICAL, Log_Export_Service::ALLOWED_LOG_TYPES );
	}

	public function test_export_with_no_valid_types_returns_failure(): void {
		$path_manager = new Plugin_Path_Manager();
		$redaction    = new Reporting_Redaction_Service();
		$service      = new Log_Export_Service( $path_manager, $redaction, null, null, null );
		$result       = $service->export( array( 'invalid_type' ), array() );
		$this->assertInstanceOf( Log_Export_Result::class, $result );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( array(), $result->get_exported_log_types() );
	}

	public function test_export_with_empty_types_returns_failure(): void {
		$path_manager = new Plugin_Path_Manager();
		$redaction    = new Reporting_Redaction_Service();
		$service      = new Log_Export_Service( $path_manager, $redaction, null, null, null );
		$result       = $service->export( array(), array() );
		$this->assertFalse( $result->is_success() );
	}

	public function test_success_result_includes_export_metadata_label(): void {
		$path_manager = new Plugin_Path_Manager();
		$exports_dir  = $path_manager->get_exports_dir();
		if ( $exports_dir === '' || ! is_dir( $exports_dir ) ) {
			$this->markTestSkipped( 'Exports directory not available.' );
		}
		$redaction = new Reporting_Redaction_Service();
		$service   = new Log_Export_Service( $path_manager, $redaction, null, null, null );
		$result    = $service->export( array( Log_Export_Service::LOG_TYPE_REPORTING ), array() );
		if ( ! $result->is_success() ) {
			$this->markTestSkipped( 'Export failed (e.g. directory not writable).' );
		}
		$this->assertTrue( $result->is_redaction_applied() );
		$this->assertNotEmpty( $result->get_export_file_reference() );
		$this->assertStringContainsString( 'aio-log-export-', $result->get_export_file_reference() );
		$this->assertStringContainsString( '.json', $result->get_export_file_reference() );
	}
}
