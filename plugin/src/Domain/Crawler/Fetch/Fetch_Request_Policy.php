<?php
/**
 * Request policy for the crawler fetcher (spec §24.7, §24.8; crawler contract §10).
 * Timeout, rate delay, user-agent, redirect cap; no auth, no cookies.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Crawler\Fetch;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable policy: timeout, delay between requests, user-agent, max redirects.
 * GET only; no cookies or auth headers.
 */
final class Fetch_Request_Policy {

	/** Default request timeout in seconds (contract §10). */
	public const DEFAULT_TIMEOUT_SECONDS = 8;

	/** Default delay between requests in milliseconds (contract §10). */
	public const DEFAULT_DELAY_MS = 250;

	/** Default max redirect hops (contract §9). */
	public const DEFAULT_MAX_REDIRECTS = 3;

	/** User-Agent format: AIOPageBuilderCrawler/{version} ({site_url}). */
	private const USER_AGENT_FORMAT = 'AIOPageBuilderCrawler/%s (%s)';

	/** @var int Timeout in seconds. */
	private $timeout_seconds;

	/** @var int Delay between requests in milliseconds (for caller to enforce). */
	private $delay_after_request_ms;

	/** @var int Max redirect hops. */
	private $max_redirects;

	/** @var string User-Agent string. */
	private $user_agent;

	/**
	 * @param int    $timeout_seconds       Request timeout (default 8).
	 * @param int    $delay_after_request_ms Delay in ms between requests (default 250).
	 * @param int    $max_redirects         Max redirect hops (default 3).
	 * @param string $user_agent            Full User-Agent string (default built from version + site URL).
	 */
	public function __construct(
		int $timeout_seconds = self::DEFAULT_TIMEOUT_SECONDS,
		int $delay_after_request_ms = self::DEFAULT_DELAY_MS,
		int $max_redirects = self::DEFAULT_MAX_REDIRECTS,
		string $user_agent = ''
	) {
		$this->timeout_seconds        = $timeout_seconds > 0 ? $timeout_seconds : self::DEFAULT_TIMEOUT_SECONDS;
		$this->delay_after_request_ms = $delay_after_request_ms >= 0 ? $delay_after_request_ms : self::DEFAULT_DELAY_MS;
		$this->max_redirects          = $max_redirects >= 0 ? $max_redirects : self::DEFAULT_MAX_REDIRECTS;
		$this->user_agent             = $user_agent !== ''
			? $user_agent
			: sprintf( self::USER_AGENT_FORMAT, $this->default_version(), $this->default_site_url() );
	}

	/** @return int Timeout in seconds. */
	public function get_timeout_seconds(): int {
		return $this->timeout_seconds;
	}

	/** @return int Delay in ms after each request (caller must sleep). */
	public function get_delay_after_request_ms(): int {
		return $this->delay_after_request_ms;
	}

	/** @return int Max redirect hops. */
	public function get_max_redirects(): int {
		return $this->max_redirects;
	}

	/** @return string User-Agent. */
	public function get_user_agent(): string {
		return $this->user_agent;
	}

	/**
	 * Headers to send with each request: User-Agent only; no cookies or auth.
	 *
	 * @return array<string, string>
	 */
	public function get_request_headers(): array {
		return array(
			'User-Agent' => $this->user_agent,
		);
	}

	private function default_version(): string {
		if ( class_exists( '\\AIOPageBuilder\\Bootstrap\\Constants' ) ) {
			return \AIOPageBuilder\Bootstrap\Constants::plugin_version();
		}
		return '1.0';
	}

	private function default_site_url(): string {
		return function_exists( 'home_url' ) ? (string) home_url( '', 'https' ) : '';
	}
}
