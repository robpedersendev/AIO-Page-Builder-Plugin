<?php
/**
 * Built-in secondary-goal starter-bundle overlay definitions (Prompt 542).
 * Returns overlay list for Secondary_Goal_Starter_Bundle_Overlay_Registry::load().
 *
 * @package AIOPageBuilder
 */

declare( strict_types=1 );

namespace AIOPageBuilder\Domain\Industry\Registry\StarterBundles\SecondaryGoalOverlays;

defined( 'ABSPATH' ) || exit;

use AIOPageBuilder\Domain\Industry\Registry\Secondary_Goal_Starter_Bundle_Overlay_Registry;

/**
 * Returns built-in secondary-goal overlay definitions (mixed-funnel pairs only).
 *
 * @return array<int, array<string, mixed>>
 */
final class Builtin_Secondary_Goal_Starter_Bundle_Overlays {

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
