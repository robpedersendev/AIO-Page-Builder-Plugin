<?php
/**
 * Unit tests for execution action contract (spec §39, §40; execution-action-contract.md, Prompt 077).
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Tests\Unit;

use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Contract;
use AIOPageBuilder\Domain\Execution\Contracts\Execution_Action_Types;
use PHPUnit\Framework\TestCase;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/wordpress/' );

$plugin_root = dirname( __DIR__, 2 );
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Types.php';
require_once $plugin_root . '/src/Domain/Execution/Contracts/Execution_Action_Contract.php';

final class Execution_Action_Contract_Test extends TestCase {

	/** Valid action envelope (contract §13): create_page with approved item. */
	public const VALID_ENVELOPE = array(
		'action_id'           => 'exec_create_plan_npc_0_20250311T120000Z',
		'action_type'         => 'create_page',
		'plan_id'             => 'aio-plan-uuid-1',
		'plan_item_id'        => 'plan_npc_0',
		'target_reference'    => array(
			'plan_item_id' => 'plan_npc_0',
			'template_ref' => array(
				'type'  => 'internal_key',
				'value' => 'template_landing',
			),
		),
		'approval_state'      => array(
			'plan_status'        => 'in_progress',
			'item_status'        => 'approved',
			'item_status_source' => 'build_plan',
			'verified_at'        => '2025-03-11T11:59:00Z',
		),
		'actor_context'       => array(
			'actor_type'         => 'user',
			'actor_id'           => '1',
			'capability_checked' => 'aio_execute_build_plans',
			'checked_at'         => '2025-03-11T11:59:00Z',
		),
		'created_at'          => '2025-03-11T12:00:00Z',
		'snapshot_required'   => false,
		'queue_eligible'      => true,
		'dependency_manifest' => array(
			'dependencies'      => array(
				array(
					'kind'      => 'template_available',
					'ref'       => array(
						'type'  => 'internal_key',
						'value' => 'template_landing',
					),
					'satisfied' => true,
				),
			),
			'resolved'          => true,
			'resolution_errors' => array(),
		),
	);

	/** Invalid action envelope (contract §14): pending plan and item, must be refused. */
	public const INVALID_ENVELOPE = array(
		'action_id'        => 'exec_replace_ep_0_20250311T120001Z',
		'action_type'      => 'replace_page',
		'plan_id'          => 'aio-plan-uuid-1',
		'plan_item_id'     => 'plan_ep_0',
		'target_reference' => array(
			'page_ref'     => array(
				'type'  => 'post_id',
				'value' => 42,
			),
			'plan_item_id' => 'plan_ep_0',
		),
		'approval_state'   => array(
			'plan_status' => 'pending_review',
			'item_status' => 'pending',
			'verified_at' => '2025-03-11T11:00:00Z',
		),
		'actor_context'    => array(
			'actor_type'         => 'user',
			'actor_id'           => '1',
			'capability_checked' => 'aio_execute_build_plans',
			'checked_at'         => '2025-03-11T12:00:01Z',
		),
		'created_at'       => '2025-03-11T12:00:01Z',
	);

	public function test_action_types_all_are_valid(): void {
		foreach ( Execution_Action_Types::ALL as $type ) {
			$this->assertTrue( Execution_Action_Types::is_valid( $type ), "Action type {$type} should be valid." );
		}
	}

	public function test_unknown_action_type_is_invalid(): void {
		$this->assertFalse( Execution_Action_Types::is_valid( 'unknown_type' ) );
		$this->assertFalse( Execution_Action_Types::is_valid( '' ) );
	}

	public function test_validate_envelope_shape_valid_envelope_returns_no_errors(): void {
		$errors = Execution_Action_Contract::validate_envelope_shape( self::VALID_ENVELOPE );
		$this->assertSame( array(), $errors, 'Valid envelope must produce no shape errors.' );
	}

	public function test_validate_envelope_shape_missing_required_field_returns_errors(): void {
		$envelope = self::VALID_ENVELOPE;
		unset( $envelope['approval_state'] );
		$errors = Execution_Action_Contract::validate_envelope_shape( $envelope );
		$this->assertNotEmpty( $errors );
		$this->assertSame( Execution_Action_Contract::ERROR_INVALID_ENVELOPE, $errors[0]['code'] );
		$this->assertSame( 'approval_state', $errors[0]['field'] );
	}

	public function test_validate_envelope_shape_invalid_action_type_returns_error(): void {
		$envelope                = self::VALID_ENVELOPE;
		$envelope['action_type'] = 'invalid_action';
		$errors                  = Execution_Action_Contract::validate_envelope_shape( $envelope );
		$this->assertNotEmpty( $errors );
		$this->assertSame( Execution_Action_Contract::ERROR_INVALID_ENVELOPE, $errors[0]['code'] );
		$this->assertSame( 'action_type', $errors[0]['field'] );
	}

	public function test_validate_envelope_shape_empty_target_reference_returns_error_for_item_scoped(): void {
		$envelope                     = self::VALID_ENVELOPE;
		$envelope['target_reference'] = array();
		$errors                       = Execution_Action_Contract::validate_envelope_shape( $envelope );
		$this->assertNotEmpty( $errors );
		$this->assertSame( Execution_Action_Contract::ERROR_INVALID_ENVELOPE, $errors[0]['code'] );
	}

	public function test_validate_approval_precondition_valid_approved_returns_no_errors(): void {
		$errors = Execution_Action_Contract::validate_approval_precondition( self::VALID_ENVELOPE );
		$this->assertSame( array(), $errors, 'Approved in_progress plan with approved item must pass.' );
	}

	public function test_validate_approval_precondition_invalid_pending_returns_unauthorized(): void {
		$errors = Execution_Action_Contract::validate_approval_precondition( self::INVALID_ENVELOPE );
		$this->assertNotEmpty( $errors );
		$codes = array_column( $errors, 'code' );
		$this->assertContains( Execution_Action_Contract::ERROR_UNAUTHORIZED, $codes );
	}

	public function test_validate_approval_precondition_non_executable_plan_status_returns_unauthorized(): void {
		$envelope                                  = self::VALID_ENVELOPE;
		$envelope['approval_state']['plan_status'] = 'rejected';
		$errors                                    = Execution_Action_Contract::validate_approval_precondition( $envelope );
		$this->assertNotEmpty( $errors );
		$this->assertSame( Execution_Action_Contract::ERROR_UNAUTHORIZED, $errors[0]['code'] );
	}

	public function test_validate_approval_precondition_non_approved_item_returns_unauthorized(): void {
		$envelope                                  = self::VALID_ENVELOPE;
		$envelope['approval_state']['item_status'] = 'pending';
		$errors                                    = Execution_Action_Contract::validate_approval_precondition( $envelope );
		$this->assertNotEmpty( $errors );
		$this->assertSame( Execution_Action_Contract::ERROR_UNAUTHORIZED, $errors[0]['code'] );
	}

	public function test_build_refused_result_has_required_shape(): void {
		$result = Execution_Action_Contract::build_refused_result(
			'action-1',
			'create_page',
			Execution_Action_Contract::ERROR_UNAUTHORIZED,
			'Item not approved.'
		);
		$this->assertSame( 'action-1', $result[ Execution_Action_Contract::RESULT_ACTION_ID ] );
		$this->assertSame( 'create_page', $result['action_type'] );
		$this->assertSame( Execution_Action_Contract::STATUS_REFUSED, $result[ Execution_Action_Contract::RESULT_STATUS ] );
		$this->assertArrayHasKey( Execution_Action_Contract::RESULT_COMPLETED_AT, $result );
		$this->assertSame( Execution_Action_Contract::ERROR_UNAUTHORIZED, $result[ Execution_Action_Contract::RESULT_ERROR ]['code'] );
		$this->assertSame( 'Item not approved.', $result[ Execution_Action_Contract::RESULT_ERROR ]['message'] );
		$this->assertTrue( $result[ Execution_Action_Contract::RESULT_ERROR ][ Execution_Action_Contract::ERROR_REFUSABLE ] );
	}

	public function test_valid_envelope_full_validation_passes(): void {
		$shape_errors = Execution_Action_Contract::validate_envelope_shape( self::VALID_ENVELOPE );
		$this->assertEmpty( $shape_errors );
		$approval_errors = Execution_Action_Contract::validate_approval_precondition( self::VALID_ENVELOPE );
		$this->assertEmpty( $approval_errors );
	}

	public function test_invalid_envelope_approval_precondition_fails(): void {
		$shape_errors = Execution_Action_Contract::validate_envelope_shape( self::INVALID_ENVELOPE );
		$this->assertEmpty( $shape_errors, 'Invalid example has valid shape.' );
		$approval_errors = Execution_Action_Contract::validate_approval_precondition( self::INVALID_ENVELOPE );
		$this->assertNotEmpty( $approval_errors, 'Pending plan/item must fail approval precondition.' );
	}
}
