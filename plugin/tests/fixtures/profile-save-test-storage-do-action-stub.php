<?php
/**
 * Bridges Profile_Store do_action calls to the integration test namespace.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Storage\Profile;

if ( ! function_exists( 'AIOPageBuilder\Domain\Storage\Profile\do_action' ) ) {
	/**
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Arguments.
	 * @return void
	 */
	function do_action( string $hook, ...$args ): void {
		\AIOPageBuilder\Tests\Integration\Domain\Profile\do_action( $hook, ...$args );
	}
}
