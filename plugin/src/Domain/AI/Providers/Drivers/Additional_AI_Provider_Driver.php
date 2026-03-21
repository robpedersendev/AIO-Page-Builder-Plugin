<?php
/**
 * Anthropic Claude driver (spec §25, §43.13, ai-provider-contract.md).
 * Maps normalized request/response to Anthropic Messages API; no secrets in logs or response.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers\Drivers;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\AI\Pricing\Provider_Cost_Calculator;
use AIOPageBuilder\Domain\AI\Providers\Abstract_AI_Provider_Driver;
use AIOPageBuilder\Domain\AI\Providers\Provider_Error_Normalizer;
use AIOPageBuilder\Domain\AI\Providers\Provider_Response_Normalizer;
use AIOPageBuilder\Domain\AI\Secrets\Provider_Secret_Store_Interface;

/**
 * Anthropic Messages API driver. Normalized request/response; errors mapped to contract taxonomy.
 */
final class Additional_AI_Provider_Driver extends Abstract_AI_Provider_Driver {

	/** Public alias used by the container provider to avoid repeating the URL string. */
	public const API_BASE_DEFAULT = 'https://api.anthropic.com/v1';
	private const API_BASE        = self::API_BASE_DEFAULT;

	/** API version header value. */
	private const ANTHROPIC_VERSION = '2023-06-01';

	/** @var string Override base URL for testing. */
	private string $base_url;

	/** @var Provider_Cost_Calculator|null */
	private ?Provider_Cost_Calculator $cost_calculator;

	/**
	 * @param Provider_Error_Normalizer       $error_normalizer
	 * @param Provider_Response_Normalizer    $response_normalizer
	 * @param Provider_Secret_Store_Interface $secret_store
	 * @param string                          $base_url        Optional; default Anthropic API base.
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
		$default_capabilities  = Additional_Provider_Capability_Profile::get_capabilities();
		parent::__construct(
			Additional_Provider_Capability_Profile::PROVIDER_ID,
			$error_normalizer,
			$response_normalizer,
			$secret_store,
			$default_capabilities
		);
	}

	/**
	 * Performs request via Anthropic Messages API. Credential used only in x-api-key header; not logged.
	 *
	 * @param array<string, mixed> $normalized_request
	 * @param string               $credential
	 * @return array<string, mixed> Raw shape for base: success + payload/usage or error_http_status/code/message.
	 */
	protected function do_perform_request( array $normalized_request, string $credential ): array {
		$model   = (string) ( $normalized_request['model'] ?? 'claude-sonnet-4-20250514' );
		$system  = (string) ( $normalized_request['system_prompt'] ?? '' );
		$user    = (string) ( $normalized_request['user_message'] ?? '' );
		$max_tok = isset( $normalized_request['max_tokens'] ) ? (int) $normalized_request['max_tokens'] : 1024;
		$timeout = isset( $normalized_request['timeout_seconds'] ) ? (int) $normalized_request['timeout_seconds'] : 60;

		$messages = array(
			array(
				'role'    => 'user',
				'content' => $user,
			),
		);
		$body     = array(
			'model'      => $model,
			'max_tokens' => max( 1, min( 4096, $max_tok ) ),
			'messages'   => $messages,
		);
		if ( $system !== '' ) {
			$body['system'] = $system;
		}

		$url  = $this->base_url . '/messages';
		$args = array(
			'method'  => 'POST',
			'timeout' => $timeout,
			'headers' => array(
				'x-api-key'         => $credential,
				'anthropic-version' => self::ANTHROPIC_VERSION,
				'content-type'      => 'application/json',
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
				$error_code    = isset( $decoded['error']['type'] ) ? (string) $decoded['error']['type'] : null;
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

		$content_blocks = $decoded['content'] ?? array();
		$content        = '';
		if ( is_array( $content_blocks ) && isset( $content_blocks[0]['text'] ) ) {
			$content = (string) $content_blocks[0]['text'];
		}

		$usage_normalized = null;
		$usage            = $decoded['usage'] ?? null;
		if ( is_array( $usage ) ) {
			$input_tok  = (int) ( $usage['input_tokens'] ?? 0 );
			$output_tok = (int) ( $usage['output_tokens'] ?? 0 );
			// * Token counts are authoritative provider-reported values (input_tokens + output_tokens).
			// * Anthropic does not return cost in the API response; cost is computed from the pricing registry.
			$cost_usd         = $this->cost_calculator !== null
				? $this->cost_calculator->calculate( 'anthropic', $model, $input_tok, $output_tok )
				: null;
			$usage_normalized = array(
				'prompt_tokens'     => $input_tok,
				'completion_tokens' => $output_tok,
				'total_tokens'      => $input_tok + $output_tok,
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
		if ( isset( $decoded['stop_reason'] ) ) {
			$raw_meta['stop_reason'] = $decoded['stop_reason'];
		}

		return array(
			'success'               => true,
			'structured_payload'    => array( 'content' => $content ),
			'usage'                 => $usage_normalized,
			'raw_provider_metadata' => $raw_meta,
		);
	}
}
