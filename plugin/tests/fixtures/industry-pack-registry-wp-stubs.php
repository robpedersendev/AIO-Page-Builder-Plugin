<?php
/**
 * WordPress-style stubs for Industry_Pack_Registry unit tests (global namespace).
 *
 * @package AIOPageBuilder
 */

if ( ! isset( $GLOBALS['__aio_opts'] ) ) {
	$GLOBALS['__aio_opts'] = array();
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( string $key, $default = false ) {
		return $GLOBALS['__aio_opts'][ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string $key      Option key.
	 * @param mixed  $value    Value.
	 * @param bool   $autoload Autoload.
	 * @return bool
	 */
	function update_option( string $key, $value, bool $autoload = false ): bool {
		$GLOBALS['__aio_opts'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( string $key ): string {
		return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_json_encode( $data ): string {
		$r = json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}
