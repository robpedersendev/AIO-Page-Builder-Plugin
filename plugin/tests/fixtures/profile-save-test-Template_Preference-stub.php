<?php
/**
 * Template_Preference_Profile stub for profile save integration tests.
 *
 * @package AIOPageBuilder
 */

namespace AIOPageBuilder\Domain\Profile;

if ( ! class_exists( 'AIOPageBuilder\Domain\Profile\Template_Preference_Profile' ) ) {
	/**
	 * Placeholder template preference model for tests.
	 */
	class Template_Preference_Profile {
		/**
		 * @return array<string, mixed>
		 */
		public function to_array(): array {
			return array();
		}

		/**
		 * @param array<string, mixed> $a Data.
		 * @return self
		 */
		public static function from_array( array $a ): self {
			return new self();
		}
	}
}
