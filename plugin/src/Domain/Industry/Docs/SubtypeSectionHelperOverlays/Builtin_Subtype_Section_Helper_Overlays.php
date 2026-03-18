<?php
/**
 * Built-in subtype section-helper overlay definitions (Prompt 424/425).
 * Returns flat list for Subtype_Section_Helper_Overlay_Registry::load(). Seed content added in Prompt 425.
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\SubtypeSectionHelperOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in subtype section-helper overlay definitions.
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Subtype_Section_Helper_Overlays {

	/**
	 * Returns built-in subtype section-helper overlays from SubtypeSectionHelperOverlays/*.php (Prompt 425).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/cosmetology-nail-subtype-overlays.php',
			$dir . '/realtor-subtype-overlays.php',
			$dir . '/plumber-subtype-overlays.php',
			$dir . '/disaster-recovery-subtype-overlays.php',
		);
		$out   = array();
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
