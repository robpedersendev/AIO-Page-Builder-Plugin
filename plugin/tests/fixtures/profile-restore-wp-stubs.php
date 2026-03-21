<?php
/**
 * Global WordPress API stubs for Profile_Snapshot_Restore_Action integration tests.
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_json_encode( $data ): string {
		$r = \json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}

if ( ! function_exists( 'error_log' ) ) {
	/**
	 * @param string $message Message.
	 * @return bool
	 */
	function error_log( string $message ): bool {
		return true;
	}
}

if ( ! function_exists( 'gmdate' ) ) {
	/**
	 * @param string   $format    Format.
	 * @param int|null $timestamp Timestamp.
	 * @return string
	 */
	function gmdate( string $format, ?int $timestamp = null ): string {
		return \gmdate( $format, $timestamp );
	}
}
