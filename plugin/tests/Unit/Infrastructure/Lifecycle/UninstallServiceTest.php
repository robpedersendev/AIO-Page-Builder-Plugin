<?php
/**
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit\Infrastructure\Lifecycle;

use AIOPageBuilder\Domain\ExportRestore\Uninstall\Uninstall_Cleanup_Service;
use PHPUnit\Framework\TestCase;

\defined( 'ABSPATH' ) || \define( 'ABSPATH', __DIR__ . '/../../../wordpress/' );

$plugin_root = dirname( __DIR__, 4 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/ExportRestore/Uninstall/Uninstall_Cleanup_Service.php';

final class UninstallServiceTest extends TestCase {

	public function test_cleanup_if_confirmed_defaults_to_preserve(): void {
		\delete_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_UNINSTALL_CLEANUP_MODE );
		$svc = new Uninstall_Cleanup_Service( null );
		$out = $svc->cleanup_if_confirmed();
		$this->assertFalse( $out['cleanup_ran'] );
	}

	public function test_cleanup_if_confirmed_runs_when_confirmed_mode_set(): void {
		\update_option( \AIOPageBuilder\Infrastructure\Config\Option_Names::PB_UNINSTALL_CLEANUP_MODE, 'confirmed_cleanup', false );
		$svc = new Uninstall_Cleanup_Service( null );
		$out = $svc->cleanup_if_confirmed();
		$this->assertTrue( $out['cleanup_ran'] );
		$this->assertSame( 'confirmed_cleanup', $out['mode'] );
	}
}
