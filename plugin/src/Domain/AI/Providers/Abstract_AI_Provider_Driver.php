<?php
/**
 * Abstract base for AI provider drivers (spec §25.1, §25.5, §43.13, ai-provider-contract.md).
 * Translates provider-specific mechanics into normalized request/response; never logs secrets; never bypasses validator.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;

/**
 * Base driver: credential check, error normalization, response envelope. Subclasses implement do_perform_request().
 */
abstract class Abstract_AI_Provider_Driver implements AI_Provider_Interface {

	/** @var string */
	protected string $provider_id;

	/** @var Provider_Error_Normalizer */
	protected Provider_Error_Normalizer $error_normalizer;

	/** @var Provider_Response_Normalizer */
	protected Provider_Response_Normalizer $response_normalizer;

	/** @var Provider_Secret_Store_Interface */
	protected Provider_Secret_Store_Interface $secret_store;

	/** @var array<string, mixed> */
	protected array $default_capabilities;

	/**
	 * @param string                        $provider_id           Stable provider id (e.g. openai).
	 * @param Provider_Error_Normalizer     $error_normalizer      Maps provider errors to contract categories.
	 * @param Provider_Response_Normalizer  $response_normalizer   Builds success/error response envelopes.
	 * @param Provider_Secret_Store_Interface $secret_store        Credential access; never logged.
	 * @param array<string, mixed>          $default_capabilities get_capabilities() return shape.
	 */
	public function __construct(
		string $provider_id,
		Provider_Error_Normalizer $error_normalizer,
		Provider_Response_Normalizer $response_normalizer,
		Provider_Secret_Store_Interface $secret_store,
		array $default_capabilities
	) {
		$this->provider_id           = $provider_id;
		$this->error_normalizer      = $error_normalizer;
		$this->response_normalizer  = $response_normalizer;
		$this->secret_store         = $secret_store;
		$this->default_capabilities = $default_capabilities;
	}

	/** @inheritdoc */
	public function get_provider_id(): string {
		return $this->provider_id;
	}

	/** @inheritdoc */
	public function get_capabilities(): array {
		return $this->default_capabilities;
	}

	/**
	 * Performs the request: checks credential, calls do_perform_request, returns normalized envelope.
	 * Safe-fail when credentials absent; exceptions mapped to normalized error. No secrets in response or logs.
	 *
	 * @param array<string, mixed> $request Normalized request (request_id, model, system_prompt, user_message, ...).
	 * @return array<string, mixed> Normalized response (request_id, success, structured_payload?, usage?, normalized_error?).
	 */
	public function request( array $request ): array {
		$request_id  = (string) ( $request['request_id'] ?? '' );
		$model_used  = (string) ( $request['model'] ?? 'unknown' );

		if ( ! $this->secret_store->has_credential( $this->provider_id ) ) {
			return $this->error_normalizer->build_error_response(
				$request_id,
				$this->provider_id,
				$model_used,
				Provider_Response_Normalizer::ERROR_AUTH_FAILURE,
				null
			);
		}

		$credential = $this->secret_store->get_credential_for_provider( $this->provider_id );
		if ( $credential === null ) {
			return $this->error_normalizer->build_error_response(
				$request_id,
				$this->provider_id,
				$model_used,
				Provider_Response_Normalizer::ERROR_AUTH_FAILURE,
				null
			);
		}

		try {
			$raw = $this->do_perform_request( $request, $credential );
		} catch ( \Throwable $e ) {
			$category = $this->error_normalizer->map_exception_to_category( $e );
			return $this->error_normalizer->build_error_response(
				$request_id,
				$this->provider_id,
				$model_used,
				$category,
				$e->getMessage()
			);
		}

		if ( ! is_array( $raw ) ) {
			return $this->error_normalizer->build_error_response(
				$request_id,
				$this->provider_id,
				$model_used,
				Provider_Response_Normalizer::ERROR_MALFORMED_RESPONSE,
				null
			);
		}

		if ( ! empty( $raw['success'] ) ) {
			return $this->response_normalizer->build_success_response(
				$request_id,
				$this->provider_id,
				$model_used,
				$raw['structured_payload'] ?? array(),
				$raw['usage'] ?? null,
				$raw['raw_provider_metadata'] ?? null
			);
		}

		$http_status = isset( $raw['error_http_status'] ) ? (int) $raw['error_http_status'] : 0;
		$code        = isset( $raw['error_code'] ) ? (string) $raw['error_code'] : null;
		$message     = isset( $raw['error_message'] ) ? (string) $raw['error_message'] : null;
		$category    = $this->error_normalizer->map_to_category( $http_status, $code, $message );
		return $this->error_normalizer->build_error_response( $request_id, $this->provider_id, $model_used, $category, $message );
	}

	/** @inheritdoc */
	public function supports_structured_output( string $schema_ref ): bool {
		$cap = $this->get_capabilities();
		if ( empty( $cap['structured_output_supported'] ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Performs the actual provider call. Subclass implements; must not log credential.
	 * Return shape: success true  -> [ 'success' => true, 'structured_payload' => array|object, 'usage' => array|null, 'raw_provider_metadata' => array|null ]
	 *              success false -> [ 'success' => false, 'error_http_status' => int, 'error_code' => string|null, 'error_message' => string|null ]
	 *
	 * @param array<string, mixed> $normalized_request Normalized request.
	 * @param string               $credential         Secret value; must not be logged or stored.
	 * @return array<string, mixed> Raw result shape (success + payload or error fields).
	 */
	abstract protected function do_perform_request( array $normalized_request, string $credential ): array;
}
