<?php
/**
 * Build Plan item status enum and transition rules (build-plan-state-machine.md §6).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Statuses;

defined( 'ABSPATH' ) || exit;

/**
 * Item-level status constants. Review-phase and execution-phase transitions; server-authoritative.
 */
final class Build_Plan_Item_Statuses {

	/** Item statuses (build-plan-state-machine.md §6.1). */
	public const PENDING     = 'pending';
	public const APPROVED    = 'approved';
	public const REJECTED    = 'rejected';
	public const SKIPPED     = 'skipped';
	public const IN_PROGRESS = 'in_progress';
	public const COMPLETED   = 'completed';
	public const FAILED      = 'failed';

	public const ALL = array(
		self::PENDING,
		self::APPROVED,
		self::REJECTED,
		self::SKIPPED,
		self::IN_PROGRESS,
		self::COMPLETED,
		self::FAILED,
	);

	/** Terminal item statuses (no further execution/review transition). */
	public const TERMINAL = array(
		self::REJECTED,
		self::SKIPPED,
		self::COMPLETED,
		self::FAILED,
	);

	/** Review-phase: allowed from pending (build-plan-state-machine.md §6.2). */
	public const REVIEW_FROM_PENDING = array( self::APPROVED, self::REJECTED, self::SKIPPED );

	/** Execution-phase: allowed from approved (build-plan-state-machine.md §6.3). */
	public const EXECUTION_FROM_APPROVED = array( self::IN_PROGRESS );

	/** Execution-phase: allowed from in_progress. */
	public const EXECUTION_FROM_IN_PROGRESS = array( self::COMPLETED, self::FAILED, self::SKIPPED );

	/** Execution-phase: allowed from failed (retry or skip). */
	public const EXECUTION_FROM_FAILED = array( self::IN_PROGRESS, self::SKIPPED );

	/**
	 * Returns whether the item status is valid.
	 */
	public static function is_valid( string $status ): bool {
		return in_array( $status, self::ALL, true );
	}

	/**
	 * Returns whether the item status is terminal (no further execution for that item).
	 */
	public static function is_terminal( string $status ): bool {
		return in_array( $status, self::TERMINAL, true );
	}

	/**
	 * Returns whether a review-phase transition from $from to $to is allowed (plan in pending_review).
	 */
	public static function can_transition_review( string $from, string $to ): bool {
		if ( ! self::is_valid( $from ) || ! self::is_valid( $to ) ) {
			return false;
		}
		if ( $from === self::PENDING ) {
			return in_array( $to, self::REVIEW_FROM_PENDING, true );
		}
		// Revert: approved/rejected/skipped -> pending (only before plan approval).
		if ( $to === self::PENDING && in_array( $from, array( self::APPROVED, self::REJECTED, self::SKIPPED ), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Returns whether an execution-phase transition from $from to $to is allowed (plan approved or in_progress).
	 */
	public static function can_transition_execution( string $from, string $to ): bool {
		if ( ! self::is_valid( $from ) || ! self::is_valid( $to ) ) {
			return false;
		}
		if ( $from === self::APPROVED ) {
			return in_array( $to, self::EXECUTION_FROM_APPROVED, true );
		}
		if ( $from === self::IN_PROGRESS ) {
			return in_array( $to, self::EXECUTION_FROM_IN_PROGRESS, true );
		}
		if ( $from === self::FAILED ) {
			return in_array( $to, self::EXECUTION_FROM_FAILED, true );
		}
		return false;
	}
}
