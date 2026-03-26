<?php
/**
 * Output token bounds for structured planning (build-plan-draft) requests.
 *
 * Drivers apply {@see self::clamp_for_provider_request()} as a sanity ceiling so requests cannot
 * specify unbounded completion sizes. Defaults are set to allow large JSON sitemaps; per-run dollar
 * budgets are an operator concern (monthly cap settings, provider pricing), not encoded here.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Planning;

defined( 'ABSPATH' ) || exit;

/**
 * Token limits for onboarding / planning runs that emit aio/build-plan-draft-v1 JSON.
 */
final class Planning_Structured_Output_Limits {

	/**
	 * Default max completion tokens for a full planning run (sitemap, existing pages, menus, SEO).
	 */
	public const DEFAULT_MAX_OUTPUT_TOKENS = 16384;

	/**
	 * Hard ceiling passed to provider APIs from normalized requests (prevents accidental huge values).
	 */
	public const ABSOLUTE_MAX_OUTPUT_TOKENS = 65536;

	/**
	 * Clamps a requested max toward what providers accept.
	 *
	 * @param int $requested Requested completion token budget (from orchestrator or tests).
	 * @return int Value in [1, ABSOLUTE_MAX_OUTPUT_TOKENS].
	 */
	public static function clamp_for_provider_request( int $requested ): int {
		$requested = max( 1, $requested );
		return min( self::ABSOLUTE_MAX_OUTPUT_TOKENS, $requested );
	}
}
