<?php
/**
 * Unit tests for Stub_Execution_Handler (SPR-008). Asserts fallback result shape and message.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Executor\Stub_Execution_Handler;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Executor/Execution_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Executor/Stub_Execution_Handler.php';

final class Stub_Execution_Handler_Test extends TestCase {

	public function test_execute_returns_failure_shape_with_expected_message(): void {
		$handler = new Stub_Execution_Handler( 'update_page_metadata' );
		$result  = $handler->execute( array() );
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'success', $result );
		$this->assertArrayHasKey( 'message', $result );
		$this->assertArrayHasKey( 'artifacts', $result );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'This action type is not available in this version.', $result['message'] );
		$this->assertSame( array(), $result['artifacts'] );
	}

	public function test_execute_with_empty_action_type_returns_same_message(): void {
		$handler = new Stub_Execution_Handler();
		$result  = $handler->execute( array( 'action_type' => 'unknown' ) );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'This action type is not available in this version.', $result['message'] );
	}
}
