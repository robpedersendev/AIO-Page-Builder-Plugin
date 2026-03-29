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
	 * @param string                     $step_key Step key.
	 * @param array<string, mixed>       $profile  Full profile from Profile_Store::get_full_profile().
	 * @param Onboarding_Prefill_Service $prefill   For provider readiness.
	 */
	public static function step_requirements_met( string $step_key, array $profile, Onboarding_Prefill_Service $prefill ): bool {
		return count( self::get_step_validation_errors( $step_key, $profile, $prefill ) ) === 0;
	}

	/**
	 * Human-readable validation errors for the step (empty when satisfied).
	 *
	 * @param string                     $step_key Step key.
	 * @param array<string, mixed>       $profile  Full profile.
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
				$pos   = isset( $brand['brand_positioning_summary'] ) ? trim( (string) $brand['brand_positioning_summary'] ) : '';
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
	 * @param string                     $step_key         Step key.
	 * @param string                     $current_step_key Active step.
	 * @param array<string, mixed>       $profile          Full profile.
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
	 * @param array<string, mixed>       $profile Full profile.
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
	 * Non-blocking quality hints for Review (thin, placeholder-like, or noisy input).
	 *
	 * @param array<string, mixed>       $profile Full profile.
	 * @param Onboarding_Prefill_Service $prefill Prefill (unused; reserved for future crawl/provider signals).
	 * @return array<int, string>
	 */
	public static function get_review_advisories( array $profile, Onboarding_Prefill_Service $prefill ): array {
		unset( $prefill );
		$biz   = isset( $profile[ Profile_Schema::ROOT_BUSINESS ] ) && is_array( $profile[ Profile_Schema::ROOT_BUSINESS ] )
			? $profile[ Profile_Schema::ROOT_BUSINESS ] : array();
		$brand = isset( $profile[ Profile_Schema::ROOT_BRAND ] ) && is_array( $profile[ Profile_Schema::ROOT_BRAND ] )
			? $profile[ Profile_Schema::ROOT_BRAND ] : array();

		$out = array();

		$bn = isset( $biz['business_name'] ) ? trim( (string) $biz['business_name'] ) : '';
		if ( $bn !== '' && self::looks_like_placeholder_or_too_short( $bn, true ) ) {
			$out[] = __( 'Business name still looks like a placeholder or is too short—use the real business name for better plans.', 'aio-page-builder' );
		}

		$bt = isset( $biz['business_type'] ) ? trim( (string) $biz['business_type'] ) : '';
		if ( $bt !== '' && self::looks_like_placeholder_or_too_short( $bt, false ) ) {
			$out[] = __( 'Business type looks generic. One concrete phrase (for example “B2B SaaS” or “Local retail”) helps planning.', 'aio-page-builder' );
		}

		$pos = isset( $brand['brand_positioning_summary'] ) ? trim( (string) $brand['brand_positioning_summary'] ) : '';
		$voc = isset( $brand['brand_voice_summary'] ) ? trim( (string) $brand['brand_voice_summary'] ) : '';
		if ( ( $pos !== '' || $voc !== '' ) && self::both_brand_summaries_weak( $pos, $voc ) ) {
			$out[] = __( 'Brand positioning and/or voice look thin. Add a sentence or two so the planner understands tone and differentiation.', 'aio-page-builder' );
		}

		$aud = isset( $biz['target_audience_summary'] ) ? trim( (string) $biz['target_audience_summary'] ) : '';
		if ( $aud !== '' && self::looks_like_placeholder_or_too_short( $aud, false ) ) {
			$out[] = __( 'Target audience summary looks like a stub. A few specifics (who, pain, context) improve sitemap quality.', 'aio-page-builder' );
		}

		$off = isset( $biz['primary_offers_summary'] ) ? trim( (string) $biz['primary_offers_summary'] ) : '';
		if ( $off !== '' && self::looks_like_placeholder_or_too_short( $off, false ) ) {
			$out[] = __( 'Primary offers read as placeholder text. List real offers or services you want the site to sell.', 'aio-page-builder' );
		}

		$geo = isset( $biz['core_geographic_market'] ) ? trim( (string) $biz['core_geographic_market'] ) : '';
		if ( $geo !== '' ) {
			$geo_norm = self::normalize_placeholder_text( $geo );
			if ( self::is_explicit_not_applicable_answer( $geo_norm ) ) {
				$out[] = __( 'Geographic market is still marked as not applicable. Name regions, countries, or say “worldwide” so delivery scope is clear.', 'aio-page-builder' );
			} elseif ( ! self::is_compact_geographic_market_label( $geo_norm ) && self::looks_like_placeholder_or_too_short( $geo, false ) ) {
				$out[] = __( 'Geography looks underspecified. Name regions, countries, or “worldwide” with intent.', 'aio-page-builder' );
			}
		}

		return array_values( array_unique( $out ) );
	}

	/**
	 * Human label for provider credential_state (safe display).
	 */
	public static function describe_provider_credential_state( string $state ): string {
		$s = strtolower( trim( $state ) );
		switch ( $s ) {
			case 'configured':
				return __( 'Configured', 'aio-page-builder' );
			case 'absent':
			case '':
				return __( 'Not configured', 'aio-page-builder' );
			case 'invalid':
			case 'error':
				return __( 'Needs attention', 'aio-page-builder' );
			default:
				return __( 'Unknown', 'aio-page-builder' );
		}
	}

	/**
	 * @param mixed $v
	 */
	private static function non_empty_text( $v ): bool {
		return is_string( $v ) && trim( $v ) !== '';
	}

	/**
	 * True when both fields are non-empty but too short to be useful together.
	 */
	private static function both_brand_summaries_weak( string $pos, string $voc ): bool {
		$p_len = strlen( $pos );
		$v_len = strlen( $voc );
		if ( $p_len === 0 && $v_len === 0 ) {
			return false;
		}
		if ( $p_len > 0 && $v_len > 0 ) {
			return ( $p_len < 12 && $v_len < 12 ) || ( self::looks_like_placeholder_or_too_short( $pos, false ) && self::looks_like_placeholder_or_too_short( $voc, false ) );
		}
		$only = $p_len > 0 ? $pos : $voc;
		return self::looks_like_placeholder_or_too_short( $only, false );
	}

	/**
	 * Normalizes free-text for placeholder / N/A checks (advisory only).
	 */
	private static function normalize_placeholder_text( string $text ): string {
		$t = trim( $text );
		return strtolower( preg_replace( '/\s+/', ' ', $t ) ?? $t );
	}

	/**
	 * True when the user explicitly indicated “not applicable” (distinct from a vague short phrase).
	 */
	private static function is_explicit_not_applicable_answer( string $norm ): bool {
		if ( $norm === '' ) {
			return false;
		}
		$exact = array(
			'na',
			'n/a',
			'n.a',
			'n.a.',
			'none',
			'not applicable',
			'not app',
			'no scope',
			'—',
			'–',
			'-',
		);
		foreach ( $exact as $e ) {
			if ( $norm === $e ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Short but intentional geography labels (regions, macro labels) — avoids false “too short” advisories.
	 */
	private static function is_compact_geographic_market_label( string $norm ): bool {
		if ( $norm === '' ) {
			return false;
		}
		$tokens            = array(
			'uk',
			'gb',
			'us',
			'usa',
			'eu',
			'uae',
			'emea',
			'apac',
			'latam',
			'amea',
			'dach',
			'benelux',
			'nordics',
			'mea',
			'anz',
			'sea',
			'nyc',
			'global',
			'worldwide',
			'world-wide',
			'international',
			'national',
			'regional',
			'local',
			'remote',
			'online',
			'online-only',
			'web-only',
			'nationwide',
			'statewide',
			'citywide',
			'world',
		);
		$short_iso_or_city = array(
			'fr',
			'de',
			'es',
			'it',
			'nl',
			'be',
			'ch',
			'at',
			'ie',
			'se',
			'no',
			'dk',
			'fi',
			'pl',
			'pt',
			'br',
			'mx',
			'in',
			'jp',
			'kr',
			'au',
			'nz',
			'sg',
			'ca',
			'lon',
			'par',
			'ber',
		);
		return in_array( $norm, $tokens, true ) || in_array( $norm, $short_iso_or_city, true );
	}

	/**
	 * Detects placeholder junk and empty-equivalent answers (advisory only).
	 *
	 * @param bool $strict When true, stricter minimum length (business name).
	 */
	private static function looks_like_placeholder_or_too_short( string $text, bool $strict ): bool {
		$norm = self::normalize_placeholder_text( $text );
		if ( $norm === '' ) {
			return false;
		}
		$min = $strict ? 3 : 4;
		if ( strlen( $norm ) < $min ) {
			return true;
		}
		$junk = array(
			'tbd',
			'tba',
			'na',
			'n/a',
			'none',
			'nothing',
			'todo',
			'test',
			'testing',
			'placeholder',
			'lorem',
			'lorem ipsum',
			'dummy',
			'asdf',
			'xxx',
			'...',
			'…',
		);
		foreach ( $junk as $j ) {
			if ( $norm === $j ) {
				return true;
			}
		}
		if ( preg_match( '/^(lorem|test|placeholder|todo|tbd)\b/i', $norm ) === 1 ) {
			return true;
		}
		return false;
	}
}
