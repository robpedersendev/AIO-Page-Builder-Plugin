<?php
/**
 * Tests for Industry_Author_Sandbox_Service (Prompt 444).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Industry\Reporting\Industry_Author_Sandbox_Service;
use PHPUnit\Framework\TestCase;

/**
 * @group industry
 */
final class Industry_Author_Sandbox_Service_Test extends TestCase {

	public static function setUpBeforeClass(): void {
		$plugin_root = \dirname( __DIR__, 2 );
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Registry.php';
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Starter_Bundle_Registry.php';
		require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Health_Check_Service.php';
		require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Definition_Linter.php';
		require_once $plugin_root . '/src/Domain/Industry/Reporting/Industry_Author_Sandbox_Service.php';
	}

	public function test_run_dry_run_returns_expected_keys(): void {
		$service = new Industry_Author_Sandbox_Service();
		$result  = $service->run_dry_run( array(), array() );
		$this->assertArrayHasKey( 'lint_result', $result );
		$this->assertArrayHasKey( 'health_result', $result );
		$this->assertArrayHasKey( 'summary', $result );
		$this->assertArrayHasKey( 'errors', $result['lint_result'] );
		$this->assertArrayHasKey( 'warnings', $result['lint_result'] );
		$this->assertArrayHasKey( 'summary', $result['lint_result'] );
		$this->assertArrayHasKey( 'errors', $result['health_result'] );
		$this->assertArrayHasKey( 'warnings', $result['health_result'] );
		$this->assertArrayHasKey( 'lint_errors', $result['summary'] );
		$this->assertArrayHasKey( 'lint_warnings', $result['summary'] );
		$this->assertArrayHasKey( 'health_errors', $result['summary'] );
		$this->assertArrayHasKey( 'health_warnings', $result['summary'] );
	}

	public function test_run_dry_run_with_valid_pack_produces_no_lint_errors(): void {
		$plugin_root = \dirname( __DIR__, 2 );
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Validator.php';
		require_once $plugin_root . '/src/Domain/Industry/Registry/Industry_Pack_Schema.php';
		$valid_pack = array(
			'industry_key'   => 'test_sandbox_pack',
			'name'           => 'Test Sandbox Pack',
			'summary'        => 'For unit test.',
			'status'         => 'active',
			'version_marker' => '1',
		);
		$service = new Industry_Author_Sandbox_Service();
		$result  = $service->run_dry_run( array( $valid_pack ), array() );
		$this->assertIsArray( $result['lint_result']['errors'] );
		$this->assertGreaterThanOrEqual( 0, $result['summary']['lint_errors'] );
	}
}
