<?php
/**
 * PHPStan fixture only: WordPress loads the real upgrade.php at runtime (DbDelta_Runner).
 *
 * @package AIOPageBuilder
 */

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $sql ) { // phpcs:ignore WordPress.DB.RestrictedFunctions
	}
}
