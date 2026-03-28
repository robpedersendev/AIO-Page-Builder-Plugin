<?php
/**
 * Ensures structured-output requests do not report transport success without a JSON object channel.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * When structured_output_schema_ref is set, provider success must include parseable JSON object
 * in structured_payload.content (chat-style envelope). Otherwise return validation_failure.
 */
final class AI_Structured_Response_Guard {

	/**
	 * @param array<string, mixed> $normalized_request Request passed to the driver.
	 * @param array<string, mixed> $normalized_response Response from AI_Provider_Interface::request().
	 * @return array<string, mixed> Same response or validation_failure envelope.
	 */
	public static function ensure_json_channel_valid( array $normalized_request, array $normalized_response ): array {
		$schema_ref = isset( $normalized_request['structured_output_schema_ref'] ) ? trim( (string) $normalized_request['structured_output_schema_ref'] ) : '';
		if ( $schema_ref === '' ) {
			return $normalized_response;
		}
		if ( empty( $normalized_response['success'] ) ) {
			return $normalized_response;
		}

		$request_id  = (string) ( $normalized_response['request_id'] ?? ( $normalized_request['request_id'] ?? '' ) );
		$provider_id = (string) ( $normalized_response['provider_id'] ?? '' );
		$model_used  = (string) ( $normalized_response['model_used'] ?? ( $normalized_request['model'] ?? '' ) );

		$payload = $normalized_response['structured_payload'] ?? null;
		$content = '';
		if ( is_array( $payload ) && isset( $payload['content'] ) && is_string( $payload['content'] ) ) {
			$content = $payload['content'];
		} elseif ( is_string( $payload ) ) {
			$content = $payload;
		}

		$content = trim( $content );
		if ( $content === '' ) {
			return self::validation_failure( $request_id, $provider_id, $model_used, 'empty_structured_channel' );
		}

		$decoded = json_decode( $content, true );
		if ( ! is_array( $decoded ) ) {
			return self::validation_failure( $request_id, $provider_id, $model_used, 'structured_channel_not_json_object' );
		}

		return $normalized_response;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function validation_failure( string $request_id, string $provider_id, string $model_used, string $internal_note ): array {
		$normalizer = new Provider_Response_Normalizer();
		return $normalizer->build_error_response(
			$request_id,
			$provider_id !== '' ? $provider_id : 'unknown',
			$model_used !== '' ? $model_used : 'unknown',
			Provider_Response_Normalizer::ERROR_VALIDATION_FAILURE,
			$internal_note
		);
	}
}
