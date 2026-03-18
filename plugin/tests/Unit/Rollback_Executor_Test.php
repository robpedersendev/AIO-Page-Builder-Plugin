<?php
/**
 * Unit tests for rollback execution (spec §38.5, §38.6, §41.9; Prompt 090).
 *
 * Covers revalidation failure at execution time, unsupported-family refusal,
 * successful rollback via stub handler, partial-rollback and failure result handling.
 *
 * Example payloads (spec §38.5, §38.6):
 *
 * --- Example 1: Successful rollback result (to_array()) ---
 * [
 *   'job_id' => 'job-abc123',
 *   'target_ref' => '42',
 *   'status' => 'success',
 *   'partial_rollback' => false,
 *   'failure_reason' => '',
 *   'log_ref' => 'log-1',
 *   'pre_snapshot_id' => 'pre-42',
 *   'post_snapshot_id' => 'post-42',
 *   'next_action_guidance' => '',
 *   'message' => 'Rollback completed.',
 *   'result_summary' => [ 'restored_title' => 'Home', 'restored_slug' => 'home', 'restored_status' => 'publish' ],
 * ]
 *
 * --- Example 2: Ineligible at execution time (to_array()) ---
 * [
 *   'job_id' => 'job-xyz',
 *   'target_ref' => 'pre-42:post-42',
 *   'status' => 'ineligible',
 *   'partial_rollback' => false,
 *   'failure_reason' => 'Rollback is not eligible. (pre_snapshot_missing)',
 *   'log_ref' => '',
 *   'pre_snapshot_id' => 'pre-missing',
 *   'post_snapshot_id' => 'post-42',
 *   'next_action_guidance' => 'Do not retry until eligibility is restored.',
 *   'message' => 'Rollback not eligible at execution time.',
 *   'result_summary' => [ 'blocking_reasons' => [ 'pre_snapshot_missing' ] ],
 * ]
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Executor;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Handler_Interface;
use AIOPageBuilder\Domain\Rollback\Execution\Rollback_Result;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Rollback/Execution/Rollback_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Execution/Rollback_Handler_Interface.php';
require_once $plugin_root . '/src/Domain/Rollback/Execution/Handlers/Rollback_Page_Replacement_Handler.php';
require_once $plugin_root . '/src/Domain/Rollback/Execution/Handlers/Rollback_Token_Set_Handler.php';
require_once $plugin_root . '/src/Domain/Rollback/Execution/Rollback_Executor.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Service.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Blocking_Reasons.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Result.php';
require_once $plugin_root . '/tests/Unit/Rollback_Eligibility_Test.php';

/**
 * Stub handler that returns a configurable result for executor tests.
 */
final class Stub_Rollback_Handler implements Rollback_Handler_Interface {

	/** @var Rollback_Result */
	public $result;

	public function __construct( Rollback_Result $result ) {
		$this->result = $result;
	}

	public function execute( array $pre_snapshot, array $post_snapshot, array $context = array() ): Rollback_Result {
		return $this->result;
	}
}

final class Rollback_Executor_Test extends TestCase {

	private static function pre_snapshot( string $id, string $target_ref, string $action_type, string $rollback_status = 'available' ): array {
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => $target_ref,
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => '2025-03-12T10:00:00+00:00',
			Operational_Snapshot_Schema::FIELD_ACTION_TYPE => $action_type,
			Operational_Snapshot_Schema::FIELD_ROLLBACK_STATUS => $rollback_status,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_' . $id,
		);
	}

	private static function post_snapshot( string $id, string $target_ref ): array {
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => $target_ref,
			Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_' . $id,
			Operational_Snapshot_Schema::FIELD_POST_CHANGE => array( 'result_snapshot' => array() ),
		);
	}

	public function test_executor_returns_ineligible_when_revalidation_fails(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array( 'post-42' => 1001 );

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );

		$payload = array(
			'pre_snapshot_id'      => 'pre-missing',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => Execution_Action_Types::REPLACE_PAGE,
		);
		$result  = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_INELIGIBLE, $result->get_status() );
		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'pre_snapshot_missing', $result->get_failure_reason() );
	}

	public function test_executor_returns_ineligible_when_no_handler_for_key(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => 1000,
			'post-42' => 1001,
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );
		$payload     = array(
			'pre_snapshot_id'      => 'pre-42',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => 'navigation',
			'target_ref'           => '42',
		);
		$result      = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_INELIGIBLE, $result->get_status() );
		$this->assertStringContainsString( 'No rollback handler', $result->get_failure_reason() );
		$this->assertSame( 'Unsupported rollback family.', $result->get_next_action_guidance() );
	}

	public function test_executor_returns_success_when_eligible_and_stub_handler_succeeds(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => 1000,
			'post-42' => 1001,
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );
		$executor->register_handler(
			Execution_Action_Types::REPLACE_PAGE,
			new Stub_Rollback_Handler(
				Rollback_Result::success( 'job-1', '42', 'pre-42', 'post-42', 'log-1', array( 'restored_title' => 'Test' ) )
			)
		);

		$payload = array(
			'pre_snapshot_id'      => 'pre-42',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => Execution_Action_Types::REPLACE_PAGE,
			'job_id'               => 'job-1',
		);
		$result  = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_SUCCESS, $result->get_status() );
		$this->assertTrue( $result->is_success() );
		$this->assertFalse( $result->is_partial_rollback() );
		$this->assertSame( 'pre-42', $result->get_pre_snapshot_id() );
		$this->assertSame( 'post-42', $result->get_post_snapshot_id() );
	}

	public function test_executor_returns_failed_when_handler_fails(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => 1000,
			'post-42' => 1001,
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );
		$executor->register_handler(
			Execution_Action_Types::REPLACE_PAGE,
			new Stub_Rollback_Handler(
				Rollback_Result::failed( 'job-1', '42', 'Target page no longer exists.', false, 'pre-42', 'post-42', 'Do not retry.', 'log-1', array() )
			)
		);

		$payload = array(
			'pre_snapshot_id'      => 'pre-42',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => Execution_Action_Types::REPLACE_PAGE,
		);
		$result  = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_FAILED, $result->get_status() );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'Target page no longer exists.', $result->get_failure_reason() );
	}

	public function test_executor_returns_partial_rollback_result(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => 1000,
			'post-42' => 1001,
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );
		$executor->register_handler(
			Execution_Action_Types::REPLACE_PAGE,
			new Stub_Rollback_Handler(
				Rollback_Result::failed( 'job-1', '42', 'Partial apply.', true, 'pre-42', 'post-42', 'Check state.', '', array( 'partial' => true ) )
			)
		);

		$payload = array(
			'pre_snapshot_id'      => 'pre-42',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => Execution_Action_Types::REPLACE_PAGE,
		);
		$result  = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_FAILED, $result->get_status() );
		$this->assertTrue( $result->is_partial_rollback() );
		$this->assertSame( 'Partial apply.', $result->get_failure_reason() );
	}

	/**
	 * Executor returns ineligible when snapshots are missing (revalidation or load step).
	 */
	public function test_executor_returns_ineligible_when_missing_snapshot_ids(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );

		$eligibility = new Rollback_Eligibility_Service( $repo );
		$executor    = new Rollback_Executor( $eligibility, $repo );

		$payload = array(
			'pre_snapshot_id'      => '',
			'post_snapshot_id'     => 'post-42',
			'rollback_handler_key' => Execution_Action_Types::REPLACE_PAGE,
		);
		$result  = $executor->execute( $payload );

		$this->assertSame( Rollback_Result::STATUS_INELIGIBLE, $result->get_status() );
		$this->assertStringContainsString( 'Missing', $result->get_failure_reason() );
	}
}
