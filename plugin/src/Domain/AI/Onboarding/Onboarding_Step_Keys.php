<?php
/**
 * Onboarding step key constants (onboarding-state-machine.md, spec §23).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Stable step identifiers for onboarding. Order is defined in the contract.
 */
final class Onboarding_Step_Keys {

	public const WELCOME               = 'welcome';
	public const BUSINESS_PROFILE      = 'business_profile';
	public const BRAND_PROFILE         = 'brand_profile';
	public const AUDIENCE_OFFERS       = 'audience_offers';
	public const GEOGRAPHY_COMPETITORS = 'geography_competitors';
	public const ASSET_INTAKE          = 'asset_intake';
	public const EXISTING_SITE         = 'existing_site';
	public const CRAWL_PREFERENCES     = 'crawl_preferences';
	public const PROVIDER_SETUP        = 'provider_setup';
	public const REVIEW                = 'review';
	public const SUBMISSION            = 'submission';

	/**
	 * Ordered list of step keys (canonical order).
	 *
	 * @return array<int, string>
	 */
	public static function ordered(): array {
		return array(
			self::WELCOME,
			self::BUSINESS_PROFILE,
			self::BRAND_PROFILE,
			self::AUDIENCE_OFFERS,
			self::GEOGRAPHY_COMPETITORS,
			self::ASSET_INTAKE,
			self::EXISTING_SITE,
			self::CRAWL_PREFERENCES,
			self::PROVIDER_SETUP,
			self::REVIEW,
			self::SUBMISSION,
		);
	}
}
