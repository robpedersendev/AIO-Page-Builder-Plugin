<?php
/**
 * Unit tests for Token_Set_Job_Service (spec §35, §40.2; Prompt 640).
 *
 * Covers successful apply (plugin-owned option + value stored), validation failure, invalid group.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Jobs\Token_Set_Job_Service;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Token_Set_Result.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Token_Set_Job_Service_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Jobs/Token_Set_Job_Service.php';

final class Token_Set_Job_Service_Test extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		\delete_option( Option_Names::APPLIED_DESIGN_TOKENS );
	}

	protected function tearDown(): void {
		\delete_option( Option_Names::APPLIED_DESIGN_TOKENS );
		parent::tearDown();
	}

	public function test_run_success_stores_value_in_plugin_option(): void {
		$service  = new Token_Set_Job_Service();
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'token_group'    => 'color',
				'token_name'     => 'primary',
				'proposed_value' => '#2563eb',
			),
		);
		$result   = $service->run( $envelope );
		$this->assertTrue( $result->is_success() );
		$stored = \get_option( Option_Names::APPLIED_DESIGN_TOKENS, array() );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'color', $stored );
		$this->assertArrayHasKey( 'primary', $stored['color'] );
		$this->assertSame( '#2563eb', $stored['color']['primary'] );
	}

	public function test_run_validation_failure_missing_group_does_not_write(): void {
		$service  = new Token_Set_Job_Service();
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'token_name'     => 'primary',
				'proposed_value' => '#333',
			),
		);
		$result   = $service->run( $envelope );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( array(), \get_option( Option_Names::APPLIED_DESIGN_TOKENS, array() ) );
	}

	public function test_run_invalid_token_group_returns_failure(): void {
		$service  = new Token_Set_Job_Service();
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'token_group'    => 'invalid_group',
				'token_name'     => 'x',
				'proposed_value' => 'y',
			),
		);
		$result   = $service->run( $envelope );
		$this->assertFalse( $result->is_success() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	public function test_run_normalizes_proposed_value_to_string(): void {
		$service  = new Token_Set_Job_Service();
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(
				'token_group'    => 'spacing',
				'token_name'     => 'unit',
				'proposed_value' => 0.25,
			),
		);
		$result   = $service->run( $envelope );
		$this->assertTrue( $result->is_success() );
		$stored = \get_option( Option_Names::APPLIED_DESIGN_TOKENS, array() );
		$this->assertSame( '0.25', $stored['spacing']['unit'] );
	}
}
