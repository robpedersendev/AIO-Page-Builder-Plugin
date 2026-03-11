<?php
/**
 * Onboarding overall and per-step status constants (onboarding-state-machine.md, spec §23, §53.2).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Overall onboarding status and per-step status values. Used for draft state and transitions.
 */
final class Onboarding_Statuses {

	/** Overall: no session in progress. */
	public const NOT_STARTED = 'not_started';

	/** Overall: user is in the flow. */
	public const IN_PROGRESS = 'in_progress';

	/** Overall: user saved and left; partial state persisted. */
	public const DRAFT_SAVED = 'draft_saved';

	/** Overall: validation or dependency failure prevents submission. */
	public const BLOCKED = 'blocked';

	/** Overall: all checkpoints passed; user may submit. */
	public const READY_FOR_SUBMISSION = 'ready_for_submission';

	/** Overall: user confirmed submission; handoff to AI plan request. */
	public const SUBMITTED = 'submitted';

	/** Per-step: step not yet entered. */
	public const STEP_NOT_STARTED = 'not_started';

	/** Per-step: step in progress. */
	public const STEP_IN_PROGRESS = 'in_progress';

	/** Per-step: step validation passed. */
	public const STEP_COMPLETED = 'completed';

	/** Per-step: step explicitly skipped (where allowed). */
	public const STEP_SKIPPED = 'skipped';

	/** Per-step: step cannot be completed due to dependency. */
	public const STEP_BLOCKED = 'blocked';

	/**
	 * All overall statuses (for validation).
	 *
	 * @return array<int, string>
	 */
	public static function overall_statuses(): array {
		return array(
			self::NOT_STARTED,
			self::IN_PROGRESS,
			self::DRAFT_SAVED,
			self::BLOCKED,
			self::READY_FOR_SUBMISSION,
			self::SUBMITTED,
		);
	}

	/**
	 * All per-step statuses (for validation).
	 *
	 * @return array<int, string>
	 */
	public static function step_statuses(): array {
		return array(
			self::STEP_NOT_STARTED,
			self::STEP_IN_PROGRESS,
			self::STEP_COMPLETED,
			self::STEP_SKIPPED,
			self::STEP_BLOCKED,
		);
	}
}
