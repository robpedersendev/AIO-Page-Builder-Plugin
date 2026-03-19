<?php
/**
 * Bounded cache for provider form-list responses (Prompt 237).
 * Stores items, outcome, and timestamp per provider; TTL and explicit invalidation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Integrations\FormProviders;

defined( 'ABSPATH' ) || exit;

/**
 * In-memory cache for picker form lists. No secrets; capability is caller's responsibility.
 */
final class Form_Provider_Picker_Cache_Service {

	/** Default TTL seconds (5 minutes). */
	private const DEFAULT_TTL = 300;

	/** Max cache entries to prevent unbounded growth. */
	private const MAX_ENTRIES = 50;

	/** @var int */
	private int $ttl_seconds;

	/**
	 * provider_key => array{ items: array, outcome: string, fetched_at: int }
	 *
	 * @var array<string, array{items: array, outcome: string, fetched_at: int}>
	 */
	private array $cache = array();

	public function __construct( int $ttl_seconds = self::DEFAULT_TTL ) {
		$this->ttl_seconds = $ttl_seconds > 0 ? $ttl_seconds : self::DEFAULT_TTL;
	}

	/**
	 * Returns cached form list for provider if present and not expired.
	 *
	 * @param string $provider_key
	 * @return array{items: array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string|null}>, outcome: string, fetched_at: int}|null
	 */
	public function get( string $provider_key ): ?array {
		$key = $this->sanitize_key( $provider_key );
		if ( $key === '' ) {
			return null;
		}
		$entry = $this->cache[ $key ] ?? null;
		if ( $entry === null ) {
			return null;
		}
		if ( ( time() - $entry['fetched_at'] ) > $this->ttl_seconds ) {
			return null;
		}
		return $entry;
	}

	/**
	 * Returns cached entry even if expired (for fallback when live fetch fails).
	 *
	 * @param string $provider_key
	 * @return array{items: array<int, array>, outcome: string, fetched_at: int}|null
	 */
	public function get_fallback( string $provider_key ): ?array {
		$key = $this->sanitize_key( $provider_key );
		return $key !== '' ? ( $this->cache[ $key ] ?? null ) : null;
	}

	/**
	 * Stores form list result for provider.
	 *
	 * @param string                                                                                            $provider_key
	 * @param array<int, array{provider_key: string, item_id: string, item_label: string, status_hint?: string|null}> $items
	 * @param string                                                                                            $outcome One of: success, empty, error.
	 * @return void
	 */
	public function set( string $provider_key, array $items, string $outcome = 'success' ): void {
		$key = $this->sanitize_key( $provider_key );
		if ( $key === '' ) {
			return;
		}
		$this->evict_if_needed();
		$this->cache[ $key ] = array(
			'items'      => $items,
			'outcome'    => in_array( $outcome, array( 'success', 'empty', 'error' ), true ) ? $outcome : 'error',
			'fetched_at' => time(),
		);
	}

	/**
	 * Removes cached entry for provider.
	 *
	 * @param string $provider_key
	 * @return void
	 */
	public function invalidate( string $provider_key ): void {
		$key = $this->sanitize_key( $provider_key );
		if ( $key !== '' ) {
			unset( $this->cache[ $key ] );
		}
	}

	/**
	 * Removes all cached entries.
	 *
	 * @return void
	 */
	public function clear(): void {
		$this->cache = array();
	}

	public function get_ttl_seconds(): int {
		return $this->ttl_seconds;
	}

	private function sanitize_key( string $key ): string {
		$key = preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
		return is_string( $key ) ? $key : '';
	}

	private function evict_if_needed(): void {
		if ( count( $this->cache ) < self::MAX_ENTRIES ) {
			return;
		}
		$oldest_key = null;
		$oldest_ts  = PHP_INT_MAX;
		foreach ( $this->cache as $k => $entry ) {
			if ( $entry['fetched_at'] < $oldest_ts ) {
				$oldest_ts  = $entry['fetched_at'];
				$oldest_key = $k;
			}
		}
		if ( $oldest_key !== null ) {
			unset( $this->cache[ $oldest_key ] );
		}
	}
}
