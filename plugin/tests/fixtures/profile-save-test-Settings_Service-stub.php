<?php
/**
 * Settings_Service stub for profile save integration tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Infrastructure\Settings;

if ( ! class_exists( 'AIOPageBuilder\Infrastructure\Settings\Settings_Service' ) ) {
	/**
	 * Minimal in-memory settings for tests.
	 */
	class Settings_Service {
		/**
		 * @param string $key Key.
		 * @return mixed
		 */
		public function get( string $key ): mixed {
			return $GLOBALS['_test_options'][ $key ] ?? null;
		}

		/**
		 * @param string $key   Key.
		 * @param mixed  $value Value.
		 * @return void
		 */
		public function set( string $key, mixed $value ): void {
			$GLOBALS['_test_options'][ $key ] = $value;
		}
	}
}
