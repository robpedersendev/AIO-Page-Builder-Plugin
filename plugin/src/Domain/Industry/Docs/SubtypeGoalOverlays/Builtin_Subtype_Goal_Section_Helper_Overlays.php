<?php
/**
 * Built-in combined subtype+goal section-helper overlay definitions (Prompt 554).
 * Returns flat list for Subtype_Goal_Section_Helper_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\SubtypeGoalOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates built-in combined subtype+goal section-helper overlays from seed files.
 */
final class Builtin_Subtype_Goal_Section_Helper_Overlays {

	/**
	 * Returns all built-in combined subtype+goal section-helper overlay definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_definitions(): array {
		$base = dirname( __FILE__ );
		$out  = array();
		$files = array(
			$base . '/realtor-buyer-consultations-helper.php',
			$base . '/cosmetology-nail-mobile-bookings-helper.php',
			$base . '/disaster-recovery-commercial-calls-helper.php',
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
