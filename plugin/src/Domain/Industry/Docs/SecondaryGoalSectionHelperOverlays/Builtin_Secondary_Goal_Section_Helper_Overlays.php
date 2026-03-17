<?php
/**
 * Built-in secondary-goal section-helper overlay definitions (Prompt 544).
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Docs\SecondaryGoalSectionHelperOverlays;

defined( 'ABSPATH' ) || exit;

/**
 * Returns built-in secondary-goal section-helper overlays (mixed-funnel pairs only).
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Secondary_Goal_Section_Helper_Overlays {

	public static function get_definitions(): array {
		$dir   = __DIR__;
		$out   = array();
		$files = array(
			$dir . '/calls-lead-capture.php',
			$dir . '/bookings-consultation.php',
			$dir . '/estimates-calls.php',
			$dir . '/consultation-lead-nurture.php',
		);
		foreach ( $files as $path ) {
			if ( ! \is_readable( $path ) ) {
				continue;
			}
			$loaded = require $path;
			if ( \is_array( $loaded ) ) {
				foreach ( $loaded as $ov ) {
					if ( \is_array( $ov ) ) {
						$out[] = $ov;
					}
				}
			}
		}
		return $out;
	}
}
