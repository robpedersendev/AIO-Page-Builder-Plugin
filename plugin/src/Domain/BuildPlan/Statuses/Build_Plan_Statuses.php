<?php
/**
 * Build Plan root and step status enums and transition rules (spec §30.4, build-plan-state-machine.md).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\BuildPlan\Statuses;

defined( 'ABSPATH' ) || exit;

/**
 * Root plan and step status constants. Transition validity is server-authoritative; this class encodes allowed transitions only.
 */
final class Build_Plan_Statuses {

	/** Root plan statuses (build-plan-state-machine.md §2). */
	public const ROOT_PENDING_REVIEW = 'pending_review';
	public const ROOT_APPROVED       = 'approved';
	public const ROOT_REJECTED       = 'rejected';
	public const ROOT_IN_PROGRESS    = 'in_progress';
	public const ROOT_COMPLETED      = 'completed';
	public const ROOT_SUPERSEDED     = 'superseded';

	public const ROOT_STATUSES = array(
		self::ROOT_PENDING_REVIEW,
		self::ROOT_APPROVED,
		self::ROOT_REJECTED,
		self::ROOT_IN_PROGRESS,
		self::ROOT_COMPLETED,
		self::ROOT_SUPERSEDED,
	);

	/** Terminal root statuses: no outgoing transitions. */
	public const ROOT_TERMINAL = array(
		self::ROOT_REJECTED,
		self::ROOT_COMPLETED,
		self::ROOT_SUPERSEDED,
	);

	/** Step statuses (build-plan-state-machine.md §4). */
	public const STEP_PENDING     = 'pending';
	public const STEP_IN_PROGRESS = 'in_progress';
	public const STEP_BLOCKED     = 'blocked';
	public const STEP_REVIEWED    = 'reviewed';
	public const STEP_COMPLETED   = 'completed';
	public const STEP_SKIPPED     = 'skipped';

	public const STEP_STATUSES = array(
		self::STEP_PENDING,
		self::STEP_IN_PROGRESS,
		self::STEP_BLOCKED,
		self::STEP_REVIEWED,
		self::STEP_COMPLETED,
		self::STEP_SKIPPED,
	);

	/** Allowed root transitions: from => [ to, ... ]. */
	private const ROOT_TRANSITIONS = array(
		self::ROOT_PENDING_REVIEW => array( self::ROOT_APPROVED, self::ROOT_REJECTED ),
		self::ROOT_APPROVED       => array( self::ROOT_IN_PROGRESS, self::ROOT_SUPERSEDED ),
		self::ROOT_IN_PROGRESS    => array( self::ROOT_COMPLETED, self::ROOT_SUPERSEDED ),
		self::ROOT_REJECTED       => array(),
		self::ROOT_COMPLETED      => array(),
		self::ROOT_SUPERSEDED     => array(),
	);

	/**
	 * Returns whether the root status is valid.
	 */
	public static function is_valid_root_status( string $status ): bool {
		return in_array( $status, self::ROOT_STATUSES, true );
	}

	/**
	 * Returns whether the root status is terminal (no outgoing transitions).
	 */
	public static function is_root_terminal( string $status ): bool {
		return in_array( $status, self::ROOT_TERMINAL, true );
	}

	/**
	 * Returns whether a root transition from $from to $to is allowed.
	 */
	public static function can_transition_root( string $from, string $to ): bool {
		if ( ! self::is_valid_root_status( $from ) || ! self::is_valid_root_status( $to ) ) {
			return false;
		}
		$allowed = self::ROOT_TRANSITIONS[ $from ] ?? array();
		return in_array( $to, $allowed, true );
	}

	/**
	 * Returns whether the step status is valid.
	 */
	public static function is_valid_step_status( string $status ): bool {
		return in_array( $status, self::STEP_STATUSES, true );
	}
}
