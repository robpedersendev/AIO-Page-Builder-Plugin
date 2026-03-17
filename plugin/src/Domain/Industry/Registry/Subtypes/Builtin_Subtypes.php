<?php
/**
 * Built-in industry subtype definitions for launch industries (Prompt 415).
 * Loads from Subtypes/*.php and returns a single flat list for Industry_Subtype_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry\Subtypes;

defined( 'ABSPATH' ) || exit;

/**
 * Returns all built-in subtype definitions. Each file returns array( array( ... ) ); we flatten to one list.
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Subtypes {

	/**
	 * Returns built-in subtype definitions from Subtypes/*.php (cosmetology_nail, realtor, plumber, disaster_recovery).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/cosmetology-nail-subtypes.php',
			$dir . '/realtor-subtypes.php',
			$dir . '/plumber-subtypes.php',
			$dir . '/disaster-recovery-subtypes.php',
		);
		$out = array();
		foreach ( $files as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$loaded = require $path;
			if ( is_array( $loaded ) ) {
				foreach ( $loaded as $def ) {
					if ( is_array( $def ) ) {
						$out[] = $def;
					}
				}
			}
		}
		return $out;
	}
}
