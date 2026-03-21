<?php
/**
 * Unit tests for operational snapshot capture (spec §41.2, §41.3; Prompt 087).
 *
 * Covers Pre_Change_Snapshot_Builder, Post_Change_Result_Builder, Operational_Snapshot_Service,
 * Operational_Snapshot_Result, repository save/get, and safe-failure when capture cannot proceed.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Repository_Interface;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Result;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Schema;
use AIOPageBuilder\Domain\Rollback\Snapshots\Operational_Snapshot_Service;
use AIOPageBuilder\Domain\Rollback\Snapshots\Post_Change_Result_Builder;
use AIOPageBuilder\Domain\Rollback\Snapshots\Pre_Change_Snapshot_Builder;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Schema.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Result.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Repository_Interface.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Pre_Change_Snapshot_Builder.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Post_Change_Result_Builder.php';
require_once $plugin_root . '/src/Domain/Rollback/Snapshots/Operational_Snapshot_Service.php';

/**
 * In-memory stub for operational snapshot repository.
 */
final class Stub_Operational_Snapshot_Repository implements Operational_Snapshot_Repository_Interface {

	/** @var array<string, array<string, mixed>> */
	public $store = array();

	public $save_return = true;

	public function save( array $snapshot ): bool {
		$id = isset( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ) && is_string( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] )
			? trim( $snapshot[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] )
			: '';
		if ( $id === '' ) {
			return false;
		}
		$this->store[ $id ] = $snapshot;
		return $this->save_return;
	}

	public function get_by_id( string $snapshot_id ): ?array {
		$snapshot_id = trim( $snapshot_id );
		return isset( $this->store[ $snapshot_id ] ) ? $this->store[ $snapshot_id ] : null;
	}

	/** @inheritDoc */
	public function list_snapshot_created_times_for_target( string $target_ref ): array {
		$target_ref = trim( $target_ref );
		$out        = array();
		foreach ( $this->store as $id => $snap ) {
			if ( ! is_array( $snap ) ) {
				continue;
			}
			$ref = isset( $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) ? trim( (string) $snap[ Operational_Snapshot_Schema::FIELD_TARGET_REF ] ) : '';
			if ( $ref !== $target_ref ) {
				continue;
			}
			$ts         = isset( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] ) && is_string( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				? strtotime( $snap[ Operational_Snapshot_Schema::FIELD_CREATED_AT ] )
				: 0;
			$out[ $id ] = $ts;
		}
		return $out;
	}

	/** @inheritDoc */
	public function list_rollback_entries_for_plan( string $plan_id ): array {
		return array();
	}

	/** @inheritDoc */
	public function list_post_change_snapshots_for_period( ?string $date_from = null, ?string $date_to = null ): array {
		return array();
	}
}

final class Operational_Snapshot_Capture_Test extends TestCase {

	/** Example pre-change snapshot result payload (spec §41.2). */
	public static function example_pre_change_snapshot_result(): array {
		return array(
			'success'     => true,
			'snapshot_id' => 'op-snap-pre-exec_replace_plan_xyz_0_20250312T100000Z-20250312T100500-123',
			'message'     => 'Pre-change snapshot captured.',
			'snapshot'    => array(
				Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID     => 'op-snap-pre-exec_replace_plan_xyz_0_20250312T100000Z-20250312T100500-123',
				Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE   => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
				Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY  => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
				Operational_Snapshot_Schema::FIELD_TARGET_REF     => '42',
				Operational_Snapshot_Schema::FIELD_CREATED_AT     => '2025-03-12T10:00:00+00:00',
				Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
				Operational_Snapshot_Schema::FIELD_PRE_CHANGE     => array(
					'captured_at'    => '2025-03-12T10:00:00+00:00',
					'state_snapshot' => array(
						'post_id'      => 42,
						'post_title'   => 'About Us',
						'post_name'    => 'about-us',
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'content_hash' => 'sha256:abc...',
					),
				),
				Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_replace_plan_xyz_0_20250312T100000Z',
				Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => 'plan-xyz',
				Operational_Snapshot_Schema::FIELD_PLAN_ITEM_REF  => 'item-0',
				Operational_Snapshot_Schema::FIELD_ACTION_TYPE    => Execution_Action_Types::REPLACE_PAGE,
				Operational_Snapshot_Schema::FIELD_ROLLBACK_ELIGIBLE => true,
			),
		);
	}

	/** Example post-change snapshot result payload (spec §41.3). */
	public static function example_post_change_snapshot_result(): array {
		return array(
			'success'     => true,
			'snapshot_id' => 'op-snap-post-exec_apply_tokens_plan_xyz_2_20250312T100500Z-20250312T100600-456',
			'message'     => 'Post-change snapshot captured.',
			'snapshot'    => array(
				Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'op-snap-post-exec_apply_tokens_plan_xyz_2_20250312T100500Z-20250312T100600-456',
				Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE,
				Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_TOKEN_SET,
				Operational_Snapshot_Schema::FIELD_TARGET_REF => 'color:primary',
				Operational_Snapshot_Schema::FIELD_CREATED_AT => '2025-03-12T10:06:00+00:00',
				Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
				Operational_Snapshot_Schema::FIELD_POST_CHANGE => array(
					'captured_at'     => '2025-03-12T10:06:00+00:00',
					'result_snapshot' => array(
						'token_set_id' => 'color:primary',
						'tokens'       => array( 'primary' => array( 'value' => '#2563eb' ) ),
					),
					'outcome'         => 'success',
					'message'         => 'Token value applied.',
				),
				Operational_Snapshot_Schema::FIELD_EXECUTION_REF => 'exec_apply_tokens_plan_xyz_2_20250312T100500Z',
				Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF => 'plan-xyz',
				'pre_snapshot_id' => 'op-snap-pre-tok-20250312T100500-789',
			),
		);
	}

	public function test_operational_snapshot_result_success_has_snapshot_id(): void {
		$result = Operational_Snapshot_Result::success( 'op-snap-1', 'Captured.', array( 'snapshot_id' => 'op-snap-1' ) );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'op-snap-1', $result->get_snapshot_id() );
		$this->assertSame( 'Captured.', $result->get_message() );
		$this->assertEmpty( $result->get_errors() );
	}

	public function test_operational_snapshot_result_failure_has_errors(): void {
		$result = Operational_Snapshot_Result::failure( 'Build failed.', array( 'build_failed' ) );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( '', $result->get_snapshot_id() );
		$this->assertContains( 'build_failed', $result->get_errors() );
	}

	public function test_service_supports_pre_capture_for_rollback_capable_actions(): void {
		$repo = new Stub_Operational_Snapshot_Repository();
		$svc  = new Operational_Snapshot_Service( $repo, new Pre_Change_Snapshot_Builder(), new Post_Change_Result_Builder() );
		$this->assertTrue( $svc->supports_pre_capture( Execution_Action_Types::REPLACE_PAGE ) );
		$this->assertFalse( $svc->supports_pre_capture( Execution_Action_Types::UPDATE_MENU ) );
		$this->assertTrue( $svc->supports_pre_capture( Execution_Action_Types::APPLY_TOKEN_SET ) );
		$this->assertFalse( $svc->supports_pre_capture( Execution_Action_Types::CREATE_PAGE ) );
		$this->assertFalse( $svc->supports_pre_capture( Execution_Action_Types::FINALIZE_PLAN ) );
	}

	public function test_service_capture_pre_change_unsupported_action_returns_failure(): void {
		$repo     = new Stub_Operational_Snapshot_Repository();
		$svc      = new Operational_Snapshot_Service( $repo, new Pre_Change_Snapshot_Builder(), new Post_Change_Result_Builder() );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::CREATE_PAGE,
			Execution_Action_Contract::ENVELOPE_ACTION_ID => 'exec_create_1',
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(),
		);
		$result   = $svc->capture_pre_change( $envelope );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( '', $result->get_snapshot_id() );
		$this->assertContains( 'unsupported_action', $result->get_errors() );
	}

	public function test_service_capture_pre_change_build_failure_returns_failure_safely(): void {
		$repo     = new Stub_Operational_Snapshot_Repository();
		$svc      = new Operational_Snapshot_Service( $repo, new Pre_Change_Snapshot_Builder(), new Post_Change_Result_Builder() );
		$envelope = array(
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::REPLACE_PAGE,
			Execution_Action_Contract::ENVELOPE_ACTION_ID => 'exec_replace_1',
			Execution_Action_Contract::ENVELOPE_PLAN_ID   => 'plan-1',
			Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID => 'item-0',
			Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE => array(), // no page_ref -> build returns null.
		);
		$result   = $svc->capture_pre_change( $envelope );
		$this->assertFalse( $result->is_success() );
		$this->assertSame( '', $result->get_snapshot_id() );
		$this->assertContains( 'build_failed', $result->get_errors() );
		$this->assertCount( 0, $repo->store );
	}

	public function test_repository_save_and_get_by_id(): void {
		$repo     = new Stub_Operational_Snapshot_Repository();
		$snapshot = array(
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID => 'test-snap-1',
			Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE => Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE,
			Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY => Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE,
			Operational_Snapshot_Schema::FIELD_TARGET_REF  => '42',
			Operational_Snapshot_Schema::FIELD_CREATED_AT  => '2025-03-12T10:00:00Z',
			Operational_Snapshot_Schema::FIELD_SCHEMA_VERSION => '1',
			Operational_Snapshot_Schema::FIELD_PRE_CHANGE  => array(
				'captured_at'    => '2025-03-12T10:00:00Z',
				'state_snapshot' => array(),
			),
		);
		$this->assertTrue( $repo->save( $snapshot ) );
		$got = $repo->get_by_id( 'test-snap-1' );
		$this->assertNotNull( $got );
		$this->assertSame( 'test-snap-1', $got[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_ID ] ?? '' );
		$this->assertSame( Operational_Snapshot_Schema::OBJECT_FAMILY_PAGE, $got[ Operational_Snapshot_Schema::FIELD_OBJECT_FAMILY ] ?? '' );
	}

	public function test_example_pre_change_payload_has_required_keys(): void {
		$example = self::example_pre_change_snapshot_result();
		$this->assertTrue( $example['success'] );
		$this->assertNotEmpty( $example['snapshot_id'] );
		$snap = $example['snapshot'];
		$this->assertSame( Operational_Snapshot_Schema::SNAPSHOT_TYPE_PRE_CHANGE, $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ?? '' );
		$this->assertArrayHasKey( Operational_Snapshot_Schema::FIELD_PRE_CHANGE, $snap );
		$this->assertArrayHasKey( 'state_snapshot', $snap[ Operational_Snapshot_Schema::FIELD_PRE_CHANGE ] );
		$this->assertArrayHasKey( Operational_Snapshot_Schema::FIELD_EXECUTION_REF, $snap );
		$this->assertArrayHasKey( Operational_Snapshot_Schema::FIELD_BUILD_PLAN_REF, $snap );
	}

	public function test_example_post_change_payload_has_required_keys(): void {
		$example = self::example_post_change_snapshot_result();
		$this->assertTrue( $example['success'] );
		$this->assertNotEmpty( $example['snapshot_id'] );
		$snap = $example['snapshot'];
		$this->assertSame( Operational_Snapshot_Schema::SNAPSHOT_TYPE_POST_CHANGE, $snap[ Operational_Snapshot_Schema::FIELD_SNAPSHOT_TYPE ] ?? '' );
		$this->assertArrayHasKey( Operational_Snapshot_Schema::FIELD_POST_CHANGE, $snap );
		$this->assertArrayHasKey( 'result_snapshot', $snap[ Operational_Snapshot_Schema::FIELD_POST_CHANGE ] );
		$this->assertArrayHasKey( 'pre_snapshot_id', $snap );
	}

	/** Template-aware snapshot: result_snapshot includes template_context when artifacts have template_replacement_execution_result (Prompt 197). */
	public function test_post_change_result_builder_sets_template_context_from_template_replacement_artifacts(): void {
		$GLOBALS['_aio_get_post_return'] = new \WP_Post(
			array(
				'ID'          => 42,
				'post_type'   => 'page',
				'post_title'  => 'Test',
				'post_name'   => 'test',
				'post_status' => 'publish',
			)
		);
		$envelope                        = array(
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::REPLACE_PAGE,
		);
		$handler_result                  = array(
			'success'   => true,
			'message'   => 'Replaced.',
			'artifacts' => array(
				'target_post_id'                        => 42,
				'template_replacement_execution_result' => array(
					'template_key'    => 'tpl_services_hub',
					'template_family' => 'services',
					'section_count'   => 5,
				),
			),
		);
		$builder                         = new Post_Change_Result_Builder();
		$out                             = $builder->build( $envelope, $handler_result );
		unset( $GLOBALS['_aio_get_post_return'] );
		$this->assertNotNull( $out );
		$this->assertArrayHasKey( 'post_change', $out );
		$this->assertArrayHasKey( 'result_snapshot', $out['post_change'] );
		$result_snapshot = $out['post_change']['result_snapshot'];
		$this->assertArrayHasKey( 'template_context', $result_snapshot );
		$this->assertSame( 'tpl_services_hub', $result_snapshot['template_context']['template_key'] );
		$this->assertSame( 'services', $result_snapshot['template_context']['template_family'] );
		$this->assertSame( 5, $result_snapshot['template_context']['section_count'] );
	}

	/** Menu post_change includes menu_apply_execution_result and navigation_hierarchy_summary when present (Prompt 207). */
	public function test_post_change_menu_includes_template_menu_apply_trace(): void {
		$envelope       = array(
			Execution_Action_Contract::ENVELOPE_ACTION_TYPE => Execution_Action_Types::UPDATE_MENU,
		);
		$handler_result = array(
			'success'   => true,
			'message'   => 'Template-aware menu apply completed.',
			'artifacts' => array(
				'menu_id'                       => 10,
				'menu_name'                     => 'Primary',
				'location_assigned'             => 'primary',
				'menu_apply_execution_result'   => array(
					'success'         => true,
					'menu_id'         => 10,
					'per_item_status' => array(
						array(
							'status' => 'applied',
							'title'  => 'Home',
						),
					),
				),
				'navigation_hierarchy_summary'  => array(
					'items_ordered_by_class' => array(
						array(
							'title'      => 'Home',
							'page_class' => 'top_level',
						),
					),
					'applied_count'          => 1,
					'warnings'               => array(),
				),
				'menu_target_validation_result' => array(
					'valid'         => true,
					'location_slug' => 'primary',
				),
			),
		);
		$builder        = new Post_Change_Result_Builder();
		$out            = $builder->build( $envelope, $handler_result );
		$this->assertNotNull( $out );
		$this->assertSame( Operational_Snapshot_Schema::OBJECT_FAMILY_MENU, $out['object_family'] );
		$snap = $out['post_change']['result_snapshot'];
		$this->assertArrayHasKey( 'menu_apply_execution_result', $snap );
		$this->assertArrayHasKey( 'navigation_hierarchy_summary', $snap );
		$this->assertArrayHasKey( 'menu_target_validation_result', $snap );
		$this->assertSame( 10, $snap['menu_apply_execution_result']['menu_id'] ?? 0 );
		$this->assertSame( 1, $snap['navigation_hierarchy_summary']['applied_count'] ?? 0 );
	}
}
