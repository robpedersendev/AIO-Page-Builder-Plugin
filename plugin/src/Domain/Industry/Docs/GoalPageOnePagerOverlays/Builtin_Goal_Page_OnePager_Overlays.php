<?php
/**
 * Built-in conversion-goal page one-pager overlay definitions (Prompt 508).
 * Loads from GoalPageOnePagerOverlays/*.php and returns a flat list for Goal_Page_OnePager_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\GoalPageOnePagerOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in goal page one-pager overlays from GoalPageOnePagerOverlays/*.php.
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Goal_Page_OnePager_Overlays {

	/**
	 * Returns built-in goal page one-pager overlays.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$dir   = __DIR__;
		$files = array(
			$dir . '/calls-goal-onepager.php',
			$dir . '/bookings-goal-onepager.php',
			$dir . '/estimates-goal-onepager.php',
			$dir . '/consultations-goal-onepager.php',
			$dir . '/valuations-goal-onepager.php',
			$dir . '/lead-capture-goal-onepager.php',
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
