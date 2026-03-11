<?php
/**
 * Respectful HTML fetcher: bounded, rate-limited, content-type gated (spec §24.2, §24.7, §24.8; crawler contract §10, §11).
 * Fetches allowed public URLs only; returns structured Fetch_Result. No auth, no cookies.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Fetch;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches a single URL via GET; enforces timeout, redirect cap, and HTML content-type.
 * Caller is responsible for rate delay and same-host filtering; fetcher refuses disallowed URLs if checker provided.
 */
final class HTML_Fetcher {

	/** Content-Type values accepted as HTML (contract: HTML pages only). */
	private const HTML_CONTENT_TYPES = array(
		'text/html',
		'application/xhtml+xml',
		'application/xml',
	);

	/** @var Fetch_Request_Policy */
	private $policy;

	/** @var callable|null (string $url): bool — returns false if URL must not be fetched (e.g. not same-host). */
	private $allowed_url_checker;

	/**
	 * @param Fetch_Request_Policy   $policy             Timeout, redirect cap, user-agent.
	 * @param callable|null           $allowed_url_checker Optional. (string $url): bool — do not fetch if false.
	 */
	public function __construct( Fetch_Request_Policy $policy, ?callable $allowed_url_checker = null ) {
		$this->policy              = $policy;
		$this->allowed_url_checker  = $allowed_url_checker;
	}

	/**
	 * Fetches one URL. Returns structured result; does not throw.
	 * Caller should enforce get_delay_after_request_ms() between calls.
	 *
	 * @param string $normalized_url Same-host, normalized URL (GET).
	 * @return Fetch_Result
	 */
	public function fetch( string $normalized_url ): Fetch_Result {
		$normalized_url = trim( $normalized_url );
		if ( $normalized_url === '' ) {
			return $this->disallowed_result( $normalized_url, 'empty_url' );
		}
		if ( $this->allowed_url_checker !== null && ! ( $this->allowed_url_checker )( $normalized_url ) ) {
			return $this->disallowed_result( $normalized_url, Fetch_Result::ERROR_DISALLOWED_URL );
		}
		$start = microtime( true );
		$response = $this->do_request( $normalized_url );
		$response_time_ms = (int) ( ( microtime( true ) - $start ) * 1000 );
		if ( is_wp_error( $response ) ) {
			return $this->error_result( $normalized_url, $response, $response_time_ms );
		}
		return $this->parse_response( $normalized_url, $response, $response_time_ms );
	}

	/**
	 * Builds request args for wp_remote_get from policy (no cookies, no auth).
	 *
	 * @param string $url URL to fetch.
	 * @return array<string, mixed>
	 */
	private function do_request( string $url ): array|\WP_Error {
		$args = array(
			'timeout'     => $this->policy->get_timeout_seconds(),
			'redirection' => $this->policy->get_max_redirects(),
			'user-agent'  => $this->policy->get_user_agent(),
			'sslverify'   => true,
			'httpversion' => '1.1',
		);
		return wp_remote_get( $url, $args );
	}

	/**
	 * @param string   $normalized_url
	 * @param \WP_Error $wp_error
	 * @param int      $response_time_ms
	 * @return Fetch_Result
	 */
	private function error_result( string $normalized_url, \WP_Error $wp_error, int $response_time_ms ): Fetch_Result {
		$code = $wp_error->get_error_code();
		$message = $wp_error->get_error_message();
		$error_code = Fetch_Result::ERROR_TRANSPORT;
		$fetch_status = Fetch_Result::FETCH_STATUS_FAILURE;
		if ( strpos( strtolower( $message ), 'timeout' ) !== false || $code === 'http_request_failed' && strpos( strtolower( $message ), 'timed' ) !== false ) {
			$error_code = Fetch_Result::ERROR_TIMEOUT;
			$fetch_status = Fetch_Result::FETCH_STATUS_TIMEOUT;
		} elseif ( strpos( strtolower( $message ), 'refused' ) !== false || strpos( strtolower( $message ), 'could not resolve' ) !== false ) {
			$fetch_status = Fetch_Result::FETCH_STATUS_BLOCKED;
		}
		return new Fetch_Result(
			$normalized_url,
			null,
			null,
			$fetch_status,
			$error_code,
			$response_time_ms,
			null,
			array(),
			null
		);
	}

	/**
	 * @param string $normalized_url
	 * @param array  $response wp_remote_get response array.
	 * @param int    $response_time_ms
	 * @return Fetch_Result
	 */
	private function parse_response( string $normalized_url, array $response, int $response_time_ms ): Fetch_Result {
		$code = (int) wp_remote_retrieve_response_code( $response );
		$headers = wp_remote_retrieve_headers( $response );
		$content_type = isset( $headers['content-type'] ) ? $this->normalize_content_type( $headers['content-type'] ) : null;
		$body = wp_remote_retrieve_body( $response );
		$final_url = null;
		if ( isset( $response['http_response'] ) && is_object( $response['http_response'] ) && method_exists( $response['http_response'], 'get_response_object' ) ) {
			$obj = $response['http_response']->get_response_object();
			if ( $obj !== null && isset( $obj->url ) && is_string( $obj->url ) ) {
				$final_url = $obj->url;
			}
		}
		$headers_subset = array();
		if ( $content_type !== null ) {
			$headers_subset['content-type'] = $content_type;
		}
		if ( $final_url === '' ) {
			$final_url = null;
		}
		if ( $code >= 300 && $code < 400 ) {
			return new Fetch_Result(
				$normalized_url,
				$code,
				$content_type,
				Fetch_Result::FETCH_STATUS_FAILURE,
				Fetch_Result::ERROR_EXCESSIVE_REDIRECTS,
				$response_time_ms,
				null,
				$headers_subset,
				$final_url ?: $normalized_url
			);
		}
		if ( $code < 200 || $code >= 300 ) {
			return new Fetch_Result(
				$normalized_url,
				$code,
				$content_type,
				Fetch_Result::FETCH_STATUS_FAILURE,
				Fetch_Result::ERROR_TRANSPORT,
				$response_time_ms,
				null,
				$headers_subset,
				$final_url
			);
		}
		if ( $content_type === null || ! $this->is_html_content_type( $content_type ) ) {
			return new Fetch_Result(
				$normalized_url,
				$code,
				$content_type,
				Fetch_Result::FETCH_STATUS_NON_HTML,
				Fetch_Result::ERROR_UNSUPPORTED_CONTENT,
				$response_time_ms,
				null,
				$headers_subset,
				$final_url
			);
		}
		if ( ! is_string( $body ) ) {
			return new Fetch_Result(
				$normalized_url,
				$code,
				$content_type,
				Fetch_Result::FETCH_STATUS_FAILURE,
				Fetch_Result::ERROR_MALFORMED_RESPONSE,
				$response_time_ms,
				null,
				$headers_subset,
				$final_url
			);
		}
		return new Fetch_Result(
			$normalized_url,
			$code,
			$content_type,
			Fetch_Result::FETCH_STATUS_SUCCESS,
			null,
			$response_time_ms,
			$body,
			$headers_subset,
			$final_url
		);
	}

	private function is_html_content_type( string $content_type ): bool {
		$lower = strtolower( trim( $content_type ) );
		foreach ( self::HTML_CONTENT_TYPES as $allowed ) {
			if ( $lower === $allowed || strpos( $lower, $allowed ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	/** Extract primary content-type (strip charset etc). */
	private function normalize_content_type( $raw ): string {
		if ( is_array( $raw ) ) {
			$raw = $raw[0] ?? '';
		}
		$s = trim( (string) $raw );
		$semicolon = strpos( $s, ';' );
		if ( $semicolon !== false ) {
			$s = trim( substr( $s, 0, $semicolon ) );
		}
		return $s;
	}

	private function disallowed_result( string $url, string $error_code ): Fetch_Result {
		return new Fetch_Result(
			$url,
			null,
			null,
			Fetch_Result::FETCH_STATUS_DISALLOWED,
			$error_code,
			0,
			null,
			array(),
			null
		);
	}
}
