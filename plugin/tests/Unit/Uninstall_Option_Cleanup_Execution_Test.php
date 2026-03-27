<?php
/**
 * Confirmed-uninstall option deletion: declared keys, foreign preservation, empty vs populated state.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service;
use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Option_Cleanup_Helper;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Infrastructure/Db/Wpdb_Prepared_Results.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Option_Registry.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Dynamic_Option_Ownership.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Option_Cleanup_Helper.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Cleanup_Service.php';
require_once $plugin_root . '/src/Domain/Reporting/Heartbeat/Heartbeat_Scheduler.php';

final class Uninstall_Option_Cleanup_Execution_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['_aio_test_options'] );
		parent::tearDown();
	}

	public function test_delete_declared_options_removes_seeded_plugin_keys_and_preserves_foreign(): void {
		$GLOBALS['_aio_test_options'] = array();
		\update_option( Option_Names::MAIN_SETTINGS, array( 'placeholder' => 'value' ) );
		\update_option( Option_Names::PB_AI_PROVIDERS, array( 'placeholder_provider' => array() ) );
		\update_option( 'foreign_theme_or_other_plugin_option', 'keep-me' );

		$removed = Uninstall_Option_Cleanup_Helper::delete_declared_options();
		$this->assertGreaterThan( 0, $removed );

		$this->assertArrayNotHasKey( Option_Names::MAIN_SETTINGS, $GLOBALS['_aio_test_options'] );
		$this->assertArrayNotHasKey( Option_Names::PB_AI_PROVIDERS, $GLOBALS['_aio_test_options'] );
		$this->assertSame( 'keep-me', \get_option( 'foreign_theme_or_other_plugin_option' ) );
	}

	public function test_delete_declared_options_runs_when_options_absent(): void {
		$GLOBALS['_aio_test_options'] = array();
		// * Test bootstrap delete_option() reports success even when unset; count is not authoritative here.
		Uninstall_Option_Cleanup_Helper::delete_declared_options();
		$this->assertArrayNotHasKey( Option_Names::MAIN_SETTINGS, $GLOBALS['_aio_test_options'] );
	}

	public function test_cleanup_if_confirmed_skips_when_mode_not_confirmed(): void {
		$GLOBALS['_aio_test_options'] = array();
		\update_option( Option_Names::MAIN_SETTINGS, array( 'x' => 1 ) );
		$svc = new Uninstall_Cleanup_Service( null );
		$out = $svc->cleanup_if_confirmed();
		$this->assertFalse( $out['cleanup_ran'] );
		$this->assertArrayHasKey( Option_Names::MAIN_SETTINGS, $GLOBALS['_aio_test_options'] );
	}

	public function test_cleanup_if_confirmed_runs_and_removes_declared_option_subset(): void {
		$GLOBALS['_aio_test_options'] = array();
		\update_option( Option_Names::PB_UNINSTALL_CLEANUP_MODE, 'confirmed_cleanup' );
		\update_option( Option_Names::QUEUE_RECOVERY_AUDIT, array( 'placeholder_audit' => true ) );
		\update_option( 'unrelated_survivor', 'stay' );

		$svc = new Uninstall_Cleanup_Service( null );
		$out = $svc->cleanup_if_confirmed();
		$this->assertTrue( $out['cleanup_ran'] );
		$this->assertArrayHasKey( 'cleanup_result', $out );
		$this->assertSame( 'stay', \get_option( 'unrelated_survivor' ) );
		$this->assertArrayNotHasKey( Option_Names::QUEUE_RECOVERY_AUDIT, $GLOBALS['_aio_test_options'] );
	}
}
