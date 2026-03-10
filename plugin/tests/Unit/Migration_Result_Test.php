<?php
/**
 * Unit tests for Migration_Result: status, formatting, safe_retry (migration-contract.md).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Storage\Migrations\Migration_Result;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Storage/Migrations/Migration_Contract.php';

/**
 * Result status, to_array formatting, safe_retry eligibility.
 */
final class Migration_Result_Test extends TestCase {

	public function test_success_result_is_success(): void {
		$r = new Migration_Result( Migration_Result::STATUS_SUCCESS, 'Done.', array(), true, 'mig_1' );
		$this->assertTrue( $r->is_success() );
		$this->assertFalse( $r->is_failure() );
	}

	public function test_failure_result_is_failure(): void {
		$r = new Migration_Result( Migration_Result::STATUS_FAILURE, 'Table creation failed.', array(), false, 'mig_1' );
		$this->assertFalse( $r->is_success() );
		$this->assertTrue( $r->is_failure() );
	}

	public function test_to_array_has_required_keys(): void {
		$r = new Migration_Result( Migration_Result::STATUS_WARNING, 'Warning.', array( 'note1' ), true, 'id_2' );
		$arr = $r->to_array();
		$this->assertArrayHasKey( 'status', $arr );
		$this->assertArrayHasKey( 'message', $arr );
		$this->assertArrayHasKey( 'notes', $arr );
		$this->assertArrayHasKey( 'safe_retry', $arr );
		$this->assertArrayHasKey( 'migration_id', $arr );
		$this->assertSame( Migration_Result::STATUS_WARNING, $arr['status'] );
		$this->assertSame( 'Warning.', $arr['message'] );
		$this->assertSame( array( 'note1' ), $arr['notes'] );
		$this->assertTrue( $arr['safe_retry'] );
		$this->assertSame( 'id_2', $arr['migration_id'] );
	}

	public function test_skipped_status(): void {
		$r = new Migration_Result( Migration_Result::STATUS_SKIPPED, 'Not applicable.', array(), true, null );
		$this->assertFalse( $r->is_failure() );
		$this->assertFalse( $r->is_success() );
	}

	public function test_safe_retry_eligibility_flag(): void {
		$safe   = new Migration_Result( Migration_Result::STATUS_FAILURE, 'Err', array(), true, 'm1' );
		$unsafe = new Migration_Result( Migration_Result::STATUS_FAILURE, 'Err', array(), false, 'm2' );
		$this->assertTrue( $safe->safe_retry );
		$this->assertFalse( $unsafe->safe_retry );
	}
}
