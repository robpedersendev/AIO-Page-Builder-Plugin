<?php
/**
 * URL discovery and pre-fetch filtering (spec §24.3, §24.8, §24.9; crawler rules contract §3, §6, §7).
 * Discovers candidate URLs from seeds and link sets; normalizes and filters; no HTTP fetching.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Discovery;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts candidate URLs from seed list or fetcher-provided links, normalizes them,
 * applies same-host and exclusion rules, and returns accepted/rejected/duplicate results.
 */
final class URL_Discovery_Service {

	/** Rejection: URL host is not the canonical crawl host. */
	public const REJECT_EXTERNAL_HOST = 'external_host';

	/** Rejection: admin path (wp-admin). */
	public const REJECT_PROHIBITED_ADMIN = 'prohibited_admin';

	/** Rejection: login/auth path or endpoint. */
	public const REJECT_PROHIBITED_LOGIN = 'prohibited_login';

	/** Rejection: REST or admin-ajax endpoint. */
	public const REJECT_PROHIBITED_REST_AJAX = 'prohibited_rest_ajax';

	/** Rejection: credential-bearing query params. */
	public const REJECT_PROHIBITED_CREDENTIAL_BEARING = 'prohibited_credential_bearing';

	/** Rejection: binary/file URL (e.g. .pdf, .zip). */
	public const REJECT_PROHIBITED_FILE_MEDIA = 'prohibited_file_media';

	/** Ignored page type reason codes (contract §6). */
	public const REJECT_IGNORED_CART         = 'ignored_cart';
	public const REJECT_IGNORED_CHECKOUT     = 'ignored_checkout';
	public const REJECT_IGNORED_ACCOUNT      = 'ignored_account';
	public const REJECT_IGNORED_LOGIN        = 'ignored_login';
	public const REJECT_IGNORED_SEARCH       = 'ignored_search';
	public const REJECT_IGNORED_FEED         = 'ignored_feed';
	public const REJECT_IGNORED_ATTACHMENT   = 'ignored_attachment';
	public const REJECT_IGNORED_THANKYOU     = 'ignored_thankyou';
	public const REJECT_IGNORED_ORDER_STATUS = 'ignored_order_status';
	public const REJECT_IGNORED_PREVIEW      = 'ignored_preview';
	public const REJECT_IGNORED_ARCHIVE      = 'ignored_archive';
	public const REJECT_IGNORED_PAGINATION   = 'ignored_pagination';
	public const REJECT_IGNORED_FACETED      = 'ignored_faceted';

	/** Path segments that indicate login/auth (contract §3.2, §6). */
	private const AUTH_PATH_SEGMENTS = array(
		'login',
		'signin',
		'signup',
		'register',
		'auth',
	);

	/** Path segments that indicate ignored page types (contract §6). */
	private const IGNORED_PATH_SEGMENTS = array(
		'cart'           => self::REJECT_IGNORED_CART,
		'basket'         => self::REJECT_IGNORED_CART,
		'checkout'       => self::REJECT_IGNORED_CHECKOUT,
		'pay'            => self::REJECT_IGNORED_CHECKOUT,
		'payment'        => self::REJECT_IGNORED_CHECKOUT,
		'account'        => self::REJECT_IGNORED_ACCOUNT,
		'my-account'     => self::REJECT_IGNORED_ACCOUNT,
		'dashboard'      => self::REJECT_IGNORED_ACCOUNT,
		'login'          => self::REJECT_IGNORED_LOGIN,
		'register'       => self::REJECT_IGNORED_LOGIN,
		'signup'         => self::REJECT_IGNORED_LOGIN,
		'search'         => self::REJECT_IGNORED_SEARCH,
		'feed'           => self::REJECT_IGNORED_FEED,
		'rss'            => self::REJECT_IGNORED_FEED,
		'thank-you'      => self::REJECT_IGNORED_THANKYOU,
		'order-received' => self::REJECT_IGNORED_THANKYOU,
		'confirmation'   => self::REJECT_IGNORED_THANKYOU,
		'order-status'   => self::REJECT_IGNORED_ORDER_STATUS,
		'track-order'    => self::REJECT_IGNORED_ORDER_STATUS,
		'tag'            => self::REJECT_IGNORED_ARCHIVE,
		'date'           => self::REJECT_IGNORED_ARCHIVE,
		'author'         => self::REJECT_IGNORED_ARCHIVE,
	);

	/** Query param names that indicate search (contract §6). */
	private const SEARCH_QUERY_PARAMS = array( 's', 'q', 'search' );

	/** Query param names that indicate feed. */
	private const FEED_QUERY_PARAMS = array( 'feed' );

	/** Query param names that indicate preview. */
	private const PREVIEW_QUERY_PARAMS = array( 'preview' );

	/** Query param names that suggest credential/session (contract §3.2). */
	private const CREDENTIAL_QUERY_NAMES = array( 'token', 'key', 'session', 'sid', 'auth', 'nonce' );

	/** File extensions that are not HTML pages (contract §3.2). */
	private const NON_HTML_EXTENSIONS = array( 'pdf', 'zip', 'rar', 'tar', 'gz', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'mp3', 'mp4', 'webm', 'doc', 'docx', 'xls', 'xlsx' );

	/** @var URL_Normalizer */
	private $normalizer;

	public function __construct( URL_Normalizer $normalizer ) {
		$this->normalizer = $normalizer;
	}

	/**
	 * Processes seed URLs and returns discovery results (accepted, rejected, duplicate).
	 * Seeds are normalized and filtered; duplicates by dedup_key are marked duplicate.
	 *
	 * @param array<int, string> $seed_urls Raw seed URLs (e.g. homepage, sitemap URLs).
	 * @return array<int, Discovery_Result>
	 */
	public function discover_from_seeds( array $seed_urls ): array {
		return $this->process_candidates( $seed_urls, Discovery_Result::SOURCE_SEED );
	}

	/**
	 * Processes candidate URLs extracted from a page (e.g. from HTML or link set).
	 * Caller is responsible for extracting hrefs; this method only normalizes and filters.
	 *
	 * @param array<int, string> $link_urls Raw URLs (e.g. from fetcher-provided link set).
	 * @param string             $discovery_source One of Discovery_Result::SOURCE_*.
	 * @return array<int, Discovery_Result>
	 */
	public function discover_from_links( array $link_urls, string $discovery_source = Discovery_Result::SOURCE_LINK ): array {
		return $this->process_candidates( $link_urls, $discovery_source );
	}

	/**
	 * Processes a list of raw candidate URLs: normalize, filter, deduplicate.
	 *
	 * @param array<int, string> $candidates Raw candidate URLs.
	 * @param string             $discovery_source SOURCE_SEED, SOURCE_LINK, or SOURCE_SITEMAP.
	 * @return array<int, Discovery_Result>
	 */
	private function process_candidates( array $candidates, string $discovery_source ): array {
		$results   = array();
		$seen_keys = array();
		$source    = in_array( $discovery_source, array( Discovery_Result::SOURCE_SEED, Discovery_Result::SOURCE_LINK, Discovery_Result::SOURCE_SITEMAP ), true )
			? $discovery_source
			: Discovery_Result::SOURCE_LINK;

		foreach ( $candidates as $raw ) {
			if ( ! is_string( $raw ) || trim( $raw ) === '' ) {
				continue;
			}
			$normalized = $this->normalizer->normalize( $raw );
			if ( $normalized === '' ) {
				$results[] = new Discovery_Result( $raw, $source, Discovery_Result::STATUS_REJECTED, self::REJECT_EXTERNAL_HOST, '' );
				continue;
			}
			$dedup_key = $this->normalizer->dedup_key( $normalized );
			if ( $dedup_key === '' ) {
				continue;
			}
			$rejection = $this->classify_rejection( $normalized, $raw );
			if ( $rejection !== null ) {
				$results[] = new Discovery_Result( $normalized, $source, Discovery_Result::STATUS_REJECTED, $rejection, $dedup_key );
				continue;
			}
			if ( isset( $seen_keys[ $dedup_key ] ) ) {
				$results[] = new Discovery_Result( $normalized, $source, Discovery_Result::STATUS_DUPLICATE, null, $dedup_key );
				continue;
			}
			$seen_keys[ $dedup_key ] = true;
			$results[]               = new Discovery_Result( $normalized, $source, Discovery_Result::STATUS_ACCEPTED, null, $dedup_key );
		}

		return $results;
	}

	/**
	 * Classifies whether a normalized URL should be rejected (prohibited or ignored).
	 *
	 * @param string $normalized_url Normalized same-host URL.
	 * @param string $raw_url        Original URL (for query param checks before normalization).
	 * @return string|null Rejection code or null if accepted.
	 */
	private function classify_rejection( string $normalized_url, string $raw_url ): ?string {
		$path      = $this->path_from_url( $normalized_url );
		$query     = $this->query_from_url( $normalized_url );
		$raw_query = $this->query_from_url( $raw_url );

		$path_lower = strtolower( $path );
		$segments   = array_filter( explode( '/', trim( $path, '/' ) ) );

		if ( $path_lower === '/wp-admin' || strpos( $path_lower, '/wp-admin/' ) === 0 ) {
			return self::REJECT_PROHIBITED_ADMIN;
		}
		if ( in_array( 'wp-login.php', $segments, true ) || $path_lower === '/wp-login.php' || strpos( $path_lower, '/wp-login.php' ) !== false ) {
			return self::REJECT_PROHIBITED_LOGIN;
		}
		foreach ( array( 'admin-ajax.php', 'wp-json' ) as $seg ) {
			if ( in_array( $seg, $segments, true ) || strpos( $path_lower, '/' . $seg . '/' ) === 0 || rtrim( $path_lower, '/' ) === '/' . $seg ) {
				return self::REJECT_PROHIBITED_REST_AJAX;
			}
		}
		foreach ( self::AUTH_PATH_SEGMENTS as $auth_seg ) {
			if ( in_array( $auth_seg, $segments, true ) ) {
				return self::REJECT_IGNORED_LOGIN;
			}
		}
		$path_normalized = rtrim( $path_lower, '/' ) !== '' ? rtrim( $path_lower, '/' ) : '/';
		foreach ( self::IGNORED_PATH_SEGMENTS as $seg => $code ) {
			$first = (string) ( array_values( $segments )[0] ?? '' );
			if ( strtolower( $first ) === $seg || strpos( $path_lower, '/' . $seg . '/' ) === 0 || $path_normalized === '/' . $seg ) {
				return $code;
			}
		}
		if ( $this->has_search_query( $query ) || $this->has_search_query( $raw_query ) ) {
			return self::REJECT_IGNORED_SEARCH;
		}
		if ( $this->has_feed_query( $query ) || $this->has_feed_query( $raw_query ) ) {
			return self::REJECT_IGNORED_FEED;
		}
		if ( $this->has_preview_query( $query ) || $this->has_preview_query( $raw_query ) ) {
			return self::REJECT_IGNORED_PREVIEW;
		}
		if ( $this->has_credential_query( $raw_query ) ) {
			return self::REJECT_PROHIBITED_CREDENTIAL_BEARING;
		}
		if ( $this->is_pagination_path( $path, $query ) ) {
			return self::REJECT_IGNORED_PAGINATION;
		}
		if ( $this->has_non_html_extension( $path ) ) {
			return self::REJECT_PROHIBITED_FILE_MEDIA;
		}
		if ( $this->looks_like_attachment( $path, $segments ) ) {
			return self::REJECT_IGNORED_ATTACHMENT;
		}

		return null;
	}

	private function path_from_url( string $url ): string {
		$p    = parse_url( $url );
		$path = isset( $p['path'] ) ? $p['path'] : '/';
		return '/' . trim( $path, '/' );
	}

	private function query_from_url( string $url ): string {
		$p = parse_url( $url );
		return isset( $p['query'] ) ? $p['query'] : '';
	}

	private function has_search_query( string $query ): bool {
		if ( $query === '' ) {
			return false;
		}
		parse_str( $query, $params );
		foreach ( self::SEARCH_QUERY_PARAMS as $name ) {
			if ( isset( $params[ $name ] ) && (string) $params[ $name ] !== '' ) {
				return true;
			}
		}
		return false;
	}

	private function has_feed_query( string $query ): bool {
		if ( $query === '' ) {
			return false;
		}
		parse_str( $query, $params );
		foreach ( self::FEED_QUERY_PARAMS as $name ) {
			if ( isset( $params[ $name ] ) ) {
				return true;
			}
		}
		return false;
	}

	private function has_preview_query( string $query ): bool {
		if ( $query === '' ) {
			return false;
		}
		parse_str( $query, $params );
		foreach ( self::PREVIEW_QUERY_PARAMS as $name ) {
			if ( isset( $params[ $name ] ) ) {
				return true;
			}
		}
		return false;
	}

	private function has_credential_query( string $query ): bool {
		if ( $query === '' ) {
			return false;
		}
		parse_str( $query, $params );
		foreach ( array_keys( $params ) as $name ) {
			$lower = strtolower( $name );
			foreach ( self::CREDENTIAL_QUERY_NAMES as $cred ) {
				if ( $lower === $cred || strpos( $lower, $cred ) !== false ) {
					return true;
				}
			}
		}
		return false;
	}

	private function is_pagination_path( string $path, string $query ): bool {
		if ( $query !== '' ) {
			parse_str( $query, $params );
			$paged = $params['paged'] ?? $params['page'] ?? null;
			if ( $paged !== null && (int) $paged > 1 ) {
				return true;
			}
		}
		if ( preg_match( '#/page/[2-9]\d*$#', $path ) ) {
			return true;
		}
		return false;
	}

	private function has_non_html_extension( string $path ): bool {
		$base = basename( $path );
		$pos  = strrpos( $base, '.' );
		if ( $pos === false ) {
			return false;
		}
		$ext = strtolower( substr( $base, $pos + 1 ) );
		return in_array( $ext, self::NON_HTML_EXTENSIONS, true );
	}

	private function looks_like_attachment( string $path, array $segments ): bool {
		if ( in_array( 'attachment', $segments, true ) ) {
			return true;
		}
		if ( preg_match( '#/wp-content/uploads/#', $path ) && $this->has_non_html_extension( $path ) ) {
			return true;
		}
		return false;
	}
}
