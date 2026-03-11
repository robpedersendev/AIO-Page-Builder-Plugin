<?php
/**
 * Redacts known secret-bearing keys from arrays and strings before logging, export, or reports
 * (provider-secret-storage-contract.md §5–6, spec §43.14, §45.9).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\AI\Secrets;

defined( 'ABSPATH' ) || exit;

/**
 * Redacts secret values so they never appear in logs, exports, or reports. Redaction is absolute; no debug exception.
 */
class Secret_Redactor {

	/**
	 * Placeholder used in place of redacted values.
	 */
	public const REDACTED_PLACEHOLDER = '[REDACTED]';

	/**
	 * Known key names (and patterns) that indicate a secret-bearing field. Keys are lowercase for matching.
	 *
	 * @var array<int, string>
	 */
	private const SECRET_KEYS = array(
		'api_key',
		'apikey',
		'secret',
		'client_secret',
		'client_secret_key',
		'token',
		'access_token',
		'refresh_token',
		'bearer_token',
		'password',
		'passwd',
		'pwd',
		'authorization',
		'auth_header',
	);

	/**
	 * Suffixes that indicate a secret-bearing key when the key ends with one of these.
	 *
	 * @var array<int, string>
	 */
	private const SECRET_KEY_SUFFIXES = array(
		'_key',
		'_secret',
		'_token',
		'_password',
	);

	/**
	 * Redacts secret-bearing keys in an array (recursively). Values for known keys are replaced with REDACTED_PLACEHOLDER.
	 * Does not modify the original array; returns a new array.
	 *
	 * @param array<string, mixed> $data Array that may contain secret-bearing keys (e.g. config, request payload).
	 * @return array<string, mixed> Copy of $data with secret values redacted.
	 */
	public function redact_array( array $data ): array {
		$out = array();
		foreach ( $data as $key => $value ) {
			$key_lower = is_string( $key ) ? strtolower( $key ) : (string) $key;
			if ( $this->is_secret_key( $key_lower ) ) {
				$out[ $key ] = self::REDACTED_PLACEHOLDER;
				continue;
			}
			if ( is_array( $value ) ) {
				$out[ $key ] = $this->redact_array( $value );
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Redacts secret-bearing keys in a string by replacing JSON-like key-value pairs for known keys.
	 * For structured data, prefer redact_array() and then encode. This method is for simple string passthrough (e.g. error messages).
	 * If the string looks like JSON, decodes, redacts, and re-encodes; otherwise returns the string unchanged (no heuristic replacement of random strings).
	 *
	 * @param string $payload String that may be JSON or plain text. Plain text is returned as-is to avoid false positives.
	 * @return string Redacted string (JSON redacted in structure; plain text unchanged).
	 */
	public function redact_string( string $payload ): string {
		$decoded = json_decode( $payload, true );
		if ( is_array( $decoded ) ) {
			return (string) wp_json_encode( $this->redact_array( $decoded ) );
		}
		return $payload;
	}

	/**
	 * Returns whether the given key name is considered a secret-bearing key.
	 *
	 * @param string $key_lower Lowercase key name.
	 * @return bool
	 */
	public function is_secret_key( string $key_lower ): bool {
		if ( in_array( $key_lower, self::SECRET_KEYS, true ) ) {
			return true;
		}
		foreach ( self::SECRET_KEY_SUFFIXES as $suffix ) {
			if ( $suffix !== '' && str_ends_with( $key_lower, $suffix ) ) {
				return true;
			}
		}
		return false;
	}
}
