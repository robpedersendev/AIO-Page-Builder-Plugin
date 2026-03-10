<?php
/**
 * Unit tests for Lifecycle_Manager and Lifecycle_Result.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Bootstrap\Lifecycle_Manager;
use AIOPageBuilder\Bootstrap\Lifecycle_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Bootstrap/Lifecycle_Manager.php';

/**
 * Tests lifecycle orchestration: activation/deactivation/uninstall call manager, results have correct shape.
 */
final class Lifecycle_Manager_Test extends TestCase {

	public function test_activate_returns_lifecycle_result(): void {
		$manager = new Lifecycle_Manager();
		$result  = $manager->activate();
		$this->assertInstanceOf( Lifecycle_Result::class, $result );
		// In unit test env required plugins are missing, so activation typically returns blocking.
		if ( $result->is_blocking() ) {
			$this->assertArrayHasKey( 'validation_results', $result->details );
		} else {
			$this->assertArrayHasKey( 'phases_run', $result->details );
		}
	}

	public function test_deactivate_returns_success_result(): void {
		$manager = new Lifecycle_Manager();
		$result  = $manager->deactivate();
		$this->assertInstanceOf( Lifecycle_Result::class, $result );
		$this->assertSame( Lifecycle_Result::STATUS_SUCCESS, $result->status );
		$this->assertFalse( $result->is_blocking() );
	}

	public function test_uninstall_returns_success_result(): void {
		$manager = new Lifecycle_Manager();
		$result  = $manager->uninstall();
		$this->assertInstanceOf( Lifecycle_Result::class, $result );
		$this->assertSame( Lifecycle_Result::STATUS_SUCCESS, $result->status );
		$this->assertFalse( $result->is_blocking() );
	}

	public function test_blocking_result_is_blocking(): void {
		$result = new Lifecycle_Result( Lifecycle_Result::STATUS_BLOCKING_FAILURE, 'Test failure', 'test_phase' );
		$this->assertTrue( $result->is_blocking() );
		$this->assertSame( 'Test failure', $result->message );
		$this->assertSame( 'test_phase', $result->phase );
	}

	public function test_result_to_array_has_expected_shape(): void {
		$result = new Lifecycle_Result( Lifecycle_Result::STATUS_WARNING, 'Warning', 'phase', array( 'key' => 'value' ) );
		$arr    = $result->to_array();
		$this->assertSame( Lifecycle_Result::STATUS_WARNING, $arr['status'] );
		$this->assertSame( 'Warning', $arr['message'] );
		$this->assertSame( 'phase', $arr['phase'] );
		$this->assertSame( array( 'key' => 'value' ), $arr['details'] );
	}
}
