<?php
/**
 * Derives step completion from stored profile and provider state (not draft flags alone).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Storage\Profile\Profile_Schema;

/**
 * Validates required fields per step and maps profile data to stepper display status.
 */
final class Onboarding_Step_Readiness {

	/**
	 * Whether stored profile + provider signals satisfy this step’s minimum requirements.
	 *
	 * @param string              $step_key Step key.
	 * @param array<string, mixed> $profile  Full profile from Profile_Store::get_full_profile().
	 * @param Onboarding_Prefill_Service $prefill   For provider readiness.
	 */
	public static function step_requirements_met( string $step_key, array $profile, Onboarding_Prefill_Service $prefill ): bool {
		return count( self::get_step_validation_errors( $step_key, $profile, $prefill ) ) === 0;
	}

	/**
	 * Human-readable validation errors for the step (empty when satisfied).
	 *
	 * @param string              $step_key Step key.
	 * @param array<string, mixed> $profile  Full profile.
	 * @param Onboarding_Prefill_Service $prefill  Prefill service.
	 * @return array<int, string>
	 */
	public static function get_step_validation_errors( string $step_key, array $profile, Onboarding_Prefill_Service $prefill ): array {
		$biz   = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$brand = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();

		switch ( $step_key ) {
			case Onboarding_Step_Keys::WELCOME:
				return array();
			case Onboarding_Step_Keys::BUSINESS_PROFILE:
				$errs = array();
				if ( ! self::non_empty_text( $biz['business_name'] ?? null ) ) {
					$errs[] = __( 'Enter a business name.', 'aio-page-builder' );
				}
				if ( ! self::non_empty_text( $biz['business_type'] ?? null ) ) {
					$errs[] = __( 'Enter a business type.', 'aio-page-builder' );
				}
				return $errs;
			case Onboarding_Step_Keys::BRAND_PROFILE:
				$pos  = isset( $brand['brand_positioning_summary'] ) ? trim( (string) $brand['brand_positioning_summary'] ) : '';
				$voice = isset( $brand['brand_voice_summary'] ) ? trim( (string) $brand['brand_voice_summary'] ) : '';
				if ( $pos === '' && $voice === '' ) {
					return array( __( 'Enter a brand positioning summary and/or brand voice summary.', 'aio-page-builder' ) );
				}
				return array();
			case Onboarding_Step_Keys::AUDIENCE_OFFERS:
				$errs = array();
				if ( ! self::non_empty_text( $biz['target_audience_summary'] ?? null ) ) {
					$errs[] = __( 'Enter a target audience summary.', 'aio-page-builder' );
				}
				if ( ! self::non_empty_text( $biz['primary_offers_summary'] ?? null ) ) {
					$errs[] = __( 'Enter a primary offers summary.', 'aio-page-builder' );
				}
				return $errs;
			case Onboarding_Step_Keys::GEOGRAPHY_COMPETITORS:
				if ( ! self::non_empty_text( $biz['core_geographic_market'] ?? null ) ) {
					return array( __( 'Enter a core geographic market.', 'aio-page-builder' ) );
				}
				return array();
			case Onboarding_Step_Keys::ASSET_INTAKE:
				return array();
			case Onboarding_Step_Keys::EXISTING_SITE:
			case Onboarding_Step_Keys::CRAWL_PREFERENCES:
			case Onboarding_Step_Keys::TEMPLATE_PREFERENCES:
				return array();
			case Onboarding_Step_Keys::PROVIDER_SETUP:
				if ( ! $prefill->is_provider_ready() ) {
					return array( __( 'Save an API key for at least one AI provider (or run a successful connection test).', 'aio-page-builder' ) );
				}
				return array();
			case Onboarding_Step_Keys::REVIEW:
			case Onboarding_Step_Keys::SUBMISSION:
				return array();
			default:
				return array();
		}
	}

	/**
	 * Display status for the stepper: completed, incomplete, in_progress, not_started.
	 *
	 * @param string              $step_key         Step key.
	 * @param string              $current_step_key Active step.
	 * @param array<string, mixed> $profile          Full profile.
	 * @param Onboarding_Prefill_Service $prefill              Prefill.
	 * @param int                        $furthest_step_index Highest step index the user has reached (0-based, ordered()).
	 * @return string One of Onboarding_Statuses step constants.
	 */
	public static function display_status_for_step(
		string $step_key,
		string $current_step_key,
		array $profile,
		Onboarding_Prefill_Service $prefill,
		int $furthest_step_index = 0
	): string {
		$ordered = Onboarding_Step_Keys::ordered();
		$cur_idx = array_search( $current_step_key, $ordered, true );
		$idx     = array_search( $step_key, $ordered, true );
		if ( $cur_idx === false || $idx === false ) {
			return Onboarding_Statuses::STEP_NOT_STARTED;
		}
		if ( $step_key === $current_step_key ) {
			return Onboarding_Statuses::STEP_IN_PROGRESS;
		}
		if ( $idx > $cur_idx ) {
			return Onboarding_Statuses::STEP_NOT_STARTED;
		}
		if ( self::step_requirements_met( $step_key, $profile, $prefill ) ) {
			return Onboarding_Statuses::STEP_COMPLETED;
		}
		if ( $idx <= $furthest_step_index ) {
			return Onboarding_Statuses::STEP_VISITED_INCOMPLETE;
		}
		return Onboarding_Statuses::STEP_INCOMPLETE;
	}

	/**
	 * Messages to show on Review when prior required data is still missing.
	 *
	 * @param array<string, mixed> $profile Full profile.
	 * @param Onboarding_Prefill_Service $prefill Prefill.
	 * @return array<int, string>
	 */
	public static function get_review_blockers( array $profile, Onboarding_Prefill_Service $prefill ): array {
		$keys = array(
			Onboarding_Step_Keys::BUSINESS_PROFILE,
			Onboarding_Step_Keys::BRAND_PROFILE,
			Onboarding_Step_Keys::AUDIENCE_OFFERS,
			Onboarding_Step_Keys::GEOGRAPHY_COMPETITORS,
			Onboarding_Step_Keys::PROVIDER_SETUP,
		);
		$out  = array();
		foreach ( $keys as $k ) {
			foreach ( self::get_step_validation_errors( $k, $profile, $prefill ) as $msg ) {
				$out[] = $msg;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param mixed $v
	 */
	private static function non_empty_text( $v ): bool {
		return is_string( $v ) && trim( $v ) !== '';
	}
}
