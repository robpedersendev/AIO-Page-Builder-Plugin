<?php
/**
 * Built-in combined subtype+goal page one-pager overlay definitions (Prompt 554).
 * Returns flat list for Subtype_Goal_Page_OnePager_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\SubtypeGoalOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates built-in combined subtype+goal page one-pager overlays from seed files.
 */
final class Builtin_Subtype_Goal_Page_OnePager_Overlays {

	/**
	 * Returns all built-in combined subtype+goal page one-pager overlay definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$base  = __DIR__;
		$out   = array();
		$files = array(
			$base . '/realtor-buyer-consultations-onepager.php',
			$base . '/cosmetology-nail-mobile-bookings-onepager.php',
			$base . '/disaster-recovery-commercial-calls-onepager.php',
		);
		foreach ( $files as $path ) {
			if ( ! is_readable( $path ) ) {
				continue;
			}
			$defs = include $path;
			if ( is_array( $defs ) ) {
				foreach ( $defs as $ov ) {
					$out[] = $ov;
				}
			}
		}
		return $out;
	}
}
