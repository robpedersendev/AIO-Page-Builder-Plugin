<?php
/**
 * AI provider abstraction interface (spec §25.1–25.13, ai-provider-contract.md).
 * All provider drivers implement this contract; no vendor-specific logic outside the driver.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Provider-agnostic AI request interface. Request/response shapes are defined in ai-provider-contract.md.
 */
interface AI_Provider_Interface {

	/**
	 * Stable provider identifier (e.g. openai, anthropic). Used in capability checks and run metadata.
	 *
	 * @return string
	 */
	public function get_provider_id(): string;

	/**
	 * Capability metadata: structured_output_supported, file_attachment_supported, models, etc.
	 *
	 * @return array{provider_id: string, structured_output_supported: bool, file_attachment_supported: bool, max_context_tokens: int|null, models: array, error_format_notes?: string, retry_notes?: string}
	 */
	public function get_capabilities(): array;

	/**
	 * Performs the AI request. Accepts normalized request array; returns normalized response array.
	 * On failure, returns response with success=false and normalized_error set (or throws; see contract).
	 *
	 * @param array<string, mixed> $request Normalized request (request_id, model, system_prompt, user_message, structured_output_schema_ref?, context_artifacts?, max_tokens?, temperature?, timeout_seconds?, options?).
	 * @return array<string, mixed> Normalized response (request_id, success, structured_payload?, raw_content?, provider_id, model_used, usage?, raw_provider_metadata?, normalized_error?).
	 */
	public function request( array $request ): array;

	/**
	 * Whether this provider supports the given schema reference for structured output.
	 *
	 * @param string $schema_ref Plugin-owned schema reference (e.g. aio/build-plan-draft-v1).
	 * @return bool
	 */
	public function supports_structured_output( string $schema_ref ): bool;
}
