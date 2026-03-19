<?php
/**
 * Stable keys for approved bounded crawl profiles (spec §24, §24.5, §24.8, §59.7; Prompt 128).
 * No arbitrary user-defined profiles; only these keys are valid.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Profiles;

defined( 'ABSPATH' ) || exit;

/**
 * Read-only profile key constants. Used by Crawl_Profile_Service and session storage.
 */
final class Crawl_Profile_Keys {

	/** Quick context refresh: fewer pages and depth for fast site-context updates. */
	public const QUICK_CONTEXT_REFRESH = 'quick_context_refresh';

	/** Full public-site baseline: spec §24.2 default bounds (500 pages, depth 4). */
	public const FULL_PUBLIC_BASELINE = 'full_public_baseline';

	/** Support triage crawl: moderate bounds for support/diagnostics use. */
	public const SUPPORT_TRIAGE_CRAWL = 'support_triage_crawl';

	/** Default profile when none specified (full baseline). */
	public const DEFAULT = self::FULL_PUBLIC_BASELINE;

	/**
	 * All approved profile keys. Unsupported keys must be rejected.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		return array(
			self::QUICK_CONTEXT_REFRESH,
			self::FULL_PUBLIC_BASELINE,
			self::SUPPORT_TRIAGE_CRAWL,
		);
	}

	/**
	 * Returns true if the given key is an approved profile.
	 *
	 * @param string $key Profile key (e.g. from request or settings).
	 * @return bool
	 */
	public static function is_approved( string $key ): bool {
		return in_array( $key, self::all(), true );
	}
}
