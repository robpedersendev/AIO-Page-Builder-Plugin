<?php
/**
 * Unit tests for rollback eligibility validation (spec §38.4, §41.9, §59.11; Prompt 089).
 *
 * Covers eligible page rollback, ineligible missing-snapshot, no-handler, newer-change conflict,
 * and permission-sensitive result handling.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Blocking_Reasons;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Result;
use AIOPageBuilder\Domain\Rollback\Validation\Rollback_Eligibility_Service;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Infrastructure/Config/Capabilities.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Blocking_Reasons.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Validation/Rollback_Eligibility_Service.php';

/**
 * Stub repository for rollback eligibility tests. Configurable get_by_id and list_snapshot_created_times_for_target.
 */
final class Stub_Rollback_Repo implements Operational_Snapshot_Repository_Interface {

	/** @var array<string, array<string, mixed>> */
	public $store = array();

	/** @var array<string, int> snapshot_id => created_at for list_snapshot_created_times_for_target. */
	public $list_for_target = array();

	public function save( array $snapshot ): bool {
		$id = isset( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) ? (string) $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] : '';
		if ( $id === '' ) {
			return false;
		}
		$this->store[ $id ] = $snapshot;
		return true;
	}

	public function get_by_id( string $snapshot_id ): ?array {
		$snapshot_id = trim( $snapshot_id );
		return isset( $this->store[ $snapshot_id ] ) ? $this->store[ $snapshot_id ] : null;
	}

	public function list_snapshot_created_times_for_target( string $target_ref ): array {
		return $this->list_for_target;
	}

	/** @inheritDoc */
	public function list_rollback_entries_for_plan( string $plan_id ): array {
		$out = array();
		foreach ( $this->store as $id => $snap ) {
			if ( ! is_array( $snap ) ) {
				continue;
			}
			$type = $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ?? '';
			if ( $type !== Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE ) {
				continue;
			}
			$plan_ref = trim( (string) ( $snap[ Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF ] ?? '' ) );
			if ( $plan_ref !== $plan_id ) {
				continue;
			}
			$pre_id = isset( $snap['pre_snapshot_id'] ) && is_string( $snap['pre_snapshot_id'] ) ? trim( $snap['pre_snapshot_id'] ) : '';
			if ( $pre_id === '' ) {
				continue;
			}
			$out[] = array(
				'post_snapshot_id' => $id,
				'pre_snapshot_id'  => $pre_id,
				'action_type'      => (string) ( $snap[ Operational_Snapshot_Schema::FIELD_ACTION_TYPE ] ?? '' ),
				'target_ref'       => (string) ( $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ?? '' ),
				'created_at'       => (string) ( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ?? '' ),
			);
		}
		return $out;
	}
}

final class Rollback_Eligibility_Test extends TestCase {

	private static function pre_snapshot( string $id, string $target_ref, string $object_family, string $action_type, string $created_at = '2025-03-12T10:00:00+00:00', string $rollback_status = 'available' ): array {
		return array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => $id,
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => $object_family,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => $target_ref,
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => $created_at,
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

	public function test_eligible_page_rollback_when_snapshots_present_and_target_resolved(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => strtotime( '2025-03-12T10:00:00+00:00' ),
			'post-42' => strtotime( '2025-03-12T10:01:00+00:00' ),
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'         => 42,
				'post_type'  => 'page',
				'post_title' => 'Test',
			)
		);

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertTrue( $result->is_eligible() );
		$this->assertEmpty( $result->get_blocking_reasons() );
		$this->assertSame( Execution_Action_Types::REPLACE_PAGE, $result->get_rollback_handler_key() );
		$this->assertSame( 'pre-42', $result->get_pre_snapshot_id() );
		$this->assertSame( 'post-42', $result->get_post_snapshot_id() );
		$this->assertSame( Rollback_Eligibility_Result::TARGET_RESOLVED, $result->get_target_resolution_state() );
		$this->assertContains( 'aio_execute_rollbacks', $result->get_required_permissions() );
	}

	public function test_ineligible_when_pre_snapshot_missing(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-missing', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::PRE_SNAPSHOT_MISSING, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_post_snapshot_missing(): void {
		$repo                  = new Stub_Rollback_Repo();
		$repo->store['pre-42'] = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-missing', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::POST_SNAPSHOT_MISSING, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_no_handler_for_action_type(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::CREATE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array();

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::NO_HANDLER_FOR_ACTION_TYPE, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_newer_change_conflict(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE, '2025-03-12T10:00:00+00:00' );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => strtotime( '2025-03-12T10:00:00+00:00' ),
			'post-42' => strtotime( '2025-03-12T10:01:00+00:00' ),
			'pre-43'  => strtotime( '2025-03-12T11:00:00+00:00' ),
		);

		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::NEWER_CHANGE_CONFLICT, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_permission_denied(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array();

		$GLOBALS['_aio_get_post_return']         = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);
		$GLOBALS['_aio_current_user_can_return'] = false;

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array() );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::PERMISSION_DENIED, $result->get_blocking_reasons() );
	}

	public function test_eligible_when_permission_granted(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array(
			'pre-42'  => 1000,
			'post-42' => 1001,
		);

		$GLOBALS['_aio_get_post_return']         = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);
		$GLOBALS['_aio_current_user_can_return'] = true;

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array() );

		$this->assertTrue( $result->is_eligible() );
		$this->assertEmpty( $result->get_blocking_reasons() );
	}

	/** v1 (Prompt 642): UPDATE_MENU has no rollback handler; eligibility returns NO_HANDLER_FOR_ACTION_TYPE. */
	public function test_ineligible_when_action_type_not_rollback_capable_in_v1(): void {
		$repo                     = new Stub_Rollback_Repo();
		$repo->store['pre-menu']  = self::pre_snapshot( 'pre-menu', '10', Operational_Snapshot_Schema::OBJECT_FAMILY_MENU, Execution_Action_Types::UPDATE_MENU );
		$repo->store['post-menu'] = self::post_snapshot( 'post-menu', '10' );

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-menu', 'post-menu', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::NO_HANDLER_FOR_ACTION_TYPE, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_snapshot_used(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE, '2025-03-12T10:00:00+00:00', Operational_Snapshot_Schema::ROLLBACK_STATUS_USED );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::SNAPSHOT_USED, $result->get_blocking_reasons() );
	}

	public function test_ineligible_when_target_unresolvable(): void {
		$repo                   = new Stub_Rollback_Repo();
		$repo->store['pre-42']  = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42'] = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target  = array();

		$GLOBALS['_aio_get_post_return'] = null;

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );

		$this->assertFalse( $result->is_eligible() );
		$this->assertContains( Rollback_Blocking_Reasons::TARGET_UNRESOLVABLE, $result->get_blocking_reasons() );
		$this->assertSame( Rollback_Eligibility_Result::TARGET_MISSING, $result->get_target_resolution_state() );
	}

	public function test_result_to_array_has_contract_shape(): void {
		$repo                            = new Stub_Rollback_Repo();
		$repo->store['pre-42']           = self::pre_snapshot( 'pre-42', '42', Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, Execution_Action_Types::REPLACE_PAGE );
		$repo->store['post-42']          = self::post_snapshot( 'post-42', '42' );
		$repo->list_for_target           = array();
		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'        => 42,
				'post_type' => 'page',
			)
		);

		$service = new Rollback_Eligibility_Service( $repo );
		$result  = $service->evaluate( 'pre-42', 'post-42', array( 'skip_permission_check' => true ) );
		$arr     = $result->to_array();

		$this->assertArrayHasKey( 'is_eligible', $arr );
		$this->assertArrayHasKey( 'blocking_reasons', $arr );
		$this->assertArrayHasKey( 'warnings', $arr );
		$this->assertArrayHasKey( 'required_permissions', $arr );
		$this->assertArrayHasKey( 'target_resolution_state', $arr );
		$this->assertArrayHasKey( 'rollback_handler_key', $arr );
		$this->assertArrayHasKey( 'pre_snapshot_id', $arr );
		$this->assertArrayHasKey( 'post_snapshot_id', $arr );
		$this->assertArrayHasKey( 'execution_ref', $arr );
		$this->assertArrayHasKey( 'message', $arr );
	}

	/** Example eligible rollback result payload (contract-shaped). */
	public static function example_eligible_rollback_result(): array {
		return array(
			'is_eligible'             => true,
			'blocking_reasons'        => array(),
			'warnings'                => array(),
			'required_permissions'    => array( 'aio_execute_rollbacks' ),
			'target_resolution_state' => Rollback_Eligibility_Result::TARGET_RESOLVED,
			'rollback_handler_key'    => Execution_Action_Types::REPLACE_PAGE,
			'pre_snapshot_id'         => 'op-snap-pre-page-42',
			'post_snapshot_id'        => 'op-snap-post-page-42',
			'execution_ref'           => 'exec_replace_plan_xyz_0_20250312T100000Z',
			'message'                 => 'Rollback is eligible.',
		);
	}

	/** Example ineligible rollback result with blockers. */
	public static function example_ineligible_rollback_result(): array {
		return array(
			'is_eligible'             => false,
			'blocking_reasons'        => array(
				Rollback_Blocking_Reasons::POST_SNAPSHOT_MISSING,
			),
			'warnings'                => array(),
			'required_permissions'    => array( 'aio_execute_rollbacks' ),
			'target_resolution_state' => Rollback_Eligibility_Result::TARGET_UNKNOWN,
			'rollback_handler_key'    => Execution_Action_Types::REPLACE_PAGE,
			'pre_snapshot_id'         => 'op-snap-pre-page-42',
			'post_snapshot_id'        => 'missing-post-id',
			'execution_ref'           => 'exec_replace_plan_xyz_0_20250312T100000Z',
			'message'                 => 'Post-change snapshot not found.',
		);
	}

	public function test_example_eligible_payload_has_contract_shape(): void {
		$ex = self::example_eligible_rollback_result();
		$this->assertTrue( $ex['is_eligible'] );
		$this->assertEmpty( $ex['blocking_reasons'] );
		$this->assertSame( Rollback_Eligibility_Result::TARGET_RESOLVED, $ex['target_resolution_state'] );
		$this->assertSame( Execution_Action_Types::REPLACE_PAGE, $ex['rollback_handler_key'] );
		$this->assertArrayHasKey( 'required_permissions', $ex );
	}

	public function test_example_ineligible_payload_has_blockers(): void {
		$ex = self::example_ineligible_rollback_result();
		$this->assertFalse( $ex['is_eligible'] );
		$this->assertContains( Rollback_Blocking_Reasons::POST_SNAPSHOT_MISSING, $ex['blocking_reasons'] );
		$this->assertNotEmpty( $ex['message'] );
	}
}
