<?php
/**
 * Builds normalized response shapes and error objects per ai-provider-contract.md (spec §25.11).
 * No credentials or raw provider payloads in logs; error categories and retry posture are stable.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Helper for normalized response and error shapes. Used by provider drivers and callers.
 */
final class Provider_Response_Normalizer {

	/** Error category: authentication failure. Retry: no. */
	public const ERROR_AUTH_FAILURE = 'auth_failure';

	/** Error category: rate limit. Retry: with backoff. */
	public const ERROR_RATE_LIMIT = 'rate_limit';

	/** Error category: timeout. Retry: with backoff. */
	public const ERROR_TIMEOUT = 'timeout';

	/** Error category: malformed response. Retry: once. */
	public const ERROR_MALFORMED_RESPONSE = 'malformed_response';

	/** Error category: structured output validation failed. Retry: no. */
	public const ERROR_VALIDATION_FAILURE = 'validation_failure';

	/** Error category: unsupported feature. Retry: no. */
	public const ERROR_UNSUPPORTED_FEATURE = 'unsupported_feature';

	/** Error category: generic provider error. Retry: with backoff. */
	public const ERROR_PROVIDER_ERROR = 'provider_error';

	/** Error category: network/transport. Retry: with backoff. */
	public const ERROR_NETWORK_ERROR = 'network_error';

	/** Retry: do not retry. */
	public const RETRY_NO_RETRY = 'no_retry';

	/** Retry: retry with backoff. */
	public const RETRY_WITH_BACKOFF = 'retry_with_backoff';

	/** Retry: retry once. */
	public const RETRY_ONCE = 'retry_once';

	/** Map category -> retry posture (contract §5.2). */
	private const CATEGORY_RETRY = array(
		self::ERROR_AUTH_FAILURE        => self::RETRY_NO_RETRY,
		self::ERROR_RATE_LIMIT          => self::RETRY_WITH_BACKOFF,
		self::ERROR_TIMEOUT             => self::RETRY_WITH_BACKOFF,
		self::ERROR_MALFORMED_RESPONSE  => self::RETRY_ONCE,
		self::ERROR_VALIDATION_FAILURE  => self::RETRY_NO_RETRY,
		self::ERROR_UNSUPPORTED_FEATURE => self::RETRY_NO_RETRY,
		self::ERROR_PROVIDER_ERROR      => self::RETRY_WITH_BACKOFF,
		self::ERROR_NETWORK_ERROR       => self::RETRY_WITH_BACKOFF,
	);

	/** Map category -> user-facing message (short, safe per §45.3). */
	private const CATEGORY_USER_MESSAGE = array(
		self::ERROR_AUTH_FAILURE        => 'AI service authentication failed. Check your settings.',
		self::ERROR_RATE_LIMIT          => 'The AI service is temporarily busy. Please try again shortly.',
		self::ERROR_TIMEOUT             => 'The request timed out. Please try again.',
		self::ERROR_MALFORMED_RESPONSE  => 'The AI response could not be read. Please try again.',
		self::ERROR_VALIDATION_FAILURE  => 'The AI response did not meet the required format.',
		self::ERROR_UNSUPPORTED_FEATURE => 'This AI model or feature is not supported for this action.',
		self::ERROR_PROVIDER_ERROR      => 'The AI service returned an error. Please try again later.',
		self::ERROR_NETWORK_ERROR       => 'A network error occurred. Please check your connection and try again.',
	);

	/**
	 * Builds a normalized error object (contract §5.1).
	 *
	 * @param string      $category   One of ERROR_* constants.
	 * @param string|null $provider_raw Optional provider-specific message (redacted if it could contain secrets).
	 * @return array{category: string, user_message: string, internal_code: string, provider_raw: string|null, retry_posture: string}
	 */
	public function normalize_error( string $category, ?string $provider_raw = null ): array {
		$retry        = self::CATEGORY_RETRY[ $category ] ?? self::RETRY_NO_RETRY;
		$user_message = self::CATEGORY_USER_MESSAGE[ $category ] ?? 'An unexpected error occurred. Please try again.';
		return array(
			'category'      => $category,
			'user_message'  => $user_message,
			'internal_code' => $category,
			'provider_raw'  => $provider_raw !== null && $provider_raw !== '' ? $provider_raw : null,
			'retry_posture' => $retry,
		);
	}

	/**
	 * Builds a full normalized response for an error (success=false, normalized_error set).
	 *
	 * @param string      $request_id  Request id from the request.
	 * @param string      $provider_id  Provider identifier.
	 * @param string      $model_used   Model used (or placeholder).
	 * @param string      $category     Error category (ERROR_*).
	 * @param string|null $provider_raw Optional raw provider message.
	 * @return array<string, mixed> Full normalized response shape.
	 */
	public function build_error_response(
		string $request_id,
		string $provider_id,
		string $model_used,
		string $category,
		?string $provider_raw = null
	): array {
		return array(
			'request_id'            => $request_id,
			'success'               => false,
			'structured_payload'    => null,
			'raw_content'           => null,
			'provider_id'           => $provider_id,
			'model_used'            => $model_used,
			'usage'                 => null,
			'raw_provider_metadata' => null,
			'normalized_error'      => $this->normalize_error( $category, $provider_raw ),
		);
	}

	/**
	 * Builds a full normalized response for success (success=true, structured_payload and optional usage).
	 *
	 * @param string       $request_id       Request id.
	 * @param string       $provider_id     Provider identifier.
	 * @param string       $model_used       Model used.
	 * @param array|object $structured_payload Validated structured payload.
	 * @param array|null   $usage            Optional usage (prompt_tokens, completion_tokens, total_tokens, cost_usd). cost_usd is computed from Provider_Cost_Calculator; null when model pricing is unknown.
	 * @param array|null   $raw_provider_metadata Optional provider metadata (debug only).
	 * @return array<string, mixed>
	 */
	public function build_success_response(
		string $request_id,
		string $provider_id,
		string $model_used,
		$structured_payload,
		?array $usage = null,
		?array $raw_provider_metadata = null
	): array {
		return array(
			'request_id'            => $request_id,
			'success'               => true,
			'structured_payload'    => $structured_payload,
			'raw_content'           => null,
			'provider_id'           => $provider_id,
			'model_used'            => $model_used,
			'usage'                 => $usage,
			'raw_provider_metadata' => $raw_provider_metadata,
			'normalized_error'      => null,
		);
	}

	/**
	 * Returns retry posture for a given error category.
	 *
	 * @param string $category One of ERROR_* constants.
	 * @return string One of RETRY_* constants.
	 */
	public function get_retry_posture( string $category ): string {
		return self::CATEGORY_RETRY[ $category ] ?? self::RETRY_NO_RETRY;
	}
}
