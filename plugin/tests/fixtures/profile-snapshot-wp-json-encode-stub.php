<?php
/**
 * wp_json_encode stub in Profile storage namespace for unit tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string
	 */
	function wp_json_encode( $data ): string {
		$r = \json_encode( $data );
		return is_string( $r ) ? $r : '';
	}
}
