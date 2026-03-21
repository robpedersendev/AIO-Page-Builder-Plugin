<?php
/**
 * gmdate stub in Profile storage namespace for unit tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\gmdate' ) ) {
	/**
	 * @param string   $format    Format.
	 * @param int|null $timestamp Timestamp.
	 * @return string
	 */
	function gmdate( string $format, ?int $timestamp = null ): string {
		return \gmdate( $format, $timestamp );
	}
}
