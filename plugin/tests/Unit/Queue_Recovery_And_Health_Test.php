<?php
/**
 * Unit tests for Queue_Recovery_Service and Queue_Health_Summary_Builder (spec §42.4, §42.5, §49.11; Prompt 124).
 *
 * Covers retry-eligible recovery, cancel, unauthorized/ineligible refusal, recovery action logging,
 * stale-lock visibility, and queue health payload shape. Includes example queue-health and recovery-action payloads.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Queue\Queue_Health_Summary_Builder;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Recovery_Repository_Interface;
use AIOPageBuilder\Domain\Execution\Queue\Queue_Recovery_Service;
use AIOPageBuilder\Domain\Storage\Repositories\Job_Queue_Status;
use AIOPageBuilder\Infrastructure\Config\Option_Names;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Queue/Queue_Recovery_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Queue_Recovery_Service.php';
require_once $plugin_root . '/src/Domain/Execution/Queue/Queue_Health_Summary_Builder.php';
require_once $plugin_root . '/src/Domain/Storage/Repositories/Job_Queue_Status.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Infrastructure/Config/Option_Names.php';

/**
 * In-memory stub for Queue_Recovery_Repository_Interface (recovery tests).
 */
final class Stub_Recovery_Job_Repository implements Queue_Recovery_Repository_Interface {

	/** @var array<string, array<string, mixed>> */
	public $jobs = array();

	public function get_by_key( string $job_ref ): ?array {
		return isset( $this->jobs[ $job_ref ] ) ? $this->jobs[ $job_ref ] : null;
	}

	public function update_job_status( string $job_ref, string $status, ?string $failure_reason = null, ?string $started_at = null, ?string $completed_at = null ): bool {
		if ( ! isset( $this->jobs[ $job_ref ] ) ) {
			return false;
		}
		$this->jobs[ $job_ref ]['queue_status'] = $status;
		if ( $failure_reason !== null ) {
			$this->jobs[ $job_ref ]['failure_reason'] = $failure_reason;
		}
		if ( $completed_at !== null ) {
			$this->jobs[ $job_ref ]['completed_at'] = $completed_at;
		}
		return true;
	}

	public function reset_for_retry( string $job_ref ): bool {
		if ( ! isset( $this->jobs[ $job_ref ] ) ) {
			return false;
		}
		$this->jobs[ $job_ref ]['queue_status'] = Job_Queue_Status::PENDING;
		$this->jobs[ $job_ref ]['lock_token']   = '';
		return true;
	}
}

/**
 * Stub repository for health builder: list_by_status( string, int, int ): array.
 */
final class Stub_Health_Job_Repository {

	/** @var array<string, list<array<string, mixed>>> */
	public $by_status = array();

	public function list_by_status( string $status, int $limit = 50, int $offset = 0 ): array {
		$rows = isset( $this->by_status[ $status ] ) ? $this->by_status[ $status ] : array();
		return array_slice( $rows, $offset, $limit > 0 ? $limit : 999 );
	}
}

final class Queue_Recovery_And_Health_Test extends TestCase {

	/** Example queue-health payload (Queue_Health_Summary_Builder::build()). */
	public static function example_queue_health_payload(): array {
		return array(
			'total_pending'           => 3,
			'total_running'           => 1,
			'total_retrying'          => 0,
			'total_failed'            => 2,
			'total_completed'         => 10,
			'total_cancelled'         => 0,
			'stale_lock_count'        => 1,
			'stale_lock_job_refs'     => array( 'job_replace_plan_1_20250312100000_456' ),
			'long_running_count'      => 1,
			'long_running_job_refs'   => array( 'job_replace_plan_1_20250312100000_456' ),
			'retry_eligible_count'    => 2,
			'retry_eligible_job_refs' => array( 'job_create_plan_0_20250312100000_123', 'job_replace_plan_2_20250312100100_789' ),
			'bottleneck_warning'      => false,
			'summary_message'         => '1 stale lock(s) detected. 2 failed job(s) eligible for retry.',
		);
	}

	/** Example recovery-action result payload (Queue_Recovery_Service::retry_job or cancel_job). */
	public static function example_recovery_action_result_payload(): array {
		return array(
			'success'         => true,
			'action'          => 'retry',
			'job_ref'         => 'job_create_plan_0_20250312100000_123',
			'message'         => 'Job queued for retry.',
			'previous_status' => 'failed',
		);
	}

	public function test_retry_eligible_job_recovery_succeeds(): void {
		$repo                      = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_retry_1'] = array(
			'job_ref'      => 'job_retry_1',
			'job_type'     => 'create_page',
			'queue_status' => Job_Queue_Status::FAILED,
			'retry_count'  => 2,
		);
		$service                   = new Queue_Recovery_Service( $repo, null );
		$result                    = $service->retry_job( 'job_retry_1', 'user:1' );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'retry', $result['action'] );
		$this->assertSame( 'job_retry_1', $result['job_ref'] );
		$this->assertSame( 'failed', $result['previous_status'] );
		$this->assertSame( Job_Queue_Status::PENDING, $repo->jobs['job_retry_1']['queue_status'] );
		$this->assertSame( '', $repo->jobs['job_retry_1']['lock_token'] ?? '' );
	}

	public function test_retry_ineligible_job_refused_not_failed(): void {
		$repo                        = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_pending_1'] = array(
			'job_ref'      => 'job_pending_1',
			'job_type'     => 'create_page',
			'queue_status' => Job_Queue_Status::PENDING,
		);
		$service                     = new Queue_Recovery_Service( $repo, null );
		$result                      = $service->retry_job( 'job_pending_1', 'user:1' );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Only failed jobs can be retried.', $result['message'] );
		$this->assertSame( Job_Queue_Status::PENDING, $repo->jobs['job_pending_1']['queue_status'] );
	}

	public function test_retry_ineligible_job_type_refused(): void {
		$repo                        = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_unknown_1'] = array(
			'job_ref'      => 'job_unknown_1',
			'job_type'     => 'unknown_type',
			'queue_status' => Job_Queue_Status::FAILED,
			'retry_count'  => 0,
		);
		$service                     = new Queue_Recovery_Service( $repo, null );
		$result                      = $service->retry_job( 'job_unknown_1', 'user:1' );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Job type does not allow manual retry.', $result['message'] );
	}

	public function test_retry_job_not_found_refused(): void {
		$repo    = new Stub_Recovery_Job_Repository();
		$service = new Queue_Recovery_Service( $repo, null );
		$result  = $service->retry_job( 'nonexistent', 'user:1' );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Job not found.', $result['message'] );
	}

	public function test_cancel_job_succeeds(): void {
		$repo                       = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_cancel_1'] = array(
			'job_ref'      => 'job_cancel_1',
			'queue_status' => Job_Queue_Status::PENDING,
		);
		$service                    = new Queue_Recovery_Service( $repo, null );
		$result                     = $service->cancel_job( 'job_cancel_1', 'user:1' );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'cancel', $result['action'] );
		$this->assertSame( Job_Queue_Status::CANCELLED, $repo->jobs['job_cancel_1']['queue_status'] );
	}

	public function test_cancel_completed_job_refused(): void {
		$repo                     = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_done_1'] = array(
			'job_ref'      => 'job_done_1',
			'queue_status' => Job_Queue_Status::COMPLETED,
		);
		$service                  = new Queue_Recovery_Service( $repo, null );
		$result                   = $service->cancel_job( 'job_done_1', 'user:1' );
		$this->assertFalse( $result['success'] );
		$this->assertSame( 'Job cannot be cancelled in its current state.', $result['message'] );
	}

	public function test_recovery_action_logging_writes_audit_option(): void {
		$repo                    = new Stub_Recovery_Job_Repository();
		$repo->jobs['job_log_1'] = array(
			'job_ref'      => 'job_log_1',
			'job_type'     => 'replace_page',
			'queue_status' => Job_Queue_Status::FAILED,
			'retry_count'  => 1,
		);
		$option_before           = \get_option( 'aio_page_builder_queue_recovery_audit', array() );
		$service                 = new Queue_Recovery_Service( $repo, null );
		$service->retry_job( 'job_log_1', 'user:42' );
		$option_after = \get_option( Option_Names::QUEUE_RECOVERY_AUDIT, array() );
		$this->assertIsArray( $option_after );
		$this->assertGreaterThanOrEqual( count( $option_before ), count( $option_after ) );
		$last = end( $option_after );
		$this->assertIsArray( $last );
		$this->assertSame( 'retry', $last['action'] ?? '' );
		$this->assertSame( 'job_log_1', $last['job_ref'] ?? '' );
		$this->assertSame( 'failed', $last['previous_status'] ?? '' );
		$this->assertTrue( $last['success'] ?? false );
	}

	public function test_health_builder_stale_lock_visibility(): void {
		$repo                         = new Stub_Health_Job_Repository();
		$old_started                  = gmdate( 'c', time() - 7200 );
		$repo->by_status['running']   = array(
			array(
				'job_ref'      => 'job_stale_1',
				'job_type'     => 'create_page',
				'queue_status' => 'running',
				'started_at'   => $old_started,
			),
		);
		$repo->by_status['pending']   = array();
		$repo->by_status['retrying']  = array();
		$repo->by_status['failed']    = array();
		$repo->by_status['completed'] = array();
		$repo->by_status['cancelled'] = array();
		$builder                      = new Queue_Health_Summary_Builder( $repo );
		$health                       = $builder->build();
		$this->assertSame( 1, $health['stale_lock_count'] );
		$this->assertContains( 'job_stale_1', $health['stale_lock_job_refs'] );
		$this->assertSame( 1, $health['total_running'] );
	}

	public function test_health_builder_empty_when_no_repository(): void {
		$builder = new Queue_Health_Summary_Builder( null );
		$health  = $builder->build();
		$this->assertSame( 0, $health['total_pending'] );
		$this->assertSame( 0, $health['stale_lock_count'] );
		$this->assertSame( array(), $health['stale_lock_job_refs'] );
		$this->assertSame( 0, $health['retry_eligible_count'] );
	}

	public function test_example_queue_health_payload_shape(): void {
		$example = self::example_queue_health_payload();
		$this->assertArrayHasKey( 'total_pending', $example );
		$this->assertArrayHasKey( 'stale_lock_count', $example );
		$this->assertArrayHasKey( 'stale_lock_job_refs', $example );
		$this->assertArrayHasKey( 'retry_eligible_count', $example );
		$this->assertArrayHasKey( 'bottleneck_warning', $example );
		$this->assertArrayHasKey( 'summary_message', $example );
		$this->assertIsArray( $example['stale_lock_job_refs'] );
	}

	public function test_example_recovery_action_result_payload_shape(): void {
		$example = self::example_recovery_action_result_payload();
		$this->assertArrayHasKey( 'success', $example );
		$this->assertArrayHasKey( 'action', $example );
		$this->assertArrayHasKey( 'job_ref', $example );
		$this->assertArrayHasKey( 'message', $example );
		$this->assertArrayHasKey( 'previous_status', $example );
	}
}
