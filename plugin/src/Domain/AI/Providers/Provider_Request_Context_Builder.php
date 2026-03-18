<?php
/**
 * Builds normalized provider request envelope from prompt-pack and input artifact data (spec §25.2, ai-provider-contract.md §3).
 * No secrets; config is secret-free with secret-store references resolved by the driver at call time.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Assembles provider_request_context (normalized request array) for driver consumption.
 */
final class Provider_Request_Context_Builder {

	/**
	 * Builds normalized request array from explicit fields. All inputs must be redacted; no secrets.
	 *
	 * @param string               $request_id   Idempotency / traceability.
	 * @param string               $model        Model identifier (e.g. gpt-4o).
	 * @param string               $system_prompt System prompt text (after redaction).
	 * @param string               $user_message User/context message.
	 * @param array<string, mixed> $options Optional: structured_output_schema_ref, context_artifacts, max_tokens, temperature, timeout_seconds, options.
	 * @return array<string, mixed> Normalized request (provider_request_context).
	 */
	public function build(
		string $request_id,
		string $model,
		string $system_prompt,
		string $user_message,
		array $options = array()
	): array {
		$out = array(
			'request_id'    => $request_id,
			'model'         => $model,
			'system_prompt' => $system_prompt,
			'user_message'  => $user_message,
		);
		if ( isset( $options['structured_output_schema_ref'] ) && is_string( $options['structured_output_schema_ref'] ) ) {
			$out['structured_output_schema_ref'] = $options['structured_output_schema_ref'];
		}
		if ( isset( $options['context_artifacts'] ) && is_array( $options['context_artifacts'] ) ) {
			$out['context_artifacts'] = $options['context_artifacts'];
		}
		if ( isset( $options['max_tokens'] ) && is_int( $options['max_tokens'] ) ) {
			$out['max_tokens'] = $options['max_tokens'];
		}
		if ( isset( $options['temperature'] ) && is_numeric( $options['temperature'] ) ) {
			$out['temperature'] = (float) $options['temperature'];
		}
		if ( isset( $options['timeout_seconds'] ) && is_int( $options['timeout_seconds'] ) ) {
			$out['timeout_seconds'] = $options['timeout_seconds'];
		}
		if ( isset( $options['options'] ) && is_array( $options['options'] ) ) {
			$out['options'] = $options['options'];
		}
		return $out;
	}

	/**
	 * Builds from prompt-pack segments (assemble system and user from segment keys).
	 * Segments array: segment_key => content string. Typically system_base + role_framing -> system; planning + artifact summary -> user.
	 *
	 * @param string                $request_id   Request id.
	 * @param string                $model        Model identifier.
	 * @param array<string, string> $system_segments Segment key => content for system prompt (concatenated in order).
	 * @param array<string, string> $user_segments   Segment key => content for user message.
	 * @param array<string, mixed>  $options Optional: structured_output_schema_ref, context_artifacts, max_tokens, temperature, timeout_seconds, options.
	 * @return array<string, mixed> Normalized request.
	 */
	public function build_from_segments(
		string $request_id,
		string $model,
		array $system_segments,
		array $user_segments,
		array $options = array()
	): array {
		$system_prompt = $this->concatenate_segments( $system_segments );
		$user_message  = $this->concatenate_segments( $user_segments );
		return $this->build( $request_id, $model, $system_prompt, $user_message, $options );
	}

	/**
	 * @param array<string, string> $segments Segment key => content.
	 * @return string Concatenated content with newline separators.
	 */
	private function concatenate_segments( array $segments ): string {
		$parts = array();
		foreach ( $segments as $content ) {
			if ( is_string( $content ) && trim( $content ) !== '' ) {
				$parts[] = trim( $content );
			}
		}
		return implode( "\n\n", $parts );
	}
}
