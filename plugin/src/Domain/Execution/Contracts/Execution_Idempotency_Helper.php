<?php
/**
 * Idempotency key and duplicate-suppression helpers (spec §40.5; executor-locking-idempotency-contract.md §6).
 *
 * Pure functions: derive keys from envelope/context; classify duplicate scenarios. No DB or queue access.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Execution\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Idempotency and duplicate-suppression classification. Governed by executor-locking-idempotency-contract.md.
 */
final class Execution_Idempotency_Helper {

	/** Duplicate classification: suppress (do not enqueue a second job). */
	public const DUPLICATE_SUPPRESS = 'suppress';

	/** Duplicate classification: allow new job (e.g. previous job failed/dead). */
	public const DUPLICATE_ALLOW_NEW = 'allow_new';

	/** Duplicate classification: optional idempotent return (existing job completed). */
	public const DUPLICATE_ALREADY_COMPLETED = 'already_completed';

	/** Duplicate classification: not a duplicate (different key or no existing job). */
	public const DUPLICATE_NONE = 'none';

	/**
	 * Builds a deterministic deduplication key from plan_id, plan_item_id, action_type, and target ref.
	 * Used to detect "same action" for duplicate suppression (contract §6.2).
	 *
	 * @param string               $plan_id Plan ID.
	 * @param string               $plan_item_id Plan item ID (empty for plan-level actions).
	 * @param string               $action_type Action type (Execution_Action_Types).
	 * @param array<string, mixed> $target_reference Target reference from envelope (normalized for hashing).
	 * @return string Deduplication key (opaque but deterministic).
	 */
	public static function build_dedup_key(
		string $plan_id,
		string $plan_item_id,
		string $action_type,
		array $target_reference
	): string {
		$plan_id           = $plan_id !== '' ? $plan_id : '_';
		$plan_item_id      = $plan_item_id !== '' ? $plan_item_id : '_';
		$action_type       = $action_type !== '' ? $action_type : '_';
		$target_normalized = self::normalize_target_for_dedup( $target_reference, $action_type );
		$target_hash       = \hash( 'sha256', \wp_json_encode( $target_normalized ) );
		return 'idem:' . $plan_id . ':' . $plan_item_id . ':' . $action_type . ':' . \substr( $target_hash, 0, 16 );
	}

	/**
	 * Normalizes target_reference for dedup key so same logical target produces same hash.
	 *
	 * @param array<string, mixed> $target_reference Target reference from envelope.
	 * @param string               $action_type Action type.
	 * @return array<string, mixed> Normalized structure (scalar/array only for hashing).
	 */
	public static function normalize_target_for_dedup( array $target_reference, string $action_type ): array {
		$out = array();
		foreach ( $target_reference as $k => $v ) {
			if ( $k === 'page_ref' && is_array( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'menu_ref' && is_array( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'plan_item_id' && is_string( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'template_ref' && is_array( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( $k === 'execution_event_id' && is_string( $v ) ) {
				$out[ $k ] = $v;
			} elseif ( is_scalar( $v ) ) {
				$out[ $k ] = $v;
			}
		}
		\ksort( $out );
		return $out;
	}

	/**
	 * Classifies duplicate behavior given existing job state (contract §6.3, §9.1).
	 *
	 * @param string $existing_queue_status Current queue_status of the existing job with same dedup key.
	 * @param bool   $treat_completed_as_idempotent If true, already_completed means return prior result; if false, allow_new is acceptable.
	 * @return string One of DUPLICATE_SUPPRESS, DUPLICATE_ALLOW_NEW, DUPLICATE_ALREADY_COMPLETED, DUPLICATE_NONE.
	 */
	public static function classify_duplicate( string $existing_queue_status, bool $treat_completed_as_idempotent = true ): string {
		if ( Execution_Lock_States::is_in_progress( $existing_queue_status ) ) {
			return self::DUPLICATE_SUPPRESS;
		}
		if ( Execution_Lock_States::is_terminal( $existing_queue_status ) ) {
			if ( $existing_queue_status === Execution_Lock_States::STATUS_COMPLETED && $treat_completed_as_idempotent ) {
				return self::DUPLICATE_ALREADY_COMPLETED;
			}
			return self::DUPLICATE_ALLOW_NEW;
		}
		return self::DUPLICATE_NONE;
	}

	/**
	 * Builds dedup key from a minimal envelope-like array (plan_id, plan_item_id, action_type, target_reference).
	 *
	 * @param array<string, mixed> $envelope Must contain plan_id, plan_item_id, action_type, target_reference.
	 * @return string Deduplication key.
	 */
	public static function build_dedup_key_from_envelope( array $envelope ): string {
		$plan_id      = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ID ]
			: '';
		$plan_item_id = isset( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_PLAN_ITEM_ID ]
			: '';
		$action_type  = isset( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] ) && is_string( $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_ACTION_TYPE ]
			: '';
		$target       = isset( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] ) && is_array( $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ] )
			? $envelope[ Execution_Action_Contract::ENVELOPE_TARGET_REFERENCE ]
			: array();
		return self::build_dedup_key( $plan_id, $plan_item_id, $action_type, $target );
	}
}
