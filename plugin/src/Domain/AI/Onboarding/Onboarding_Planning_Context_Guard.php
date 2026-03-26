<?php
/**
 * Validates onboarding draft + prefill before an expensive planning API call.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Ensures the wizard captured enough structured context for full-site planning.
 */
final class Onboarding_Planning_Context_Guard {

	public const MIN_GOAL_LENGTH = 40;

	/**
	 * Returns a user-safe block message when planning should not run yet, or null when OK.
	 *
	 * @param array<string, mixed> $draft   Onboarding draft.
	 * @param array<string, mixed> $prefill From Onboarding_Prefill_Service::get_prefill_data().
	 */
	public static function get_blocking_message( array $draft, array $prefill ): ?string {
		$goal = isset( $draft['goal_or_intent_text'] ) && is_string( $draft['goal_or_intent_text'] ) ? trim( $draft['goal_or_intent_text'] ) : '';
		if ( strlen( $goal ) < self::MIN_GOAL_LENGTH ) {
			return sprintf(
				/* translators: %d: minimum characters for the site goal field */
				__( 'Enter a detailed site goal (at least %d characters) on the Review step so the planner can scope a full site.', 'aio-page-builder' ),
				self::MIN_GOAL_LENGTH
			);
		}

		$profile  = isset( $prefill['profile'] ) && is_array( $prefill['profile'] ) ? $prefill['profile'] : array();
		$business = isset( $profile['business_profile'] ) && is_array( $profile['business_profile'] ) ? $profile['business_profile'] : array();
		$brand    = isset( $profile['brand_profile'] ) && is_array( $profile['brand_profile'] ) ? $profile['brand_profile'] : array();

		$biz_name = isset( $business['business_name'] ) && is_string( $business['business_name'] ) ? trim( $business['business_name'] ) : '';
		$brand_nm = isset( $brand['brand_name'] ) && is_string( $brand['brand_name'] ) ? trim( $brand['brand_name'] ) : '';

		if ( $biz_name === '' && $brand_nm === '' ) {
			return __( 'Complete your Business Profile or Brand Profile (at least a business or brand name) before generating a full plan.', 'aio-page-builder' );
		}

		return null;
	}
}
