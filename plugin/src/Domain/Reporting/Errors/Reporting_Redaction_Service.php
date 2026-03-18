<?php
/**
 * Redaction for developer error reports (spec §45.9, §46.8, §46.9).
 *
 * Removes or masks secrets, tokens, passwords, and prohibited data before display or outbound delivery.
 * Redaction is mandatory and systematic.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Reporting\Errors;

defined( 'ABSPATH' ) || exit;

/**
 * Redacts strings and context for safe inclusion in reports. Never returns secrets.
 */
final class Reporting_Redaction_Service {

	/** Placeholder shown when a value is redacted. */
	private const REDACTED_PLACEHOLDER = '[redacted]';

	/** Patterns (regex) that indicate secret-like content; replaced with placeholder. */
	private const SECRET_PATTERNS = array(
		'/password\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'/api[_-]?key\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'/bearer\s+[a-zA-Z0-9._-]+/iu',
		'/token\s*[=:]\s*["\']?[a-zA-Z0-9._-]{20,}["\']?/iu',
		'/nonce\s*[=:]\s*["\']?[a-zA-Z0-9]+["\']?/iu',
		'/auth[_-]?cookie\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'/session[_-]?id\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'/secret\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'/database[_\s]?(?:password|credential|connection)\s*[=:]\s*["\']?[^\s"\']+["\']?/iu',
		'#\b[A-Za-z0-9+/]{40,}={0,2}\b#', // Long base64-like tokens; # delimiter so / is literal.
	);

	/**
	 * Redacts a message string for safe inclusion in a report. Removes or masks secrets.
	 *
	 * @param string $message Raw or partially sanitized message.
	 * @return string Redacted message; never contains unmasked secrets.
	 */
	public function redact_message( string $message ): string {
		if ( $message === '' ) {
			return '';
		}
		$out = $message;
		foreach ( self::SECRET_PATTERNS as $pattern ) {
			$replaced = preg_replace( $pattern, self::REDACTED_PLACEHOLDER, $out );
			$out      = $replaced !== null ? $replaced : $out;
		}
		// * Strip any remaining long hex/base64-looking sequences that might be tokens.
		$replaced = preg_replace( '#\b[a-fA-F0-9]{32,64}\b#', self::REDACTED_PLACEHOLDER, (string) $out );
		$out      = $replaced !== null ? $replaced : $out;
		return trim( (string) $out );
	}

	/**
	 * Redacts an associative array of context keys. Keys matching prohibited names are removed or masked.
	 * Values are recursively redacted (one level). Spec §46.9 prohibited: passwords, API keys, tokens, etc.
	 *
	 * @param array<string, mixed> $context Raw context (e.g. error context, payload draft).
	 * @return array<string, mixed> Redacted context safe for report payload.
	 */
	public function redact_context( array $context ): array {
		$prohibited_keys = array(
			'password',
			'api_key',
			'bearer_token',
			'auth_cookie',
			'nonce',
			'database_credentials',
			'raw_ai_payload',
			'session_id',
			'secret',
			'token',
			'credential',
			'key',
		);
		$out             = array();
		foreach ( $context as $key => $value ) {
			$key_lower  = strtolower( (string) $key );
			$prohibited = false;
			foreach ( $prohibited_keys as $p ) {
				if ( str_contains( $key_lower, $p ) || $key_lower === $p ) {
					$prohibited = true;
					break;
				}
			}
			if ( $prohibited ) {
				$out[ $key ] = self::REDACTED_PLACEHOLDER;
				continue;
			}
			if ( is_string( $value ) ) {
				$out[ $key ] = $this->redact_message( $value );
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = $this->redact_context( $value );
			} else {
				$out[ $key ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Builds a sanitized error summary from a message and optional expected/actual. No raw stack traces.
	 *
	 * @param string $message Sanitized or redacted message.
	 * @param string $expected_behavior Optional expected behavior (short).
	 * @param string $actual_behavior Optional actual behavior (short).
	 * @return string Sanitized summary suitable for sanitized_error_summary field (max length bounded).
	 */
	public function build_sanitized_summary( string $message, string $expected_behavior = '', string $actual_behavior = '' ): string {
		$message = $this->redact_message( $message );
		$message = preg_replace( '/\s+/', ' ', $message );
		$message = trim( $message );
		$max     = 500;
		if ( strlen( $message ) > $max ) {
			$message = substr( $message, 0, $max - 3 ) . '...';
		}
		if ( $expected_behavior !== '' || $actual_behavior !== '' ) {
			$exp = $this->redact_message( $expected_behavior );
			$act = $this->redact_message( $actual_behavior );
			if ( strlen( $exp ) > 200 ) {
				$exp = substr( $exp, 0, 197 ) . '...';
			}
			if ( strlen( $act ) > 200 ) {
				$act = substr( $act, 0, 197 ) . '...';
			}
			$extra = trim( $exp . ' | ' . $act );
			if ( $extra !== ' | ' && $extra !== '' ) {
				$message = $message . ' — ' . $extra;
			}
		}
		return substr( $message, 0, 1000 );
	}
}
