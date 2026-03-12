<?php
/**
 * Unit tests for executor locking and idempotency contract (spec §40.5, §40.6, §42; executor-locking-idempotency-contract.md, Prompt 078).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Idempotency_Helper;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Lock_States;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Lock_States.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Idempotency_Helper.php';

final class Executor_Locking_Idempotency_Contract_Test extends TestCase {

	public function test_queue_statuses_include_queued_running_completed_failed_dead(): void {
		$this->assertContains( Execution_Lock_States::STATUS_QUEUED, Execution_Lock_States::QUEUE_STATUSES );
		$this->assertContains( Execution_Lock_States::STATUS_RUNNING, Execution_Lock_States::QUEUE_STATUSES );
		$this->assertContains( Execution_Lock_States::STATUS_COMPLETED, Execution_Lock_States::QUEUE_STATUSES );
		$this->assertContains( Execution_Lock_States::STATUS_FAILED, Execution_Lock_States::QUEUE_STATUSES );
		$this->assertContains( Execution_Lock_States::STATUS_DEAD, Execution_Lock_States::QUEUE_STATUSES );
	}

	public function test_is_in_progress_returns_true_for_queued_running_retrying(): void {
		$this->assertTrue( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_QUEUED ) );
		$this->assertTrue( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_RUNNING ) );
		$this->assertTrue( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_RETRYING ) );
		$this->assertFalse( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_COMPLETED ) );
		$this->assertFalse( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_FAILED ) );
	}

	public function test_is_terminal_returns_true_for_completed_failed_cancelled_dead(): void {
		$this->assertTrue( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_COMPLETED ) );
		$this->assertTrue( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_FAILED ) );
		$this->assertTrue( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_DEAD ) );
		$this->assertFalse( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_RUNNING ) );
	}

	public function test_typically_holds_lock_only_for_running(): void {
		$this->assertTrue( Execution_Lock_States::typically_holds_lock( Execution_Lock_States::STATUS_RUNNING ) );
		$this->assertFalse( Execution_Lock_States::typically_holds_lock( Execution_Lock_States::STATUS_QUEUED ) );
		$this->assertFalse( Execution_Lock_States::typically_holds_lock( Execution_Lock_States::STATUS_COMPLETED ) );
	}

	public function test_stale_lock_default_max_run_seconds_is_positive(): void {
		$this->assertGreaterThan( 0, Execution_Lock_States::DEFAULT_MAX_RUN_SECONDS );
	}

	public function test_build_dedup_key_is_deterministic(): void {
		$target = array( 'plan_item_id' => 'plan_npc_0', 'template_ref' => array( 'type' => 'internal_key', 'value' => 't1' ) );
		$key1 = Execution_Idempotency_Helper::build_dedup_key( 'plan-1', 'plan_npc_0', Execution_Action_Types::CREATE_PAGE, $target );
		$key2 = Execution_Idempotency_Helper::build_dedup_key( 'plan-1', 'plan_npc_0', Execution_Action_Types::CREATE_PAGE, $target );
		$this->assertSame( $key1, $key2 );
		$this->assertStringStartsWith( 'idem:', $key1 );
	}

	public function test_build_dedup_key_different_target_produces_different_key(): void {
		$target1 = array( 'page_ref' => array( 'type' => 'post_id', 'value' => 42 ), 'plan_item_id' => 'plan_ep_0' );
		$target2 = array( 'page_ref' => array( 'type' => 'post_id', 'value' => 99 ), 'plan_item_id' => 'plan_ep_1' );
		$key1 = Execution_Idempotency_Helper::build_dedup_key( 'plan-1', 'plan_ep_0', Execution_Action_Types::REPLACE_PAGE, $target1 );
		$key2 = Execution_Idempotency_Helper::build_dedup_key( 'plan-1', 'plan_ep_1', Execution_Action_Types::REPLACE_PAGE, $target2 );
		$this->assertNotSame( $key1, $key2 );
	}

	public function test_build_dedup_key_from_envelope_uses_contract_keys(): void {
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_PLAN_ID => 'p1',
			Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID => 'item_0',
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::CREATE_PAGE,
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array( 'plan_item_id' => 'item_0', 'template_ref' => array( 'type' => 'internal_key', 'value' => 't1' ) ),
		);
		$key = Execution_Idempotency_Helper::build_dedup_key_from_envelope( $envelope );
		$this->assertStringStartsWith( 'idem:', $key );
		$this->assertStringContainsString( 'p1', $key );
		$this->assertStringContainsString( 'item_0', $key );
	}

	public function test_classify_duplicate_in_progress_returns_suppress(): void {
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_SUPPRESS, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_QUEUED ) );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_SUPPRESS, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_RUNNING ) );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_SUPPRESS, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_RETRYING ) );
	}

	public function test_classify_duplicate_failed_dead_returns_allow_new(): void {
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALLOW_NEW, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_FAILED ) );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALLOW_NEW, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_DEAD ) );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALLOW_NEW, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_CANCELLED ) );
	}

	public function test_classify_duplicate_completed_returns_already_completed_when_treat_idempotent(): void {
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALREADY_COMPLETED, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_COMPLETED, true ) );
	}

	public function test_classify_duplicate_completed_returns_allow_new_when_not_treat_idempotent(): void {
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALLOW_NEW, Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_COMPLETED, false ) );
	}

	public function test_duplicate_scenario_matrix_suppress_when_existing_queued(): void {
		$classification = Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_QUEUED );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_SUPPRESS, $classification );
	}

	public function test_duplicate_scenario_matrix_allow_new_when_existing_failed(): void {
		$classification = Execution_Idempotency_Helper::classify_duplicate( Execution_Lock_States::STATUS_FAILED );
		$this->assertSame( Execution_Idempotency_Helper::DUPLICATE_ALLOW_NEW, $classification );
	}

	public function test_stale_lock_scenario_running_is_in_progress_not_terminal(): void {
		$this->assertTrue( Execution_Lock_States::is_in_progress( Execution_Lock_States::STATUS_RUNNING ) );
		$this->assertFalse( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_RUNNING ) );
		$this->assertTrue( Execution_Lock_States::typically_holds_lock( Execution_Lock_States::STATUS_RUNNING ) );
	}

	public function test_stale_lock_scenario_completed_does_not_hold_lock(): void {
		$this->assertFalse( Execution_Lock_States::typically_holds_lock( Execution_Lock_States::STATUS_COMPLETED ) );
		$this->assertTrue( Execution_Lock_States::is_terminal( Execution_Lock_States::STATUS_COMPLETED ) );
	}

	public function test_scope_prefixes_are_non_empty(): void {
		$this->assertNotSame( '', Execution_Lock_States::SCOPE_PREFIX_JOB );
		$this->assertNotSame( '', Execution_Lock_States::SCOPE_PREFIX_PLAN );
		$this->assertNotSame( '', Execution_Lock_States::SCOPE_PREFIX_PLAN_ITEM );
		$this->assertNotSame( '', Execution_Lock_States::SCOPE_PREFIX_PAGE );
	}

	public function test_max_retry_count_default_is_positive(): void {
		$this->assertGreaterThan( 0, Execution_Lock_States::DEFAULT_MAX_RETRY_COUNT );
	}

	public function test_is_valid_status_accepts_all_queue_statuses(): void {
		foreach ( Execution_Lock_States::QUEUE_STATUSES as $status ) {
			$this->assertTrue( Execution_Lock_States::is_valid_status( $status ), "Status {$status} should be valid." );
		}
		$this->assertFalse( Execution_Lock_States::is_valid_status( 'invalid' ) );
	}
}
