<?php
/**
 * Provider-agnostic secret store interface (provider-secret-storage-contract.md, spec §43.13, §25.4).
 * Credentials are retrieved server-side only; callers must never log, serialize, or expose return values.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Secrets;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for storing and retrieving provider credentials. All access is server-side and capability-gated at the call site.
 * Raw secret values must never be logged, serialized, or sent to the front-end.
 */
interface Provider_Secret_Store_Interface {

	/**
	 * Credential state for a provider (stored in non-secret config; safe to display).
	 */
	public const STATE_ABSENT             = 'absent';
	public const STATE_CONFIGURED         = 'configured';
	public const STATE_INVALID            = 'invalid';
	public const STATE_ROTATED            = 'rotated';
	public const STATE_PENDING_VALIDATION = 'pending_validation';

	/**
	 * Returns the credential for the given provider. Server-side only; caller must not log or expose the return value.
	 *
	 * @param string $provider_id Stable provider identifier (e.g. openai, anthropic).
	 * @return string|null The credential value, or null if absent. Use only in memory for the immediate request.
	 */
	public function get_credential_for_provider( string $provider_id ): ?string;

	/**
	 * Returns the credential state for the provider. Safe to log and display (no secret value).
	 *
	 * @param string $provider_id Stable provider identifier.
	 * @return string One of STATE_ABSENT, STATE_CONFIGURED, STATE_INVALID, STATE_ROTATED, STATE_PENDING_VALIDATION.
	 */
	public function get_credential_state( string $provider_id ): string;

	/**
	 * Whether a credential is present for the provider (convenience; equivalent to state !== absent).
	 *
	 * @param string $provider_id Stable provider identifier.
	 * @return bool
	 */
	public function has_credential( string $provider_id ): bool;

	/**
	 * Stores or replaces the credential for the provider. Must be capability-gated at the call site.
	 * Writes only to segregated storage; must not write to exportable options.
	 *
	 * @param string $provider_id Stable provider identifier.
	 * @param string $value       The secret value. Must not be logged or persisted in exportable config.
	 * @return bool True if the write succeeded.
	 */
	public function set_credential( string $provider_id, string $value ): bool;

	/**
	 * Removes the credential for the provider. Must be capability-gated at the call site.
	 *
	 * @param string $provider_id Stable provider identifier.
	 * @return bool True if a credential was removed.
	 */
	public function delete_credential( string $provider_id ): bool;
}
