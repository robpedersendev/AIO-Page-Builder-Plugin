<?php
/**
 * Resolves provider capability metadata without exposing secrets (spec §25.6, ai-provider-contract.md §6).
 * Uses driver get_capabilities() and supports_structured_output(); no credential access.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves capability metadata from a driver. Safe to use for feature gating and model selection.
 */
final class Provider_Capability_Resolver {

	/**
	 * Returns full capability array for the driver. No secrets; metadata only.
	 *
	 * @param AI_Provider_Interface $driver Provider driver.
	 * @return array{provider_id: string, structured_output_supported: bool, file_attachment_supported: bool, max_context_tokens: int|null, models: array, error_format_notes?: string, retry_notes?: string}
	 */
	public function get_capabilities( AI_Provider_Interface $driver ): array {
		return $driver->get_capabilities();
	}

	/**
	 * Whether the provider supports the given schema reference for structured output.
	 *
	 * @param AI_Provider_Interface $driver     Provider driver.
	 * @param string                $schema_ref Plugin-owned schema ref (e.g. aio/build-plan-draft-v1).
	 * @return bool
	 */
	public function supports_schema( AI_Provider_Interface $driver, string $schema_ref ): bool {
		return $driver->supports_structured_output( $schema_ref );
	}

	/**
	 * Returns provider_id from capabilities (convenience).
	 *
	 * @param AI_Provider_Interface $driver Provider driver.
	 * @return string
	 */
	public function get_provider_id( AI_Provider_Interface $driver ): string {
		$cap = $driver->get_capabilities();
		return (string) ( $cap['provider_id'] ?? $driver->get_provider_id() );
	}

	/**
	 * Picks a default model for planning from capabilities when possible.
	 * Prefers model with default_for_planning or supports_structured_output; otherwise first model.
	 *
	 * @param AI_Provider_Interface $driver    Provider driver.
	 * @param string                $schema_ref Schema ref to check support for.
	 * @return string|null Model id or null if none suitable.
	 */
	public function resolve_default_model_for_planning( AI_Provider_Interface $driver, string $schema_ref ): ?string {
		if ( ! $driver->supports_structured_output( $schema_ref ) ) {
			return null;
		}
		$cap   = $driver->get_capabilities();
		$models = $cap['models'] ?? array();
		if ( ! is_array( $models ) ) {
			return null;
		}
		foreach ( $models as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			if ( ! empty( $m['default_for_planning'] ) ) {
				return isset( $m['id'] ) ? (string) $m['id'] : null;
			}
		}
		foreach ( $models as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			if ( ! empty( $m['supports_structured_output'] ) && isset( $m['id'] ) ) {
				return (string) $m['id'];
			}
		}
		return isset( $models[0]['id'] ) ? (string) $models[0]['id'] : null;
	}
}
