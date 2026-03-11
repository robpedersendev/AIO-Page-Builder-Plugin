<?php
/**
 * Maps provider-specific errors to stable plugin categories (spec §25.5, §45.1, ai-provider-contract.md §5).
 * No secrets in output; provider_raw is redacted when it could contain credentials.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Translates HTTP status, provider codes, and exceptions into contract error categories.
 * Uses Provider_Response_Normalizer for final envelope shape.
 */
final class Provider_Error_Normalizer {

	/** @var Provider_Response_Normalizer */
	private Provider_Response_Normalizer $response_normalizer;

	public function __construct( ?Provider_Response_Normalizer $response_normalizer = null ) {
		$this->response_normalizer = $response_normalizer ?? new Provider_Response_Normalizer();
	}

	/**
	 * Maps HTTP status and optional provider code to contract category.
	 *
	 * @param int         $http_status   HTTP status code (0 if not HTTP).
	 * @param string|null $provider_code Optional provider-specific error code.
	 * @param string|null $provider_message Optional raw message; redacted if it could contain secrets.
	 * @return string One of Provider_Response_Normalizer::ERROR_* constants.
	 */
	public function map_to_category( int $http_status, ?string $provider_code = null, ?string $provider_message = null ): string {
		if ( $http_status === 401 || $http_status === 403 ) {
			return Provider_Response_Normalizer::ERROR_AUTH_FAILURE;
		}
		if ( $http_status === 429 ) {
			return Provider_Response_Normalizer::ERROR_RATE_LIMIT;
		}
		if ( $http_status === 408 || $http_status === 504 || $http_status === 524 ) {
			return Provider_Response_Normalizer::ERROR_TIMEOUT;
		}
		if ( $http_status >= 500 && $http_status < 600 ) {
			return Provider_Response_Normalizer::ERROR_PROVIDER_ERROR;
		}
		if ( $http_status >= 400 && $http_status < 500 ) {
			$code = $provider_code !== null ? strtolower( $provider_code ) : '';
			if ( strpos( $code, 'invalid_request' ) !== false || strpos( $code, 'unsupported' ) !== false ) {
				return Provider_Response_Normalizer::ERROR_UNSUPPORTED_FEATURE;
			}
			return Provider_Response_Normalizer::ERROR_PROVIDER_ERROR;
		}
		$msg = $provider_message !== null ? strtolower( $provider_message ) : '';
		if ( strpos( $msg, 'timeout' ) !== false || strpos( $msg, 'timed out' ) !== false ) {
			return Provider_Response_Normalizer::ERROR_TIMEOUT;
		}
		if ( strpos( $msg, 'rate limit' ) !== false || strpos( $msg, 'rate_limit' ) !== false ) {
			return Provider_Response_Normalizer::ERROR_RATE_LIMIT;
		}
		if ( strpos( $msg, 'auth' ) !== false || strpos( $msg, 'api key' ) !== false || strpos( $msg, 'invalid key' ) !== false ) {
			return Provider_Response_Normalizer::ERROR_AUTH_FAILURE;
		}
		return Provider_Response_Normalizer::ERROR_PROVIDER_ERROR;
	}

	/**
	 * Maps a throwable to contract category.
	 *
	 * @param \Throwable $e Exception from provider call.
	 * @return string One of Provider_Response_Normalizer::ERROR_* constants.
	 */
	public function map_exception_to_category( \Throwable $e ): string {
		$msg = strtolower( $e->getMessage() );
		if ( strpos( $msg, 'timeout' ) !== false || strpos( $msg, 'timed out' ) !== false ) {
			return Provider_Response_Normalizer::ERROR_TIMEOUT;
		}
		if ( strpos( $msg, 'connection' ) !== false || strpos( $msg, 'network' ) !== false
			|| strpos( $msg, 'resolve' ) !== false || strpos( $msg, 'curl' ) !== false ) {
			return Provider_Response_Normalizer::ERROR_NETWORK_ERROR;
		}
		if ( $e instanceof \JsonException ) {
			return Provider_Response_Normalizer::ERROR_MALFORMED_RESPONSE;
		}
		return Provider_Response_Normalizer::ERROR_PROVIDER_ERROR;
	}

	/**
	 * Builds full normalized error response (success=false envelope).
	 *
	 * @param string      $request_id   Request id.
	 * @param string      $provider_id  Provider identifier.
	 * @param string      $model_used   Model used or placeholder.
	 * @param string      $category     One of Provider_Response_Normalizer::ERROR_*.
	 * @param string|null $provider_raw Optional provider message (redacted).
	 * @return array<string, mixed> Normalized response with normalized_error set.
	 */
	public function build_error_response(
		string $request_id,
		string $provider_id,
		string $model_used,
		string $category,
		?string $provider_raw = null
	): array {
		$safe_raw = $this->redact_provider_message( $provider_raw );
		return $this->response_normalizer->build_error_response( $request_id, $provider_id, $model_used, $category, $safe_raw );
	}

	/**
	 * Builds only the normalized_error object.
	 *
	 * @param string      $category     One of Provider_Response_Normalizer::ERROR_*.
	 * @param string|null $provider_raw Optional provider message (redacted).
	 * @return array{category: string, user_message: string, internal_code: string, provider_raw: string|null, retry_posture: string}
	 */
	public function normalize_error( string $category, ?string $provider_raw = null ): array {
		$safe_raw = $this->redact_provider_message( $provider_raw );
		return $this->response_normalizer->normalize_error( $category, $safe_raw );
	}

	/**
	 * Redacts provider message for logs and user-facing fields.
	 *
	 * @param string|null $message Raw provider message.
	 * @return string|null Redacted message or null if empty.
	 */
	public function redact_provider_message( ?string $message ): ?string {
		if ( $message === null || trim( $message ) === '' ) {
			return null;
		}
		$s = trim( $message );
		$s = preg_replace( '/sk-[a-zA-Z0-9]{20,}/', '[REDACTED]', $s );
		$s = preg_replace( '/api[_-]?key["\']?\s*[:=]\s*["\']?[^\s"\']+/i', 'api_key=[REDACTED]', $s );
		return $s !== '' ? $s : null;
	}
}
