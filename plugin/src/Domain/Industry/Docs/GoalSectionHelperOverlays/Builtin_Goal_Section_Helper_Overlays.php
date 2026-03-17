<?php
/**
 * Built-in conversion-goal section-helper overlay definitions (Prompt 506).
 * Returns flat list for Goal_Section_Helper_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\GoalSectionHelperOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in goal section-helper overlay definitions from GoalSectionHelperOverlays/*.php.
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Goal_Section_Helper_Overlays {

	/**
	 * Returns built-in goal section-helper overlays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/calls-goal-overlays.php',
			$dir . '/bookings-goal-overlays.php',
			$dir . '/estimates-goal-overlays.php',
			$dir . '/consultations-goal-overlays.php',
			$dir . '/valuations-goal-overlays.php',
			$dir . '/lead-capture-goal-overlays.php',
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
