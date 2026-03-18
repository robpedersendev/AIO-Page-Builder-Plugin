<?php
/**
 * Site-scoping helper for industry subsystem (Prompt 397, industry-lifecycle-hardening-contract §2).
 * Ensures cache/transient keys are site-local on multisite to prevent cross-site bleed.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry;

defined( 'ABSPATH' ) || exit;

/**
 * Provides site-scoped keys for industry caches and transients. Use for any industry-related transient or cache key.
 */
final class Industry_Site_Scope_Helper {

	/** Prefix for industry cache keys when multisite. */
	private const MULTISITE_PREFIX = 'aio_industry_';

	/**
	 * Returns a cache/transient key that is site-local on multisite. Single-site unchanged.
	 *
	 * @param string $base_key Base key (e.g. recommendation_preview, diagnostics_snapshot). Alphanumeric and underscore only.
	 * @return string Key safe for get_transient/set_transient or option name; includes blog id on multisite.
	 */
	public static function scope_cache_key( string $base_key ): string {
		$base_key = preg_replace( '/[^a-z0-9_]/i', '_', $base_key );
		if ( $base_key === '' ) {
			$base_key = 'industry';
		}
		if ( ! \function_exists( 'is_multisite' ) || ! \is_multisite() ) {
			return self::MULTISITE_PREFIX . $base_key;
		}
		$blog_id = \function_exists( 'get_current_blog_id' ) ? \get_current_blog_id() : 1;
		return self::MULTISITE_PREFIX . $base_key . '_blog_' . $blog_id;
	}

	/**
	 * Returns current blog id when multisite, or 1 on single-site. For inclusion in diagnostics/logs.
	 *
	 * @return int
	 */
	public static function current_blog_id(): int {
		if ( ! \function_exists( 'get_current_blog_id' ) ) {
			return 1;
		}
		return (int) \get_current_blog_id();
	}
}
