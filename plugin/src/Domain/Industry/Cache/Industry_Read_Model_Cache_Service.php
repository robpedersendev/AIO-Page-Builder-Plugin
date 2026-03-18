<?php
/**
 * Site-local get/set/delete for industry read-model caches (industry-cache-contract.md; Prompt 434).
 * Uses transients; keys are scoped via Industry_Site_Scope_Helper. Safe fallback on miss or failure.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Cache;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Industry_Site_Scope_Helper;
use AIOPageBuilder\Infrastructure\Config\Option_Names;

/**
 * Bounded, site-local cache for industry read models. Values are JSON-encoded; TTL applied on set.
 * Cache miss or decode failure returns null; caller must compute and optionally set.
 */
final class Industry_Read_Model_Cache_Service {

	/** Default TTL in seconds (24 hours). */
	public const DEFAULT_TTL = 86400;

	/** Transient key prefix; must match Industry_Site_Scope_Helper::MULTISITE_PREFIX for uninstall discovery. */
	public const TRANSIENT_PREFIX = 'aio_industry_';

	/** Option name for cache version; bump to invalidate all industry read-model caches (Prompt 435). Use Option_Names::INDUSTRY_CACHE_VERSION for uninstall. */
	public const OPTION_CACHE_VERSION = Option_Names::INDUSTRY_CACHE_VERSION;

	/** @var int */
	private int $default_ttl;

	public function __construct( int $default_ttl = self::DEFAULT_TTL ) {
		$this->default_ttl = $default_ttl > 0 ? $default_ttl : self::DEFAULT_TTL;
	}

	/**
	 * Gets cached value by base key. Applies site scoping and version. Returns decoded array or null on miss/failure.
	 *
	 * @param string $base_key Base key from Industry_Cache_Key_Builder (no site scope).
	 * @return array<string, mixed>|null Cached payload or null.
	 */
	public function get( string $base_key ): ?array {
		$scoped = $this->scoped_key_with_version( $base_key );
		if ( ! function_exists( 'get_transient' ) ) {
			return null;
		}
		$raw = get_transient( $scoped );
		if ( $raw === false || ! is_string( $raw ) ) {
			return null;
		}
		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}
		return $decoded;
	}

	/**
	 * Stores value with TTL. Applies site scoping and version. Safe: no throw.
	 *
	 * @param string               $base_key Base key.
	 * @param array<string, mixed> $value    Payload to store (JSON-encodable).
	 * @param int|null             $ttl      Seconds; null = default.
	 * @return bool True if set_transient succeeded (or equivalent).
	 */
	public function set( string $base_key, array $value, ?int $ttl = null ): bool {
		$scoped = $this->scoped_key_with_version( $base_key );
		$ttl    = $ttl !== null && $ttl > 0 ? $ttl : $this->default_ttl;
		$json   = json_encode( $value );
		if ( $json === false ) {
			return false;
		}
		if ( ! function_exists( 'set_transient' ) ) {
			return false;
		}
		return set_transient( $scoped, $json, $ttl );
	}

	/**
	 * Deletes a single entry by base key. Applies site scoping and version.
	 *
	 * @param string $base_key Base key.
	 * @return bool True if deleted or not present.
	 */
	public function delete( string $base_key ): bool {
		$scoped = $this->scoped_key_with_version( $base_key );
		if ( ! function_exists( 'delete_transient' ) ) {
			return true;
		}
		return delete_transient( $scoped );
	}

	/**
	 * Bumps the cache version so all existing industry read-model entries become stale (next get will miss).
	 * Call on profile, pack, subtype, bundle, or overlay changes per industry-cache-invalidation-map.
	 *
	 * @return void
	 */
	public function invalidate_all_industry_read_models(): void {
		if ( ! function_exists( 'get_option' ) || ! function_exists( 'update_option' ) ) {
			return;
		}
		$ver = (int) get_option( self::OPTION_CACHE_VERSION, 1 );
		update_option( self::OPTION_CACHE_VERSION, $ver + 1, true );
	}

	private function scoped_key_with_version( string $base_key ): string {
		$scoped = Industry_Site_Scope_Helper::scope_cache_key( $base_key );
		$ver    = function_exists( 'get_option' ) ? (int) get_option( self::OPTION_CACHE_VERSION, 1 ) : 1;
		return $scoped . '_v' . $ver;
	}
}
