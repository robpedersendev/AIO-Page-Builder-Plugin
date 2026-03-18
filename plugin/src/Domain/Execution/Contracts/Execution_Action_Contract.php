<?php
/**
 * Execution action envelope contract constants and validation (spec §39, §40; execution-action-contract.md).
 *
 * Declarative only: envelope field names, required keys, executable approval states, error codes.
 * Validation helpers return error codes; no execution is performed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Execution action envelope and result contract. Governed by docs/contracts/execution-action-contract.md.
 */
final class Execution_Action_Contract {

	// -------------------------------------------------------------------------
	// Envelope required keys (§4.1)
	// -------------------------------------------------------------------------

	public const ENVELOPE_ACTION_ID       = 'action_id';
	public const ENVELOPE_ACTION_TYPE    = 'action_type';
	public const ENVELOPE_PLAN_ID        = 'plan_id';
	public const ENVELOPE_PLAN_ITEM_ID    = 'plan_item_id';
	public const ENVELOPE_TARGET_REFERENCE = 'target_reference';
	public const ENVELOPE_APPROVAL_STATE  = 'approval_state';
	public const ENVELOPE_ACTOR_CONTEXT   = 'actor_context';
	public const ENVELOPE_CREATED_AT      = 'created_at';

	/** @var array<int, string> Required envelope top-level keys. */
	public const REQUIRED_ENVELOPE_KEYS = array(
		self::ENVELOPE_ACTION_ID,
		self::ENVELOPE_ACTION_TYPE,
		self::ENVELOPE_PLAN_ID,
		self::ENVELOPE_PLAN_ITEM_ID,
		self::ENVELOPE_TARGET_REFERENCE,
		self::ENVELOPE_APPROVAL_STATE,
		self::ENVELOPE_ACTOR_CONTEXT,
		self::ENVELOPE_CREATED_AT,
	);

	// -------------------------------------------------------------------------
	// Approval state keys (§6)
	// -------------------------------------------------------------------------

	public const APPROVAL_PLAN_STATUS   = 'plan_status';
	public const APPROVAL_ITEM_STATUS  = 'item_status';
	public const APPROVAL_VERIFIED_AT  = 'verified_at';

	/** Plan statuses that allow execution (spec §39.6, contract §6.2). */
	public const EXECUTABLE_PLAN_STATUSES = array( 'approved', 'in_progress' );

	/** Item status that allows execution for item-scoped actions (contract §6.2). */
	public const EXECUTABLE_ITEM_STATUS = 'approved';

	// -------------------------------------------------------------------------
	// Actor context keys (§7)
	// -------------------------------------------------------------------------

	public const ACTOR_ACTOR_TYPE           = 'actor_type';
	public const ACTOR_ACTOR_ID             = 'actor_id';
	public const ACTOR_CAPABILITY_CHECKED   = 'capability_checked';
	public const ACTOR_CHECKED_AT           = 'checked_at';

	// -------------------------------------------------------------------------
	// Result and error (§10, §11)
	// -------------------------------------------------------------------------

	public const RESULT_STATUS    = 'status';
	public const RESULT_ERROR     = 'error';
	public const RESULT_ACTION_ID  = 'action_id';
	public const RESULT_COMPLETED_AT = 'completed_at';

	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED    = 'failed';
	public const STATUS_REFUSED   = 'refused';
	public const STATUS_PARTIAL   = 'partial';

	public const ERROR_CODE   = 'code';
	public const ERROR_MESSAGE = 'message';
	public const ERROR_REFUSABLE = 'refusable';

	/** Error code categories (contract §11.2). */
	public const ERROR_INVALID_ENVELOPE     = 'invalid_envelope';
	public const ERROR_UNAUTHORIZED         = 'unauthorized';
	public const ERROR_STALE_APPROVAL       = 'stale_approval';
	public const ERROR_DEPENDENCY_FAILED     = 'dependency_failed';
	public const ERROR_SNAPSHOT_REQUIRED    = 'snapshot_required';
	public const ERROR_TARGET_NOT_FOUND     = 'target_not_found';
	public const ERROR_EXECUTION_FAILED     = 'execution_failed';
	public const ERROR_CONFLICT              = 'conflict';
	public const ERROR_ROLLBACK_NOT_ELIGIBLE = 'rollback_not_eligible';
	public const ERROR_ACTION_NOT_AVAILABLE  = 'action_not_available';

	/**
	 * Validates that the envelope has all required top-level keys and a valid action type.
	 * Does not validate approval state or actor; use validate_approval_precondition for that.
	 *
	 * @param array<string, mixed> $envelope Action envelope (associative array).
	 * @return array<int, array{code: string, field?: string}> Empty if valid; list of errors otherwise.
	 */
	public static function validate_envelope_shape( array $envelope ): array {
		$errors = array();
		foreach ( self::REQUIRED_ENVELOPE_KEYS as $key ) {
			if ( ! array_key_exists( $key, $envelope ) ) {
				$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => $key );
				continue;
			}
			$val = $envelope[ $key ];
			if ( $key === self::ENVELOPE_PLAN_ITEM_ID && $envelope[ self::ENVELOPE_ACTION_TYPE ] === Execution_Action_Types::FINALIZE_PLAN ) {
				// plan_item_id may be empty for plan-level actions.
				continue;
			}
			if ( in_array( $key, array( self::ENVELOPE_ACTION_ID, self::ENVELOPE_ACTION_TYPE, self::ENVELOPE_PLAN_ID, self::ENVELOPE_PLAN_ITEM_ID, self::ENVELOPE_CREATED_AT ), true ) && ! is_string( $val ) ) {
				$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => $key );
			}
		}
		$action_type = isset( $envelope[ self::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ self::ENVELOPE_ACTION_TYPE ] ) ? $envelope[ self::ENVELOPE_ACTION_TYPE ] : '';
		if ( $action_type !== '' && ! Execution_Action_Types::is_valid( $action_type ) ) {
			$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => self::ENVELOPE_ACTION_TYPE );
		}
		$target = $envelope[ self::ENVELOPE_TARGET_REFERENCE ] ?? null;
		if ( $action_type !== Execution_Action_Types::FINALIZE_PLAN && ( ! is_array( $target ) || empty( $target ) ) ) {
			$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => self::ENVELOPE_TARGET_REFERENCE );
		}
		$approval = $envelope[ self::ENVELOPE_APPROVAL_STATE ] ?? null;
		if ( ! is_array( $approval ) ) {
			$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => self::ENVELOPE_APPROVAL_STATE );
		}
		$actor = $envelope[ self::ENVELOPE_ACTOR_CONTEXT ] ?? null;
		if ( ! is_array( $actor ) ) {
			$errors[] = array( 'code' => self::ERROR_INVALID_ENVELOPE, 'field' => self::ENVELOPE_ACTOR_CONTEXT );
		}
		return $errors;
	}

	/**
	 * Validates approval precondition: plan_status and item_status (when item-scoped) must be executable.
	 * Call after validate_envelope_shape.
	 *
	 * @param array<string, mixed> $envelope Action envelope.
	 * @return array<int, array{code: string, field?: string}> Empty if valid; list of errors otherwise.
	 */
	public static function validate_approval_precondition( array $envelope ): array {
		$errors = array();
		$approval = $envelope[ self::ENVELOPE_APPROVAL_STATE ] ?? array();
		if ( ! is_array( $approval ) ) {
			return array( array( 'code' => self::ERROR_UNAUTHORIZED, 'field' => self::ENVELOPE_APPROVAL_STATE ) );
		}
		$plan_status = isset( $approval[ self::APPROVAL_PLAN_STATUS ] ) && is_string( $approval[ self::APPROVAL_PLAN_STATUS ] ) ? $approval[ self::APPROVAL_PLAN_STATUS ] : '';
		if ( ! in_array( $plan_status, self::EXECUTABLE_PLAN_STATUSES, true ) ) {
			$errors[] = array( 'code' => self::ERROR_UNAUTHORIZED, 'field' => self::APPROVAL_PLAN_STATUS );
		}
		$action_type = isset( $envelope[ self::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ self::ENVELOPE_ACTION_TYPE ] ) ? $envelope[ self::ENVELOPE_ACTION_TYPE ] : '';
		$plan_item_id = isset( $envelope[ self::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ self::ENVELOPE_PLAN_ITEM_ID ] ) ? $envelope[ self::ENVELOPE_PLAN_ITEM_ID ] : '';
		$is_item_scoped = $action_type !== Execution_Action_Types::FINALIZE_PLAN && $action_type !== Execution_Action_Types::ROLLBACK_ACTION && $plan_item_id !== '';
		if ( $is_item_scoped ) {
			$item_status = isset( $approval[ self::APPROVAL_ITEM_STATUS ] ) && is_string( $approval[ self::APPROVAL_ITEM_STATUS ] ) ? $approval[ self::APPROVAL_ITEM_STATUS ] : '';
			if ( $item_status !== self::EXECUTABLE_ITEM_STATUS ) {
				$errors[] = array( 'code' => self::ERROR_UNAUTHORIZED, 'field' => self::APPROVAL_ITEM_STATUS );
			}
		}
		return $errors;
	}

	/**
	 * Builds a refusal result object (contract §10, §11). No mutation is performed.
	 *
	 * @param string $action_id From envelope.
	 * @param string $action_type From envelope.
	 * @param string $error_code One of ERROR_* constants.
	 * @param string $message Human-readable message.
	 * @return array<string, mixed> Result shape with status refused and error.
	 */
	public static function build_refused_result( string $action_id, string $action_type, string $error_code, string $message ): array {
		return array(
			self::RESULT_ACTION_ID   => $action_id,
			'action_type'           => $action_type,
			self::RESULT_STATUS     => self::STATUS_REFUSED,
			self::RESULT_COMPLETED_AT => gmdate( 'c' ),
			self::RESULT_ERROR      => array(
				self::ERROR_CODE      => $error_code,
				self::ERROR_MESSAGE   => $message,
				self::ERROR_REFUSABLE => true,
			),
		);
	}
}
