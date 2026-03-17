<?php
/**
 * Built-in subtype page one-pager overlay definitions (Prompt 426/427).
 * Loads from SubtypePageOnePagerOverlays/*.php and returns a flat list for Subtype_Page_OnePager_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\SubtypePageOnePagerOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in subtype page one-pager overlay definitions from SubtypePageOnePagerOverlays/*.php (Prompt 427).
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Subtype_Page_OnePager_Overlays {

	/**
	 * Returns built-in subtype page one-pager overlays from SubtypePageOnePagerOverlays/*.php.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/cosmetology-nail-subtype-onepager.php',
			$dir . '/realtor-subtype-onepager.php',
			$dir . '/plumber-subtype-onepager.php',
			$dir . '/disaster-recovery-subtype-onepager.php',
		);
		$out = array();
		foreach ( $files as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$loaded = require $path;
			if ( is_array( $loaded ) ) {
				foreach ( $loaded as $ov ) {
					if ( is_array( $ov ) ) {
						$out[] = $ov;
					}
				}
			}
		}
		return $out;
	}
}
