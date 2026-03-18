<?php
/**
 * Runs dbDelta for CREATE TABLE statements (spec §11, §53.1). No direct SQL execution elsewhere for table creation.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Storage\Tables;

defined( 'ABSPATH' ) || exit;

/**
 * Wrapper around WordPress dbDelta. Loads upgrade.php and executes; returns success and sanitized error.
 * For internal use by Table_Installer only; no request-triggered schema alteration.
 */
final class DbDelta_Runner {

	/**
	 * Executes a CREATE TABLE statement via dbDelta. Idempotent for create/upgrade.
	 *
	 * @param \wpdb|object $wpdb Database abstraction (must have suppress_errors, last_error).
	 * @param string       $sql  Full CREATE TABLE statement (dbDelta-compliant).
	 * @return array{success: bool, error: string} success true when no db error; error sanitized, empty on success.
	 */
	public function run( $wpdb, string $sql ): array {
		$error = '';
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		$wpdb->suppress_errors();
		dbDelta( $sql );
		$wpdb->suppress_errors( false );
		if ( ! empty( $wpdb->last_error ) ) {
			$error = $this->sanitize_error( $wpdb->last_error );
		}
		return array(
			'success' => $error === '',
			'error'   => $error,
		);
	}

	/**
	 * Sanitizes DB error for logging; no raw SQL or secrets.
	 *
	 * @param string $raw Raw last_error from wpdb.
	 * @return string
	 */
	private function sanitize_error( string $raw ): string {
		$s = strip_tags( $raw );
		$s = preg_replace( '/\s+/', ' ', $s );
		return substr( $s, 0, 512 );
	}
}
