<?php
/**
 * LEGACY — NOT LOADED BY ACTIVE PLUGIN.
 * Old diagnostics logger. Active plugin uses AIOPageBuilder logging.
 * Quarantined in plugin/legacy/; see legacy/README.md.
 *
 * Diagnostics logger scaffold.
 *
 * @package PrivatePluginBase
 */

declare(strict_types=1);

namespace PrivatePluginBase\Diagnostics;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin diagnostics logger.
 */
final class Logger {

	/**
	 * Log prefix.
	 *
	 * @var string
	 */
	private const PREFIX = '[PrivatePluginBase]';

	/**
	 * Logs a message with optional context.
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Optional context (redacted; no secrets).
	 * @return void
	 */
	public static function log( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$entry = self::PREFIX . ' ' . $message;
		if ( ! empty( $context ) ) {
			$entry .= ' ' . wp_json_encode( self::redact( $context ) );
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $entry );
	}

	/**
	 * Redacts sensitive keys from context.
	 *
	 * @param array<string, mixed> $context Raw context.
	 * @return array<string, mixed> Redacted context.
	 */
	private static function redact( array $context ): array {
		$sensitive = array( 'password', 'token', 'secret', 'key', 'api_key' );
		foreach ( $context as $k => $v ) {
			$lower = strtolower( (string) $k );
			foreach ( $sensitive as $pattern ) {
				if ( str_contains( $lower, $pattern ) ) {
					$context[ $k ] = '[REDACTED]';
					break;
				}
			}
		}
		return $context;
	}
}
