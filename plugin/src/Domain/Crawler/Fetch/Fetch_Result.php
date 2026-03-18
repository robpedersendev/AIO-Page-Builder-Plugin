<?php
/**
 * Structured outcome of a single HTML fetch (spec §24.7, §24.15, §24.16; crawler contract §10, §11).
 * Machine-readable: normalized_url, http_status, content_type, fetch_status, error_code, response_time_ms, html.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Fetch;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable result of one fetch attempt. Used for diagnostics and downstream classification.
 */
final class Fetch_Result {

	/** Fetch succeeded; response is HTML and within policy. */
	public const FETCH_STATUS_SUCCESS = 'success';

	/** Fetch failed: request timed out. */
	public const FETCH_STATUS_TIMEOUT = 'timeout';

	/** Fetch failed: blocked (e.g. robots, connection refused, TLS). */
	public const FETCH_STATUS_BLOCKED = 'blocked';

	/** Fetch failed: response was not HTML (content-type gating). */
	public const FETCH_STATUS_NON_HTML = 'non_html';

	/** Fetch failed: unsupported content type or empty. */
	public const FETCH_STATUS_UNSUPPORTED = 'unsupported_content';

	/** Fetch failed: generic transport/HTTP error. */
	public const FETCH_STATUS_FAILURE = 'failure';

	/** Fetch refused: URL disallowed (e.g. not same-host). */
	public const FETCH_STATUS_DISALLOWED = 'disallowed';

	/** Error code: contract §11. */
	public const ERROR_TIMEOUT             = 'timeout_failure';
	public const ERROR_TRANSPORT           = 'provider/transport_failure';
	public const ERROR_EXCESSIVE_REDIRECTS = 'excessive_redirects';
	public const ERROR_MALFORMED_RESPONSE  = 'malformed_response';
	public const ERROR_UNSUPPORTED_CONTENT = 'unsupported_content_type';
	public const ERROR_LOGIN_GATED         = 'login_gated';
	public const ERROR_DISALLOWED_URL      = 'disallowed_url';

	/** @var string Normalized URL that was requested. */
	public $normalized_url;

	/** @var int|null HTTP status code (e.g. 200); null if no response. */
	public $http_status;

	/** @var string|null Content-Type value; null if not present. */
	public $content_type;

	/** @var string One of FETCH_STATUS_*. */
	public $fetch_status;

	/** @var string|null Diagnostic error code when fetch_status is not success. */
	public $error_code;

	/** @var int Response time in milliseconds; 0 if not measured. */
	public $response_time_ms;

	/** @var string|null Response body (HTML) or excerpt; null on failure or when not stored. */
	public $html;

	/** @var array<string, string> Safe subset of response headers (e.g. content-type); minimal. */
	public $headers_subset;

	/** @var string|null Final URL after redirects; null if same as normalized_url or not followed. */
	public $final_url;

	/**
	 * @param string                $normalized_url   URL requested.
	 * @param int|null              $http_status      HTTP status.
	 * @param string|null           $content_type     Content-Type header.
	 * @param string                $fetch_status     One of FETCH_STATUS_*.
	 * @param string|null           $error_code       Error code when not success.
	 * @param int                   $response_time_ms Time in ms.
	 * @param string|null           $html             Body or null.
	 * @param array<string, string> $headers_subset Safe headers.
	 * @param string|null           $final_url        Final URL after redirects.
	 */
	public function __construct(
		string $normalized_url,
		?int $http_status,
		?string $content_type,
		string $fetch_status,
		?string $error_code,
		int $response_time_ms,
		?string $html,
		array $headers_subset = array(),
		?string $final_url = null
	) {
		$this->normalized_url   = $normalized_url;
		$this->http_status      = $http_status;
		$this->content_type     = $content_type;
		$this->fetch_status     = $fetch_status;
		$this->error_code       = $error_code;
		$this->response_time_ms = $response_time_ms;
		$this->html             = $html;
		$this->headers_subset   = $headers_subset;
		$this->final_url        = $final_url;
	}

	/**
	 * Whether the fetch succeeded and returned HTML.
	 *
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->fetch_status === self::FETCH_STATUS_SUCCESS;
	}

	/**
	 * Returns a machine-readable array for logging or persistence.
	 *
	 * @return array{normalized_url: string, http_status: int|null, content_type: string|null, fetch_status: string, error_code: string|null, response_time_ms: int, final_url: string|null, has_html: bool}
	 */
	public function to_array(): array {
		return array(
			'normalized_url'   => $this->normalized_url,
			'http_status'      => $this->http_status,
			'content_type'     => $this->content_type,
			'fetch_status'     => $this->fetch_status,
			'error_code'       => $this->error_code,
			'response_time_ms' => $this->response_time_ms,
			'final_url'        => $this->final_url,
			'has_html'         => $this->html !== null && $this->html !== '',
		);
	}
}
