<?php
/**
 * Built-in starter bundle definitions for the first four industries (Prompt 387).
 * Loads from StarterBundles/*.php and returns a single flat list for Industry_Starter_Bundle_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry\StarterBundles;

defined( 'ABSPATH' ) || exit;

/**
 * Returns all built-in starter bundle definitions. Each file returns array( array( ... ) ); we flatten to one list.
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Starter_Bundles {

	/**
	 * Returns built-in starter bundle definitions from StarterBundles/*.php (cosmetology_nail, realtor, plumber, disaster_recovery).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/cosmetology-nail-starter.php',
			$dir . '/realtor-starter.php',
			$dir . '/plumber-starter.php',
			$dir . '/disaster-recovery-starter.php',
		);
		$out = array();
		foreach ( $files as $path ) {
			if ( ! \is_readable( $path ) ) {
				continue;
			}
			$loaded = require $path;
			if ( \is_array( $loaded ) ) {
				foreach ( $loaded as $bundle ) {
					if ( \is_array( $bundle ) ) {
						$out[] = $bundle;
					}
				}
			}
		}
		return $out;
	}
}
