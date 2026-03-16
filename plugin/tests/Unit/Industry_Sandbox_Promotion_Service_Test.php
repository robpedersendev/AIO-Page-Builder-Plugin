<?php
/**
 * Unit tests for Industry_Sandbox_Promotion_Service: prerequisites and release-ready summary (Prompt 454).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Sandbox_Promotion_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Sandbox_Promotion_Service.php';

final class Industry_Sandbox_Promotion_Service_Test extends TestCase {

	public function test_check_prerequisites_met_when_no_errors(): void {
		$service = new Industry_Sandbox_Promotion_Service();
		$out = $service->check_prerequisites( array( 'summary' => array( 'lint_errors' => 0, 'health_errors' => 0 ) ) );
		$this->assertTrue( $out[ Industry_Sandbox_Promotion_Service::RESULT_PREREQUISITES_MET ] );
		$this->assertSame( array(), $out[ Industry_Sandbox_Promotion_Service::RESULT_MISSING_REQUIREMENTS ] );
	}

	public function test_check_prerequisites_not_met_when_lint_errors(): void {
		$service = new Industry_Sandbox_Promotion_Service();
		$out = $service->check_prerequisites( array( 'summary' => array( 'lint_errors' => 2, 'health_errors' => 0 ) ) );
		$this->assertFalse( $out[ Industry_Sandbox_Promotion_Service::RESULT_PREREQUISITES_MET ] );
		$this->assertCount( 1, $out[ Industry_Sandbox_Promotion_Service::RESULT_MISSING_REQUIREMENTS ] );
	}

	public function test_check_prerequisites_not_met_when_health_errors(): void {
		$service = new Industry_Sandbox_Promotion_Service();
		$out = $service->check_prerequisites( array( 'summary' => array( 'lint_errors' => 0, 'health_errors' => 1 ) ) );
		$this->assertFalse( $out[ Industry_Sandbox_Promotion_Service::RESULT_PREREQUISITES_MET ] );
		$this->assertCount( 1, $out[ Industry_Sandbox_Promotion_Service::RESULT_MISSING_REQUIREMENTS ] );
	}

	public function test_get_release_ready_summary_does_not_mutate_live_state(): void {
		$service = new Industry_Sandbox_Promotion_Service();
		$packs = array( array( 'industry_key' => 'realtor', 'name' => 'Realtor', 'version_marker' => '1' ) );
		$bundles = array( array( 'bundle_key' => 'realtor_starter', 'industry_key' => 'realtor', 'version_marker' => '1' ) );
		$dry_run = array( 'summary' => array( 'lint_errors' => 0, 'health_errors' => 0 ) );
		$out = $service->get_release_ready_summary( $packs, $bundles, $dry_run );
		$this->assertArrayHasKey( Industry_Sandbox_Promotion_Service::RESULT_PACK_KEYS, $out );
		$this->assertArrayHasKey( Industry_Sandbox_Promotion_Service::RESULT_BUNDLE_KEYS, $out );
		$this->assertSame( array( 'realtor' ), $out[ Industry_Sandbox_Promotion_Service::RESULT_PACK_KEYS ] );
		$this->assertSame( array( 'realtor_starter' ), $out[ Industry_Sandbox_Promotion_Service::RESULT_BUNDLE_KEYS ] );
		$this->assertTrue( $out['prerequisites_met'] );
	}
}
