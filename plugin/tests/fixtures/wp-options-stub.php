<?php
/**
 * In-memory get_option / update_option for unit tests (global namespace).
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
	 * @param bool   $autoload Autoload flag.
	 * @return bool
	 */
	function update_option( string $key, $value, bool $autoload = false ): bool {
		$GLOBALS['__aio_opts'][ $key ] = $value;
		return true;
	}
}
