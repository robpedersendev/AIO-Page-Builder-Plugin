<?php
/**
 * URL canonicalization and dedup key generation (crawler rules contract §3.4, §7.2).
 * Same-host only; removes fragments and tracking parameters; deterministic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes candidate URLs for crawl discovery: same-host canonical form,
 * fragment removal, tracking-parameter removal, and stable dedup key.
 */
final class URL_Normalizer {

	/** Default scheme used when normalizing (contract: one scheme per run). */
	public const DEFAULT_SCHEME = 'https';

	/** Query parameter names stripped before deduplication (contract §7.2, §3.3). */
	private const TRACKING_PARAMS = array(
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'fbclid',
		'gclid',
		'msclkid',
	);

	/** Max URL length for sanity (align with snapshot payload). */
	private const URL_MAX_LENGTH = 2048;

	/** @var string Canonical host for the crawl (no port, no scheme). */
	private $canonical_host;

	/** @var string Scheme to use when building normalized URL (e.g. https). */
	private $scheme;

	/**
	 * @param string $canonical_host Site canonical host (e.g. example.com).
	 * @param string $scheme        Scheme for normalized URLs (default https).
	 */
	public function __construct( string $canonical_host, string $scheme = self::DEFAULT_SCHEME ) {
		$this->canonical_host = $this->normalize_host_for_compare( $canonical_host );
		$this->scheme         = in_array( $scheme, array( 'http', 'https' ), true ) ? $scheme : self::DEFAULT_SCHEME;
	}

	/**
	 * Normalizes a URL to same-host canonical form: strip fragment and tracking params.
	 * Returns empty string if URL is invalid or not same-host.
	 *
	 * @param string $raw_url Raw candidate URL.
	 * @return string Normalized URL or empty if invalid/other host.
	 */
	public function normalize( string $raw_url ): string {
		$raw_url = trim( $raw_url );
		if ( $raw_url === '' || strlen( $raw_url ) > self::URL_MAX_LENGTH ) {
			return '';
		}
		$parsed = $this->parse_url_safe( $raw_url );
		if ( $parsed === null ) {
			return '';
		}
		$input_host = isset( $parsed['host'] ) ? strtolower( trim( (string) $parsed['host'], " \t\n\r." ) ) : '';
		if ( $input_host === '' && preg_match( '#^https?://([^/?#]+)#i', $raw_url, $m ) ) {
			$input_host = strtolower( trim( $m[1], " \t\n\r." ) );
		}
		if ( $input_host === '' || $input_host !== $this->canonical_host ) {
			return '';
		}
		$raw_path = $parsed['path'] ?? '/';
		if ( $raw_path === false || $raw_path === '' ) {
			$raw_path = '/';
		}
		$path  = $this->normalize_path( $raw_path );
		$query = $this->strip_tracking_from_query( $parsed['query'] ?? '' );
		return $this->build_url( $path, $query );
	}

	/**
	 * Builds a deterministic deduplication key for the URL (after normalization).
	 * Same logical page yields same key.
	 *
	 * @param string $normalized_url Already normalized URL from this normalizer.
	 * @return string Dedup key (stable string).
	 */
	public function dedup_key( string $normalized_url ): string {
		if ( $normalized_url === '' ) {
			return '';
		}
		$parsed = $this->parse_url_safe( $normalized_url );
		if ( $parsed === null ) {
			return $normalized_url;
		}
		$raw_path = $parsed['path'] ?? '/';
		$path     = $this->normalize_path( ( $raw_path === false || $raw_path === '' ) ? '/' : $raw_path );
		$query = $this->strip_tracking_from_query( $parsed['query'] ?? '' );
		$path  = $path === '' ? '/' : $path;
		$q     = $query !== '' ? '?' . $query : '';
		return $this->scheme . '://' . $this->canonical_host . $path . $q;
	}

	/**
	 * Checks whether a raw URL is same-host against the canonical host.
	 * Subdomains are not treated as same host (contract §3.2, §3.4).
	 *
	 * @param string $raw_url Raw candidate URL.
	 * @return bool True if host matches canonical (exact; no subdomain allowance).
	 */
	public function is_same_host_url( string $raw_url ): bool {
		$parsed = $this->parse_url_safe( trim( $raw_url ) );
		if ( $parsed === null ) {
			return false;
		}
		return $this->is_same_host( $parsed );
	}

	/**
	 * Returns the canonical host this normalizer is bound to.
	 *
	 * @return string
	 */
	public function get_canonical_host(): string {
		return $this->canonical_host;
	}

	/**
	 * Parses URL and returns components; invalid or non-http(s) returns null.
	 *
	 * @param string $url URL string.
	 * @return array{scheme?: string, host?: string, port?: int, path?: string, query?: string}|null
	 */
	private function parse_url_safe( string $url ): ?array {
		$scheme = substr( $url, 0, 5 );
		if ( $scheme !== 'http/' && $scheme !== 'https' ) {
			$url = 'https://' . ltrim( $url, '/' );
		}
		$parsed = parse_url( $url );
		if ( ! is_array( $parsed ) ) {
			$parsed = $this->parse_url_fallback( $url );
			if ( $parsed === null ) {
				return null;
			}
		} else {
			$host = $parsed['host'] ?? null;
			if ( $host === null || $host === '' || $host === false ) {
				if ( preg_match( '#^https?://([^/?#]+)#i', $url, $m ) ) {
					$parsed['host'] = $m[1];
				} else {
					return null;
				}
			}
		}
		$s = $parsed['scheme'] ?? 'https';
		if ( $s !== 'http' && $s !== 'https' ) {
			return null;
		}
		return $parsed;
	}

	/**
	 * Fallback when parse_url returns false (e.g. some PHPUnit/Windows environments).
	 *
	 * @param string $url Full URL.
	 * @return array{scheme: string, host: string, path: string, query?: string}|null
	 */
	private function parse_url_fallback( string $url ): ?array {
		if ( ! preg_match( '#^(https?)://([^/?#]+)(/[^?#]*)?(\?(?:[^#]*))?(\#.*)?$#i', $url, $m ) ) {
			if ( preg_match( '#^(https?)://([^/?#]+)/?$#i', $url, $m ) ) {
				return array( 'scheme' => strtolower( $m[1] ), 'host' => $m[2], 'path' => '/' );
			}
			return null;
		}
		return array(
			'scheme' => strtolower( $m[1] ),
			'host'   => $m[2],
			'path'   => isset( $m[3] ) && $m[3] !== '' ? $m[3] : '/',
			'query'  => isset( $m[4] ) ? substr( $m[4], 1 ) : '',
		);
	}

	/**
	 * Compares parsed host to canonical host (no subdomain match). Port 80/443 ignored.
	 *
	 * @param array{scheme?: string, host?: string, port?: int, path?: string, query?: string} $parsed
	 * @return bool
	 */
	private function is_same_host( array $parsed ): bool {
		$raw_host = $parsed['host'] ?? '';
		$host     = strtolower( trim( (string) $raw_host, " \t\n\r." ) );
		if ( $host === '' ) {
			return false;
		}
		$port   = isset( $parsed['port'] ) ? (int) $parsed['port'] : null;
		$compare = ( $port === 80 || $port === 443 || $port === null ) ? $host : $host . ':' . $port;
		return $compare === $this->canonical_host;
	}

	/**
	 * Lowercase host; strip default port from comparison (for constructor canonical_host).
	 *
	 * @param string $host Host with optional port.
	 * @return string
	 */
	private function normalize_host_for_compare( string $host ): string {
		$host = strtolower( trim( $host ) );
		$port = null;
		if ( strpos( $host, ':' ) !== false ) {
			$parts = explode( ':', $host, 2 );
			$host  = $parts[0];
			$port  = is_numeric( $parts[1] ?? '' ) ? (int) $parts[1] : null;
		}
		if ( $port === 80 || $port === 443 ) {
			return $host;
		}
		if ( $port !== null ) {
			return $host . ':' . $port;
		}
		return $host;
	}

	private function normalize_path( string $path ): string {
		$path = '/' . trim( $path, '/' );
		if ( $path === '//' ) {
			return '/';
		}
		return $path;
	}

	/**
	 * Removes tracking query parameters; preserves order of remaining params.
	 *
	 * @param string $query Query string (without leading ?).
	 * @return string
	 */
	private function strip_tracking_from_query( string $query ): string {
		if ( $query === '' ) {
			return '';
		}
		parse_str( $query, $params );
		if ( ! is_array( $params ) ) {
			return '';
		}
		foreach ( self::TRACKING_PARAMS as $name ) {
			unset( $params[ $name ] );
		}
		return http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	private function build_url( string $path, string $query ): string {
		$path = $path === '' ? '/' : $path;
		$q    = $query !== '' ? '?' . $query : '';
		return $this->scheme . '://' . $this->canonical_host . $path . $q;
	}
}
