<?php
/**
 * Production OpenAI driver (spec §25, §43.13, ai-provider-contract.md).
 * Maps normalized request/response to OpenAI Chat Completions API; no secrets in logs or response.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Drivers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Planning\Planning_Structured_Output_Limits;
use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;
use AIOPageBuilder\Domain\AI\Providers\Abstract_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;

/**
 * OpenAI Chat Completions driver. Single production-grade concrete driver; response flows through normalizer/validator.
 */
final class Concrete_AI_Provider_Driver extends Abstract_AI_Provider_Driver {

	/** Public alias used by the container provider to avoid repeating the URL string. */
	public const API_BASE_DEFAULT = 'https://api.openai.com/v1';
	private const API_BASE        = self::API_BASE_DEFAULT;

	/** @var string Override base URL for testing (e.g. mock server). */
	private string $base_url;

	/** @var Provider_Cost_Calculator|null */
	private ?Provider_Cost_Calculator $cost_calculator;

	/**
	 * @param Provider_Error_Normalizer       $error_normalizer
	 * @param Provider_Response_Normalizer    $response_normalizer
	 * @param Provider_Secret_Store_Interface $secret_store
	 * @param string                          $base_url        Optional; default OpenAI API base.
	 * @param Provider_Cost_Calculator|null   $cost_calculator Optional; when null, cost_usd remains null.
	 */
	public function __construct(
		Provider_Error_Normalizer $error_normalizer,
		Provider_Response_Normalizer $response_normalizer,
		Provider_Secret_Store_Interface $secret_store,
		string $base_url = self::API_BASE,
		?Provider_Cost_Calculator $cost_calculator = null
	) {
		$this->cost_calculator = $cost_calculator;
		$this->base_url        = rtrim( $base_url, '/' );
		$default_capabilities  = array(
			'provider_id'                 => 'openai',
			'structured_output_supported' => true,
			'file_attachment_supported'   => false,
			'max_context_tokens'          => 128000,
			'models'                      => array(
				array(
					'id'                         => 'gpt-4o',
					'supports_structured_output' => true,
					'default_for_planning'       => true,
				),
				array(
					'id'                         => 'gpt-4o-mini',
					'supports_structured_output' => true,
					'default_for_planning'       => false,
				),
				array(
					'id'                         => 'gpt-4-turbo',
					'supports_structured_output' => true,
					'default_for_planning'       => false,
				),
			),
		);
		parent::__construct( 'openai', $error_normalizer, $response_normalizer, $secret_store, $default_capabilities );
	}

	/**
	 * Performs request via OpenAI Chat Completions API. Credential used only in Authorization header; not logged.
	 *
	 * @param array<string, mixed> $normalized_request
	 * @param string               $credential
	 * @return array<string, mixed> Raw shape for base: success + payload/usage or error_http_status/code/message.
	 */
	protected function do_perform_request( array $normalized_request, string $credential ): array {
		$model   = (string) ( $normalized_request['model'] ?? 'gpt-4o' );
		$system  = (string) ( $normalized_request['system_prompt'] ?? '' );
		$user    = (string) ( $normalized_request['user_message'] ?? '' );
		$max_tok = isset( $normalized_request['max_tokens'] ) ? (int) $normalized_request['max_tokens'] : 1024;
		$timeout = isset( $normalized_request['timeout_seconds'] ) ? (int) $normalized_request['timeout_seconds'] : 60;

		$messages = array();
		if ( $system !== '' ) {
			$messages[] = array(
				'role'    => 'system',
				'content' => $system,
			);
		}
		$messages[] = array(
			'role'    => 'user',
			'content' => $user,
		);

		$body = array(
			'model'      => $model,
			'messages'   => $messages,
			'max_tokens' => Planning_Structured_Output_Limits::clamp_for_provider_request( $max_tok ),
		);

		$url  = $this->base_url . '/chat/completions';
		$args = array(
			'method'  => 'POST',
			'timeout' => $timeout,
			'headers' => array(
				'Authorization' => 'Bearer ' . $credential,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		);

		$response = wp_remote_post( $url, $args );
		$code     = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) ) {
			return array(
				'success'           => false,
				'error_http_status' => 0,
				'error_code'        => 'network',
				'error_message'     => $response->get_error_message(),
			);
		}

		if ( $code !== 200 ) {
			$error_code    = null;
			$error_message = null;
			$decoded       = json_decode( $body_raw, true );
			if ( is_array( $decoded ) && isset( $decoded['error'] ) && is_array( $decoded['error'] ) ) {
				$error_message = isset( $decoded['error']['message'] ) ? (string) $decoded['error']['message'] : null;
				$error_code    = isset( $decoded['error']['code'] ) ? (string) $decoded['error']['code'] : ( isset( $decoded['error']['type'] ) ? (string) $decoded['error']['type'] : null );
			}
			if ( $error_message === null && $body_raw !== '' ) {
				$error_message = strlen( $body_raw ) > 200 ? substr( $body_raw, 0, 200 ) . '...' : $body_raw;
			}
			return array(
				'success'           => false,
				'error_http_status' => (int) $code,
				'error_code'        => $error_code,
				'error_message'     => $error_message,
			);
		}

		$decoded = json_decode( $body_raw, true );
		if ( ! is_array( $decoded ) ) {
			return array(
				'success'           => false,
				'error_http_status' => 200,
				'error_code'        => 'invalid_json',
				'error_message'     => 'Invalid JSON response',
			);
		}

		$choices = $decoded['choices'] ?? array();
		$usage   = $decoded['usage'] ?? null;
		$content = '';
		if ( is_array( $choices ) && isset( $choices[0]['message']['content'] ) ) {
			$content = (string) $choices[0]['message']['content'];
		}

		$structured_payload = array( 'content' => $content );
		$usage_normalized   = null;
		if ( is_array( $usage ) ) {
			// * Token counts are authoritative provider-reported values.
			// * OpenAI does not return cost in the API response; cost is computed from the pricing registry.
			$prompt_tok       = (int) ( $usage['prompt_tokens'] ?? 0 );
			$completion_tok   = (int) ( $usage['completion_tokens'] ?? 0 );
			$cost_usd         = $this->cost_calculator !== null
				? $this->cost_calculator->calculate( 'openai', $model, $prompt_tok, $completion_tok )
				: null;
			$usage_normalized = array(
				'prompt_tokens'     => $prompt_tok,
				'completion_tokens' => $completion_tok,
				'total_tokens'      => (int) ( $usage['total_tokens'] ?? 0 ),
				'cost_usd'          => $cost_usd,
			);
		}

		$raw_meta = array();
		if ( isset( $decoded['id'] ) ) {
			$raw_meta['id'] = $decoded['id'];
		}
		if ( isset( $decoded['model'] ) ) {
			$raw_meta['model'] = $decoded['model'];
		}

		return array(
			'success'               => true,
			'structured_payload'    => $structured_payload,
			'usage'                 => $usage_normalized,
			'raw_provider_metadata' => $raw_meta,
		);
	}
}
