<?php
/**
 * Defines global __() stub for unit tests when WordPress is not loaded.
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = '' ) {
		return $text;
	}
}
