<?php
/**
 * Maps draft + step + gates to a single user-facing onboarding status (spec §23.3, §23.9).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Stable labels for the wizard shell; does not replace stored draft overall_status.
 */
final class Onboarding_User_Facing_Status {

	public const KEY_DRAFT_SAVED      = 'draft_saved';
	public const KEY_IN_PROGRESS      = 'in_progress';
	public const KEY_READY_FOR_REVIEW = 'ready_for_review';
	public const KEY_READY_TO_SUBMIT  = 'ready_to_submit';
	public const KEY_NEEDS_ITEMS      = 'needs_required_items';

	/**
	 * Resolves display status from draft and current UI gates.
	 *
	 * @param array<string, mixed> $draft              Normalized onboarding draft.
	 * @param string               $current_step_key   Active step.
	 * @param bool                 $has_review_blockers True when review/submission blockers apply.
	 * @return array{key: string, label: string, hint: string}
	 */
	public static function resolve( array $draft, string $current_step_key, bool $has_review_blockers ): array {
		$raw_overall = isset( $draft['overall_status'] ) && is_string( $draft['overall_status'] )
			? $draft['overall_status']
			: Onboarding_Statuses::NOT_STARTED;

		if ( $raw_overall === Onboarding_Statuses::DRAFT_SAVED ) {
			return array(
				'key'   => self::KEY_DRAFT_SAVED,
				'label' => __( 'Saved draft', 'aio-page-builder' ),
				'hint'  => __( 'Progress is stored. Continue when you are ready.', 'aio-page-builder' ),
			);
		}

		if ( $current_step_key === Onboarding_Step_Keys::REVIEW ) {
			if ( ! $has_review_blockers ) {
				return array(
					'key'   => self::KEY_READY_FOR_REVIEW,
					'label' => __( 'Ready for review', 'aio-page-builder' ),
					'hint'  => __( 'Check the summary below, then go to Submission to request planning.', 'aio-page-builder' ),
				);
			}
			return array(
				'key'   => self::KEY_NEEDS_ITEMS,
				'label' => __( 'Review: required items missing', 'aio-page-builder' ),
				'hint'  => __( 'Complete the items listed above, or save a draft and return later.', 'aio-page-builder' ),
			);
		}

		if ( $current_step_key === Onboarding_Step_Keys::SUBMISSION ) {
			if ( ! $has_review_blockers ) {
				return array(
					'key'   => self::KEY_READY_TO_SUBMIT,
					'label' => __( 'Ready to request planning', 'aio-page-builder' ),
					'hint'  => __( 'Submit sends a planning request to your configured AI provider. You can still save a draft first.', 'aio-page-builder' ),
				);
			}
			return array(
				'key'   => self::KEY_NEEDS_ITEMS,
				'label' => __( 'Submission blocked', 'aio-page-builder' ),
				'hint'  => __( 'Fix the required items above before requesting a plan.', 'aio-page-builder' ),
			);
		}

		return array(
			'key'   => self::KEY_IN_PROGRESS,
			'label' => __( 'In progress', 'aio-page-builder' ),
			'hint'  => self::build_last_run_hint( $draft ),
		);
	}

	/**
	 * Secondary hint when a prior planning run exists (non-blocking).
	 *
	 * @param array<string, mixed> $draft Draft.
	 */
	private static function build_last_run_hint( array $draft ): string {
		$rid = isset( $draft['last_planning_run_id'] ) && is_string( $draft['last_planning_run_id'] )
			? trim( $draft['last_planning_run_id'] )
			: '';
		if ( $rid === '' ) {
			return __( 'Use Save draft anytime; required checks apply only when you move past certain steps.', 'aio-page-builder' );
		}
		return __( 'A previous planning run is on file. You can update your profile and request another run when ready.', 'aio-page-builder' );
	}
}
